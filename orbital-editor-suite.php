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
                'status'   => 'Module Controls',
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

    // Initialize main OptionsKit (LEGACY - will be replaced)
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


    // Debug: Check what options are saved (both OptionsKit and new framework)
    $optionskit_settings = get_option('orbital_editor_suite_settings', array());
    $framework_settings = get_option('orbital_editor_suite_new', array());
    error_log('OptionsKit settings: ' . print_r($optionskit_settings, true));
    error_log('Framework settings: ' . print_r($framework_settings, true));
    
    // Check both sources for Typography Presets setting (new framework takes precedence)
    $typography_enabled = false;
    if (!empty($framework_settings['typography_presets_enabled'])) {
        $typography_enabled = $framework_settings['typography_presets_enabled'];
    } elseif (!empty($optionskit_settings['typography_presets_enabled'])) {
        $typography_enabled = $optionskit_settings['typography_presets_enabled'];
    }
    
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
 * REAL PLUGIN ADMIN: Orbital Editor Suite Main Settings
 * 
 * This defines the main plugin admin page using our new Orbital Admin Framework.
 * This will replace the OptionsKit implementation above.
 */

// Define admin structure for main plugin page
add_filter('orbital_editor_suite_new_admin_structure', function($structure) {
    return array(
        'dashboard' => array(
            'title' => 'Dashboard',
            'display_mode' => 'cards',
            'sections' => array(
                'status'   => 'Module Controls',
            ),
        ),
        'modules' => array(
            'title' => 'Modules',
            'display_mode' => 'tabs',
            'sections' => array(
                // Module sections are registered by individual modules via hooks
            ),
        ),
        'settings' => array(
            'title' => 'Settings',
            'display_mode' => 'tabs',
            'sections' => array(
                'general'     => 'General Settings',
                'performance' => 'Performance',
                'cleanup'     => 'Data Cleanup',
            ),
        ),
        'updates' => array(
            'title' => 'Updates',
            'display_mode' => 'cards',
            'sections' => array(
                'version' => 'Version Information',
                'auto'    => 'Automatic Updates',
            ),
        ),
    );
});

// Define settings for main plugin page
add_filter('orbital_editor_suite_new_settings', function($settings) {
    return array(
        'dashboard' => array(
            // Active modules display (dynamically generated)
            array(
                'id'      => 'active_modules_count',
                'name'    => 'Active Modules',
                'type'    => 'html',
                'std'     => orbital_get_active_modules_html(),
                'section' => 'status',
            ),
            
            // Typography Presets module enable/disable
            array(
                'id'      => 'typography_presets_enabled',
                'name'    => 'Typography Presets',
                'desc'    => 'Enable typography presets module for advanced text styling options.',
                'type'    => 'checkbox',
                'std'     => '1',
                'section' => 'status',
            ),
        ),
        
        'modules' => array(
            // Module settings are registered by individual modules via hooks
        ),
        
        'settings' => array(
            // Debug and development settings
            array(
                'id'      => 'debug_mode',
                'name'    => 'Debug Mode',
                'desc'    => 'Enable debug logging for troubleshooting issues.',
                'type'    => 'checkbox',
                'std'     => false,
                'section' => 'general',
            ),
            
            // Performance optimization settings
            array(
                'id'      => 'cache_css',
                'name'    => 'Cache Generated CSS',
                'desc'    => 'Cache CSS output for better performance.',
                'type'    => 'checkbox',
                'std'     => true,
                'section' => 'performance',
            ),
            
            // Data cleanup and maintenance settings
            array(
                'id'      => 'reset_on_deactivation',
                'name'    => 'Reset Data on Deactivation',
                'desc'    => 'Remove all plugin data when deactivating (cannot be undone).',
                'type'    => 'checkbox',
                'std'     => false,
                'section' => 'cleanup',
            ),
        ),
        
        'updates' => array(
            // Current version display
            array(
                'id'      => 'current_version',
                'name'    => 'Current Version',
                'type'    => 'html',
                'std'     => '<p>Version: ' . ORBITAL_EDITOR_SUITE_VERSION . '</p>',
                'section' => 'version',
            ),
            
            // Automatic updates setting
            array(
                'id'      => 'auto_updates',
                'name'    => 'Automatic Updates',
                'desc'    => 'Enable automatic updates for this plugin.',
                'type'    => 'checkbox',
                'std'     => false,
                'section' => 'auto',
            ),
            
            // Update channel selection
            array(
                'id'      => 'update_channel',
                'name'    => 'Update Channel',
                'desc'    => 'Choose which updates to receive.',
                'type'    => 'select',
                'options' => array(
                    'stable' => 'Stable releases only',
                    'beta'   => 'Include beta releases',
                ),
                'std'     => 'stable',
                'section' => 'auto',
            ),
        ),
    );
});

/**
 * DEMO: Orbital Admin Framework Test Page
 * 
 * This creates a demo admin page to showcase the new framework.
 * 
 * NOTE: This demo intentionally includes a duplicate field ID to demonstrate
 * the validation system. You should see an admin error notice when viewing
 * the demo page that says "Duplicate field IDs detected: demo_enabled"
 * 
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
    
    // NEW: Initialize Orbital Admin Framework for main plugin settings
    $orbital_admin = orbital_admin_framework('orbital-editor-suite-new');
    $orbital_admin->set_page_title('Orbital Editor Suite (New)');
    $orbital_admin->set_page_description('Advanced WordPress editor enhancements and typography tools.');
    
    // Configure menu
    $orbital_admin->set_menu_config(array(
        'parent'     => 'options-general.php',
        'page_title' => 'Orbital Editor Suite (New)',
        'menu_title' => 'Orbital Editor Suite (New)',
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
                'name'    => 'Single Checkbox Example',
                'desc'    => 'This demonstrates a single checkbox field.',
                'type'    => 'checkbox',
                'std'     => true,
                'section' => 'fields',
            ),
            array(
                'id'      => 'select_sample',
                'name'    => 'Single Select Example',
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
                'id'      => 'multiselect_sample',
                'name'    => 'Multi-Select Example',
                'desc'    => 'This shows a multiple selection field. Hold Ctrl/Cmd to select multiple options.',
                'type'    => 'select',
                'multiple'=> true,
                'options' => array(
                    'red'    => 'Red Color',
                    'green'  => 'Green Color',
                    'blue'   => 'Blue Color',
                    'yellow' => 'Yellow Color',
                    'purple' => 'Purple Color',
                    'orange' => 'Orange Color',
                ),
                'std'     => array( 'red', 'blue' ),
                'section' => 'fields',
            ),
            array(
                'id'      => 'textarea_sample',
                'name'    => 'Textarea Example',
                'desc'    => 'This demonstrates a textarea field.',
                'type'    => 'textarea',
                'std'     => 'This is sample textarea content.',
                'rows'    => 6,
                'section' => 'fields',
            ),
            array(
                'id'      => 'number_sample',
                'name'    => 'Number Field Example',
                'desc'    => 'This shows a number input field with min/max values.',
                'type'    => 'number',
                'std'     => 50,
                'min'     => 1,
                'max'     => 100,
                'step'    => 1,
                'section' => 'fields',
            ),
            array(
                'id'      => 'multicheckbox_sample',
                'name'    => 'Multi-Checkbox Example',
                'desc'    => 'Select multiple options from this list.',
                'type'    => 'checkbox',
                'options' => array(
                    'red'    => 'Red',
                    'green'  => 'Green',
                    'blue'   => 'Blue',
                    'yellow' => 'Yellow',
                    'purple' => 'Purple',
                ),
                'std'     => array( 'red', 'blue' ),
                'section' => 'fields',
            ),
            array(
                'id'      => 'radio_sample',
                'name'    => 'Single Radio Example',
                'desc'    => 'This demonstrates a single radio button field.',
                'type'    => 'radio',
                'std'     => true,
                'section' => 'fields',
            ),
            array(
                'id'      => 'multiradio_sample',
                'name'    => 'Multi-Radio Example',
                'desc'    => 'Choose one option from this radio button group.',
                'type'    => 'radio',
                'options' => array(
                    'small'  => 'Small Size',
                    'medium' => 'Medium Size',
                    'large'  => 'Large Size',
                    'xlarge' => 'Extra Large Size',
                ),
                'std'     => 'medium',
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
            array(
                'id'      => 'custom_styled_text',
                'name'    => 'Custom Styled Text Field',
                'desc'    => 'This text field has custom CSS classes applied.',
                'type'    => 'text',
                'class'   => 'custom-styling highlight-field',
                'std'     => 'Text with custom classes',
                'section' => 'samples',
            ),
            array(
                'id'      => 'special_checkbox',
                'name'    => 'Special Styled Checkbox',
                'desc'    => 'This checkbox has custom styling classes.',
                'type'    => 'checkbox',
                'class'   => array( 'premium-field', 'highlight-border' ),
                'std'     => true,
                'section' => 'samples',
            ),
            array(
                'id'         => 'required_text_field',
                'name'       => 'Required Text Field',
                'desc'       => 'This field is required and demonstrates accessibility features.',
                'type'       => 'text',
                'required'   => true,
                'placeholder'=> 'Enter required text here...',
                'std'        => '',
                'section'    => 'samples',
            ),
            array(
                'id'       => 'custom_template_checkbox',
                'name'     => 'Custom Template Checkbox',
                'desc'     => 'This checkbox uses a completely custom template file with custom styling.',
                'type'     => 'checkbox',
                'template' => 'custom-templates/custom-checkbox.php',
                'std'      => false,
                'section'  => 'samples',
            ),
            array(
                'id'       => 'different_location_checkbox',
                'name'     => 'Different Location Template',
                'desc'     => 'This checkbox uses a template from a different directory (should be red/orange).',
                'type'     => 'checkbox',
                'template' => 'my-custom-location/special-checkbox.php',
                'std'      => false,
                'section'  => 'samples',
            ),
            array(
                'id'       => 'absolute_path_checkbox',
                'name'     => 'Absolute Path Template',
                'desc'     => 'This checkbox uses an absolute path to demonstrate cross-plugin template usage.',
                'type'     => 'checkbox',
                'template' => WP_CONTENT_DIR . '/plugins/orbital-editor-suite/my-custom-location/special-checkbox.php',
                'std'      => false,
                'section'  => 'samples',
            ),
            array(
                'id'      => 'accessible_radio_group',
                'name'    => 'Accessible Radio Group',
                'desc'    => 'This radio group demonstrates proper semantic structure with fieldset and legend.',
                'type'    => 'radio',
                'required'=> true,
                'options' => array(
                    'option_a' => 'Option A',
                    'option_b' => 'Option B', 
                    'option_c' => 'Option C',
                ),
                'std'     => '',
                'section' => 'samples',
            ),
            // INTENTIONAL DUPLICATE ID for demonstration - this will trigger an error!
            array(
                'id'      => 'demo_enabled',  // ← DUPLICATE! Same as checkbox above
                'name'    => 'Duplicate ID Demo',
                'desc'    => 'This field intentionally has the same ID as "Enable Demo Mode" to show the validation error.',
                'type'    => 'text',
                'std'     => 'This demonstrates duplicate ID detection',
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
    
    // Check both OptionsKit and new framework settings
    $optionskit_settings = get_option('orbital_editor_suite_settings', array());
    $framework_settings = get_option('orbital_editor_suite_new', array());
    
    // Check Typography Presets module (new framework takes precedence)
    $typography_enabled = false;
    if (!empty($framework_settings['typography_presets_enabled'])) {
        $typography_enabled = $framework_settings['typography_presets_enabled'];
    } elseif (!empty($optionskit_settings['typography_presets_enabled'])) {
        $typography_enabled = $optionskit_settings['typography_presets_enabled'];
    }
    
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