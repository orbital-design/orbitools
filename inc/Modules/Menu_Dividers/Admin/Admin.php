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
        return !empty($settings['menu-dividers_enabled']) && $settings['menu-dividers_enabled'] !== '0';
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

        // Only enqueue script on nav-menus page
        if ('nav-menus.php' === $hook_suffix) {
            wp_enqueue_style(
                'orbitools-menu-dividers-admin',
                ORBITOOLS_URL . 'build/admin/css/modules/menu-dividers/admin.css',
                array(),
                self::VERSION
            );

            wp_enqueue_script(
                'orbitools-menu-dividers-admin',
                ORBITOOLS_URL . 'build/admin/js/modules/menu-dividers/admin.js',
                array(),
                self::VERSION,
                true
            );

            wp_enqueue_script(
                'orbitools-menu-dividers-processor',
                ORBITOOLS_URL . 'build/admin/js/modules/menu-dividers/processor.js',
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
