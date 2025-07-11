<?php
/**
 * Force Enable Debug
 * 
 * This file forces the Typography Presets module to be enabled and shows debug info
 */

add_action('admin_init', 'force_enable_typography_debug', 1);

function force_enable_typography_debug() {
    // Force enable the module
    $options = get_option('orbital_editor_suite_options', array());
    if (!isset($options['settings'])) {
        $options['settings'] = array();
    }
    if (!isset($options['settings']['enabled_modules'])) {
        $options['settings']['enabled_modules'] = array();
    }
    
    // Force add typography-presets if not present
    if (!in_array('typography-presets', $options['settings']['enabled_modules'])) {
        $options['settings']['enabled_modules'][] = 'typography-presets';
        update_option('orbital_editor_suite_options', $options);
        
        // Add admin notice
        add_action('admin_notices', function() {
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p><strong>FORCED:</strong> Typography Presets module enabled! Options: ' . print_r(get_option('orbital_editor_suite_options'), true) . '</p>';
            echo '</div>';
        });
    }
}

// Add a simple menu item that always shows
add_action('admin_menu', 'force_debug_menu');

function force_debug_menu() {
    add_submenu_page(
        'orbital-editor-suite',
        'FORCE DEBUG',
        'FORCE DEBUG',
        'manage_options',
        'force-debug-test',
        'force_debug_page'
    );
}

function force_debug_page() {
    $options = get_option('orbital_editor_suite_options', array());
    ?>
    <div class="wrap">
        <h1>Force Debug Test</h1>
        <pre><?php print_r($options); ?></pre>
        
        <h2>Module Status</h2>
        <p>Typography Presets Enabled: <?php 
            $enabled = isset($options['settings']['enabled_modules']) && in_array('typography-presets', $options['settings']['enabled_modules']);
            echo $enabled ? 'YES' : 'NO'; 
        ?></p>
        
        <h2>Class Check</h2>
        <p>Vue Admin Class: <?php echo class_exists('\Orbital\Editor_Suite\Modules\Typography_Presets\Typography_Presets_Vue_Admin') ? 'EXISTS' : 'NOT FOUND'; ?></p>
        <p>Original Admin Class: <?php echo class_exists('\Orbital\Editor_Suite\Modules\Typography_Presets\Typography_Presets_Admin') ? 'EXISTS' : 'NOT FOUND'; ?></p>
        
        <h2>Menu Items</h2>
        <p>Check if you see these menu items in the left sidebar:</p>
        <ul>
            <li>Typography Presets (original)</li>
            <li>Typography Presets (Vue.js)</li>
            <li>FORCE DEBUG (this page)</li>
        </ul>
    </div>
    <?php
}
?>