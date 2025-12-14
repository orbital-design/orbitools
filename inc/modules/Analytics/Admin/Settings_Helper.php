<?php

/**
 * Analytics Settings Helper
 *
 * Utility functions for handling settings data type normalization.
 * AdminKit sometimes stores checkbox values as arrays or strings.
 *
 * @package    Orbitools
 * @subpackage Modules/Analytics/Admin
 * @since      1.0.0
 */

namespace Orbitools\Modules\Analytics\Admin;

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
        // Simple check matching Typography_Presets pattern
        return !empty($value) && $value !== '0';
    }

    /**
     * Get a setting value with default fallback
     *
     * @since 1.0.0
     * @param string $key Setting key.
     * @param mixed $default Default value if setting not found.
     * @return mixed Setting value or default.
     */
    public static function get_setting(string $key, $default = '')
    {
        $settings = get_option('orbitools_settings', array());
        $value = $settings[$key] ?? $default;
        
        // For boolean settings, normalize the value
        if (is_bool($default)) {
            return self::normalize_boolean($value);
        }
        
        return $value;
    }

    /**
     * Check if the Analytics module is enabled
     *
     * @since 1.0.0
     * @return bool True if module is enabled, false otherwise.
     */
    public static function is_module_enabled(): bool
    {
        return self::get_setting('analytics_enabled', false);
    }

    /**
     * Get Analytics configuration setting
     *
     * @since 1.0.0
     * @param string $key Setting key (without analytics_ prefix).
     * @param mixed $default Default value.
     * @return mixed Setting value.
     */
    public static function get_analytics_setting(string $key, $default = '')
    {
        return self::get_setting('analytics_' . $key, $default);
    }

    /**
     * Check if tracking should be enabled for current request
     *
     * @since 1.0.0
     * @return bool True if tracking should be enabled.
     */
    public static function should_track(): bool
    {
        // Check if module is enabled
        if (!self::is_module_enabled()) {
            return false;
        }

        // Check Do Not Track header
        if (self::get_analytics_setting('respect_dnt', true) && 
            !empty($_SERVER['HTTP_DNT']) && $_SERVER['HTTP_DNT'] == '1') {
            return false;
        }

        // Check if current user role should be excluded
        $excluded_roles = self::get_analytics_setting('exclude_roles', array('administrator'));
        
        // Ensure excluded_roles is always an array
        if (!is_array($excluded_roles)) {
            $excluded_roles = array($excluded_roles);
        }
        
        if (is_user_logged_in() && !empty($excluded_roles)) {
            $user = wp_get_current_user();
            if (array_intersect($excluded_roles, $user->roles)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if a specific custom event is enabled
     *
     * @since 1.0.0
     * @param string $event_type Event type: downloads, outbound, scroll, forms.
     * @return bool True if event is enabled.
     */
    public static function is_custom_event_enabled(string $event_type): bool
    {
        $custom_events = self::get_analytics_setting('custom_events', array());
        
        // Handle array or single values
        if (is_array($custom_events)) {
            return in_array($event_type, $custom_events);
        }
        
        // Fallback for non-array values
        return false;
    }

    /**
     * Get enabled custom events
     *
     * @since 1.0.0
     * @return array Array of enabled custom event types.
     */
    public static function get_enabled_custom_events(): array
    {
        $custom_events = self::get_analytics_setting('custom_events', array());
        
        if (is_array($custom_events)) {
            return $custom_events;
        }
        
        return array();
    }

    /**
     * Check if any custom events are enabled
     *
     * @since 1.0.0
     * @return bool True if any custom events are enabled.
     */
    public static function has_custom_events_enabled(): bool
    {
        $custom_events = self::get_enabled_custom_events();
        return !empty($custom_events);
    }
}