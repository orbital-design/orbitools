<?php

/**
 * Plugin uninstall script.
 *
 * This file is executed when the plugin is uninstalled from WordPress.
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * Clean up plugin options and data on uninstall.
 *
 * @return void
 */
function orbitools_uninstall_cleanup(): void
{
    // Remove plugin options
    delete_option('orbitools_settings');
    delete_option('orbitools_version');
    
    // Remove any transients
    delete_transient('orbitools_cache');
    
    // Clear any scheduled hooks
    wp_clear_scheduled_hook('orbitools_daily_cleanup');
    
    // Remove user meta (if any)
    delete_metadata('user', 0, 'orbitools_user_preferences', '', true);
    
    // Flush rewrite rules
    flush_rewrite_rules();
}

orbitools_uninstall_cleanup();