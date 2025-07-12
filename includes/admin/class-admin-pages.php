<?php
/**
 * Admin pages functionality using WP Options Kit.
 *
 * @package    Orbital_Editor_Suite
 * @subpackage Orbital_Editor_Suite/includes/admin
 */

namespace Orbital\Editor_Suite\Admin;

use TDP\OptionsKit;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin pages functionality using WP Options Kit.
 *
 * All admin pages are created using OptionsKit, no custom admin pages.
 */
class Admin_Pages
{
    /**
     * The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     */
    private $version;

    /**
     * Flag to prevent multiple initializations.
     */
    private static $initialized = false;

    /**
     * Initialize the class and set its properties.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Initialize OptionsKit admin pages.
     * 
     * Note: This should be called early (during plugins_loaded) not during admin_menu.
     */
    public function init()
    {
        // Prevent multiple initializations
        if (self::$initialized) {
            error_log('Admin_Pages::init() already initialized - skipping');
            return;
        }
        
        self::$initialized = true;
        error_log('Admin_Pages::init() called - first time');
        
        // Setup main dashboard OptionsKit configuration
        $this->setup_main_optionskit_configuration();
        
        // Initialize main dashboard OptionsKit
        $main_options_kit = new OptionsKit('orbital-editor-suite');
        $main_options_kit->set_page_title('Orbital Editor Suite');
        error_log('Main OptionsKit initialized');

        // Setup updates OptionsKit configuration  
        $this->setup_updates_optionskit_configuration();
        
        // Initialize updates OptionsKit
        $updates_options_kit = new OptionsKit('orbital-editor-suite-updates');
        $updates_options_kit->set_page_title('Updates');
        error_log('Updates OptionsKit initialized');

        // Allow modules to register their own OptionsKit admin pages
        do_action('orbital_editor_suite_admin_pages');
    }

    /**
     * Setup main dashboard OptionsKit configuration.
     */
    private function setup_main_optionskit_configuration() {
        add_filter('orbital_editor_suite_menu', [$this, 'configure_main_optionskit_menu']);
        add_filter('orbital_editor_suite_settings_tabs', [$this, 'register_main_optionskit_tabs']);
        add_filter('orbital_editor_suite_registered_settings_sections', [$this, 'register_main_optionskit_sections']);
        add_filter('orbital_editor_suite_registered_settings', [$this, 'register_main_optionskit_fields']);
    }

    /**
     * Setup updates OptionsKit configuration.
     */
    private function setup_updates_optionskit_configuration() {
        add_filter('orbital_editor_suite_updates_menu', [$this, 'configure_updates_optionskit_menu']);
        add_filter('orbital_editor_suite_updates_settings_tabs', [$this, 'register_updates_optionskit_tabs']);
        add_filter('orbital_editor_suite_updates_registered_settings_sections', [$this, 'register_updates_optionskit_sections']);
        add_filter('orbital_editor_suite_updates_registered_settings', [$this, 'register_updates_optionskit_fields']);
    }

    /**
     * Configure main OptionsKit menu.
     */
    public function configure_main_optionskit_menu($menu) {
        $menu['parent'] = null; // Top level menu
        $menu['page_title'] = 'Orbital Editor Suite';
        $menu['menu_title'] = 'Orbital Editor Suite';
        $menu['capability'] = 'manage_options';
        
        return $menu;
    }

    /**
     * Configure updates OptionsKit menu.
     */
    public function configure_updates_optionskit_menu($menu) {
        $menu['parent'] = 'orbital-editor-suite-settings'; // Submenu under main
        $menu['page_title'] = 'Updates';
        $menu['menu_title'] = 'Updates';
        $menu['capability'] = 'manage_options';
        
        return $menu;
    }

    /**
     * Register main OptionsKit tabs.
     */
    public function register_main_optionskit_tabs($tabs) {
        $tabs['dashboard'] = [
            'title' => 'Dashboard',
            'description' => 'Overview and quick access to plugin features.'
        ];

        $tabs['settings'] = [
            'title' => 'Settings', 
            'description' => 'Global plugin settings and configuration.'
        ];

        return $tabs;
    }

    /**
     * Register updates OptionsKit tabs.
     */
    public function register_updates_optionskit_tabs($tabs) {
        $tabs['updates'] = [
            'title' => 'Updates',
            'description' => 'Manage plugin updates and version control.'
        ];

        return $tabs;
    }

    /**
     * Register main OptionsKit sections.
     */
    public function register_main_optionskit_sections($sections) {
        $sections['dashboard']['overview'] = [
            'title' => 'Plugin Overview',
            'description' => 'Quick overview of your Orbital Editor Suite configuration.',
            'tab' => 'dashboard'
        ];

        $sections['settings']['general'] = [
            'title' => 'General Settings',
            'description' => 'Global settings that apply to all modules.',
            'tab' => 'settings'
        ];

        return $sections;
    }

    /**
     * Register updates OptionsKit sections.
     */
    public function register_updates_optionskit_sections($sections) {
        $sections['updates']['version'] = [
            'title' => 'Version Information',
            'description' => 'Current version and update settings.',
            'tab' => 'updates'
        ];

        return $sections;
    }

    /**
     * Register main OptionsKit fields.
     */
    public function register_main_optionskit_fields($fields) {
        $fields['plugin_status'] = [
            'tab' => 'dashboard',
            'section' => 'overview',
            'id' => 'plugin_status',
            'title' => 'Plugin Status',
            'description' => 'Current status of Orbital Editor Suite.',
            'type' => 'html',
            'std' => '<p><strong>Status:</strong> Active</p><p><strong>Version:</strong> ' . ORBITAL_EDITOR_SUITE_VERSION . '</p>'
        ];

        $fields['enabled_modules'] = [
            'tab' => 'settings',
            'section' => 'general',
            'id' => 'enabled_modules',
            'title' => 'Enabled Modules',
            'description' => 'Select which modules to enable.',
            'type' => 'multicheck',
            'options' => [
                'typography-presets' => 'Typography Presets'
            ],
            'default' => ['typography-presets']
        ];

        return $fields;
    }

    /**
     * Register updates OptionsKit fields.
     */
    public function register_updates_optionskit_fields($fields) {
        $fields['current_version'] = [
            'tab' => 'updates',
            'section' => 'version',
            'id' => 'current_version',
            'title' => 'Current Version',
            'description' => 'Currently installed version.',
            'type' => 'html',
            'std' => '<p>Version: ' . ORBITAL_EDITOR_SUITE_VERSION . '</p>'
        ];

        $fields['auto_updates'] = [
            'tab' => 'updates',
            'section' => 'version',
            'id' => 'auto_updates',
            'title' => 'Auto Updates',
            'description' => 'Enable automatic updates for this plugin.',
            'type' => 'checkbox',
            'default' => false
        ];

        return $fields;
    }
}