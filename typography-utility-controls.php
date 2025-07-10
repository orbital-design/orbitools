<?php
/**
 * Plugin Name: Typography Utility Controls
 * Description: Adds typography utility class selector to core blocks
 * Version: 1.0.0
 * Author: Your Name
 */

if (!defined('ABSPATH')) {
    exit;
}

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