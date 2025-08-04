<?php

/**
 * Menu Groups Admin Handler
 *
 * Handles all admin-related functionality for the Menu Groups module,
 * including module registration, settings integration, and admin notices.
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
 * Menu Groups Admin Class
 *
 * Manages admin interface integration for the Menu Groups module.
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
    const MODULE_SLUG = 'menu-groups';

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
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'));
    }

    /**
     * Check if the Menu Groups module is enabled
     *
     * @since 1.0.0
     * @return bool True if module is enabled, false otherwise.
     */
    public function is_module_enabled(): bool
    {
        $settings = get_option('orbitools_settings', array());
        return !empty($settings['menu_groups_enabled']) && $settings['menu_groups_enabled'] !== '0';
    }

    /**
     * Register module metadata for the admin interface
     *
     * @since 1.0.0
     * @param array $modules Existing modules array.
     * @return array Modified modules array with Menu Groups metadata.
     */
    public function register_module_metadata(array $modules): array
    {
        $modules['menu_groups'] = array(
            'name'        => __('Menu Groups', 'orbitools'),
            'subtitle'    => __('Organize menu items into groups', 'orbitools'),
            'description' => __('Add group headings to your WordPress menus to organize menu items visually. Create cleaner navigation with logical groupings.', 'orbitools'),
            'icon'        => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="#32a3e2" d="M0 96C0 60.7 28.7 32 64 32l384 0c35.3 0 64 28.7 64 64l0 64c0 35.3-28.7 64-64 64l-288 0 0 96c0 17.7 14.3 32 32 32l32 0c0-35.3 28.7-64 64-64l160 0c35.3 0 64 28.7 64 64l0 64c0 35.3-28.7 64-64 64l-160 0c-35.3 0-64-28.7-64-64l-32 0c-53 0-96-43-96-96l0-96-32 0c-35.3 0-64-28.7-64-64L0 96zM448 352l-160 0 0 64 160 0 0-64z"/></svg>',
            'configure_url' => null,
        );

        return $modules;
    }


    /**
     * Enqueue admin styles and scripts for menu editing
     *
     * @since 1.0.0
     * @param string $hook_suffix Current admin page.
     */
    public function enqueue_admin_styles($hook_suffix)
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
            'orbitools-menu-groups-admin',
            ORBITOOLS_URL . 'build/admin/css/modules/menu-groups.css',
            array(),
            self::VERSION
        );

        // Only enqueue script on nav-menus page
        if ('nav-menus.php' === $hook_suffix) {
            wp_enqueue_script(
                'orbitools-menu-groups-admin',
                plugin_dir_url(__FILE__) . '../js/admin-menu-groups.js',
                array(),
                self::VERSION,
                true
            );

            wp_enqueue_script(
                'orbitools-menu-groups-processor',
                plugin_dir_url(__FILE__) . '../js/menu-item-processor.js',
                array(),
                self::VERSION,
                true
            );

            wp_localize_script(
                'orbitools-menu-groups-admin',
                'menuGroupsAdmin',
                array(
                    'addGroupText' => __('Add Group', 'orbitools')
                )
            );
        }
    }
}
