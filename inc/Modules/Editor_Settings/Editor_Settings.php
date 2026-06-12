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

        if ($this->get_setting('strip_core_theme_json_defaults', true)) {
            \add_filter('wp_theme_json_data_default', [$this, 'strip_theme_json_defaults']);
        }

        // The compiled global-styles CSS is transient-cached, and the
        // cache key doesn't change when our filters do — so toggling
        // strip_core_theme_json_defaults (or any other theme.json-
        // affecting setting) won't take effect until the cache expires.
        // Invalidate whenever orbitools_settings is saved so the next
        // request rebuilds against the live filter state.
        \add_action('update_option_orbitools_settings', [$this, 'invalidate_theme_json_cache']);
        \add_action('add_option_orbitools_settings',    [$this, 'invalidate_theme_json_cache']);
    }

    public function get_default_settings(): array
    {
        return [
            'disable_block_directory'        => true,
            'disable_remote_patterns'        => true,
            'disable_openverse'              => true,
            'disable_font_library'           => true,
            'force_post_image_full'          => true,
            'strip_wp_elements_classes'      => true,
            'dequeue_core_block_supports'    => true,
            'strip_core_theme_json_defaults' => true,
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

    // =========================================================================
    // Strip core's default theme.json presets
    // =========================================================================

    /**
     * Wipe WordPress's default theme.json presets — color palette,
     * gradients, duotone, shadow, font sizes, aspect ratios, spacing
     * scale. The editor only shows what the theme declares
     * explicitly after this fires.
     *
     * Returns the same WP_Theme_JSON_Data instance with the empties
     * merged in via `update_with()`, matching the documented filter
     * contract.
     *
     * @param mixed $theme_json
     * @return mixed
     */
    public function strip_theme_json_defaults($theme_json)
    {
        if (!is_object($theme_json) || !method_exists($theme_json, 'update_with')) {
            return $theme_json;
        }

        return $theme_json->update_with([
            'version'  => 3,
            'settings' => [
                'color' => [
                    // Empty the arrays AND flip the `default*` flags
                    // off — without those flags WP merges its built-in
                    // black / white / vivid-red / etc. back in even
                    // when the array we provide is empty. This is the
                    // bit a lot of "disable WP defaults" snippets miss.
                    'palette'          => [],
                    'gradients'        => [],
                    'duotone'          => [],
                    'defaultPalette'   => false,
                    'defaultGradients' => false,
                    'defaultDuotone'   => false,
                ],
                'shadow' => [
                    'presets'        => [],
                    'defaultPresets' => false,
                ],
                'typography' => [
                    'fontSizes'        => [],
                    'defaultFontSizes' => false,
                ],
                'dimensions' => [
                    'aspectRatios'        => [],
                    'defaultAspectRatios' => false,
                ],
                'spacing' => [
                    'spacingScale'        => [
                        'steps' => 0,
                    ],
                    'defaultSpacingSizes' => false,
                ],
            ],
        ]);
    }

    /**
     * Drop the cached compiled global-styles CSS so the next request
     * rebuilds against the current filter state.
     *
     * Hooked on `update_option_orbitools_settings` so toggling
     * `strip_core_theme_json_defaults` (or any future theme.json-
     * affecting setting) takes effect on the next page load instead
     * of waiting for the transient to expire. Safe to call when
     * the function isn't available — older WPs without theme.json
     * cache support just no-op.
     */
    public function invalidate_theme_json_cache(): void
    {
        if (\function_exists('wp_clean_theme_json_cache')) {
            \wp_clean_theme_json_cache();
        }
    }
}
