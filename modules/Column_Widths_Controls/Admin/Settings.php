<?php

/**
 * Column Widths Controls Settings Configuration
 *
 * Handles settings field definitions and configuration for the Column Widths Controls module.
 * This class centralizes all settings-related logic for better maintainability.
 *
 * @package    Orbitools
 * @subpackage Modules/Column_Widths_Controls/Admin
 * @since      1.0.0
 */

namespace Orbitools\Modules\Column_Widths_Controls\Admin;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Column Widths Controls Settings Class
 *
 * Manages settings configuration and validation for the Column Widths Controls module.
 *
 * @since 1.0.0
 */
class Settings
{
    /**
     * Initialize the Settings class
     *
     * @since 1.0.0
     */
    public static function init(): void
    {
        // Add AJAX handler for saving accordion state
        add_action('wp_ajax_orbitools_save_accordion_state', array(self::class, 'save_accordion_state'));

        // Add AJAX handler for clearing cache
        add_action('wp_ajax_orbitools_clear_column_widths_cache', array(self::class, 'clear_column_widths_cache'));
    }

    /**
     * Get default settings configuration
     *
     * @since 1.0.0
     * @return array Default settings array.
     */
    public static function get_defaults(): array
    {
        return array(
            'column_widths_controls_enabled' => false,
            'column_widths_output_css' => true,
        );
    }

    /**
     * Get settings field definitions for admin framework
     *
     * @since 1.0.0
     * @return array Settings fields array.
     */
    public static function get_field_definitions(): array
    {
        return array(
            array(
                'id'      => 'column_widths_controls_preview',
                'name'    => '',
                'desc'    => '',
                'type'    => 'html',
                'std'     => self::get_column_widths_preview_html(),
                'section' => 'column-widths',
            ),
            array(
                'id'      => 'column_widths_output_css',
                'name'    => __('Output Column Widths CSS', 'orbitools'),
                'desc'    => __('Automatically output CSS for column width controls in the page head.', 'orbitools'),
                'type'    => 'checkbox',
                'std'     => true,
                'section' => 'column-widths',
            ),
            array(
                'id'      => 'column_widths_clear_cache',
                'name'    => __('Cache Management', 'orbitools'),
                'desc'    => __('Clear the column widths CSS cache to force regeneration.', 'orbitools'),
                'type'    => 'html',
                'std'     => '<button type="button" id="orbitools-clear-column-widths-cache" class="button button-secondary" data-nonce="' . wp_create_nonce('orbitools_admin_nonce') . '">' . __('Clear Column Widths CSS Cache', 'orbitools') . '</button><div id="orbitools-clear-column-widths-cache-result" style="margin-top: 8px;"></div>',
                'section' => 'column-widths',
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
                'column-widths' => array(
                    'title' => __('Column Widths Controls', 'orbitools'),
                    'icon' => array(
                        'type' => 'svg',
                        'value' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path fill="#32a3e2" d="M0 80C0 53.5 21.5 32 48 32h96c26.5 0 48 21.5 48 48v352c0 26.5-21.5 48-48 48H48c-26.5 0-48-21.5-48-48V80zM208 80c0-26.5 21.5-48 48-48h96c26.5 0 48 21.5 48 48v352c0 26.5-21.5 48-48 48h-96c-26.5 0-48-21.5-48-48V80zM416 80c0-26.5 21.5-48 48-48h96c26.5 0 48 21.5 48 48v352c0 26.5-21.5 48-48 48h-96c-26.5 0-48-21.5-48-48V80z"/></svg>'
                    )
                ),
            ),
        );
    }


    /**
     * Get column widths controls preview HTML
     *
     * @since 1.0.0
     * @return string Preview HTML.
     */
    private static function get_column_widths_preview_html(): string
    {
        return '<div class="orbitools-preview-section">
            <h4>' . __('Column Widths Controls', 'orbitools') . '</h4>
            <p>' . __('This module adds responsive 12-column grid width controls to WordPress blocks via block.json supports.', 'orbitools') . '</p>
            <div class="orbitools-column-widths-preview">
                <div class="preview-controls">
                    <p><strong>' . __('Usage:', 'orbitools') . '</strong> ' . __('Add columnWidthControls to your block.json supports to enable controls.', 'orbitools') . '</p>
                    <code style="display: block; background: #f6f7f7; padding: 8px; border-radius: 4px; margin: 8px 0;">
"supports": {<br>
&nbsp;&nbsp;"columnWidthControls": true<br>
}
                    </code>
                    <p><strong>' . __('Available Features:', 'orbitools') . '</strong></p>
                    <ul>
                        <li><strong>' . __('12-Column Grid:', 'orbitools') . '</strong> ' . __('1-12 column width options', 'orbitools') . '</li>
                        <li><strong>' . __('Responsive Breakpoints:', 'orbitools') . '</strong> ' . __('Base, SM, MD, LG, XL', 'orbitools') . '</li>
                        <li><strong>' . __('Parent Context Aware:', 'orbitools') . '</strong> ' . __('Different controls for row vs grid', 'orbitools') . '</li>
                        <li><strong>' . __('ToolsPanel Interface:', 'orbitools') . '</strong> ' . __('Only show needed breakpoints', 'orbitools') . '</li>
                    </ul>
                </div>
            </div>
        </div>';
    }

    /**
     * Save accordion state via AJAX
     *
     * @since 1.0.0
     */
    public static function save_accordion_state(): void
    {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'orbitools_admin_nonce')) {
            wp_die('Security check failed');
        }

        // Save accordion state
        $accordion_id = sanitize_text_field($_POST['accordion_id']);
        $is_open = (bool) $_POST['is_open'];

        $user_meta_key = 'orbitools_accordion_' . $accordion_id;
        update_user_meta(get_current_user_id(), $user_meta_key, $is_open);

        wp_send_json_success();
    }

    /**
     * Clear column widths cache via AJAX
     *
     * @since 1.0.0
     */
    public static function clear_column_widths_cache(): void
    {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'orbitools_admin_nonce')) {
            wp_die('Security check failed');
        }

        // Clear transients
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_orbitools_column_widths_css_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_orbitools_column_widths_css_%'");

        // Clear object cache
        wp_cache_delete('orbitools_column_widths', 'theme_json');

        wp_send_json_success(array('message' => __('Column widths cache cleared successfully.', 'orbitools')));
    }
}