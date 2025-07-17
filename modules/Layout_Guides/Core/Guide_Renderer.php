<?php

/**
 * Layout Guides Renderer
 *
 * Handles the rendering of layout guides including grid overlay,
 * baseline grid, rulers, and spacing visualization.
 *
 * @package    Orbitools
 * @subpackage Modules/Layout_Guides/Core
 * @since      1.0.0
 */

namespace Orbitools\Modules\Layout_Guides\Core;

use Orbitools\Modules\Layout_Guides\Admin\Settings_Helper;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Guide Renderer Class
 *
 * Responsible for rendering the visual layout guides on the frontend.
 *
 * @since 1.0.0
 */
class Guide_Renderer
{
    /**
     * Initialize the guide renderer
     *
     * @since 1.0.0
     */
    public function init()
    {
        // Add body classes for layout guides
        add_filter('body_class', array($this, 'add_body_classes'));

        // Add layout guides to page
        add_action('wp_footer', array($this, 'render_layout_guides'));
        add_action('admin_footer', array($this, 'render_layout_guides'));

        // Add admin bar toggle
        add_action('admin_bar_menu', array($this, 'add_admin_bar_toggle'), 100);
    }

    /**
     * Add body classes for layout guides
     *
     * @since 1.0.0
     * @param array $classes Existing body classes.
     * @return array Modified body classes.
     */
    public function add_body_classes($classes)
    {
        $guide_classes = Settings_Helper::get_body_classes();
        return array_merge($classes, $guide_classes);
    }

    /**
     * Render layout guides HTML
     *
     * @since 1.0.0
     */
    public function render_layout_guides()
    {
        if (!Settings_Helper::should_show_guides()) {
            return;
        }

        echo $this->get_guides_html();
    }

    /**
     * Get layout guides HTML
     *
     * @since 1.0.0
     * @return string Layout guides HTML.
     */
    private function get_guides_html()
    {
        $settings = Settings_Helper::get_js_config();
        
        $html = '<div id="orbitools-layout-guides" class="orbitools-layout-guides">';
        
        // Grid overlay
        if ($settings['showGrid']) {
            $html .= $this->get_grid_html($settings);
        }
        
        // Baseline grid
        if ($settings['showBaseline']) {
            $html .= $this->get_baseline_html($settings);
        }
        
        // Rulers
        if ($settings['showRulers']) {
            $html .= $this->get_rulers_html($settings);
        }
        
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Get grid overlay HTML
     *
     * @since 1.0.0
     * @param array $settings Settings configuration.
     * @return string Grid HTML.
     */
    private function get_grid_html($settings)
    {
        $html = '<div class="orbitools-layout-guides__grid">';
        
        for ($i = 0; $i < $settings['gridColumns']; $i++) {
            $html .= '<div class="orbitools-layout-guides__grid-column"></div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Get baseline grid HTML
     *
     * @since 1.0.0
     * @param array $settings Settings configuration.
     * @return string Baseline HTML.
     */
    private function get_baseline_html($settings)
    {
        return '<div class="orbitools-layout-guides__baseline"></div>';
    }

    /**
     * Get rulers HTML
     *
     * @since 1.0.0
     * @param array $settings Settings configuration.
     * @return string Rulers HTML.
     */
    private function get_rulers_html($settings)
    {
        $html = '<div class="orbitools-layout-guides__rulers">';
        $html .= '<div class="orbitools-layout-guides__ruler orbitools-layout-guides__ruler--horizontal"></div>';
        $html .= '<div class="orbitools-layout-guides__ruler orbitools-layout-guides__ruler--vertical"></div>';
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Add admin bar toggle
     *
     * @since 1.0.0
     * @param \WP_Admin_Bar $wp_admin_bar Admin bar instance.
     */
    public function add_admin_bar_toggle($wp_admin_bar)
    {
        $settings = Settings_Helper::get_js_config();
        
        if (!$settings['adminBarToggle'] || !Settings_Helper::should_show_guides()) {
            return;
        }

        $wp_admin_bar->add_node(array(
            'id'    => 'orbitools-layout-guides-toggle',
            'title' => '<span class="ab-icon dashicons-grid-view"></span>' . __('Layout Guides', 'orbitools'),
            'href'  => '#',
            'meta'  => array(
                'class' => 'orbitools-layout-guides-toggle',
                'title' => __('Toggle Layout Guides', 'orbitools'),
            ),
        ));
    }
}