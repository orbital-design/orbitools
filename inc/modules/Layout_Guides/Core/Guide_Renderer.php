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
        // Only show FAB if at least one feature is enabled
        $config = Settings_Helper::get_js_config();
        if (!is_admin() && is_user_logged_in() && ($config['showGrids'] || $config['showRulers'])) {
            echo $this->get_fab_html($config);
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
        if ($settings['showGrids']) {
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

        // Default to 12 columns - JavaScript will handle switching between 5 and 12
        for ($i = 0; $i < 12; $i++) {
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
        $html .= '<img src="' . ORBITOOLS_URL . 'build/media/orbitools-logo.svg" alt="Orbitools" class="orbitools-layout-guides__fab-logo" />';
        $html .= '</button>';

        $html .= '<div class="orbitools-layout-guides__fab-panel">';

        // Grid toggles - show both when grids are enabled
        if ($settings['showGrids']) {
            // 12 Column Grid toggle
            $html .= '<div class="orbitools-layout-guides__fab-control">';
            $html .= '<button class="orbitools-layout-guides__fab-btn" data-action="toggle-12-grid">';
            $html .= '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" class="orbitools-layout-guides__fab-icon"><path fill="currentColor" d="M0 72c0-22.1 17.9-40 40-40h48c22.1 0 40 17.9 40 40v48c0 22.1-17.9 40-40 40H40c-22.1 0-40-17.9-40-40V72zm0 160c0-22.1 17.9-40 40-40h48c22.1 0 40 17.9 40 40v48c0 22.1-17.9 40-40 40H40c-22.1 0-40-17.9-40-40v-48zm128 160v48c0 22.1-17.9 40-40 40H40c-22.1 0-40-17.9-40-40v-48c0-22.1 17.9-40 40-40h48c22.1 0 40 17.9 40 40zm32-320c0-22.1 17.9-40 40-40h48c22.1 0 40 17.9 40 40v48c0 22.1-17.9 40-40 40h-48c-22.1 0-40-17.9-40-40V72zm128 160v48c0 22.1-17.9 40-40 40h-48c-22.1 0-40-17.9-40-40v-48c0-22.1 17.9-40 40-40h48c22.1 0 40 17.9 40 40zM160 392c0-22.1 17.9-40 40-40h48c22.1 0 40 17.9 40 40v48c0 22.1-17.9 40-40 40h-48c-22.1 0-40-17.9-40-40v-48zM448 72v48c0 22.1-17.9 40-40 40h-48c-22.1 0-40-17.9-40-40V72c0-22.1 17.9-40 40-40h48c22.1 0 40 17.9 40 40zM320 232c0-22.1 17.9-40 40-40h48c22.1 0 40 17.9 40 40v48c0 22.1-17.9 40-40 40h-48c-22.1 0-40-17.9-40-40v-48zm128 160v48c0 22.1-17.9 40-40 40h-48c-22.1 0-40-17.9-40-40v-48c0-22.1 17.9-40 40-40h48c22.1 0 40 17.9 40 40z"/></svg>';
            $html .= '<span class="orbitools-layout-guides__fab-label">12 Grid</span>';
            $html .= '</button>';
            $html .= '</div>';

            // 5 Column Grid toggle
            $html .= '<div class="orbitools-layout-guides__fab-control">';
            $html .= '<button class="orbitools-layout-guides__fab-btn" data-action="toggle-5-grid">';
            $html .= '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" class="orbitools-layout-guides__fab-icon"><path fill="currentColor" d="M0 72c0-22.1 17.9-40 40-40h48c22.1 0 40 17.9 40 40v48c0 22.1-17.9 40-40 40H40c-22.1 0-40-17.9-40-40V72zm0 160c0-22.1 17.9-40 40-40h48c22.1 0 40 17.9 40 40v48c0 22.1-17.9 40-40 40H40c-22.1 0-40-17.9-40-40v-48zm128 160v48c0 22.1-17.9 40-40 40H40c-22.1 0-40-17.9-40-40v-48c0-22.1 17.9-40 40-40h48c22.1 0 40 17.9 40 40zm32-320c0-22.1 17.9-40 40-40h48c22.1 0 40 17.9 40 40v48c0 22.1-17.9 40-40 40h-48c-22.1 0-40-17.9-40-40V72zm128 160v48c0 22.1-17.9 40-40 40h-48c-22.1 0-40-17.9-40-40v-48c0-22.1 17.9-40 40-40h48c22.1 0 40 17.9 40 40zM160 392c0-22.1 17.9-40 40-40h48c22.1 0 40 17.9 40 40v48c0 22.1-17.9 40-40 40h-48c-22.1 0-40-17.9-40-40v-48zM448 72v48c0 22.1-17.9 40-40 40h-48c-22.1 0-40-17.9-40-40V72c0-22.1 17.9-40 40-40h48c22.1 0 40 17.9 40 40zM320 232c0-22.1 17.9-40 40-40h48c22.1 0 40 17.9 40 40v48c0 22.1-17.9 40-40 40h-48c-22.1 0-40-17.9-40-40v-48zm128 160v48c0 22.1-17.9 40-40 40h-48c-22.1 0-40-17.9-40-40v-48c0-22.1 17.9-40 40-40h48c22.1 0 40 17.9 40 40z"/></svg>';
            $html .= '<span class="orbitools-layout-guides__fab-label">5 Grid</span>';
            $html .= '</button>';
            $html .= '</div>';
        }


        // Rulers toggle
        if ($settings['showRulers']) {
            $html .= '<div class="orbitools-layout-guides__fab-control">';
            $html .= '<button class="orbitools-layout-guides__fab-btn" data-action="toggle-rulers">';
            $html .= '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" class="orbitools-layout-guides__fab-icon"><path fill="currentColor" d="M0 32v416c0 35.3 28.7 64 64 64h416c17.7 0 32-14.3 32-32v-2.7c0-8.5-3.4-16.6-9.4-22.6l-51.3-51.3-24 24c-6.2 6.2-16.4 6.2-22.6 0s-6.2-16.4 0-22.6l24-24-57.4-57.4-24 24c-6.2 6.2-16.4 6.2-22.6 0s-6.2-16.4 0-22.6l24-24-57.4-57.4-24 24c-6.2 6.2-16.4 6.2-22.6 0s-6.2-16.4 0-22.6l24-24-57.4-57.4-24 24c-6.2 6.2-16.4 6.2-22.6 0s-6.2-16.4 0-22.6l24-24-57.4-57.5-24 24c-6.2 6.2-16.4 6.2-22.6 0s-6.2-16.4 0-22.6l24-24L57.4 9.4c-6-6-14.2-9.4-22.7-9.4H32C14.3 0 0 14.3 0 32zm128 224 128 128H128V256z"/></svg>';
            $html .= '<span class="orbitools-layout-guides__fab-label">Rulers</span>';
            $html .= '</button>';
            $html .= '</div>';
        }


        $html .= '</div>'; // Close panel
        $html .= '</div>'; // Close FAB

        return $html;
    }
}
