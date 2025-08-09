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

use Orbitools\Core\Abstracts\Module_Base;
use Orbitools\Modules\Menu_Groups\Admin\Admin;
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
class Menu_Groups extends Module_Base
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
     * Group manager instance
     *
     * @since 1.0.0
     * @var Group_Manager
     */
    private $group_manager;


    /**
     * Initialize the Menu Groups module
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
        return 'menu-groups';
    }

    /**
     * Get the module's display name
     * 
     * @return string
     */
    public function get_name(): string
    {
        return __('Menu Groups', 'orbitools');
    }

    /**
     * Get the module's description
     * 
     * @return string
     */
    public function get_description(): string
    {
        return __('Organize navigation menu items into collapsible groups with headings.', 'orbitools');
    }

    /**
     * Get module's default settings
     * 
     * @return array
     */
    public function get_default_settings(): array
    {
        return [
            'menu-groups_enabled' => true,
            'menu-groups_style' => 'default',
            'menu-groups_collapsible' => true,
            'menu-groups_custom_css' => ''
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
        
        // Initialize core group manager
        $this->group_manager = new Group_Manager();

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
        // Add custom meta box for group headings
        add_action('admin_init', array($this->group_manager, 'add_group_meta_box'));
        
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