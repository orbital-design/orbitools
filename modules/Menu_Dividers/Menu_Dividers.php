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

use Orbitools\Abstracts\Module_Base;
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
class Menu_Dividers extends Module_Base
{
    /**
     * Module version
     */
    protected const VERSION = '1.0.0';

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
     * Sets up the module by calling the parent constructor which handles
     * the initialization logic via the Module_Base system.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        // Call parent constructor which handles initialization
        parent::__construct();
    }

    /**
     * Get the module's unique slug
     * 
     * @return string
     */
    public function get_slug(): string
    {
        return 'menu-dividers';
    }

    /**
     * Get the module's display name
     * 
     * @return string
     */
    public function get_name(): string
    {
        return __('Menu Dividers', 'orbitools');
    }

    /**
     * Get the module's description
     * 
     * @return string
     */
    public function get_description(): string
    {
        return __('Add visual dividers and separators to navigation menus for improved organization.', 'orbitools');
    }

    /**
     * Get module's default settings
     * 
     * @return array
     */
    public function get_default_settings(): array
    {
        return [
            'menu-dividers_enabled' => true,
            'menu-dividers_style' => 'default',
            'menu-dividers_custom_css' => ''
        ];
    }

    /**
     * Initialize the module
     * Called by Module_Base when module should be initialized
     * 
     * @return void
     */
    public function init(): void
    {
        // Always initialize admin functionality for module registration
        $this->admin = new Admin();
        
        // Initialize core divider manager
        $this->divider_manager = new Divider_Manager();

        // Initialize frontend functionality
        $this->init_frontend_functionality();

        // Set up admin hooks
        $this->setup_admin_hooks();
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