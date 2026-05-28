<?php

namespace Orbitools\Modules\Block_Manager;

use Orbitools\Core\Abstracts\Module_Base;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Block Manager module.
 *
 * Walks `WP_Block_Type_Registry` and exposes the result via REST so
 * the React admin can present an "enable / disable per block"
 * picker. A simple `disabled` setting (array of block names) is
 * subtracted from `allowed_block_types_all` so the editor inserter
 * actually respects the choices.
 *
 * @package Orbitools
 * @since   3.3.0
 */
final class Block_Manager extends Module_Base
{
    public function get_slug(): string
    {
        return 'block-manager';
    }

    public function get_name(): string
    {
        return \__('Block Manager', 'orbitools');
    }

    public function get_description(): string
    {
        return \__('List every block registered on the site and toggle which ones appear in the editor inserter.', 'orbitools');
    }

    public function init(): void
    {
        // Priority 100 so any plugin that returns a tighter
        // allowlist at the default priority still wins — we just
        // subtract our deny list from whatever they hand us.
        \add_filter('allowed_block_types_all', [$this, 'filter_allowed_blocks'], 100, 2);
        \add_action('rest_api_init', [$this, 'register_rest_routes']);
    }

    /**
     * Subtract the user's disabled-block list from whatever WP /
     * other plugins decided the allowed set is.
     *
     * @param array<int,string>|bool $allowed
     * @param mixed                  $context
     * @return array<int,string>|bool
     */
    public function filter_allowed_blocks($allowed, $context)
    {
        $disabled = $this->get_disabled_blocks();
        if (empty($disabled)) {
            return $allowed;
        }

        // Upstream said "nothing allowed" — leave them be.
        if ($allowed === false) {
            return false;
        }

        // Upstream said "everything allowed" → start from the full
        // registry and remove our blocks.
        if ($allowed === true) {
            $registered = \WP_Block_Type_Registry::get_instance()->get_all_registered();
            return array_values(array_diff(array_keys($registered), $disabled));
        }

        if (is_array($allowed)) {
            return array_values(array_diff($allowed, $disabled));
        }

        return $allowed;
    }

    public function register_rest_routes(): void
    {
        \register_rest_route('orbitools/v1', '/blocks', [
            [
                'methods'             => 'GET',
                'callback'            => [$this, 'rest_list_blocks'],
                'permission_callback' => function () {
                    return \current_user_can('manage_options');
                },
            ],
        ]);
    }

    public function rest_list_blocks(): \WP_REST_Response
    {
        $registered = \WP_Block_Type_Registry::get_instance()->get_all_registered();
        $blocks = [];
        foreach ($registered as $name => $type) {
            $blocks[] = $this->serialise_block((string) $name, $type);
        }
        // Stable order: by category then title.
        usort($blocks, static function (array $a, array $b): int {
            $cmp = strcmp($a['category'], $b['category']);
            return $cmp !== 0 ? $cmp : strcmp($a['title'], $b['title']);
        });
        return new \WP_REST_Response(['blocks' => $blocks]);
    }

    /**
     * @return array<string,mixed>
     */
    private function serialise_block(string $name, \WP_Block_Type $type): array
    {
        return [
            'name'        => $name,
            'title'       => is_string($type->title ?? null) && $type->title !== '' ? $type->title : $name,
            'category'    => is_string($type->category ?? null) && $type->category !== '' ? $type->category : 'uncategorized',
            'description' => is_string($type->description ?? null) ? $type->description : '',
            'icon'        => $this->serialise_icon($type),
        ];
    }

    /**
     * Return the icon as a string if WP has one in a serialisable
     * form (dashicon slug or inline SVG). Otherwise null and the UI
     * uses a generic placeholder.
     */
    private function serialise_icon(\WP_Block_Type $type): ?string
    {
        $icon = $type->icon ?? null;
        if (is_string($icon) && $icon !== '') {
            return $icon;
        }
        return null;
    }

    /**
     * @return array<int,string>
     */
    private function get_disabled_blocks(): array
    {
        $value = $this->get_setting('disabled', []);
        if (!is_array($value)) {
            return [];
        }
        $names = [];
        foreach ($value as $v) {
            if (is_string($v) && $v !== '') {
                $names[] = $v;
            }
        }
        return $names;
    }
}
