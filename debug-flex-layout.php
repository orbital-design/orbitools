<?php
/**
 * Debug script for Flex Layout Controls
 * 
 * This file helps debug why flex layout classes aren't appearing on the frontend
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    echo "Debugging Flex Layout Controls\n";
    echo "============================\n\n";
    
    // Simulate WordPress environment for debugging
    require_once dirname(__FILE__) . '/../../../wp-config.php';
}

use Orbitools\Modules\Flex_Layout_Controls\Admin\Settings_Helper;
use Orbitools\Modules\Flex_Layout_Controls\Core\CSS_Generator;

/**
 * Debug Flex Layout Controls functionality
 */
function debug_flex_layout_controls() {
    echo "Flex Layout Controls Debug Report\n";
    echo "=================================\n\n";
    
    // 1. Check if module is enabled
    echo "1. Module Status:\n";
    echo "   - Module Enabled: " . (Settings_Helper::is_module_enabled() ? 'YES' : 'NO') . "\n";
    echo "   - CSS Output Enabled: " . (Settings_Helper::output_flex_css() ? 'YES' : 'NO') . "\n";
    
    // 2. Check settings from database
    echo "\n2. Settings from Database:\n";
    $settings = get_option('orbitools_settings', array());
    echo "   - orbitools_settings exists: " . (empty($settings) ? 'NO' : 'YES') . "\n";
    echo "   - flex_layout_controls_enabled: " . var_export($settings['flex_layout_controls_enabled'] ?? 'NOT_SET', true) . "\n";
    echo "   - flex_output_css: " . var_export($settings['flex_output_css'] ?? 'NOT_SET', true) . "\n";
    
    // 3. Test CSS generation
    echo "\n3. CSS Generation Test:\n";
    $css_generator = new CSS_Generator();
    $generated_css = $css_generator->generate_flex_css();
    echo "   - CSS Generated: " . (!empty($generated_css) ? 'YES' : 'NO') . "\n";
    echo "   - CSS Length: " . strlen($generated_css) . " characters\n";
    
    if (!empty($generated_css)) {
        echo "   - First 200 characters of CSS:\n";
        echo "     " . substr($generated_css, 0, 200) . "...\n";
    }
    
    // 4. Check hooks and filters
    echo "\n4. WordPress Hooks:\n";
    echo "   - wp_head action for CSS output: " . (has_action('wp_head', array($css_generator, 'output_flex_css')) ? 'HOOKED' : 'NOT_HOOKED') . "\n";
    
    // 5. Check if scripts are enqueued (in admin)
    if (is_admin()) {
        echo "\n5. Block Editor Assets (Admin Only):\n";
        global $wp_scripts;
        $flex_scripts = array(
            'orbitools-flex-config',
            'orbitools-flex-icons', 
            'orbitools-flex-attribute-registration',
            'orbitools-flex-editor-controls',
            'orbitools-flex-class-application'
        );
        
        foreach ($flex_scripts as $script) {
            echo "   - $script: " . (wp_script_is($script, 'registered') ? 'REGISTERED' : 'NOT_REGISTERED') . "\n";
        }
    }
    
    // 6. Check file existence
    echo "\n6. File Existence Check:\n";
    $module_dir = ORBITOOLS_DIR . 'modules/Flex_Layout_Controls/';
    $files_to_check = array(
        'js/flex-config.js',
        'js/flex-icons.js', 
        'js/attribute-registration.js',
        'js/editor-controls.js',
        'js/class-application.js',
        'css/editor.css',
        'Core/CSS_Generator.php'
    );
    
    foreach ($files_to_check as $file) {
        $file_path = $module_dir . $file;
        echo "   - $file: " . (file_exists($file_path) ? 'EXISTS' : 'MISSING') . "\n";
    }
    
    // 7. Check transient cache
    echo "\n7. Cache Status:\n";
    global $wpdb;
    $cache_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE '_transient_orbitools_flex_css_%'");
    echo "   - Cached CSS entries: $cache_count\n";
    
    echo "\n=================================\n";
    echo "Debug report complete.\n";
}

// Run debug if called directly
if (!defined('ABSPATH') || (defined('WP_CLI') && WP_CLI)) {
    debug_flex_layout_controls();
}

// Add admin page for debugging if needed
if (defined('ABSPATH') && is_admin()) {
    add_action('admin_menu', function() {
        add_submenu_page(
            'tools.php',
            'Debug Flex Layout',
            'Debug Flex Layout', 
            'manage_options',
            'debug-flex-layout',
            function() {
                echo '<div class="wrap"><h1>Flex Layout Debug</h1><pre>';
                debug_flex_layout_controls();
                echo '</pre></div>';
            }
        );
    });
}