<?php

/**
 * Flex Layout Controls Settings Configuration
 *
 * Handles settings field definitions and configuration for the Flex Layout Controls module.
 * This class centralizes all settings-related logic for better maintainability.
 *
 * @package    Orbitools
 * @subpackage Modules/Flex_Layout_Controls/Admin
 * @since      1.0.0
 */

namespace Orbitools\Modules\Flex_Layout_Controls\Admin;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Flex Layout Controls Settings Class
 *
 * Manages settings configuration and validation for the Flex Layout Controls module.
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
        add_action('wp_ajax_orbitools_clear_flex_cache', array(self::class, 'clear_flex_cache'));
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
            'flex_layout_controls_enabled' => false,
            'flex_output_css' => true,
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
                'id'      => 'flex_layout_controls_preview',
                'name'    => '',
                'desc'    => '',
                'type'    => 'html',
                'std'     => self::get_flex_preview_html(),
                'section' => 'flex-layout',
            ),
            array(
                'id'      => 'flex_output_css',
                'name'    => __('Output Flex CSS', 'orbitools'),
                'desc'    => __('Automatically output CSS for flex layout controls in the page head.', 'orbitools'),
                'type'    => 'checkbox',
                'std'     => true,
                'section' => 'flex-layout',
            ),
            array(
                'id'      => 'flex_clear_cache',
                'name'    => __('Cache Management', 'orbitools'),
                'desc'    => __('Clear the flex layout CSS cache to force regeneration.', 'orbitools'),
                'type'    => 'html',
                'std'     => '<button type="button" id="orbitools-clear-flex-cache" class="button button-secondary" data-nonce="' . wp_create_nonce('orbitools_admin_nonce') . '">' . __('Clear Flex CSS Cache', 'orbitools') . '</button><div id="orbitools-clear-flex-cache-result" style="margin-top: 8px;"></div>',
                'section' => 'flex-layout',
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
                'flex-layout' => array(
                    'title' => __('Flex Layout Controls', 'orbitools'),
                    'icon' => array(
                        'type' => 'svg',
                        'value' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><path fill="#32a3e2" d="M0 32C0 14.3 14.3 0 32 0H288c17.7 0 32 14.3 32 32V96H160c-17.7 0-32 14.3-32 32s14.3 32 32 32H320v64H160c-17.7 0-32 14.3-32 32s14.3 32 32 32H320v64H160c-17.7 0-32 14.3-32 32s14.3 32 32 32H320v64H32c-17.7 0-32-14.3-32-32V32zM352 96v64H608c17.7 0 32-14.3 32-32s-14.3-32-32-32H352zm0 128v64H608c17.7 0 32-14.3 32-32s-14.3-32-32-32H352zm0 128v64H608c17.7 0 32-14.3 32-32s-14.3-32-32-32H352z"/></svg>'
                    )
                ),
            ),
        );
    }


    /**
     * Get flex controls preview HTML
     *
     * @since 1.0.0
     * @return string Preview HTML.
     */
    private static function get_flex_preview_html(): string
    {
        return '<div class="orbitools-preview-section">
            <h4>' . __('Flex Layout Controls', 'orbitools') . '</h4>
            <p>' . __('This module adds comprehensive flexbox layout controls to WordPress blocks via block.json supports.', 'orbitools') . '</p>
            <div class="orbitools-flex-preview">
                <div class="preview-controls">
                    <p><strong>' . __('Usage:', 'orbitools') . '</strong> ' . __('Add flexControls to your block.json supports to enable controls.', 'orbitools') . '</p>
                    <code style="display: block; background: #f6f7f7; padding: 8px; border-radius: 4px; margin: 8px 0;">
"supports": {<br>
&nbsp;&nbsp;"flexControls": true<br>
}
                    </code>
                    <p><strong>' . __('Available Controls:', 'orbitools') . '</strong></p>
                    <ul>
                        <li><strong>' . __('flexDirection:', 'orbitools') . '</strong> ' . __('Row, column orientations', 'orbitools') . '</li>
                        <li><strong>' . __('flexWrap:', 'orbitools') . '</strong> ' . __('Wrap behaviors', 'orbitools') . '</li>
                        <li><strong>' . __('alignItems:', 'orbitools') . '</strong> ' . __('Cross-axis alignment', 'orbitools') . '</li>
                        <li><strong>' . __('justifyContent:', 'orbitools') . '</strong> ' . __('Main-axis alignment', 'orbitools') . '</li>
                        <li><strong>' . __('alignContent:', 'orbitools') . '</strong> ' . __('Multi-line alignment', 'orbitools') . '</li>
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
     * Clear flex layout cache via AJAX
     *
     * @since 1.0.0
     */
    public static function clear_flex_cache(): void
    {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'orbitools_admin_nonce')) {
            wp_die('Security check failed');
        }

        // Clear transients
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_orbitools_flex_css_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_orbitools_flex_css_%'");

        // Clear object cache
        wp_cache_delete('orbitools_flex_layout', 'theme_json');

        wp_send_json_success(array('message' => __('Flex layout cache cleared successfully.', 'orbitools')));
    }
}