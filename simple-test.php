<?php
/**
 * Simple Test
 * 
 * Basic test to see if our plugin files are loading
 */

// Add a simple admin notice
add_action('admin_notices', function() {
    echo '<div class="notice notice-warning is-dismissible">';
    echo '<p><strong>SIMPLE TEST:</strong> This file is being loaded! Plugin is working.</p>';
    echo '</div>';
});

// Add a menu item that should always show
add_action('admin_menu', function() {
    add_submenu_page(
        'orbital-editor-suite',
        'SIMPLE TEST',
        'SIMPLE TEST',
        'manage_options',
        'simple-test-page',
        function() {
            echo '<div class="wrap">';
            echo '<h1>Simple Test Page</h1>';
            echo '<p>If you can see this page, the plugin is loading files correctly.</p>';
            echo '<p>Plugin Path: ' . ORBITAL_EDITOR_SUITE_PATH . '</p>';
            echo '<p>Plugin URL: ' . ORBITAL_EDITOR_SUITE_URL . '</p>';
            
            // Check if main plugin class exists
            echo '<h2>Class Check</h2>';
            echo '<p>Main Plugin Class: ' . (class_exists('\Orbital\Editor_Suite\Plugin') ? 'EXISTS' : 'NOT FOUND') . '</p>';
            
            // Check options
            $options = get_option('orbital_editor_suite_options', array());
            echo '<h2>Options</h2>';
            echo '<pre>' . print_r($options, true) . '</pre>';
            
            echo '</div>';
        }
    );
});
?>