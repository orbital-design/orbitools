<?php

/**
 * Flex Layout Controls CSS Enqueuer
 *
 * Enqueues static CSS file for flex layout controls.
 *
 * @package    Orbitools
 * @subpackage Modules/Flex_Layout_Controls/Core
 * @since      1.0.0
 */

namespace Orbitools\Modules\Flex_Layout_Controls\Core;

use Orbitools\Modules\Flex_Layout_Controls\Admin\Settings_Helper;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * CSS Enqueuer Class
 *
 * Handles enqueuing static CSS for flex layout controls.
 *
 * @since 1.0.0
 */
class CSS_Generator
{
    /**
     * Initialize CSS enqueuing
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        // Hook into wp_enqueue_scripts for frontend
        add_action('wp_enqueue_scripts', array($this, 'enqueue_flex_css'));
        
        // Hook into admin_enqueue_scripts for editor
        add_action('admin_enqueue_scripts', array($this, 'enqueue_flex_css'));
    }

    /**
     * Enqueue flex layout CSS inline from file with caching
     *
     * @since 1.0.0
     */
    public function enqueue_flex_css(): void
    {
        // Only enqueue if module is enabled and CSS output is enabled
        if (!Settings_Helper::is_module_enabled() || !Settings_Helper::output_flex_css()) {
            return;
        }

        // Path to the CSS file
        $css_file_path = ORBITOOLS_DIR . 'modules/Flex_Layout_Controls/css/flex-utils.css';

        // Check if the file exists
        if (!file_exists($css_file_path)) {
            return;
        }

        // Cache key based on file modification time
        $file_mtime = filemtime($css_file_path);
        $cache_key = 'orbitools_flex_utils_css_' . $file_mtime;
        
        // Try to get cached CSS
        $flex_css = get_transient($cache_key);
        
        if ($flex_css === false) {
            // Cache miss - read file and cache it
            $flex_css = file_get_contents($css_file_path);
            
            // Cache for 24 hours
            set_transient($cache_key, $flex_css, 24 * HOUR_IN_SECONDS);
            
            // Clean up old cache entries
            $this->cleanup_old_cache();
        }

        // Register and enqueue a dummy stylesheet
        wp_register_style('orbitools-flex-utils', false);
        wp_enqueue_style('orbitools-flex-utils');

        // Add the CSS inline
        wp_add_inline_style('orbitools-flex-utils', $flex_css);
    }

    /**
     * Clean up old cached CSS entries
     *
     * @since 1.0.0
     */
    private function cleanup_old_cache(): void
    {
        global $wpdb;
        
        // Delete old flex utils cache entries (keep only current one)
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_orbitools_flex_utils_css_%' 
             OR option_name LIKE '_transient_timeout_orbitools_flex_utils_css_%'"
        );
    }
}