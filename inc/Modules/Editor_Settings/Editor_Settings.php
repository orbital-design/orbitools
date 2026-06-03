<?php

declare(strict_types=1);

namespace Orbitools\Modules\Editor_Settings;

use Orbitools\Core\Abstracts\Module_Base;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Editor Settings — trim down WP's block editor.
 *
 * Each behaviour is gated by its own toggle in the module's settings.
 * The module only wires the hooks it needs based on those toggles, so
 * disabled toggles cost nothing at runtime.
 *
 * Ported from the dream-and-leap-sage theme's `BlockEditorServiceProvider`.
 *
 * @package Orbitools
 * @since   1.0.0
 */
final class Editor_Settings extends Module_Base
{
    protected const VERSION = '1.0.0';

    public function get_slug(): string
    {
        return 'editor-settings';
    }

    public function get_name(): string
    {
        return \__('Editor Settings', 'orbitools');
    }

    public function get_description(): string
    {
        return \__('Trim down the block editor: strip marketplace surfaces, tighten defaults, drop unused output.', 'orbitools');
    }

    public function init(): void
    {
        if ($this->get_setting('disable_block_directory', true)) {
            \remove_action('enqueue_block_editor_assets', 'wp_enqueue_editor_block_directory_assets');
        }

        if ($this->get_setting('disable_remote_patterns', true)) {
            \add_filter('should_load_remote_block_patterns', '__return_false');
        }

        if ($this->any_editor_settings_toggle_on()) {
            \add_filter('block_editor_settings_all', [$this, 'adjust_editor_settings'], 10, 2);
        }

        if ($this->get_setting('strip_wp_elements_classes', true)) {
            \add_filter('render_block_data', [$this, 'strip_wp_elements_classes']);
        }

        if ($this->get_setting('dequeue_core_block_supports', true)) {
            // Priority 5 so we cancel before wp_print_footer_styles
            // (default priority 10) flushes block-supports inline CSS.
            \add_action('wp_footer', [$this, 'dequeue_core_block_supports'], 5);
        }
    }

    public function get_default_settings(): array
    {
        return [
            'disable_block_directory'     => true,
            'disable_remote_patterns'     => true,
            'disable_openverse'           => true,
            'disable_font_library'        => true,
            'force_post_image_full'       => true,
            'strip_wp_elements_classes'   => true,
            'dequeue_core_block_supports' => true,
        ];
    }

    // =========================================================================
    // block_editor_settings_all
    // =========================================================================

    /**
     * Merge our editor-settings tweaks into the all-blocks settings
     * map. Three toggles drop into this single filter because they
     * all mutate the same `$settings` array.
     *
     * @param array<string, mixed> $settings
     * @return array<string, mixed>
     */
    public function adjust_editor_settings(array $settings, \WP_Block_Editor_Context $context): array
    {
        if ($this->get_setting('force_post_image_full', true)) {
            if (isset($context->post) && $context->post instanceof \WP_Post && $context->post->post_type === 'post') {
                $settings['imageDefaultSize'] = 'full';
            }
        }

        if ($this->get_setting('disable_openverse', true)) {
            $settings['enableOpenverseMediaCategory'] = false;
        }

        if ($this->get_setting('disable_font_library', true)) {
            $settings['fontLibraryEnabled'] = false;
        }

        return $settings;
    }

    private function any_editor_settings_toggle_on(): bool
    {
        return $this->get_setting('force_post_image_full', true)
            || $this->get_setting('disable_openverse', true)
            || $this->get_setting('disable_font_library', true);
    }

    // =========================================================================
    // render_block_data — strip wp-elements-* classes
    // =========================================================================

    /**
     * `wp-elements-{hash}` classes get added to blocks that use the
     * Elements API for per-instance styling. We don't use that
     * pathway, and the classes are noise — strip them before render
     * so they never reach the wire.
     *
     * Frontend only: leaves the editor untouched so React's
     * reconciliation has stable class lists to work with.
     *
     * @param array<string, mixed> $parsed_block
     * @return array<string, mixed>
     */
    public function strip_wp_elements_classes(array $parsed_block): array
    {
        if (\is_admin()) {
            return $parsed_block;
        }

        $class_name = $parsed_block['attrs']['className'] ?? '';

        if (!is_string($class_name) || $class_name === '' || stripos($class_name, 'wp-elements-') === false) {
            return $parsed_block;
        }

        $classes  = preg_split('/\s+/', trim($class_name)) ?: [];
        $filtered = array_filter($classes, static fn ($class) => stripos((string) $class, 'wp-elements-') !== 0);

        if ($filtered === []) {
            unset($parsed_block['attrs']['className']);
        } else {
            $parsed_block['attrs']['className'] = implode(' ', $filtered);
        }

        return $parsed_block;
    }

    // =========================================================================
    // Dequeue core-block-supports inline style
    // =========================================================================

    public function dequeue_core_block_supports(): void
    {
        \wp_dequeue_style('core-block-supports');
    }
}
