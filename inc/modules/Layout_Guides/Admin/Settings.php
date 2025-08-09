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
            'layout_guides_show_grids' => true,
            'layout_guides_show_rulers' => true,
            'layout_guides_grid_gutter' => 'var(--gutter)',
            'layout_guides_opacity' => '0.3',
            'layout_guides_color' => '#32a3e2',
            'layout_guides_toggle_key' => 'ctrl+shift+g',
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
                'id'      => 'layout_guides_show_grids',
                'name'    => __('Enable Layout Grids', 'orbitools'),
                'desc'    => __('Enable grid overlays (12 and 5 column). Use the FAB to switch between grid types.', 'orbitools'),
                'type'    => 'checkbox',
                'std'     => true,
                'section' => 'layout_guides',
            ),
            array(
                'id'      => 'layout_guides_show_rulers',
                'name'    => __('Enable Rulers', 'orbitools'),
                'desc'    => __('Enable measurement rulers feature. Use the FAB to show/hide when active.', 'orbitools'),
                'type'    => 'checkbox',
                'std'     => true,
                'section' => 'layout_guides',
            ),
            array(
                'id'      => 'layout_guides_grid_gutter',
                'name'    => __('Grid Gutter', 'orbitools'),
                'desc'    => __('Space between grid columns. Can be a CSS value (e.g., "20px") or CSS custom property (e.g., "var(--gutter)").', 'orbitools'),
                'type'    => 'text',
                'std'     => 'var(--gutter)',
                'section' => 'layout_guides',
            ),
            array(
                'id'      => 'layout_guides_opacity',
                'name'    => __('Guide Opacity', 'orbitools'),
                'desc'    => __('Opacity of layout guides. Enter a decimal between 0.1 (very transparent) and 1.0 (fully opaque).', 'orbitools'),
                'type'    => 'text',
                'std'     => '0.3',
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
        );
    }

    /**
     * Get admin structure configuration
     *
     * @since 1.0.0
     * @return array Admin structure configuration.
     */
    public static function get_admin_structure(): array
    {
        return array(
            'sections' => array(
                'layout_guides' => array(
                    'title' => __('Layout Guides', 'orbitools'),
                    'icon' => array(
                        'type' => 'svg',
                        'value' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="#32a3e2" d="M.2 468.9C2.7 493.1 23.1 512 48 512h416c26.5 0 48-21.5 48-48v-96c0-26.5-21.5-48-48-48h-48v80c0 8.8-7.2 16-16 16s-16-7.2-16-16v-80h-64v80c0 8.8-7.2 16-16 16s-16-7.2-16-16v-80h-64v80c0 8.8-7.2 16-16 16s-16-7.2-16-16v-80h-80c-8.8 0-16-7.2-16-16s7.2-16 16-16h80v-64h-80c-8.8 0-16-7.2-16-16s7.2-16 16-16h80v-64h-80c-8.8 0-16-7.2-16-16s7.2-16 16-16h80V48c0-26.5-21.5-48-48-48H48C21.5 0 0 21.5 0 48v416c0 1.7.1 3.3.2 4.9z"/></svg>'
                    )
                ),
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
                <div class="layout-guides-settings-preview__content">
                    <div class="layout-guides-settings-preview__box">Content Area</div>
                    <div class="layout-guides-settings-preview__box">Content Area</div>
                </div>
            </div>
            <div class="layout-guides-settings-preview__features">
                <ul>
                    <li>✓ Grid overlay with customizable columns</li>
                    <li>✓ Rulers for precise measurements</li>
                    <li>✓ Keyboard shortcuts for quick toggling</li>
                </ul>
            </div>
        </div>
        ';
    }
}