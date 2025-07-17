<?php

/**
 * Layout Guides Settings Configuration
 *
 * Handles settings field definitions and configuration for the Layout Guides module.
 * This class centralizes all settings-related logic for better maintainability.
 *
 * @package    Orbitools
 * @subpackage Modules/Layout_Guides/Admin
 * @since      1.0.0
 */

namespace Orbitools\Modules\Layout_Guides\Admin;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Layout Guides Settings Class
 *
 * Manages settings configuration and validation for the Layout Guides module.
 *
 * @since 1.0.0
 */
class Settings
{
    /**
     * Get default settings configuration
     *
     * @since 1.0.0
     * @return array Default settings array.
     */
    public static function get_defaults()
    {
        return array(
            'layout_guides_enabled' => false,
            'layout_guides_show_grid' => true,
            'layout_guides_show_baseline' => true,
            'layout_guides_show_rulers' => true,
            'layout_guides_show_spacing' => true,
            'layout_guides_grid_columns' => 12,
            'layout_guides_grid_gutter' => 20,
            'layout_guides_baseline_height' => 24,
            'layout_guides_opacity' => 0.3,
            'layout_guides_color' => '#ff0000',
            'layout_guides_toggle_key' => 'ctrl+shift+g',
            'layout_guides_admin_bar_toggle' => true,
            'layout_guides_show_on_frontend_only' => false,
        );
    }

    /**
     * Get settings field definitions for admin framework
     *
     * @since 1.0.0
     * @return array Settings fields array.
     */
    public static function get_field_definitions()
    {
        return array(
            array(
                'id'      => 'layout_guides_preview',
                'name'    => '',
                'desc'    => '',
                'type'    => 'html',
                'std'     => self::get_preview_html(),
                'section' => 'layout_guides',
            ),
            array(
                'id'      => 'layout_guides_show_grid',
                'name'    => __('Show Grid', 'orbitools'),
                'desc'    => __('Display grid overlay for layout alignment.', 'orbitools'),
                'type'    => 'checkbox',
                'std'     => true,
                'section' => 'layout_guides',
            ),
            array(
                'id'      => 'layout_guides_show_baseline',
                'name'    => __('Show Baseline Grid', 'orbitools'),
                'desc'    => __('Display horizontal baseline grid for typography alignment.', 'orbitools'),
                'type'    => 'checkbox',
                'std'     => true,
                'section' => 'layout_guides',
            ),
            array(
                'id'      => 'layout_guides_show_rulers',
                'name'    => __('Show Rulers', 'orbitools'),
                'desc'    => __('Display measurement rulers on page edges.', 'orbitools'),
                'type'    => 'checkbox',
                'std'     => true,
                'section' => 'layout_guides',
            ),
            array(
                'id'      => 'layout_guides_show_spacing',
                'name'    => __('Show Element Spacing', 'orbitools'),
                'desc'    => __('Highlight element margins and padding when hovering.', 'orbitools'),
                'type'    => 'checkbox',
                'std'     => true,
                'section' => 'layout_guides',
            ),
            array(
                'id'      => 'layout_guides_grid_columns',
                'name'    => __('Grid Columns', 'orbitools'),
                'desc'    => __('Number of columns in the grid overlay.', 'orbitools'),
                'type'    => 'number',
                'std'     => 12,
                'min'     => 1,
                'max'     => 24,
                'section' => 'layout_guides',
            ),
            array(
                'id'      => 'layout_guides_grid_gutter',
                'name'    => __('Grid Gutter (px)', 'orbitools'),
                'desc'    => __('Space between grid columns in pixels.', 'orbitools'),
                'type'    => 'number',
                'std'     => 20,
                'min'     => 0,
                'max'     => 100,
                'section' => 'layout_guides',
            ),
            array(
                'id'      => 'layout_guides_baseline_height',
                'name'    => __('Baseline Height (px)', 'orbitools'),
                'desc'    => __('Height of baseline grid lines in pixels.', 'orbitools'),
                'type'    => 'number',
                'std'     => 24,
                'min'     => 8,
                'max'     => 48,
                'section' => 'layout_guides',
            ),
            array(
                'id'      => 'layout_guides_opacity',
                'name'    => __('Guide Opacity', 'orbitools'),
                'desc'    => __('Opacity of layout guides (0.1 = very transparent, 1.0 = fully opaque).', 'orbitools'),
                'type'    => 'number',
                'std'     => 0.3,
                'min'     => 0.1,
                'max'     => 1.0,
                'step'    => 0.1,
                'section' => 'layout_guides',
            ),
            array(
                'id'      => 'layout_guides_color',
                'name'    => __('Guide Color', 'orbitools'),
                'desc'    => __('Color of the layout guides.', 'orbitools'),
                'type'    => 'text',
                'std'     => '#ff0000',
                'section' => 'layout_guides',
            ),
            array(
                'id'      => 'layout_guides_toggle_key',
                'name'    => __('Toggle Keyboard Shortcut', 'orbitools'),
                'desc'    => __('Keyboard shortcut to toggle guides on/off.', 'orbitools'),
                'type'    => 'select',
                'std'     => 'ctrl+shift+g',
                'options' => array(
                    'ctrl+shift+g' => 'Ctrl+Shift+G',
                    'ctrl+shift+l' => 'Ctrl+Shift+L',
                    'ctrl+shift+r' => 'Ctrl+Shift+R',
                    'alt+shift+g'  => 'Alt+Shift+G',
                    'alt+shift+l'  => 'Alt+Shift+L',
                ),
                'section' => 'layout_guides',
            ),
            array(
                'id'      => 'layout_guides_admin_bar_toggle',
                'name'    => __('Admin Bar Toggle', 'orbitools'),
                'desc'    => __('Show toggle button in WordPress admin bar.', 'orbitools'),
                'type'    => 'checkbox',
                'std'     => true,
                'section' => 'layout_guides',
            ),
            array(
                'id'      => 'layout_guides_show_on_frontend_only',
                'name'    => __('Frontend Only', 'orbitools'),
                'desc'    => __('Only show guides on frontend pages (not in admin).', 'orbitools'),
                'type'    => 'checkbox',
                'std'     => false,
                'section' => 'layout_guides',
            ),
        );
    }

    /**
     * Get current settings with defaults
     *
     * @since 1.0.0
     * @return array Current settings merged with defaults.
     */
    public static function get_current_settings()
    {
        $saved_settings = get_option('orbitools_settings', array());
        return wp_parse_args($saved_settings, self::get_defaults());
    }

    /**
     * Get preview HTML for settings
     *
     * @since 1.0.0
     * @return string Preview HTML.
     */
    private static function get_preview_html()
    {
        return '
        <div class="layout-guides-settings-preview">
            <div class="layout-guides-settings-preview__header">
                <h3>' . __('Layout Guides Preview', 'orbitools') . '</h3>
                <p>' . __('Visual debugging tools to help with layout development and alignment.', 'orbitools') . '</p>
            </div>
            <div class="layout-guides-settings-preview__demo">
                <div class="layout-guides-settings-preview__grid">
                    <div class="layout-guides-settings-preview__column"></div>
                    <div class="layout-guides-settings-preview__column"></div>
                    <div class="layout-guides-settings-preview__column"></div>
                    <div class="layout-guides-settings-preview__column"></div>
                </div>
                <div class="layout-guides-settings-preview__baseline"></div>
                <div class="layout-guides-settings-preview__content">
                    <div class="layout-guides-settings-preview__box">Content Area</div>
                    <div class="layout-guides-settings-preview__box">Content Area</div>
                </div>
            </div>
            <div class="layout-guides-settings-preview__features">
                <ul>
                    <li>✓ Grid overlay with customizable columns</li>
                    <li>✓ Baseline grid for typography alignment</li>
                    <li>✓ Rulers for precise measurements</li>
                    <li>✓ Element spacing visualization</li>
                    <li>✓ Keyboard shortcuts for quick toggling</li>
                </ul>
            </div>
        </div>
        ';
    }
}