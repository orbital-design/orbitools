<?php

namespace Orbitools\Helpers;

/**
 * Settings Manager
 * 
 * Centralized settings management for all OrbiTools modules.
 * Provides a consistent API for reading and writing module settings
 * with caching and validation.
 * 
 * @package Orbitools
 * @since 1.0.0
 */
class Settings_Manager
{
    /**
     * Settings cache to avoid repeated database queries
     * 
     * @var array|null
     */
    private static $settings_cache = null;

    /**
     * The main settings option name in WordPress
     * 
     * @var string
     */
    private const SETTINGS_OPTION = 'orbitools_settings';

    /**
     * Get all OrbiTools settings
     * 
     * @return array All settings with caching
     */
    public function get_all_settings(): array
    {
        if (self::$settings_cache === null) {
            self::$settings_cache = get_option(self::SETTINGS_OPTION, []);
        }
        
        return self::$settings_cache;
    }

    /**
     * Check if a module is enabled
     * 
     * @param string $module_slug Module slug identifier
     * @return bool True if enabled, false otherwise
     */
    public function is_module_enabled(string $module_slug): bool
    {
        $settings = $this->get_all_settings();
        $key = $module_slug . '_enabled';
        
        return isset($settings[$key]) ? (bool) $settings[$key] : true; // Default to enabled
    }

    /**
     * Get a specific module setting
     * 
     * @param string $module_slug Module slug identifier
     * @param string $setting_key Setting key within the module
     * @param mixed $default Default value if setting doesn't exist
     * @return mixed Setting value or default
     */
    public function get_module_setting(string $module_slug, string $setting_key, $default = null)
    {
        $settings = $this->get_all_settings();
        $full_key = $module_slug . '_' . $setting_key;
        
        return isset($settings[$full_key]) ? $settings[$full_key] : $default;
    }

    /**
     * Update a specific module setting
     * 
     * @param string $module_slug Module slug identifier
     * @param string $setting_key Setting key within the module
     * @param mixed $value New value
     * @return bool True on success, false on failure
     */
    public function update_module_setting(string $module_slug, string $setting_key, $value): bool
    {
        $settings = $this->get_all_settings();
        $full_key = $module_slug . '_' . $setting_key;
        
        $settings[$full_key] = $value;
        
        $result = update_option(self::SETTINGS_OPTION, $settings);
        
        if ($result) {
            self::$settings_cache = $settings; // Update cache
        }
        
        return $result;
    }

    /**
     * Update multiple settings at once
     * 
     * @param array $settings_array Associative array of setting_key => value
     * @return bool True on success, false on failure
     */
    public function update_multiple_settings(array $settings_array): bool
    {
        $current_settings = $this->get_all_settings();
        $updated_settings = array_merge($current_settings, $settings_array);
        
        $result = update_option(self::SETTINGS_OPTION, $updated_settings);
        
        if ($result) {
            self::$settings_cache = $updated_settings; // Update cache
        }
        
        return $result;
    }

    /**
     * Clear the settings cache
     * Useful when settings are updated outside this class
     * 
     * @return void
     */
    public function clear_cache(): void
    {
        self::$settings_cache = null;
    }

    /**
     * Get default settings for a module
     * Merges module defaults with current settings
     * 
     * @param string $module_slug Module slug identifier
     * @param array $defaults Default settings array
     * @return array Merged settings
     */
    public function get_module_settings_with_defaults(string $module_slug, array $defaults): array
    {
        $current_settings = $this->get_all_settings();
        $module_settings = [];
        
        foreach ($defaults as $key => $default_value) {
            $full_key = $module_slug . '_' . str_replace($module_slug . '_', '', $key);
            $module_settings[$key] = isset($current_settings[$full_key]) ? $current_settings[$full_key] : $default_value;
        }
        
        return $module_settings;
    }
}