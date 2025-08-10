<?php
/**
 * Spacing Utilities
 *
 * Static utility methods for getting spacing sizes and breakpoints configuration
 * from WordPress, theme.json, or plugin defaults. Can be used across the plugin.
 *
 * @since 1.0.0
 */

namespace Orbitools\Core\Helpers;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Spacing_Utils
{
    /**
     * Get spacing sizes from WordPress configuration
     * 
     * @return array Array of spacing size objects with 'slug', 'name', 'size' keys
     */
    public static function get_spacing_sizes(): array
    {
        // Try to get from SpacingConfig first
        if (class_exists('Orbitools\\Core\\SpacingConfig')) {
            $config = \Orbitools\Core\SpacingConfig::get_spacing_config();
            if (!empty($config)) {
                return $config;
            }
        }

        // Fallback to WordPress theme settings
        if (\function_exists('wp_get_global_settings')) {
            $global_settings = \wp_get_global_settings();
            $spacing_data = $global_settings['spacing']['spacingSizes'] ?? null;
            
            if (is_array($spacing_data)) {
                // Try theme spacings first
                if (isset($spacing_data['theme']) && !empty($spacing_data['theme'])) {
                    return $spacing_data['theme'];
                }
                // Fall back to default spacings
                if (isset($spacing_data['default']) && !empty($spacing_data['default'])) {
                    return $spacing_data['default'];
                }
            }
        }

        // Ultimate fallback to plugin defaults
        return self::get_default_spacing_sizes();
    }

    /**
     * Get breakpoints configuration
     * 
     * @return array Array of breakpoint objects with 'slug', 'name', 'value' keys
     */
    public static function get_breakpoints(): array
    {
        // Try to get from SpacingConfig first
        if (class_exists('Orbitools\\Core\\SpacingConfig')) {
            $config = \Orbitools\Core\SpacingConfig::get_breakpoints_config();
            if (!empty($config)) {
                return $config;
            }
        }

        // Fallback to default breakpoints
        return self::get_default_breakpoints();
    }

    /**
     * Get default spacing sizes from plugin config
     * 
     * @return array Default spacing sizes from defaults.json
     */
    public static function get_default_spacing_sizes(): array
    {
        $defaults_file = ORBITOOLS_DIR . 'config/defaults.json';
        
        if (file_exists($defaults_file)) {
            $defaults = json_decode(file_get_contents($defaults_file), true);
            return $defaults['defaults']['spacings'] ?? [];
        }
        
        return [];
    }

    /**
     * Get default breakpoints from plugin config
     * 
     * @return array Default breakpoints from defaults.json
     */
    public static function get_default_breakpoints(): array
    {
        $defaults_file = ORBITOOLS_DIR . 'config/defaults.json';
        
        if (file_exists($defaults_file)) {
            $defaults = json_decode(file_get_contents($defaults_file), true);
            return $defaults['defaults']['breakpoints'] ?? [];
        }
        
        return [];
    }

    /**
     * Get spacing size by slug
     * 
     * @param string $slug The spacing slug to find
     * @return array|null Spacing size object or null if not found
     */
    public static function get_spacing_size_by_slug(string $slug): ?array
    {
        $spacing_sizes = self::get_spacing_sizes();
        
        foreach ($spacing_sizes as $spacing) {
            if ($spacing['slug'] === $slug) {
                return $spacing;
            }
        }
        
        return null;
    }

    /**
     * Get breakpoint by slug
     * 
     * @param string $slug The breakpoint slug to find
     * @return array|null Breakpoint object or null if not found
     */
    public static function get_breakpoint_by_slug(string $slug): ?array
    {
        $breakpoints = self::get_breakpoints();
        
        foreach ($breakpoints as $breakpoint) {
            if ($breakpoint['slug'] === $slug) {
                return $breakpoint;
            }
        }
        
        return null;
    }

    /**
     * Check if a spacing slug is valid
     * 
     * @param string $slug The spacing slug to validate
     * @return bool True if valid, false otherwise
     */
    public static function is_valid_spacing_slug(string $slug): bool
    {
        // Special cases
        if (in_array($slug, ['0', 'fill'], true)) {
            return true;
        }
        
        return self::get_spacing_size_by_slug($slug) !== null;
    }

    /**
     * Check if a breakpoint slug is valid
     * 
     * @param string $slug The breakpoint slug to validate
     * @return bool True if valid, false otherwise
     */
    public static function is_valid_breakpoint_slug(string $slug): bool
    {
        // Base breakpoint is always valid
        if ($slug === 'base') {
            return true;
        }
        
        return self::get_breakpoint_by_slug($slug) !== null;
    }
}