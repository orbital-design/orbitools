<?php

/**
 * Menu Groups Module
 *
 * Main coordinator class for the Menu Groups module. This class acts as
 * the primary entry point and orchestrates the various components of the module.
 *
 * @package    Orbitools
 * @subpackage Modules/Menu_Groups
 * @since      1.0.0
 */

namespace Orbitools\Modules\Menu_Groups;

use Orbitools\Modules\Menu_Groups\Admin\Admin;
use Orbitools\Modules\Menu_Groups\Admin\Settings;
use Orbitools\Modules\Menu_Groups\Core\Group_Manager;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Menu Groups Module Class
 *
 * Coordinates all aspects of the Menu Groups functionality by managing
 * the interaction between admin and core components.
 *
 * @since 1.0.0
 */
class Menu_Groups
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
     * Admin handler instance
     *
     * @since 1.0.0
     * @var Admin
     */
    private $admin;

    /**
     * Group manager instance
     *
     * @since 1.0.0
     * @var Group_Manager
     */
    private $group_manager;

    /**
     * Whether the module has been initialized
     *
     * @since 1.0.0
     * @var bool
     */
    private static $initialized = false;

    /**
     * Initialize the Menu Groups module
     *
     * Sets up the module by initializing admin functionality and,
     * if the module is enabled, the core components.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        // Prevent multiple initialization
        if (self::$initialized) {
            return;
        }

        // Always initialize admin functionality for module registration
        $this->admin = new Admin();
        
        // Initialize Settings class for AJAX handlers
        Settings::init();

        // Always initialize group manager for functionality
        $this->group_manager = new Group_Manager();

        // Always setup admin menu hooks for configuration
        $this->setup_admin_hooks();

        // Only initialize frontend functionality if module is enabled
        if ($this->admin->is_module_enabled()) {
            $this->init_frontend_functionality();
        }

        self::$initialized = true;
    }

    /**
     * Initialize frontend module functionality
     *
     * Sets up frontend integration when the module is enabled.
     *
     * @since 1.0.0
     */
    private function init_frontend_functionality(): void
    {
        // Set up additional hooks
        $this->setup_hooks();
    }

    /**
     * Set up admin hooks for menu editor
     *
     * @since 1.0.0
     */
    private function setup_admin_hooks(): void
    {
        // Add custom meta box for group headings
        add_action('admin_init', array($this->group_manager, 'add_group_meta_box'));
        add_action('wp_update_nav_menu_item', array($this->group_manager, 'save_group_fields'), 10, 3);
        
        // Setup group menu items (for type label)
        add_filter('wp_setup_nav_menu_item', array($this->group_manager, 'setup_group_menu_item'));
    }

    /**
     * Set up WordPress hooks for frontend
     *
     * @since 1.0.0
     */
    private function setup_hooks(): void
    {
        // Hook into WordPress menu system for frontend processing
        add_filter('wp_nav_menu_objects', array($this->group_manager, 'process_menu_groups'), 10, 2);
    }

    /**
     * Get the group manager instance
     *
     * @since 1.0.0
     * @return Group_Manager|null Group manager instance or null if not initialized.
     */
    public function get_group_manager(): ?Group_Manager
    {
        return $this->group_manager;
    }

    /**
     * Get the admin handler instance
     *
     * @since 1.0.0
     * @return Admin Admin instance.
     */
    public function get_admin(): Admin
    {
        return $this->admin;
    }

    /**
     * Check if the module is fully initialized
     *
     * @since 1.0.0
     * @return bool True if core functionality is loaded, false otherwise.
     */
    public function is_fully_initialized(): bool
    {
        return $this->group_manager !== null;
    }
}