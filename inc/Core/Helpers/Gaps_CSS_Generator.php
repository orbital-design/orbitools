<?php
/**
 * Gaps CSS Generator
 *
 * Static utility for generating gap CSS classes dynamically from spacing configuration.
 * Similar to the spacer height system but for flexbox/grid gaps.
 *
 * @since 1.0.0
 */

namespace Orbitools\Core\Helpers;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Gaps_CSS_Generator
{
    /**
     * Transient key prefix for cached CSS
     */
    private const TRANSIENT_KEY = 'orbitools_gaps_css';

    /**
     * In-memory cache for the current request
     *
     * @var string|null
     */
    private static $css_cache = null;

    /**
     * Generate CSS for all gap classes (with transient caching)
     *
     * @return string Generated CSS for gap classes
     */
    public static function generate_gaps_css(): string
    {
        // Return in-memory cache if available (same request)
        if (self::$css_cache !== null) {
            return self::$css_cache;
        }

        $spacing_sizes = Spacing_Utils::get_spacing_sizes();
        $breakpoints = Spacing_Utils::get_breakpoints();

        if (empty($spacing_sizes)) {
            self::$css_cache = '';
            return '';
        }

        // Build cache key from config data. The leading schema version
        // busts stale transients whenever the generated CSS shape changes
        // (the config inputs alone wouldn't) — bump it on any edit below.
        $cache_key = self::TRANSIENT_KEY . '_' . md5(\wp_json_encode([3, $spacing_sizes, $breakpoints]));

        // Check transient cache
        $cached = \get_transient($cache_key);
        if ($cached !== false) {
            self::$css_cache = $cached;
            return $cached;
        }

        $css = '';
        
        // Base gap classes with has-gap pattern
        $css .= "/* Gap Classes - has-gap Pattern */\n";
        
        // Special case: zero gap
        $css .= ".has-gap.has-gap--0 {\n";
        $css .= "    gap: 0;\n";
        $css .= "}\n\n";

        // Generate spacing size gap classes. No `, {$size}` fallback:
        // WordPress emits --wp--preset--spacing--{slug} for every preset and
        // this CSS only exists when those presets do, so the var is always
        // defined. Slug 0 is the special-cased zero above.
        foreach ($spacing_sizes as $spacing) {
            $slug = $spacing['slug'];

            if ((string) $slug === '0') {
                continue;
            }

            $css .= ".has-gap.has-gap--{$slug} {\n";
            $css .= "    gap: var(--wp--preset--spacing--{$slug});\n";
            $css .= "}\n\n";
        }

        // Axis-specific gap classes (row-gap / column-gap) for split gaps.
        // Same conventions as the shorthand above: zero is special-cased,
        // slug 0 is skipped in the loop, and there's no var fallback.
        $css .= ".has-gap.has-row-gap--0 {\n";
        $css .= "    row-gap: 0;\n";
        $css .= "}\n\n";
        $css .= ".has-gap.has-column-gap--0 {\n";
        $css .= "    column-gap: 0;\n";
        $css .= "}\n\n";

        foreach ($spacing_sizes as $spacing) {
            $slug = $spacing['slug'];

            if ((string) $slug === '0') {
                continue;
            }

            $css .= ".has-gap.has-row-gap--{$slug} {\n";
            $css .= "    row-gap: var(--wp--preset--spacing--{$slug});\n";
            $css .= "}\n\n";

            $css .= ".has-gap.has-column-gap--{$slug} {\n";
            $css .= "    column-gap: var(--wp--preset--spacing--{$slug});\n";
            $css .= "}\n\n";
        }

        // Generate responsive gap classes for all breakpoints.
        // Desktop-first cascade (tablet then mobile), each honouring
        // its own `query` direction (defaults to max-width) so the
        // queries line up with WordPress's device-preview widths.
        foreach ($breakpoints as $breakpoint) {
            $breakpoint_slug = $breakpoint['slug'];
            $breakpoint_value = $breakpoint['value'];
            $breakpoint_query = $breakpoint['query'] ?? 'max-width';

            $css .= "@media ({$breakpoint_query}: {$breakpoint_value}) {\n";
            
            // Zero gap for this breakpoint
            $css .= "    .has-gap.{$breakpoint_slug}\:has-gap--0 {\n";
            $css .= "        gap: 0;\n";
            $css .= "    }\n\n";
            
            // Spacing sizes for this breakpoint (no fallback; slug 0 is
            // special-cased above).
            foreach ($spacing_sizes as $spacing) {
                $slug = $spacing['slug'];

                if ((string) $slug === '0') {
                    continue;
                }

                $css .= "    .has-gap.{$breakpoint_slug}\:has-gap--{$slug} {\n";
                $css .= "        gap: var(--wp--preset--spacing--{$slug});\n";
                $css .= "    }\n\n";
            }

            // Axis-specific zero for this breakpoint.
            $css .= "    .has-gap.{$breakpoint_slug}\:has-row-gap--0 {\n";
            $css .= "        row-gap: 0;\n";
            $css .= "    }\n\n";
            $css .= "    .has-gap.{$breakpoint_slug}\:has-column-gap--0 {\n";
            $css .= "        column-gap: 0;\n";
            $css .= "    }\n\n";

            // Axis-specific spacing sizes for this breakpoint.
            foreach ($spacing_sizes as $spacing) {
                $slug = $spacing['slug'];

                if ((string) $slug === '0') {
                    continue;
                }

                $css .= "    .has-gap.{$breakpoint_slug}\:has-row-gap--{$slug} {\n";
                $css .= "        row-gap: var(--wp--preset--spacing--{$slug});\n";
                $css .= "    }\n\n";

                $css .= "    .has-gap.{$breakpoint_slug}\:has-column-gap--{$slug} {\n";
                $css .= "        column-gap: var(--wp--preset--spacing--{$slug});\n";
                $css .= "    }\n\n";
            }

            $css .= "}\n\n";
        }

        // Minify before caching
        $css = Minifier::css($css);

        // Store in transient (no expiry — invalidated on config change)
        \set_transient($cache_key, $css);

        // Store in-memory for same request
        self::$css_cache = $css;

        return $css;
    }

    /**
     * Clear cached gaps CSS
     *
     * Call this when spacing sizes or breakpoints config changes.
     */
    public static function clear_cache(): void
    {
        global $wpdb;

        // Clear all gaps CSS transients
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
                '_transient_' . self::TRANSIENT_KEY . '_%',
                '_transient_timeout_' . self::TRANSIENT_KEY . '_%'
            )
        );

        // Clear in-memory cache
        self::$css_cache = null;
    }

    /**
     * Enqueue gaps CSS for frontend with filter
     * 
     * @param string $handle Optional stylesheet handle to attach to
     */
    public static function enqueue_frontend_gaps_css(string $handle = ''): void
    {
        // Filter to allow themes to disable frontend gaps CSS generation
        if (!\apply_filters('orbitools_gaps_frontend_css', true)) {
            return;
        }

        $css = self::generate_gaps_css();
        if (!empty($css)) {
            if (empty($handle)) {
                // Fold into the consolidated frontend block (global-styles).
                Inline_CSS::add_frontend($css);
            } else {
                \wp_add_inline_style($handle, $css);
            }
        }
    }

    /**
     * Enqueue gaps CSS for block editor with filter
     * 
     * @param string $handle Optional stylesheet handle to attach to
     */
    public static function enqueue_editor_gaps_css(string $handle = ''): void
    {
        // Only inside the editor. This runs on `enqueue_block_assets`, which
        // also fires on the frontend — there the frontend handler on
        // `wp_enqueue_scripts` owns gap CSS, so bail when not in admin.
        if (!\is_admin()) {
            return;
        }

        // Filter to allow themes to disable editor gaps CSS generation
        if (!\apply_filters('orbitools_gaps_editor_css', true)) {
            return;
        }

        $css = self::generate_gaps_css();
        if (!empty($css)) {
            if (empty($handle)) {
                // Create our own handle if none provided
                \wp_register_style('orbitools-gaps-editor', false);
                \wp_enqueue_style('orbitools-gaps-editor');
                \wp_add_inline_style('orbitools-gaps-editor', $css);
            } else {
                \wp_add_inline_style($handle, $css);
            }
        }
    }

    /**
     * Setup gaps CSS generation hooks
     * Call this method to automatically enqueue gaps CSS
     */
    public static function init(): void
    {
        // Add inline styles for frontend
        \add_action('wp_enqueue_scripts', [self::class, 'enqueue_frontend_gaps_css']);

        // Add inline styles for the block editor. `enqueue_block_assets` (not
        // `enqueue_block_editor_assets`) is the hook WordPress injects into the
        // editor canvas iframe, where the blocks actually render — the latter
        // only reaches the editor's outer chrome, so gap classes wouldn't apply
        // to block previews. The handler bails on the frontend via is_admin().
        \add_action('enqueue_block_assets', [self::class, 'enqueue_editor_gaps_css']);

        // Clear CSS cache when theme settings change
        \add_action('switch_theme', [self::class, 'clear_cache']);
        \add_action('customize_save_after', [self::class, 'clear_cache']);
        \add_filter('wp_theme_json_data_user', function ($theme_json) {
            self::clear_cache();
            return $theme_json;
        });
    }

    /**
     * Get array of all available gap class names
     * Useful for validation or class generation
     * 
     * @param bool $include_responsive Whether to include responsive variants
     * @return array Array of gap class names (returns both base and modifier classes)
     */
    public static function get_available_gap_classes(bool $include_responsive = true): array
    {
        $spacing_sizes = Spacing_Utils::get_spacing_sizes();
        $breakpoints = Spacing_Utils::get_breakpoints();
        
        $classes = ['has-gap', 'has-gap--0']; // Base class and zero value
        
        // Add base modifier classes
        foreach ($spacing_sizes as $spacing) {
            $classes[] = "has-gap--{$spacing['slug']}";
        }
        
        // Add responsive modifier classes if requested
        if ($include_responsive) {
            foreach ($breakpoints as $breakpoint) {
                $breakpoint_slug = $breakpoint['slug'];
                
                $classes[] = "{$breakpoint_slug}:has-gap--0";
                
                foreach ($spacing_sizes as $spacing) {
                    $classes[] = "{$breakpoint_slug}:has-gap--{$spacing['slug']}";
                }
            }
        }
        
        return $classes;
    }

    /**
     * Check if a gap class name is valid
     * 
     * @param string $class_name The gap class to validate
     * @return bool True if valid, false otherwise
     */
    public static function is_valid_gap_class(string $class_name): bool
    {
        $available_classes = self::get_available_gap_classes(true);
        return in_array($class_name, $available_classes, true);
    }

    /**
     * Generate gap classes from responsive value object
     * Returns both base class and modifier classes
     * 
     * @param array $gap_values Responsive gap values (e.g., ['base' => '4', 'md' => '6'])
     * @return string Space-separated gap class names
     */
    public static function get_gap_classes_from_values(array $gap_values): string
    {
        $classes = ['has-gap']; // Always include base class
        
        foreach ($gap_values as $breakpoint => $value) {
            if ($value === null || $value === '' || $value === false) {
                continue;
            }
            
            $class_name = $breakpoint === 'base'
                ? "has-gap--{$value}"
                : "{$breakpoint}:has-gap--{$value}";
            
            $classes[] = $class_name;
        }
        
        return implode(' ', $classes);
    }
}