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
            // Only filter `_default` — wiping `_theme` would also blow
            // away the theme's own palette / spacing / etc. We want
            // core's defaults gone but the theme.json's own values
            // intact. The merge order downstream
            // (`get_merged_data`: core → blocks → theme → user) brings
            // the theme's values in on top of our empty core.
            \add_filter('wp_theme_json_data_default', [$this, 'strip_theme_json_defaults']);
        }

        $in_head = $this->get_setting('global_styles_in_head', true);
        $strip   = $this->get_setting('disable_layout_styles', true);

        if ($in_head) {
            // Take over global-styles printing so it lands in the <head>
            // WITHOUT disabling on-demand block-asset loading (the theme
            // opts into that and we leave it alone). Under on-demand WP
            // defers the global-styles CSS to wp_footer + an output-
            // buffer hoist that doesn't reliably run here, so it ends up
            // in the footer. We suppress WP's own global-styles printing
            // (both the wp_enqueue_scripts placeholder and the wp_footer
            // build) and emit our own copy in the head instead.
            //
            // Only global styles are affected — per-block CSS still
            // loads on demand via the separate render_block path.
            \remove_action('wp_enqueue_scripts', 'wp_enqueue_global_styles');
            \remove_action('wp_footer', 'wp_enqueue_global_styles', 1);
            \add_action('wp_enqueue_scripts', [$this, 'print_global_styles_in_head'], 9);
        } elseif ($strip) {
            // Not forcing into the head — just trim the CSS in place
            // wherever WP prints the global-styles handle. Patches the
            // handle's inline data on whichever hook has it registered
            // (head for block themes, footer for classic on-demand).
            \add_action('wp_enqueue_scripts', [$this, 'replace_global_styles'], 100);
            \add_action('wp_footer',          [$this, 'replace_global_styles'], 2);
        }

        // Per-request cache invalidation while any theme.json /
        // global-stylesheet-affecting toggle is on. The compiled
        // global-styles inline CSS lives in a transient
        // `wp_clean_theme_json_cache()` doesn't reach; we flush it on
        // init so a toggle change takes effect on the next load.
        if ($this->get_setting('strip_core_theme_json_defaults', true)
            || $this->get_setting('disable_layout_styles', true)
        ) {
            \add_action('init', [$this, 'invalidate_theme_json_cache']);
        }

        // Same idea, save-side — useful for any other theme.json
        // toggle we add later that doesn't already register the init
        // hook above.
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
            'disable_layout_styles'          => true,
            'global_styles_in_head'          => true,
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
    // Replace global-styles with variables + presets only
    // =========================================================================

    /**
     * Strip WP's layout / element / block scaffolding from the
     * frontend global-styles CSS while keeping the preset tokens.
     *
     * WordPress compiles `<style id="global-styles-inline-css">` from
     * three stylesheet "types": `variables` (the `--wp--preset--*`
     * custom properties + content-size vars), `presets` (the
     * `.has-*-color` / `.has-*-font-size` utility classes), and
     * `styles` (root layout rules, `.is-layout-*` alignment +
     * flex/grid, block-gap, element button defaults, per-block
     * styles). Only `styles` carries the layout cruft.
     *
     * The compiled CSS is stored as inline 'after' data on the
     * `global-styles` style handle. Rather than dequeue the handle
     * (which doesn't survive WP 6.9's classic-theme footer-hoist
     * machinery), we replace that stored CSS in place with a fresh
     * build of only `variables` + `presets`. The handle stays
     * registered, so whatever printing / hoisting path WP uses still
     * runs — it just carries our trimmed CSS.
     *
     * Idempotent: runs on two hooks (head + footer) to cover both
     * theme types; the static guard makes the second call a no-op.
     */
    /**
     * Emit our own copy of the global-styles CSS in the <head>.
     *
     * Used when `global_styles_in_head` is on. WP's own global-styles
     * actions have been removed (see init), so this is the only copy
     * printed. Content is trimmed to `variables` + `presets` when
     * `disable_layout_styles` is also on (drops the layout / element /
     * block scaffolding), otherwise the full stylesheet is emitted —
     * either way it prints in the head via our own handle.
     *
     * On-demand per-block CSS is unaffected; only the global styles
     * are taken over here.
     */
    public function print_global_styles_in_head(): void
    {
        if (!\function_exists('wp_get_global_stylesheet')) {
            return;
        }

        $types = $this->get_setting('disable_layout_styles', true)
            ? ['variables', 'presets']
            : [];

        $css = \wp_get_global_stylesheet($types);
        if ($css === '') {
            return;
        }

        // Reuse the native `global-styles` handle. WP no longer
        // registers it (we removed its enqueue actions in init), so
        // there's no collision — and reusing it means the output is
        // indistinguishable from core's: <style id="global-styles-
        // inline-css"> with the same `sourceURL` annotation WP_Styles
        // auto-appends, instead of leaking an `orbitools-` handle into
        // the page source. Any dependency on `global-styles` still
        // resolves too. Deregister first in case anything pre-
        // registered the handle.
        \wp_deregister_style('global-styles');
        \wp_register_style('global-styles', false);
        \wp_enqueue_style('global-styles');
        \wp_add_inline_style('global-styles', $css);
    }

    public function replace_global_styles(): void
    {
        static $done = false;
        if ($done || !\function_exists('wp_get_global_stylesheet') || !\function_exists('wp_styles')) {
            return;
        }

        $styles = \wp_styles();
        if (!isset($styles->registered['global-styles'])) {
            // Handle not built yet on this hook — let the other hook
            // catch it. Don't set $done.
            return;
        }

        $css = \wp_get_global_stylesheet(['variables', 'presets']);

        // Replace the compiled stylesheet's inline 'after' data with
        // our trimmed build. Wholesale replace is correct for classic
        // themes (Customizer custom CSS prints on its own handle);
        // block themes merge custom CSS in here, but the Orbital
        // target is classic.
        $styles->registered['global-styles']->extra['after'] = $css === '' ? [] : [$css];

        $done = true;
    }

    // =========================================================================
    // Strip core's default theme.json presets
    // =========================================================================

    /**
     * Wipe WordPress's default theme.json presets — color palette,
     * gradients, duotone, shadow, font sizes, aspect ratios, spacing
     * scale.
     *
     * Returns a **fresh** WP_Theme_JSON_Data with only `version: 3`
     * and the `default*: false` flags set, instead of mutating the
     * one passed in. The reason: `WP_Theme_JSON_Data::update_with()`
     * internally calls `$this->theme_json->merge(...)`, which does
     * `array_replace_recursive`. Passing `palette: []` to that is a
     * no-op when the existing palette has entries — empty arrays
     * don't clear lists, they just leave them alone. A fresh object
     * has no existing palette / gradients / etc. to leave alone.
     *
     * Only filter `_default` (not `_theme`) — the merge order
     * downstream (core → blocks → theme → user) brings the theme's
     * own palette in on top of our empty core, so the theme.json's
     * presets are preserved while WP's built-in black / white /
     * vivid-red etc. are gone.
     *
     * @param mixed $theme_json
     * @return mixed
     */
    public function strip_theme_json_defaults($theme_json)
    {
        if (!class_exists('\WP_Theme_JSON_Data')) {
            return $theme_json;
        }

        return new \WP_Theme_JSON_Data([
            'version'  => 3,
            'settings' => [
                'color' => [
                    'defaultPalette'   => false,
                    'defaultGradients' => false,
                    'defaultDuotone'   => false,
                ],
                'shadow' => [
                    'defaultPresets' => false,
                ],
                'typography' => [
                    'defaultFontSizes' => false,
                ],
                'dimensions' => [
                    'defaultAspectRatios' => false,
                ],
                'spacing' => [
                    'defaultSpacingSizes' => false,
                ],
            ],
        ], 'default');
    }

    /**
     * Drop every cache that holds the compiled theme.json / global-
     * styles output so the next request rebuilds against the current
     * filter state.
     *
     * `wp_clean_theme_json_cache()` only handles the resolver-side
     * caches; the actual inline CSS the user sees on the frontend is
     * cached separately in a transient keyed by `$types` (set by
     * `wp_get_global_stylesheet()`). We delete the known variants
     * plus reset the resolver's static state by hand. Safe to call
     * unconditionally — every step is a no-op when the matching
     * key / function isn't there.
     */
    public function invalidate_theme_json_cache(): void
    {
        if (\function_exists('wp_clean_theme_json_cache')) {
            \wp_clean_theme_json_cache();
        }

        // Resolver static cache — fresh data on next read.
        if (class_exists('WP_Theme_JSON_Resolver')
            && method_exists('WP_Theme_JSON_Resolver', 'clean_cached_data')
        ) {
            \WP_Theme_JSON_Resolver::clean_cached_data();
        }

        // Compiled global-styles inline CSS transients. WP varies the
        // key by `$types`; cover the ones in active use across 6.0+.
        $stylesheet_slug = \function_exists('get_stylesheet') ? \get_stylesheet() : '';
        $transient_keys  = [
            'wp_styles_for_blocks',
            'global_styles_inline_css',
            'gutenberg_global_styles',
            'wp_styles_global_styles_' . $stylesheet_slug,
            'wp_global_styles_' . $stylesheet_slug,
        ];
        foreach ($transient_keys as $key) {
            if ($key === '' || $key === 'wp_styles_global_styles_' || $key === 'wp_global_styles_') {
                continue;
            }
            \delete_transient($key);
        }

        // Object cache `theme_json` group — at least the entries WP
        // documents writing to. wp_cache_flush_group works on some
        // backends only, so we delete known keys directly.
        \wp_cache_delete('theme_json',                     'theme_json');
        \wp_cache_delete('wp_theme_features',              'theme_json');
        \wp_cache_delete('wp_styles_for_blocks',           'theme_json');
        \wp_cache_delete('wp_global_styles_data_compiled', 'theme_json');
    }
}
