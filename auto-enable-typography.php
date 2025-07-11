<?php
/**
 * Auto-enable Typography Presets Module
 * 
 * This file automatically enables the Typography Presets module if it's not already enabled
 */

add_action('admin_init', 'auto_enable_typography_presets_module');

function auto_enable_typography_presets_module() {
    // Only run in admin
    if (!is_admin()) {
        return;
    }
    
    // Get current options
    $options = get_option('orbital_editor_suite_options', array());
    $settings = isset($options['settings']) ? $options['settings'] : array();
    $enabled_modules = isset($settings['enabled_modules']) ? $settings['enabled_modules'] : array();
    
    // Check if Typography Presets is already enabled
    if (in_array('typography-presets', $enabled_modules)) {
        return; // Already enabled
    }
    
    // Auto-enable Typography Presets module
    $enabled_modules[] = 'typography-presets';
    $settings['enabled_modules'] = $enabled_modules;
    $options['settings'] = $settings;
    
    // Update options
    update_option('orbital_editor_suite_options', $options);
    
    // Add admin notice
    add_action('admin_notices', function() {
        echo '<div class="notice notice-success is-dismissible">';
        echo '<p><strong>Orbital Editor Suite:</strong> Typography Presets module has been automatically enabled!</p>';
        echo '</div>';
    });
}
?>