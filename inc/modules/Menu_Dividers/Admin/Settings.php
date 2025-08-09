<?php

/**
 * Menu Dividers Settings
 *
 * Handles settings management for the Menu Dividers module.
 * This module is plug-and-play with no configurable settings.
 *
 * @package    Orbitools
 * @subpackage Modules/Menu_Dividers/Admin
 * @since      1.0.0
 */

namespace Orbitools\Modules\Menu_Dividers\Admin;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Menu Dividers Settings Class
 *
 * @since 1.0.0
 */
class Settings
{
    /**
     * Settings option name
     *
     * @since 1.0.0
     * @var string
     */
    const OPTION_NAME = 'orbitools_menu_dividers_settings';

    /**
     * Get default settings
     *
     * @since 1.0.0
     * @return array Default settings array.
     */
    public static function get_defaults()
    {
        return array(
            'menu_dividers_enabled' => true // Always enabled, no settings needed
        );
    }

    /**
     * Get current settings with defaults merged
     *
     * @since 1.0.0
     * @return array Current settings array.
     */
    public static function get_current_settings()
    {
        $saved_settings = get_option(self::OPTION_NAME, array());
        return wp_parse_args($saved_settings, self::get_defaults());
    }

    /**
     * Update settings
     *
     * @since 1.0.0
     * @param array $new_settings Settings to save.
     * @return bool True if successful, false otherwise.
     */
    public static function update_settings($new_settings)
    {
        $defaults = self::get_defaults();
        $sanitized_settings = wp_parse_args($new_settings, $defaults);
        
        return update_option(self::OPTION_NAME, $sanitized_settings);
    }
}