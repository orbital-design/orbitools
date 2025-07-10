<?php
/**
 * Plugin Name: Typography Utility Controls
 * Description: Adds typography utility class selector to core blocks with modern admin panel
 * Version: 1.0.0
 * Author: Your Name
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('TUC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('TUC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('TUC_PLUGIN_FILE', __FILE__);

// Include modern admin panel
require_once TUC_PLUGIN_DIR . 'inc/admin-panel.php';

function enqueue_typography_utility_controls() {
    $script_url = plugin_dir_url(__FILE__) . 'typography-controls-full.js';
    
    wp_enqueue_script(
        'typography-utility-controls',
        $script_url,
        array('wp-dom-ready', 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-compose', 'wp-hooks'),
        '1.0.2'
    );
}
add_action('enqueue_block_editor_assets', 'enqueue_typography_utility_controls');


// Admin menu
function tuc_admin_menu() {
    add_menu_page(
        'Typography Utility Controls',
        'Typography Utils',
        'manage_options',
        'typography-utility-controls',
        'tuc_admin_page',
        'dashicons-editor-textcolor',
        30
    );
}
add_action('admin_menu', 'tuc_admin_menu');

function tuc_admin_page() {
    $admin_panel = new TUC_Admin_Panel();
    $admin_panel->render_admin_page();
}

// Plugin activation
function tuc_plugin_activate() {
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
    
    update_option('tuc_options', $default_options);
}
register_activation_hook(__FILE__, 'tuc_plugin_activate');