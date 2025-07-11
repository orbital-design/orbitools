<?php
/**
 * The main plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * @package    Orbital_Editor_Suite
 * @subpackage Orbital_Editor_Suite/includes
 */

namespace Orbital\Editor_Suite;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * The main plugin class.
 *
 * This is the core plugin class that ties together all functionality.
 */
class Plugin {

    /**
     * The loader that's responsible for maintaining and registering all hooks.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     */
    public function __construct() {
        if (defined('ORBITAL_EDITOR_SUITE_VERSION')) {
            $this->version = ORBITAL_EDITOR_SUITE_VERSION;
        } else {
            $this->version = '1.0.0';
        }
        $this->plugin_name = 'orbital-editor-suite';

        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    /**
     * Load the required dependencies for this plugin.
     */
    private function load_dependencies() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-loader.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/admin/class-admin.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/updater/class-github-updater.php';
        
        // Load modules
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/modules/typography-presets/class-typography-presets.php';

        $this->loader = new Loader();
    }

    /**
     * Register all of the hooks related to the admin area functionality.
     */
    private function define_admin_hooks() {
        $plugin_admin = new Admin\Admin($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_admin_menu');
    }

    /**
     * Register all of the hooks related to the public-facing functionality.
     */
    private function define_public_hooks() {
        // Public hooks are now handled by individual modules
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     */
    public function run() {
        $this->loader->run();
        
        // Initialize updater for admin only
        if (is_admin()) {
            new Updater\GitHub_Updater(
                plugin_dir_path(dirname(__FILE__)) . $this->plugin_name . '.php',
                $this->version
            );
        }
        
        // Initialize modules
        $this->init_modules();
    }
    
    /**
     * Initialize plugin modules.
     */
    private function init_modules() {
        // Initialize Typography Presets module
        new Modules\Typography_Presets\Typography_Presets();
        
        // Load examples (only in admin and for development)
        if (is_admin()) {
            $this->load_examples();
        }
    }
    
    /**
     * Load admin interface examples for development.
     */
    private function load_examples() {
        $examples_dir = plugin_dir_path(dirname(__FILE__)) . 'examples/';
        
        if (file_exists($examples_dir . 'simplified-admin-example.php')) {
            require_once $examples_dir . 'simplified-admin-example.php';
        }
        
        if (file_exists($examples_dir . 'actual-wp-options-kit-example.php')) {
            require_once $examples_dir . 'actual-wp-options-kit-example.php';
        }
        
        // Load debug file for Vue.js testing
        $debug_file = plugin_dir_path(dirname(__FILE__)) . 'debug-vue-admin.php';
        if (file_exists($debug_file)) {
            require_once $debug_file;
        }
        
        // Load debug status page
        $debug_status = plugin_dir_path(dirname(__FILE__)) . 'debug-status.php';
        if (file_exists($debug_status)) {
            require_once $debug_status;
        }
        
        // Auto-enable Typography Presets module
        $auto_enable = plugin_dir_path(dirname(__FILE__)) . 'auto-enable-typography.php';
        if (file_exists($auto_enable)) {
            require_once $auto_enable;
        }
        
        // Force enable debug
        $force_enable = plugin_dir_path(dirname(__FILE__)) . 'force-enable-debug.php';
        if (file_exists($force_enable)) {
            require_once $force_enable;
        }
        
        // Simple test
        $simple_test = plugin_dir_path(dirname(__FILE__)) . 'simple-test.php';
        if (file_exists($simple_test)) {
            require_once $simple_test;
        }
    }

    /**
     * The name of the plugin used to uniquely identify it.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks.
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }
}