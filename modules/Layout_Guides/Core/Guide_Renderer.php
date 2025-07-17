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

        // Add layout guides to page (frontend only)
        add_action('wp_footer', array($this, 'render_layout_guides'));
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
        
        // Render FAB separately (not inside the guides container)
        if (!is_admin() && is_user_logged_in()) {
            echo $this->get_fab_html(Settings_Helper::get_js_config());
        }
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
     * Get FAB (Floating Action Button) HTML
     *
     * @since 1.0.0
     * @param array $settings Settings configuration.
     * @return string FAB HTML.
     */
    private function get_fab_html($settings)
    {
        $html = '<div class="orbitools-layout-guides__fab" id="orbitools-layout-guides-fab">';
        $html .= '<button class="orbitools-layout-guides__fab-toggle" title="Layout Guides Controls">';
        $html .= '<img src="' . ORBITOOLS_URL . 'assets/images/orbitools-logo.svg" alt="Orbitools" class="orbitools-layout-guides__fab-logo" />';
        $html .= '</button>';
        
        $html .= '<div class="orbitools-layout-guides__fab-panel">';
        
        // Main toggle
        $html .= '<div class="orbitools-layout-guides__fab-control">';
        $html .= '<button class="orbitools-layout-guides__fab-btn orbitools-layout-guides__fab-btn--toggle" data-action="toggle">';
        $html .= '<span class="dashicons dashicons-visibility"></span>';
        $html .= '<span class="orbitools-layout-guides__fab-label">Toggle Guides</span>';
        $html .= '</button>';
        $html .= '</div>';
        
        // Grid toggle
        if ($settings['showGrid']) {
            $html .= '<div class="orbitools-layout-guides__fab-control">';
            $html .= '<button class="orbitools-layout-guides__fab-btn" data-action="toggle-grid">';
            $html .= '<span class="dashicons dashicons-grid-view"></span>';
            $html .= '<span class="orbitools-layout-guides__fab-label">Grid</span>';
            $html .= '</button>';
            $html .= '</div>';
        }
        
        
        // Rulers toggle
        if ($settings['showRulers']) {
            $html .= '<div class="orbitools-layout-guides__fab-control">';
            $html .= '<button class="orbitools-layout-guides__fab-btn" data-action="toggle-rulers">';
            $html .= '<span class="dashicons dashicons-editor-table"></span>';
            $html .= '<span class="orbitools-layout-guides__fab-label">Rulers</span>';
            $html .= '</button>';
            $html .= '</div>';
        }
        
        // Spacing toggle
        if ($settings['showSpacing']) {
            $html .= '<div class="orbitools-layout-guides__fab-control">';
            $html .= '<button class="orbitools-layout-guides__fab-btn" data-action="toggle-spacing">';
            $html .= '<span class="dashicons dashicons-screenoptions"></span>';
            $html .= '<span class="orbitools-layout-guides__fab-label">Spacing</span>';
            $html .= '</button>';
            $html .= '</div>';
        }
        
        $html .= '</div>'; // Close panel
        $html .= '</div>'; // Close FAB
        
        return $html;
    }
}