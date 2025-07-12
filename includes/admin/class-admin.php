<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @package    Orbital_Editor_Suite
 * @subpackage Orbital_Editor_Suite/includes/admin
 */

namespace Orbital\Editor_Suite\Admin;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and hooks for enqueuing
 * the admin-specific stylesheet and JavaScript.
 */
class Admin {

    /**
     * The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the admin area.
     */
    public function enqueue_styles($hook) {
        // Only load on our admin pages
        if (!$this->is_orbital_admin_page($hook)) {
            return;
        }

        wp_enqueue_style(
            $this->plugin_name,
            plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/css/admin.css',
            array(),
            $this->version,
            'all'
        );
    }

    /**
     * Register the JavaScript for the admin area.
     */
    public function enqueue_scripts($hook) {
        // Only load on our admin pages
        if (!$this->is_orbital_admin_page($hook)) {
            return;
        }

        // Load Vue.js 3.0 first (centralized loading)
        wp_enqueue_script(
            'vue-js',
            'https://unpkg.com/vue@3/dist/vue.global.js',
            array(),
            '3.0.0',
            true
        );

        // Load main admin script with Vue dependency
        wp_enqueue_script(
            $this->plugin_name,
            plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/js/admin.js',
            array('vue-js'),
            $this->version,
            true
        );

        // Load page-specific Vue apps based on current page
        $this->load_page_specific_assets($hook);

        // Localize script for AJAX
        wp_localize_script(
            $this->plugin_name,
            'orbital_editor_suite_admin',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('orbital_editor_suite_nonce'),
                'strings' => array(
                    'saving' => 'Saving...',
                    'saved' => 'Settings saved successfully!',
                    'error' => 'An error occurred while saving.'
                )
            )
        );
    }

    /**
     * Load page-specific Vue.js assets.
     */
    private function load_page_specific_assets($hook) {
        // Typography Presets Vue app
        if (strpos($hook, 'orbital-typography-vue-new') !== false) {
            wp_enqueue_script(
                'orbital-typography-presets-vue-app',
                plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/js/typography-presets-vue-app.js',
                array('vue-js'),
                $this->version,
                true
            );
            
            wp_enqueue_style(
                'orbital-typography-presets-vue-styles',
                plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/css/typography-presets-vue-styles.css',
                array(),
                $this->version
            );
        }

        // Main dashboard Vue app
        if (strpos($hook, 'orbital-editor-suite') !== false && strpos($hook, 'updates') === false) {
            wp_enqueue_script(
                'orbital-main-vue-app',
                plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/js/main-vue-app.js',
                array('vue-js'),
                $this->version,
                true
            );
            
            wp_enqueue_style(
                'orbital-main-vue-styles',
                plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/css/main-vue-styles.css',
                array(),
                $this->version
            );
        }

        // Updates Vue app
        if (strpos($hook, 'orbital-editor-suite-updates') !== false) {
            wp_enqueue_script(
                'orbital-updates-vue-app',
                plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/js/updates-vue-app.js',
                array('vue-js'),
                $this->version,
                true
            );
            
            wp_enqueue_style(
                'orbital-updates-vue-styles',
                plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/css/updates-vue-styles.css',
                array(),
                $this->version
            );
        }
    }

    /**
     * Add admin menu pages.
     */
    public function add_admin_menu() {
        require_once plugin_dir_path(__FILE__) . 'class-admin-pages.php';
        $admin_pages = new Admin_Pages($this->plugin_name, $this->version);
        $admin_pages->init();
    }

    /**
     * Check if we're on an Orbital admin page.
     */
    private function is_orbital_admin_page($hook) {
        return strpos($hook, 'orbital-editor-suite') !== false || 
               strpos($hook, 'orbital_editor_suite') !== false;
    }
}