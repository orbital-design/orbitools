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

        // Build cache key from config data
        $cache_key = self::TRANSIENT_KEY . '_' . md5(\wp_json_encode([$spacing_sizes, $breakpoints]));

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

        // Generate spacing size gap classes
        foreach ($spacing_sizes as $spacing) {
            $slug = $spacing['slug'];
            $size = $spacing['size'];
            
            $css .= ".has-gap.has-gap--{$slug} {\n";
            $css .= "    gap: var(--wp--preset--spacing--{$slug}, {$size});\n";
            $css .= "}\n\n";
        }

        // Generate responsive gap classes for all breakpoints
        foreach ($breakpoints as $breakpoint) {
            $breakpoint_slug = $breakpoint['slug'];
            $breakpoint_value = $breakpoint['value'];
            
            $css .= "@media (min-width: {$breakpoint_value}) {\n";
            
            // Zero gap for this breakpoint
            $css .= "    .has-gap.{$breakpoint_slug}\:has-gap--0 {\n";
            $css .= "        gap: 0;\n";
            $css .= "    }\n\n";
            
            // Spacing sizes for this breakpoint
            foreach ($spacing_sizes as $spacing) {
                $slug = $spacing['slug'];
                $size = $spacing['size'];
                
                $css .= "    .has-gap.{$breakpoint_slug}\:has-gap--{$slug} {\n";
                $css .= "        gap: var(--wp--preset--spacing--{$slug}, {$size});\n";
                $css .= "    }\n\n";
            }
            
            $css .= "}\n\n";
        }

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
                // Create our own handle if none provided
                \wp_register_style('orbitools-gaps-frontend', false);
                \wp_enqueue_style('orbitools-gaps-frontend');
                \wp_add_inline_style('orbitools-gaps-frontend', $css);
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

        // Add inline styles for block editor
        \add_action('enqueue_block_editor_assets', [self::class, 'enqueue_editor_gaps_css']);

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