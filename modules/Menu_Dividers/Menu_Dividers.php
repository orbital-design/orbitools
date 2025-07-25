<?php

/**
 * Menu Dividers Module
 *
 * Main coordinator class for the Menu Dividers module. This class acts as
 * the primary entry point and orchestrates the various components of the module.
 *
 * @package    Orbitools
 * @subpackage Modules/Menu_Dividers
 * @since      1.0.0
 */

namespace Orbitools\Modules\Menu_Dividers;

use Orbitools\Modules\Menu_Dividers\Admin\Admin;
use Orbitools\Modules\Menu_Dividers\Core\Divider_Manager;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Menu Dividers Module Class
 *
 * Coordinates all aspects of the Menu Dividers functionality by managing
 * the interaction between admin and core components.
 *
 * @since 1.0.0
 */
class Menu_Dividers
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
     * Admin handler instance
     *
     * @since 1.0.0
     * @var Admin
     */
    private $admin;

    /**
     * Divider manager instance
     *
     * @since 1.0.0
     * @var Divider_Manager
     */
    private $divider_manager;

    /**
     * Whether the module has been initialized
     *
     * @since 1.0.0
     * @var bool
     */
    private static $initialized = false;

    /**
     * Initialize the Menu Dividers module
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

        // Always initialize divider manager for functionality
        $this->divider_manager = new Divider_Manager();

        // Always setup admin menu hooks for configuration
        $this->setup_admin_hooks();

        // Initialize frontend functionality (module is always enabled now)
        $this->init_frontend_functionality();

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
        // Add custom meta box for dividers
        add_action('admin_init', array($this->divider_manager, 'add_divider_meta_box'));
        
        // Setup divider menu items (for type label)
        add_filter('wp_setup_nav_menu_item', array($this->divider_manager, 'setup_divider_menu_item'));
    }

    /**
     * Set up WordPress hooks for frontend
     *
     * @since 1.0.0
     */
    private function setup_hooks(): void
    {
        // Hook into WordPress menu system for frontend processing
        add_filter('wp_nav_menu_objects', array($this->divider_manager, 'process_menu_dividers'), 10, 2);
    }

    /**
     * Get the divider manager instance
     *
     * @since 1.0.0
     * @return Divider_Manager|null Divider manager instance or null if not initialized.
     */
    public function get_divider_manager(): ?Divider_Manager
    {
        return $this->divider_manager;
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
        return $this->divider_manager !== null;
    }
}