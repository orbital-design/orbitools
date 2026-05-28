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
    /**
     * Fallback dashicon names for core blocks whose `icon` field is
     * set in JS (not block.json), so PHP's WP_Block_Type::$icon is
     * null. Covers the common content/media/design/theme blocks;
     * anything else falls through to `block-default`.
     */
    private const CORE_ICON_FALLBACKS = [
        // Text
        'core/paragraph'         => 'editor-paragraph',
        'core/heading'           => 'heading',
        'core/list'              => 'editor-ul',
        'core/list-item'         => 'editor-ul',
        'core/quote'             => 'editor-quote',
        'core/pullquote'         => 'format-quote',
        'core/code'              => 'editor-code',
        'core/preformatted'      => 'editor-paragraph',
        'core/verse'             => 'editor-paragraph',
        'core/details'           => 'editor-justify',
        'core/footnotes'         => 'editor-justify',
        'core/freeform'          => 'editor-kitchensink',

        // Media
        'core/image'             => 'format-image',
        'core/gallery'           => 'format-gallery',
        'core/audio'             => 'format-audio',
        'core/video'             => 'format-video',
        'core/file'              => 'media-document',
        'core/media-text'        => 'format-gallery',
        'core/cover'             => 'cover-image',

        // Design
        'core/buttons'           => 'button',
        'core/button'            => 'button',
        'core/columns'           => 'columns',
        'core/column'            => 'columns',
        'core/group'             => 'category',
        'core/row'               => 'editor-alignleft',
        'core/stack'             => 'menu',
        'core/separator'         => 'minus',
        'core/spacer'            => 'image-flip-vertical',
        'core/page-break'        => 'editor-break',
        'core/more'              => 'editor-insertmore',
        'core/nextpage'          => 'editor-insertmore',

        // Widgets / utility
        'core/table'             => 'editor-table',
        'core/shortcode'         => 'shortcode',
        'core/html'              => 'html',
        'core/block'             => 'block-default',
        'core/pattern'           => 'layout',
        'core/missing'           => 'warning',
        'core/embed'             => 'embed-generic',

        // Post / site
        'core/post-title'        => 'editor-bold',
        'core/post-content'      => 'media-text',
        'core/post-excerpt'      => 'editor-paragraph',
        'core/post-date'         => 'clock',
        'core/post-author'       => 'admin-users',
        'core/post-featured-image' => 'format-image',
        'core/post-comments'     => 'admin-comments',
        'core/post-navigation-link' => 'arrow-right-alt',
        'core/read-more'         => 'editor-paragraph',
        'core/site-logo'         => 'format-image',
        'core/site-title'        => 'admin-site',
        'core/site-tagline'      => 'editor-paragraph',

        // Navigation
        'core/navigation'        => 'menu',
        'core/navigation-link'   => 'admin-links',
        'core/navigation-submenu' => 'admin-links',
        'core/home-link'         => 'admin-home',
        'core/page-list'         => 'admin-page',
        'core/loginout'          => 'admin-users',

        // Query / archives
        'core/query'             => 'loop',
        'core/post-template'     => 'list-view',
        'core/post-terms'        => 'tag',
        'core/term-description'  => 'editor-paragraph',
        'core/archives'          => 'archive',
        'core/calendar'          => 'calendar-alt',
        'core/categories'        => 'category',
        'core/tag-cloud'         => 'tag',
        'core/latest-comments'   => 'admin-comments',
        'core/latest-posts'      => 'admin-post',
        'core/comments'          => 'admin-comments',
        'core/rss'               => 'rss',
        'core/search'            => 'search',
        'core/social-links'      => 'share',
        'core/social-link'       => 'share',
        'core/template-part'     => 'layout',
    ];

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
            $name_str = (string) $name;
            // Skip Orbital-namespaced blocks — they each have their
            // own settings page in this same Blocks tab, so showing
            // them here would be a second place to flip the same
            // switch.
            if ($this->is_orbital_block($name_str)) {
                continue;
            }
            $blocks[] = $this->serialise_block($name_str, $type);
        }
        // Stable order: by category then title.
        usort($blocks, static function (array $a, array $b): int {
            $cmp = strcmp($a['category'], $b['category']);
            return $cmp !== 0 ? $cmp : strcmp($a['title'], $b['title']);
        });
        return new \WP_REST_Response(['blocks' => $blocks]);
    }

    /**
     * Our blocks register under `orb/` or `orbital/` — both prefixes
     * are recognised by the theme's allow-list and by every existing
     * site. Matching both keeps history-compatible installs covered.
     */
    private function is_orbital_block(string $name): bool
    {
        return strpos($name, 'orb/') === 0 || strpos($name, 'orbital/') === 0;
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
            'icon'        => $this->serialise_icon($name, $type),
        ];
    }

    /**
     * Return the icon as a string if we can — block.json's `icon`
     * field (dashicon slug or inline SVG markup) when WP gave us
     * one, our hardcoded fallback map for core blocks whose icons
     * live in JS-only registration, or null for the UI to use a
     * generic placeholder.
     */
    private function serialise_icon(string $name, \WP_Block_Type $type): ?string
    {
        $icon = $type->icon ?? null;
        if (is_string($icon) && $icon !== '') {
            return $icon;
        }
        return self::CORE_ICON_FALLBACKS[$name] ?? null;
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
