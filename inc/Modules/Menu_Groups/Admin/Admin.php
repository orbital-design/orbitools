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
        return !empty($settings['menu-groups_enabled']) && $settings['menu-groups_enabled'] !== '0';
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
            ORBITOOLS_URL . 'build/admin/css/modules/menu-groups/admin.css',
            array(),
            self::VERSION
        );

        // Only enqueue script on nav-menus page
        if ('nav-menus.php' === $hook_suffix) {
            wp_enqueue_script(
                'orbitools-menu-groups-admin',
                ORBITOOLS_URL . 'build/admin/js/modules/menu-groups/admin.js',
                array(),
                self::VERSION,
                true
            );

            wp_enqueue_script(
                'orbitools-menu-groups-processor',
                ORBITOOLS_URL . 'build/admin/js/modules/menu-groups/processor.js',
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
