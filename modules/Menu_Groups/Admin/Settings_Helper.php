<?php

/**
 * Menu Groups Settings Helper
 *
 * Provides helper methods for accessing Menu Groups module settings.
 *
 * @package    Orbitools
 * @subpackage Modules/Menu_Groups/Admin
 * @since      1.0.0
 */

namespace Orbitools\Modules\Menu_Groups\Admin;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Menu Groups Settings Helper Class
 *
 * @since 1.0.0
 */
class Settings_Helper
{
    /**
     * Check if the Menu Groups module is enabled
     *
     * @since 1.0.0
     * @return bool True if module is enabled, false otherwise.
     */
    public static function is_module_enabled(): bool
    {
        $settings = get_option('orbitools_settings', array());
        return !empty($settings['menu_groups_enabled']);
    }

    /**
     * Get the group heading style setting
     *
     * @since 1.0.0
     * @return string The selected heading style.
     */
    public static function get_heading_style(): string
    {
        $settings = get_option('orbitools_settings', array());
        return isset($settings['menu_groups_heading_style']) ? $settings['menu_groups_heading_style'] : 'default';
    }

    /**
     * Check if group separators should be shown
     *
     * @since 1.0.0
     * @return bool True if separators should be shown, false otherwise.
     */
    public static function show_separators(): bool
    {
        $settings = get_option('orbitools_settings', array());
        return !empty($settings['menu_groups_show_separator']);
    }

    /**
     * Get custom CSS classes for group headings
     *
     * @since 1.0.0
     * @return string Custom CSS classes.
     */
    public static function get_custom_classes(): string
    {
        $settings = get_option('orbitools_settings', array());
        return isset($settings['menu_groups_custom_classes']) ? sanitize_text_field($settings['menu_groups_custom_classes']) : '';
    }

    /**
     * Get all Menu Groups settings as an array
     *
     * @since 1.0.0
     * @return array Menu Groups settings.
     */
    public static function get_all_settings(): array
    {
        return array(
            'enabled'         => self::is_module_enabled(),
            'heading_style'   => self::get_heading_style(),
            'show_separators' => self::show_separators(),
            'custom_classes'  => self::get_custom_classes(),
        );
    }
}