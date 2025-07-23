<?php

/**
 * Layout Guides Settings Helper
 *
 * Provides utility functions for settings management and validation
 * for the Layout Guides module.
 *
 * @package    Orbitools
 * @subpackage Modules/Layout_Guides/Admin
 * @since      1.0.0
 */

namespace Orbitools\Modules\Layout_Guides\Admin;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Layout Guides Settings Helper Class
 *
 * Provides utility functions for settings normalization and validation.
 *
 * @since 1.0.0
 */
class Settings_Helper
{
    /**
     * Normalize settings values
     *
     * @since 1.0.0
     * @param array $settings Raw settings values.
     * @return array Normalized settings values.
     */
    public static function normalize_settings($settings)
    {
        $defaults = Settings::get_defaults();
        $normalized = wp_parse_args($settings, $defaults);

        // Validate and normalize specific settings
        $normalized['layout_guides_grid_gutter'] = sanitize_text_field($normalized['layout_guides_grid_gutter']);
        
        $normalized['layout_guides_opacity'] = max(0.1, min(1.0, floatval($normalized['layout_guides_opacity'])));
        
        $normalized['layout_guides_color'] = sanitize_hex_color($normalized['layout_guides_color']) ?: $defaults['layout_guides_color'];

        // Validate keyboard shortcut
        $valid_shortcuts = array('ctrl+shift+g', 'ctrl+shift+l', 'ctrl+shift+r', 'alt+shift+g', 'alt+shift+l');
        if (!in_array($normalized['layout_guides_toggle_key'], $valid_shortcuts)) {
            $normalized['layout_guides_toggle_key'] = $defaults['layout_guides_toggle_key'];
        }

        return $normalized;
    }

    /**
     * Get CSS custom properties for layout guides
     *
     * @since 1.0.0
     * @return string CSS custom properties.
     */
    public static function get_css_custom_properties()
    {
        $settings = Settings::get_current_settings();
        $settings = self::normalize_settings($settings);

        $css = ':root {';
        $css .= '--layout-guides-columns: 12;'; // Default to 12, JavaScript will handle switching
        $css .= '--layout-guides-gutter: ' . $settings['layout_guides_grid_gutter'] . ';';
        $css .= '--layout-guides-opacity: ' . $settings['layout_guides_opacity'] . ';';
        $css .= '--layout-guides-color: ' . $settings['layout_guides_color'] . ';';
        $css .= '}';

        return $css;
    }

    /**
     * Get JavaScript configuration object
     *
     * @since 1.0.0
     * @return array JavaScript configuration.
     */
    public static function get_js_config()
    {
        $settings = Settings::get_current_settings();
        $settings = self::normalize_settings($settings);

        return array(
            'enabled' => $settings['layout_guides_enabled'],
            'shouldShow' => self::should_show_guides(), // Server-side authorization check
            'showGrids' => $settings['layout_guides_show_grids'],
            'showRulers' => $settings['layout_guides_show_rulers'],
            'gridGutter' => $settings['layout_guides_grid_gutter'],
            'opacity' => $settings['layout_guides_opacity'],
            'color' => $settings['layout_guides_color'],
            'toggleKey' => $settings['layout_guides_toggle_key'],
        );
    }

    /**
     * Check if the Layout Guides module is enabled
     *
     * @since 1.0.0
     * @return bool True if module is enabled, false otherwise.
     */
    public static function is_module_enabled(): bool
    {
        $settings = Settings::get_current_settings();
        return !empty($settings['layout_guides_enabled']);
    }

    /**
     * Check if guides should be shown on current page
     *
     * @since 1.0.0
     * @return bool Whether guides should be shown.
     */
    public static function should_show_guides()
    {
        $settings = Settings::get_current_settings();
        $settings = self::normalize_settings($settings);


        // Module must be enabled
        if (!$settings['layout_guides_enabled']) {
            return false;
        }

        // Only show for logged-in users
        if (!is_user_logged_in()) {
            return false;
        }

        // Only show on frontend (never in admin)
        if (is_admin()) {
            return false;
        }

        // Don't show in login page
        if (in_array($GLOBALS['pagenow'], array('wp-login.php', 'wp-register.php'))) {
            return false;
        }
        return true;
    }

    /**
     * Get body classes for layout guides
     *
     * @since 1.0.0
     * @return array Body classes.
     */
    public static function get_body_classes()
    {
        $classes = array();
        $settings = Settings::get_current_settings();
        $settings = self::normalize_settings($settings);

        if (self::should_show_guides()) {
            $classes[] = 'has-layout-guides';
            $classes[] = 'has-layout-guides--enabled';

            if ($settings['layout_guides_show_grids']) {
                $classes[] = 'has-layout-guides--12-grid'; // Default to 12-grid
            }

            if ($settings['layout_guides_show_rulers']) {
                $classes[] = 'has-layout-guides--rulers';
            }
        }

        return $classes;
    }
}