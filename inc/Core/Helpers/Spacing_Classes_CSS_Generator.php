<?php
/**
 * Spacing Classes CSS Generator
 *
 * Generates the padding / margin utility classes that the Spacings control
 * applies to blocks (`.p-4`, `.pt-8`, `.px-2`, `.m-3`, `.mb-1`, … plus their
 * responsive `tablet:` / `mobile:` variants). The class *names* are produced by
 * SpacingsRenderer (PHP) and the editor classname-application script (JS); this
 * generator produces the matching CSS *rules*.
 *
 * Mirrors Gaps_CSS_Generator: the plugin owns the Spacings control, so the
 * plugin owns the CSS for it — it must not depend on the active theme happening
 * to ship the same utilities. The frontend output folds into the consolidated
 * global-styles block; the editor output gets its own handle on
 * `enqueue_block_assets` so it reaches the canvas iframe where blocks render.
 *
 * @since 1.0.0
 */

namespace Orbitools\Core\Helpers;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Spacing_Classes_CSS_Generator
{
    /**
     * Transient key prefix for cached CSS
     */
    private const TRANSIENT_KEY = 'orbitools_spacing_classes_css';

    /**
     * In-memory cache for the current request
     *
     * @var string|null
     */
    private static $css_cache = null;

    /**
     * Class-prefix → suffix → CSS properties map.
     *
     * Suffix '' is the shorthand (`.p-4`); t/r/b/l are the single sides; x/y use
     * the logical `*-inline` / `*-block` properties so LTR / RTL is handled by
     * the browser (matches the theme's previous utility set). The class name is
     * "{prefix}{suffix}-{slug}", e.g. `p` + `t` + `8` → `.pt-8`.
     */
    private const SPECS = [
        'p' => [
            ''  => ['padding'],
            't' => ['padding-top'],
            'r' => ['padding-right'],
            'b' => ['padding-bottom'],
            'l' => ['padding-left'],
            'x' => ['padding-inline'],
            'y' => ['padding-block'],
        ],
        'm' => [
            ''  => ['margin'],
            't' => ['margin-top'],
            'r' => ['margin-right'],
            'b' => ['margin-bottom'],
            'l' => ['margin-left'],
            'x' => ['margin-inline'],
            'y' => ['margin-block'],
        ],
    ];

    /**
     * Generate CSS for all padding / margin utility classes (transient-cached).
     *
     * @return string Generated CSS
     */
    public static function generate_spacing_classes_css(): string
    {
        if (self::$css_cache !== null) {
            return self::$css_cache;
        }

        $spacing_sizes = Spacing_Utils::get_spacing_sizes();
        $breakpoints   = Spacing_Utils::get_breakpoints();

        if (empty($spacing_sizes)) {
            self::$css_cache = '';
            return '';
        }

        // Leading schema version busts stale transients when the generated
        // shape changes — bump it on any edit to the emitted CSS below.
        $cache_key = self::TRANSIENT_KEY . '_' . md5(\wp_json_encode([1, $spacing_sizes, $breakpoints]));

        $cached = \get_transient($cache_key);
        if ($cached !== false) {
            self::$css_cache = $cached;
            return $cached;
        }

        $css = "/* Spacing Utility Classes - padding / margin */\n";

        // Base (desktop) classes.
        foreach ($spacing_sizes as $spacing) {
            $css .= self::emit_block('', (string) $spacing['slug'], self::value_for($spacing));
        }

        // Responsive variants. Desktop-first cascade (tablet then mobile), each
        // honouring its own `query` direction (defaults to max-width) so the
        // queries line up with WordPress's device-preview widths.
        foreach ($breakpoints as $breakpoint) {
            $bp_slug  = $breakpoint['slug'];
            $bp_value = $breakpoint['value'];
            $bp_query = $breakpoint['query'] ?? 'max-width';

            $css .= "@media ({$bp_query}: {$bp_value}) {\n";

            foreach ($spacing_sizes as $spacing) {
                // Escaped colon: the applied class is e.g. `tablet:pt-8`, so the
                // selector is `.tablet\:pt-8`.
                $css .= self::emit_block($bp_slug . '\\:', (string) $spacing['slug'], self::value_for($spacing));
            }

            $css .= "}\n";
        }

        $css = Minifier::css($css);

        \set_transient($cache_key, $css);
        self::$css_cache = $css;

        return $css;
    }

    /**
     * Resolve the CSS value for one spacing size.
     *
     * Slug 0 is a literal `0`. Everything else uses the preset var with the
     * literal size as a fallback — the var wins on the frontend (preset vars sit
     * on :root in global-styles), but inside the editor canvas iframe those
     * preset vars aren't reliably in scope for these classes, so the fallback is
     * what actually paints the spacing there.
     *
     * @param array $spacing Spacing size (`slug`, `size`).
     * @return string
     */
    private static function value_for(array $spacing): string
    {
        $slug = (string) $spacing['slug'];
        if ($slug === '0') {
            return '0';
        }

        $size = $spacing['size'] ?? '';
        return $size !== ''
            ? "var(--wp--preset--spacing--{$slug}, {$size})"
            : "var(--wp--preset--spacing--{$slug})";
    }

    /**
     * Emit the 14 padding / margin rules for one slug at one breakpoint.
     *
     * @param string $prefix Escaped breakpoint prefix ('' for base, e.g. `tablet\:`).
     * @param string $slug   Spacing slug.
     * @param string $value  Resolved CSS value.
     * @return string
     */
    private static function emit_block(string $prefix, string $slug, string $value): string
    {
        $css = '';

        foreach (self::SPECS as $letter => $suffixes) {
            foreach ($suffixes as $suffix => $props) {
                $declarations = '';
                foreach ($props as $prop) {
                    $declarations .= "{$prop}: {$value};";
                }
                $css .= ".{$prefix}{$letter}{$suffix}-{$slug} { {$declarations} }\n";
            }
        }

        return $css;
    }

    /**
     * Clear cached spacing-classes CSS.
     */
    public static function clear_cache(): void
    {
        global $wpdb;

        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
                '_transient_' . self::TRANSIENT_KEY . '_%',
                '_transient_timeout_' . self::TRANSIENT_KEY . '_%'
            )
        );

        self::$css_cache = null;
    }

    /**
     * Frontend: fold the spacing-classes CSS into the consolidated
     * global-styles block.
     */
    public static function enqueue_frontend_spacing_classes_css(): void
    {
        if (!\apply_filters('orbitools_spacing_classes_frontend_css', true)) {
            return;
        }

        $css = self::generate_spacing_classes_css();
        if (!empty($css)) {
            Inline_CSS::add_frontend($css);
        }
    }

    /**
     * Editor: dedicated handle on `enqueue_block_assets` so the CSS reaches the
     * canvas iframe where blocks render. is_admin() keeps it off the frontend,
     * where the handler above owns it.
     */
    public static function enqueue_editor_spacing_classes_css(): void
    {
        if (!\is_admin()) {
            return;
        }

        if (!\apply_filters('orbitools_spacing_classes_editor_css', true)) {
            return;
        }

        $css = self::generate_spacing_classes_css();
        if (!empty($css)) {
            \wp_register_style('orbitools-spacing-classes-editor', false);
            \wp_enqueue_style('orbitools-spacing-classes-editor');
            \wp_add_inline_style('orbitools-spacing-classes-editor', $css);
        }
    }

    /**
     * Setup hooks.
     */
    public static function init(): void
    {
        \add_action('wp_enqueue_scripts', [self::class, 'enqueue_frontend_spacing_classes_css']);
        \add_action('enqueue_block_assets', [self::class, 'enqueue_editor_spacing_classes_css']);

        \add_action('switch_theme', [self::class, 'clear_cache']);
        \add_action('customize_save_after', [self::class, 'clear_cache']);
        \add_filter('wp_theme_json_data_user', function ($theme_json) {
            self::clear_cache();
            return $theme_json;
        });
    }
}
