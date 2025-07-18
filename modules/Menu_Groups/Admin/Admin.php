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

use Orbitools\Modules\Menu_Groups\Admin\Settings;
use Orbitools\Modules\Menu_Groups\Admin\Settings_Helper;

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

        // Register with admin framework
        add_filter('orbitools_adminkit_structure', array($this, 'register_new_framework_structure'));
        add_filter('orbitools_adminkit_fields', array($this, 'register_new_framework_settings'));

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
        return Settings_Helper::is_module_enabled();
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
            'configure_url' => admin_url('admin.php?page=orbitools&tab=modules&section=menu-groups'),
        );

        return $modules;
    }

    /**
     * Register admin structure for the new framework
     *
     * @since 1.0.0
     * @param array $structure Existing structure array.
     * @return array Modified structure array.
     */
    public function register_new_framework_structure(array $structure): array
    {
        if (!isset($structure['modules']['sections'])) {
            $structure['modules']['sections'] = array();
        }

        // Get structure from Settings class
        $settings_structure = Settings::get_admin_structure();
        $structure['modules']['sections'] = array_merge(
            $structure['modules']['sections'],
            $settings_structure['sections']
        );

        return $structure;
    }

    /**
     * Register settings for the new framework
     *
     * @since 1.0.0
     * @param array $settings Existing settings array.
     * @return array Modified settings array.
     */
    public function register_new_framework_settings(array $settings): array
    {
        if (!isset($settings['modules'])) {
            $settings['modules'] = array();
        }

        // Get settings from Settings class
        $module_settings = Settings::get_field_definitions();
        $settings['modules'] = array_merge($settings['modules'], $module_settings);

        return $settings;
    }

    /**
     * Enqueue admin styles for menu editing
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

        wp_enqueue_style(
            'orbitools-menu-groups-admin',
            plugin_dir_url(__FILE__) . '../css/admin-menu-groups.css',
            array(),
            self::VERSION
        );
    }
}