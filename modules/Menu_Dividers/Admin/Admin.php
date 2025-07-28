<?php

/**
 * Menu Dividers Admin Handler
 *
 * Manages the administrative interface for the Menu Dividers module,
 * including module registration and admin assets.
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
 * Menu Dividers Admin Class
 *
 * Handles administrative functionality for the Menu Dividers module.
 *
 * @since 1.0.0
 */
class Admin
{
    /**
     * Module version
     *
     * @since 1.0.0
     * @var string
     */
    const VERSION = '1.0.0';

    /**
     * Module slug identifier
     *
     * @since 1.0.0
     * @var string
     */
    const MODULE_SLUG = 'menu-dividers';

    /**
     * Initialize admin functionality
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        // Register module metadata
        add_filter('orbitools_available_modules', array($this, 'register_module_metadata'));

        // Add admin styles for menu editing
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }

    /**
     * Check if the Menu Dividers module is enabled
     *
     * @since 1.0.0
     * @return bool True if module is enabled, false otherwise.
     */
    public function is_module_enabled(): bool
    {
        $settings = get_option('orbitools_settings', array());
        return !empty($settings['menu_dividers_enabled']) && $settings['menu_dividers_enabled'] !== '0';
    }

    /**
     * Register module metadata for the admin interface
     *
     * @since 1.0.0
     * @param array $modules Existing modules array.
     * @return array Modified modules array with Menu Dividers metadata.
     */
    public function register_module_metadata(array $modules): array
    {
        $modules['menu_dividers'] = array(
            'name'        => __('Menu Dividers', 'orbitools'),
            'subtitle'    => __('Add visual dividers between menu items', 'orbitools'),
            'description' => __('Add visual separator lines between menu items to create cleaner navigation with logical groupings and improved visual hierarchy.', 'orbitools'),
            'icon'        => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 672 672"><!--!Font Awesome Pro 7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2025 Fonticons, Inc.--><path fill="#32a3e2" d="M112 336c0 22 1.1 32.1 3.5 37.9 1 2.4 2.3 5.5 12.9 8.9 12.5 4 33.1 6.6 67.9 7.9 92.8 3.5 186.7 3.5 279.5 0 34.8-1.3 55.4-3.9 67.9-7.9 10.6-3.4 11.9-6.5 12.9-8.9 2.4-5.9 3.5-16 3.5-37.9s-1.1-32.1-3.5-37.9c-1-2.4-2.3-5.5-12.9-8.9-12.5-4-33.1-6.6-67.9-7.9-92.8-3.5-186.7-3.5-279.5 0-34.8 1.3-55.4 3.9-67.9 7.9-10.6 3.4-11.9 6.5-12.9 8.9-2.4 5.9-3.5 16-3.5 37.9z" opacity=".4"/><path fill="#32a3e2" d="M63.7 395.2C56.4 377.4 56 356 56 336s.4-41.4 7.7-59.2c8.7-21.2 25.3-33.9 47.7-41 20.5-6.5 47.9-9.2 82.8-10.5 93.8-3.5 189.9-3.5 283.7 0 35 1.3 62.3 4 82.8 10.5 22.4 7.1 39 19.8 47.7 41 7.3 17.8 7.7 39.1 7.7 59.2s-.4 41.4-7.7 59.2c-8.7 21.2-25.3 33.9-47.7 41-20.5 6.5-47.9 9.2-82.8 10.5-93.8 3.5-189.9 3.5-283.7 0-35-1.3-62.3-4-82.8-10.5-22.4-7.1-39-19.8-47.7-41zm132.6-4.5c92.8 3.5 186.7 3.5 279.5 0 34.8-1.3 55.4-3.9 67.9-7.9 10.6-3.4 11.9-6.5 12.9-8.9 2.4-5.9 3.5-16 3.5-37.9s-1.1-32.1-3.5-37.9c-1-2.4-2.3-5.5-12.9-8.9-12.5-4-33.1-6.6-67.9-7.9-92.8-3.5-186.7-3.5-279.5 0-34.8 1.3-55.4 3.9-67.9 7.9-10.6 3.4-11.9 6.5-12.9 8.9-2.4 5.9-3.5 16-3.5 37.9s1.1 32.1 3.5 37.9c1 2.4 2.3 5.5 12.9 8.9 12.5 4 33.1 6.6 67.9 7.9z"/></svg>',
            'configure_url' => null, // No settings needed
        );

        return $modules;
    }

    /**
     * Enqueue admin assets for menu editing
     *
     * @since 1.0.0
     * @param string $hook_suffix Current admin page.
     */
    public function enqueue_admin_assets($hook_suffix)
    {
        // Only load on nav-menus.php page and our settings page
        if ('nav-menus.php' !== $hook_suffix && strpos($hook_suffix, 'orbitools') === false) {
            return;
        }

        // Don't load if module is disabled
        if ('nav-menus.php' === $hook_suffix && !$this->is_module_enabled()) {
            return;
        }

        wp_enqueue_style(
            'orbitools-menu-dividers-admin',
            plugin_dir_url(__FILE__) . '../css/admin-menu-dividers.css',
            array(),
            self::VERSION
        );

        // Only enqueue script on nav-menus page
        if ('nav-menus.php' === $hook_suffix) {
            wp_enqueue_script(
                'orbitools-menu-dividers-admin',
                plugin_dir_url(__FILE__) . '../js/admin-menu-dividers.js',
                array(),
                self::VERSION,
                true
            );

            wp_enqueue_script(
                'orbitools-menu-dividers-processor',
                plugin_dir_url(__FILE__) . '../js/menu-item-processor.js',
                array(),
                self::VERSION,
                true
            );

            wp_localize_script(
                'orbitools-menu-dividers-admin',
                'menuDividersAdmin',
                array(
                    'addDividerText' => __('Add Divider', 'orbitools')
                )
            );
        }
    }
}