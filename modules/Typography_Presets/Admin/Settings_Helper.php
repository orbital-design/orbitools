<?php

/**
 * Typography Presets Settings Helper
 *
 * Utility functions for handling settings data type normalization.
 * AdminKit sometimes stores checkbox values as arrays or strings.
 *
 * @package    Orbitools
 * @subpackage Modules/Typography_Presets/Admin
 * @since      1.0.0
 */

namespace Orbitools\Modules\Typography_Presets\Admin;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Settings Helper Class
 *
 * Provides utility functions for normalizing settings values.
 *
 * @since 1.0.0
 */
class Settings_Helper
{
    /**
     * Normalize a setting value to boolean
     *
     * AdminKit stores checkbox values as strings: "1" or ""
     *
     * @since 1.0.0
     * @param mixed $value The setting value to normalize.
     * @return bool Normalized boolean value.
     */
    public static function normalize_boolean($value): bool
    {
        return !empty($value) && $value !== '0';
    }

    /**
     * Get a normalized setting value
     *
     * @since 1.0.0
     * @param string $setting_key The setting key to retrieve.
     * @param mixed $default Default value if setting not found.
     * @return mixed Normalized setting value.
     */
    public static function get_setting(string $setting_key, $default = false)
    {
        $settings = get_option('orbitools_settings', array());
        $value = $settings[$setting_key] ?? $default;
        
        // For boolean settings, normalize the value
        if (is_bool($default)) {
            return self::normalize_boolean($value);
        }
        
        return $value;
    }

    /**
     * Check if Typography Presets module is enabled
     *
     * @since 1.0.0
     * @return bool True if enabled, false otherwise.
     */
    public static function is_module_enabled(): bool
    {
        return self::get_setting('typography_presets_enabled', false);
    }

    /**
     * Check if groups should be shown in dropdown
     *
     * @since 1.0.0
     * @return bool True if groups should be shown, false otherwise.
     */
    public static function show_groups_in_dropdown(): bool
    {
        return self::get_setting('typography_show_groups_in_dropdown', false);
    }

    /**
     * Check if CSS should be output
     *
     * @since 1.0.0
     * @return bool True if CSS should be output, false otherwise.
     */
    public static function output_preset_css(): bool
    {
        return self::get_setting('typography_output_preset_css', true);
    }
}