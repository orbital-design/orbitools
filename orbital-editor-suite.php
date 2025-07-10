<?php
/**
 * Plugin Name: Orbital Editor Suite
 * Description: Professional suite of editor enhancements with typography utilities and modern admin panel
 * Version: 1.0.0
 * Author: Orbital
 * Plugin URI: https://orbital.com
 * Text Domain: orbital-editor-suite
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('OES_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('OES_PLUGIN_URL', plugin_dir_url(__FILE__));
define('OES_PLUGIN_FILE', __FILE__);

// Include modern admin panel
require_once OES_PLUGIN_DIR . 'inc/admin-panel.php';
require_once OES_PLUGIN_DIR . 'inc/github-updater.php';

function oes_enqueue_editor_assets() {
    $script_url = plugin_dir_url(__FILE__) . 'typography-controls-full.js';
    
    wp_enqueue_script(
        'orbital-editor-suite',
        $script_url,
        array('wp-dom-ready', 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-compose', 'wp-hooks'),
        '1.0.0'
    );
}
add_action('enqueue_block_editor_assets', 'oes_enqueue_editor_assets');


// Admin menu
function oes_admin_menu() {
    add_menu_page(
        'Orbital Editor Suite',
        'Orbital Editor',
        'manage_options',
        'orbital-editor-suite',
        'oes_admin_page',
        'dashicons-admin-customizer',
        30
    );
}
add_action('admin_menu', 'oes_admin_menu');

function oes_admin_page() {
    $admin_panel = new OES_Admin_Panel();
    $admin_panel->render_admin_page();
}

// Plugin activation
function oes_plugin_activate() {
    // Set default options
    $default_options = array(
        'enable_plugin' => true,
        'allowed_blocks' => array(
            'core/paragraph',
            'core/heading',
            'core/list',
            'core/button'
        ),
        'utility_categories' => array(
            'font_family' => true,
            'font_size' => true,
            'font_weight' => true,
            'text_color' => true,
            'text_align' => true
        )
    );
    
    update_option('oes_options', $default_options);
    
    // Clean up any old GitHub token (no longer needed for public repo)
    delete_option('oes_github_token');
}
register_activation_hook(__FILE__, 'oes_plugin_activate');

// Initialize GitHub updater
if (is_admin()) {
    $github_updater = new OES_GitHub_Updater(
        __FILE__,
        '1.0.0'  // Current version
    );
}