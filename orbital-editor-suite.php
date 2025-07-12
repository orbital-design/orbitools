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