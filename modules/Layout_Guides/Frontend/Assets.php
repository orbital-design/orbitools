<?php

/**
 * Layout Guides Frontend Assets
 *
 * Handles loading of CSS and JavaScript assets for the Layout Guides module
 * on the frontend.
 *
 * @package    Orbitools
 * @subpackage Modules/Layout_Guides/Frontend
 * @since      1.0.0
 */

namespace Orbitools\Modules\Layout_Guides\Frontend;

use Orbitools\Modules\Layout_Guides\Admin\Settings_Helper;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Frontend Assets Class
 *
 * Manages the loading of frontend assets for layout guides.
 *
 * @since 1.0.0
 */
class Assets
{
    /**
     * Initialize frontend assets
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        error_log('Layout Guides Debug - Assets constructor called');
        
        // Hook into WordPress asset loading immediately
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));

        // Add inline CSS for custom properties
        add_action('wp_head', array($this, 'add_inline_css'));
        add_action('admin_head', array($this, 'add_inline_css'));
        
        error_log('Layout Guides Debug - Assets hooks registered in constructor');
    }

    /**
     * Initialize frontend assets (kept for compatibility)
     *
     * @since 1.0.0
     */
    public function init()
    {
        // This method is kept for compatibility but hooks are now registered in constructor
        error_log('Layout Guides Debug - Assets init() called (hooks already registered)');
    }

    /**
     * Enqueue frontend assets
     *
     * @since 1.0.0
     */
    public function enqueue_frontend_assets()
    {
        error_log('Layout Guides Debug - enqueue_frontend_assets called');
        
        // Temporarily bypass the check to force loading
        // if (!Settings_Helper::should_show_guides()) {
        //     error_log('Layout Guides Debug - should_show_guides returned false, not enqueuing');
        //     return;
        // }
        
        error_log('Layout Guides Debug - Enqueuing frontend assets');

        // Enqueue CSS
        wp_enqueue_style(
            'orbitools-layout-guides',
            ORBITOOLS_URL . 'modules/Layout_Guides/css/layout-guides.css',
            array(),
            '1.0.0'
        );

        // Enqueue JavaScript
        wp_enqueue_script(
            'orbitools-layout-guides',
            ORBITOOLS_URL . 'modules/Layout_Guides/js/layout-guides.js',
            array(),
            '1.0.0',
            true
        );

        // Localize script with settings
        wp_localize_script(
            'orbitools-layout-guides',
            'orbitoolsLayoutGuides',
            Settings_Helper::get_js_config()
        );
    }

    /**
     * Enqueue admin assets
     *
     * @since 1.0.0
     */
    public function enqueue_admin_assets()
    {
        // Layout guides are frontend-only, never load in admin
        return;
    }

    /**
     * Add inline CSS for custom properties
     *
     * @since 1.0.0
     */
    public function add_inline_css()
    {
        if (!Settings_Helper::should_show_guides()) {
            return;
        }

        echo '<style type="text/css">';
        echo Settings_Helper::get_css_custom_properties();
        echo '</style>';
    }
}