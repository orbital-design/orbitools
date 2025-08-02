<?php
/**
 * Debug script for Column Widths Controls
 * 
 * This file helps debug why column widths controls aren't appearing
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    echo "Debugging Column Widths Controls\n";
    echo "===============================\n\n";
    
    // Simulate WordPress environment for debugging
    require_once dirname(__FILE__) . '/../../../wp-config.php';
}

use Orbitools\Modules\Column_Widths_Controls\Admin\Settings_Helper;

/**
 * Debug Column Widths Controls functionality
 */
function debug_column_widths_controls() {
    echo "Column Widths Controls Debug Report\n";
    echo "===================================\n\n";
    
    // 1. Check if module is enabled
    echo "1. Module Status:\n";
    echo "   - Module Enabled: " . (Settings_Helper::is_module_enabled() ? 'YES' : 'NO') . "\n";
    echo "   - CSS Output Enabled: " . (Settings_Helper::output_column_widths_css() ? 'YES' : 'NO') . "\n";
    
    // 2. Check settings from database
    echo "\n2. Settings from Database:\n";
    $settings = get_option('orbitools_settings', array());
    echo "   - orbitools_settings exists: " . (empty($settings) ? 'NO' : 'YES') . "\n";
    echo "   - column_widths_controls_enabled: " . var_export($settings['column_widths_controls_enabled'] ?? 'NOT_SET', true) . "\n";
    echo "   - column_widths_output_css: " . var_export($settings['column_widths_output_css'] ?? 'NOT_SET', true) . "\n";
    
    // 3. Show all current settings
    echo "\n3. All Current Settings:\n";
    if (!empty($settings)) {
        foreach ($settings as $key => $value) {
            echo "   - {$key}: " . var_export($value, true) . "\n";
        }
    } else {
        echo "   No settings found in database\n";
    }
    
    // 4. Check WordPress admin URL
    echo "\n4. Admin Access:\n";
    echo "   - Admin URL: " . admin_url('options-general.php?page=orbitools') . "\n";
    echo "   - Direct Module Config: " . admin_url('options-general.php?page=orbitools&tab=modules&section=column-widths') . "\n";
    
    echo "\n5. Solution:\n";
    echo "   To enable the Column Widths Controls module:\n";
    echo "   1. Go to WordPress Admin Dashboard\n";
    echo "   2. Navigate to Settings > OrbiTools\n";
    echo "   3. Find the 'Column Widths Controls' module card\n";
    echo "   4. Toggle the switch to enable it\n";
    echo "   5. Save settings\n";
    
    echo "\n   OR use WP-CLI:\n";
    echo "   wp option patch update orbitools_settings column_widths_controls_enabled 1\n";
    
    echo "\n6. Troubleshooting:\n";
    echo "   Since the module is already enabled, check the following:\n";
    echo "   1. Open browser console when editing a post/page\n";
    echo "   2. Look for JavaScript errors from orbitools-column-widths scripts\n";
    echo "   3. Verify that orbital/column blocks have 'columnWidthControls: true' in supports\n";
    echo "   4. Check that the scripts are loading: orbitools-column-widths-attribute-registration.js\n";
    echo "   5. Check that the scripts are loading: orbitools-column-widths-editor-controls.js\n";
    echo "   6. The orbital/column block DOES have columnWidthControls support enabled\n";
    echo "   7. Try adding an orbital/column block and check the block settings panel\n";
    
    // 7. Check if scripts exist
    echo "\n7. Script Files Check:\n";
    $script_files = [
        'attribute-registration.js' => ORBITOOLS_DIR . 'modules/Column_Widths_Controls/js/attribute-registration.js',
        'editor-controls.js' => ORBITOOLS_DIR . 'modules/Column_Widths_Controls/js/editor-controls.js'
    ];
    
    foreach ($script_files as $name => $path) {
        if (file_exists($path)) {
            echo "   - {$name}: EXISTS ✓\n";
        } else {
            echo "   - {$name}: MISSING ✗\n";
        }
    }
}

// Run the debug function
debug_column_widths_controls();