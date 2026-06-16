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
     * Register (but don't enqueue) the frontend aspect-ratio CSS.
     *
     * Enqueued per-block during render_block so the CSS only loads on
     * pages that actually contain a block using the aspect-ratio
     * control — see enqueue_aspect_ratio_on_render. Because render_block
     * fires after wp_head, the style prints in the footer via
     * print_late_styles() (non-render-blocking), same pattern as the
     * Spacer block + Block_Style_Loader.
     */
    public static function register_frontend_aspect_ratio_css(): void
    {
        if (!\apply_filters('orbitools_aspect_ratio_frontend_css', true)) {
            return;
        }

        $css = self::generate_aspect_ratio_css();
        if (!empty($css)) {
            \wp_register_style('orbitools-aspect-ratio-frontend', false);
            \wp_add_inline_style('orbitools-aspect-ratio-frontend', $css);
        }
    }

    /**
     * Enqueue the aspect-ratio frontend style the first time a block
     * using the control renders. The control always adds the base
     * `has-aspect-ratio` class (see AspectRatioRenderer), so a simple
     * substring check on the rendered HTML reliably detects it. The
     * Video block's own `has-{ratio}-aspect-ratio` scheme doesn't
     * contain the `has-aspect-ratio` substring, so it won't false-match.
     *
     * @param string              $content      Rendered block HTML.
     * @param array<string,mixed> $parsed_block Parsed block data.
     */
    public static function enqueue_aspect_ratio_on_render(string $content, array $parsed_block): string
    {
        if (
            strpos($content, 'has-aspect-ratio') !== false
            && \wp_style_is('orbitools-aspect-ratio-frontend', 'registered')
            && !\wp_style_is('orbitools-aspect-ratio-frontend', 'enqueued')
        ) {
            \wp_enqueue_style('orbitools-aspect-ratio-frontend');
        }

        return $content;
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
        \add_action('wp_enqueue_scripts', [self::class, 'register_frontend_aspect_ratio_css']);
        \add_filter('render_block', [self::class, 'enqueue_aspect_ratio_on_render'], 10, 2);
        \add_action('enqueue_block_editor_assets', [self::class, 'enqueue_editor_aspect_ratio_css']);

        \add_action('switch_theme', [self::class, 'clear_cache']);
        \add_action('customize_save_after', [self::class, 'clear_cache']);
        \add_filter('wp_theme_json_data_user', function ($theme_json) {
            self::clear_cache();
            return $theme_json;
        });
    }
}
