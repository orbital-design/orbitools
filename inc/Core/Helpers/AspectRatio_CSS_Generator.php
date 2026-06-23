<?php
/**
 * Aspect Ratio CSS Generator
 *
 * Generates responsive aspect-ratio CSS classes from theme.json aspect ratio presets.
 * Follows the same pattern as Gaps_CSS_Generator.
 *
 * @since 1.4.0
 */

namespace Orbitools\Core\Helpers;

use Orbitools\Core\AspectRatioConfig;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class AspectRatio_CSS_Generator
{
    /**
     * Transient key prefix for cached CSS
     */
    private const TRANSIENT_KEY = 'orbitools_aspect_ratio_css';

    /**
     * In-memory cache for the current request
     *
     * @var string|null
     */
    private static $css_cache = null;

    /**
     * Generate CSS for all aspect ratio classes (with transient caching)
     *
     * @return string Generated CSS
     */
    public static function generate_aspect_ratio_css(): string
    {
        if (self::$css_cache !== null) {
            return self::$css_cache;
        }

        $ratios = AspectRatioConfig::get_aspect_ratio_config();
        $breakpoints = Spacing_Utils::get_breakpoints();

        if (empty($ratios)) {
            self::$css_cache = '';
            return '';
        }

        // Build cache key from config data
        $cache_key = self::TRANSIENT_KEY . '_' . md5(\wp_json_encode([$ratios, $breakpoints]));

        $cached = \get_transient($cache_key);
        if ($cached !== false) {
            self::$css_cache = $cached;
            return $cached;
        }

        $css = '';

        // Base aspect ratio classes
        foreach ($ratios as $ratio) {
            $slug = $ratio['slug'];
            $value = $ratio['ratio'];

            $css .= ".has-aspect-ratio.has-aspect-ratio--{$slug} {\n";
            $css .= "    aspect-ratio: {$value};\n";
            $css .= "}\n\n";
        }

        // Responsive aspect ratio classes per breakpoint. Breakpoints
        // are emitted in array order (desktop-first cascade: tablet
        // then mobile), each honouring its own `query` direction
        // (defaults to max-width) so the tablet override applies at
        // <=781px and the mobile override (later in source) wins at
        // <=479px — matching WordPress's device-preview canvas widths.
        foreach ($breakpoints as $breakpoint) {
            $bp_slug  = $breakpoint['slug'];
            $bp_value = $breakpoint['value'];
            $bp_query = $breakpoint['query'] ?? 'max-width';

            $css .= "@media ({$bp_query}: {$bp_value}) {\n";

            foreach ($ratios as $ratio) {
                $slug = $ratio['slug'];
                $value = $ratio['ratio'];

                $css .= "    .has-aspect-ratio.{$bp_slug}\\:has-aspect-ratio--{$slug} {\n";
                $css .= "        aspect-ratio: {$value};\n";
                $css .= "    }\n\n";
            }

            $css .= "}\n\n";
        }

        // Minify before caching
        $css = Minifier::css($css);

        \set_transient($cache_key, $css);

        self::$css_cache = $css;

        return $css;
    }

    /**
     * Clear cached aspect ratio CSS
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
     * Enqueue the frontend aspect-ratio CSS (inline) in <head>.
     *
     * The `has-aspect-ratio` class is added server-side at render time
     * (by AspectRatioRenderer), so it isn't in the stored post content —
     * there's no cheap way to detect usage up front, and the previous
     * render_block late-enqueue relied on print_late_styles() firing in
     * the footer, which doesn't happen reliably in block themes (the
     * style silently never printed). Enqueue directly, same as
     * Gaps_CSS_Generator. The inline CSS is small.
     */
    public static function enqueue_frontend_aspect_ratio_css(): void
    {
        if (!\apply_filters('orbitools_aspect_ratio_frontend_css', true)) {
            return;
        }

        $css = self::generate_aspect_ratio_css();
        if (!empty($css)) {
            // Fold into the consolidated frontend block (global-styles).
            Inline_CSS::add_frontend($css);
        }
    }

    /**
     * Enqueue aspect ratio CSS for block editor
     */
    public static function enqueue_editor_aspect_ratio_css(): void
    {
        if (!\apply_filters('orbitools_aspect_ratio_editor_css', true)) {
            return;
        }

        $css = self::generate_aspect_ratio_css();
        if (!empty($css)) {
            \wp_register_style('orbitools-aspect-ratio-editor', false);
            \wp_enqueue_style('orbitools-aspect-ratio-editor');
            \wp_add_inline_style('orbitools-aspect-ratio-editor', $css);
        }
    }

    /**
     * Setup hooks for automatic CSS enqueuing
     */
    public static function init(): void
    {
        \add_action('wp_enqueue_scripts', [self::class, 'enqueue_frontend_aspect_ratio_css']);
        \add_action('enqueue_block_editor_assets', [self::class, 'enqueue_editor_aspect_ratio_css']);

        \add_action('switch_theme', [self::class, 'clear_cache']);
        \add_action('customize_save_after', [self::class, 'clear_cache']);
        \add_filter('wp_theme_json_data_user', function ($theme_json) {
            self::clear_cache();
            return $theme_json;
        });
    }
}
