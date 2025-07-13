<?php

/**
 * Ultra simple OptionsKit implementation
 */
/**
 * Current plugin version.
 */
define('ORBITAL_EDITOR_SUITE_VERSION', '1.0.0');

/**
 * Plugin directory path.
 */
define('ORBITAL_EDITOR_SUITE_PATH', plugin_dir_path(__FILE__));

/**
 * Plugin directory URL.
 */
define('ORBITAL_EDITOR_SUITE_URL', plugin_dir_url(__FILE__));

/**
 * Plugin file path.
 */
define('ORBITAL_EDITOR_SUITE_FILE', __FILE__);

add_action('plugins_loaded', function () {
    static $done = false;
    if ($done) return;
    $done = true;

    // Include Composer autoloader
    if (file_exists(ORBITAL_EDITOR_SUITE_PATH . 'vendor/autoload.php')) {
        require_once ORBITAL_EDITOR_SUITE_PATH . 'vendor/autoload.php';
    }

    // Main dashboard menu 
    add_filter('orbital_editor_suite_menu', function ($menu) {
        $menu['parent'] = 'options-general.php'; // Back to Settings submenu for now
        $menu['page_title'] = 'Orbital Editor Suite';
        $menu['menu_title'] = 'Orbital Editor Suite';
        $menu['capability'] = 'manage_options';
        return $menu;
    });

    add_filter('orbital_editor_suite_settings_tabs', function ($tabs) {
        return array(
            'dashboard' => 'Dashboard',
            'modules'   => 'Modules',
            'settings'  => 'Settings',
            'updates'   => 'Updates',
        );
    });

    add_filter('orbital_editor_suite_registered_settings_sections', function ($subsections) {
        $subsections = array(
            'dashboard' => array(
                'overview' => 'Plugin Overview',
                'status'   => 'Module Controls',
            ),
            'modules' => array(
                'settings' => 'Module Settings',
            ),
            'settings' => array(
                'general'     => 'General Settings',
                'performance' => 'Performance',
                'cleanup'     => 'Data Cleanup',
            ),
            'updates' => array(
                'version' => 'Version Information',
                'auto'    => 'Automatic Updates',
            ),
        );

        return $subsections;
    });

    add_filter('orbital_editor_suite_registered_settings', function ($settings) {
        $settings = require ORBITAL_EDITOR_SUITE_PATH . 'orbital-settings.php';
        return $settings;
    });

    // Initialize main OptionsKit
    $kit = new \TDP\OptionsKit('orbital-editor-suite');
    $kit->set_page_title('Orbital Editor Suite');
    
    // Enqueue custom admin notice styling
    add_action('admin_enqueue_scripts', function($hook) {
        if (strpos($hook, 'orbital-editor-suite') !== false) {
            wp_enqueue_style(
                'orbital-admin-notices',
                ORBITAL_EDITOR_SUITE_URL . 'assets/css/admin-notices.css',
                array(),
                ORBITAL_EDITOR_SUITE_VERSION
            );
        }
    });


    // Debug: Check what options OptionsKit is actually saving
    $all_options = get_option('orbital_editor_suite_settings', array());
    error_log('OptionsKit settings: ' . print_r($all_options, true));
    
    // Only initialize Typography Presets if enabled
    $typography_enabled = isset($all_options['typography_presets_enabled']) ? $all_options['typography_presets_enabled'] : '0';
    error_log('Typography enabled check: ' . $typography_enabled);
    
    if ($typography_enabled == '1' || $typography_enabled === 1) {
        // Initialize Typography Presets module (simplified)
        require_once ORBITAL_EDITOR_SUITE_PATH . 'includes/modules/typography-presets/class-typography-presets.php';
        new \Orbital\Editor_Suite\Modules\Typography_Presets\Typography_Presets();
        
        error_log('Typography Presets module loaded');
    } else {
        error_log('Typography Presets module disabled');
    }

    error_log('MAIN: OptionsKit and conditional modules initialized');
}, 1);

/**
 * DEMO: Orbital Admin Framework Test Page
 * 
 * This creates a demo admin page to showcase the new framework.
 * Remove this section once you're satisfied with the framework.
 */
add_action('plugins_loaded', function() {
    // Load the framework
    require_once ORBITAL_EDITOR_SUITE_PATH . 'includes/admin-framework/loader.php';
    
    // Create test admin page
    $demo_admin = orbital_admin_framework('orbital-framework-demo');
    $demo_admin->set_page_title('Framework Demo');
    $demo_admin->set_page_description('This is a demonstration of the Orbital Admin Framework with all its features.');
    
    // Configure menu
    $demo_admin->set_menu_config(array(
        'parent'     => 'tools.php',
        'page_title' => 'Orbital Framework Demo',
        'menu_title' => 'Framework Demo',
        'capability' => 'manage_options',
    ));
});

// Define admin structure for demo page (NEW UNIFIED APPROACH)
// This replaces the separate tabs, sections, and display_modes filters
add_filter('orbital_framework_demo_admin_structure', function($structure) {
    return array(
        'general' => array(
            'title' => 'General Settings',
            'display_mode' => 'tabs',
            'sections' => array(
                'basic'   => 'Basic Configuration',
                'options' => 'General Options',
            ),
        ),
        'advanced' => array(
            'title' => 'Advanced Options',
            'display_mode' => 'cards',
            'sections' => array(
                'performance' => 'Performance Settings',
                'debug'       => 'Debug Options',
            ),
        ),
        'styling' => array(
            'title' => 'Visual Settings',
            'display_mode' => 'tabs',
            'sections' => array(
                'appearance' => 'Visual Appearance',
                'layout'     => 'Layout Options',
            ),
        ),
        'testing' => array(
            'title' => 'Test Fields',
            'display_mode' => 'cards',
            'sections' => array(
                'fields'  => 'Field Types Demo',
                'samples' => 'Sample Data',
                'empty'   => 'Empty Section Demo', // This will show the "no fields" message
            ),
        ),
    );
});


// Define settings for demo page
add_filter('orbital_framework_demo_settings', function($settings) {
    return array(
        'general' => array(
            array(
                'id'      => 'demo_enabled',
                'name'    => 'Enable Demo Mode',
                'desc'    => 'Turn on demo functionality for testing.',
                'type'    => 'checkbox',
                'std'     => true,
                'section' => 'basic',
            ),
            array(
                'id'      => 'site_title',
                'name'    => 'Demo Site Title',
                'desc'    => 'Enter a custom title for demo purposes.',
                'type'    => 'text',
                'std'     => 'My Awesome Site',
                'section' => 'basic',
            ),
            array(
                'id'      => 'api_endpoint',
                'name'    => 'API Endpoint URL',
                'desc'    => 'Full URL to your API endpoint.',
                'type'    => 'text',
                'std'     => 'https://api.example.com/v1',
                'section' => 'options',
            ),
            array(
                'id'      => 'notification_email',
                'name'    => 'Notification Email',
                'desc'    => 'Email address for system notifications.',
                'type'    => 'text',
                'std'     => get_option('admin_email'),
                'section' => 'options',
            ),
            // Example: To show all fields together without sub-tabs,
            // either don't define sections for this tab, or give all fields
            // the same section name, or omit the 'section' parameter entirely
        ),
        'advanced' => array(
            array(
                'id'      => 'cache_duration',
                'name'    => 'Cache Duration',
                'desc'    => 'How long to cache data (in seconds).',
                'type'    => 'select',
                'options' => array(
                    '300'   => '5 minutes',
                    '900'   => '15 minutes', 
                    '1800'  => '30 minutes',
                    '3600'  => '1 hour',
                    '86400' => '24 hours',
                ),
                'std'     => '1800',
                'section' => 'performance',
            ),
            array(
                'id'      => 'enable_compression',
                'name'    => 'Enable Compression',
                'desc'    => 'Use gzip compression for better performance.',
                'type'    => 'checkbox',
                'std'     => true,
                'section' => 'performance',
            ),
            array(
                'id'      => 'debug_logging',
                'name'    => 'Debug Logging',
                'desc'    => 'Enable detailed logging for troubleshooting.',
                'type'    => 'checkbox',
                'std'     => false,
                'section' => 'debug',
            ),
            array(
                'id'      => 'log_level',
                'name'    => 'Log Level',
                'desc'    => 'Set the minimum log level to record.',
                'type'    => 'select',
                'options' => array(
                    'error'   => 'Errors Only',
                    'warning' => 'Warnings & Errors',
                    'info'    => 'Info, Warnings & Errors',
                    'debug'   => 'All Messages',
                ),
                'std'     => 'warning',
                'section' => 'debug',
            ),
        ),
        'styling' => array(
            array(
                'id'      => 'primary_color',
                'name'    => 'Primary Color Scheme',
                'desc'    => 'Choose the main color for your interface.',
                'type'    => 'select',
                'options' => array(
                    'blue'   => 'Professional Blue',
                    'green'  => 'Nature Green',
                    'purple' => 'Creative Purple',
                    'red'    => 'Bold Red',
                    'orange' => 'Energetic Orange',
                ),
                'std'     => 'blue',
                'section' => 'appearance',
            ),
            array(
                'id'      => 'dark_mode',
                'name'    => 'Enable Dark Mode',
                'desc'    => 'Use dark theme for better night viewing.',
                'type'    => 'checkbox',
                'std'     => false,
                'section' => 'appearance',
            ),
            array(
                'id'      => 'sidebar_position',
                'name'    => 'Sidebar Position',
                'desc'    => 'Choose where to display the sidebar.',
                'type'    => 'select',
                'options' => array(
                    'left'  => 'Left Side',
                    'right' => 'Right Side',
                    'none'  => 'No Sidebar',
                ),
                'std'     => 'right',
                'section' => 'layout',
            ),
            array(
                'id'      => 'full_width',
                'name'    => 'Full Width Layout',
                'desc'    => 'Use the entire screen width.',
                'type'    => 'checkbox',
                'std'     => false,
                'section' => 'layout',
            ),
        ),
        'testing' => array(
            array(
                'id'      => 'text_sample',
                'name'    => 'Text Field Example',
                'desc'    => 'This is a standard text input field.',
                'type'    => 'text',
                'std'     => 'Sample text value',
                'section' => 'fields',
            ),
            array(
                'id'      => 'checkbox_sample',
                'name'    => 'Checkbox Example',
                'desc'    => 'This demonstrates a checkbox field.',
                'type'    => 'checkbox',
                'std'     => true,
                'section' => 'fields',
            ),
            array(
                'id'      => 'select_sample',
                'name'    => 'Select Dropdown Example',
                'desc'    => 'This shows a dropdown selection field.',
                'type'    => 'select',
                'options' => array(
                    'option1' => 'First Option',
                    'option2' => 'Second Option',
                    'option3' => 'Third Option',
                ),
                'std'     => 'option2',
                'section' => 'fields',
            ),
            array(
                'id'      => 'sample_data',
                'name'    => 'Sample Data Field',
                'desc'    => 'This field contains sample data for testing.',
                'type'    => 'text',
                'std'     => 'Lorem ipsum dolor sit amet',
                'section' => 'samples',
            ),
        ),
    );
});

// Add custom header content for demo
add_action('orbital_framework_demo_render_header', function() {
    echo '<div style="float: right; margin-top: 10px;">';
    echo '<a href="https://docs.example.com" class="button" target="_blank">Documentation</a> ';
    echo '<a href="https://support.example.com" class="button button-primary" target="_blank">Get Support</a>';
    echo '</div>';
    echo '<div style="clear: both;"></div>';
});

// Add custom content after notices
add_action('orbital_framework_demo_after_notices', function() {
    echo '<div style="background: #e7f3ff; border: 1px solid #72aee6; border-radius: 4px; padding: 15px; margin-bottom: 20px;">';
    echo '<p><strong>🚀 Framework Demo:</strong> This page demonstrates all the features of the Orbital Admin Framework. ';
    echo 'Try switching tabs, filling out fields, and saving settings to see how everything works!</p>';
    echo '</div>';
});

// Hook into settings save for demo
add_action('orbital_framework_demo_post_save_settings', function($settings_data, $success) {
    if ($success) {
        error_log('Demo settings saved: ' . print_r($settings_data, true));
    }
}, 10, 2);

/**
 * Get HTML for active modules display
 */
function orbital_get_active_modules_html() {
    $html = '';
    
    // Check if Typography Presets module is enabled and loaded
    $all_options = get_option('orbital_editor_suite_settings', array());
    $typography_enabled = isset($all_options['typography_presets_enabled']) ? $all_options['typography_presets_enabled'] : '0';
    $typography_loaded = class_exists('\Orbital\Editor_Suite\Modules\Typography_Presets\Typography_Presets');
    
    if (($typography_enabled == '1' || $typography_enabled === 1) && $typography_loaded) {
        $html .= '<p>Typography Presets: <span style="color: green;">✓ Active</span></p>';
    } elseif (($typography_enabled == '1' || $typography_enabled === 1) && !$typography_loaded) {
        $html .= '<p>Typography Presets: <span style="color: orange;">⚠ Enabled but not loaded</span></p>';
    } else {
        $html .= '<p>Typography Presets: <span style="color: red;">✗ Disabled</span></p>';
    }
    
    return $html;
}