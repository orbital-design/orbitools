<?php

/**
 * Typography Presets Settings Configuration
 *
 * Handles settings field definitions and configuration for the Typography Presets module.
 * This class centralizes all settings-related logic for better maintainability.
 *
 * @package    Orbitools
 * @subpackage Modules/Typography_Presets/Admin
 * @since      1.0.0
 */

namespace Orbitools\Modules\Typography_Presets\Admin;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Typography Presets Settings Class
 *
 * Manages settings configuration and validation for the Typography Presets module.
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
    public static function get_defaults(): array
    {
        return array(
            'typography_presets_enabled' => false,
            'typography_show_groups_in_dropdown' => false,
            'allowed_blocks' => array(
                'core/paragraph',
                'core/heading',
                'core/list',
                'core/quote',
                'core/button',
            ),
            'output_preset_css' => true,
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
                'id'      => 'typography_presets_enabled',
                'name'    => __('Enable Typography Presets', 'orbitools'),
                'desc'    => __('Replace core typography controls with preset system.', 'orbitools'),
                'type'    => 'checkbox',
                'std'     => false,
                'section' => 'typography',
            ),
            array(
                'id'      => 'typography_show_groups_in_dropdown',
                'name'    => __('Show Groups in Dropdown', 'orbitools'),
                'desc'    => __('Display preset groups as separate dropdown options.', 'orbitools'),
                'type'    => 'checkbox',
                'std'     => false,
                'section' => 'typography',
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
                'typography' => __('Typography Presets', 'orbitools'),
            ),
        );
    }

    /**
     * Validate and sanitize settings
     *
     * @since 1.0.0
     * @param array $input Raw input values.
     * @return array Sanitized settings.
     */
    public static function validate_settings(array $input): array
    {
        $validated = array();
        $defaults = self::get_defaults();

        // Validate enabled checkbox
        $validated['typography_presets_enabled'] = !empty($input['typography_presets_enabled']);

        // Validate show groups checkbox
        $validated['typography_show_groups_in_dropdown'] = !empty($input['typography_show_groups_in_dropdown']);

        // Validate allowed blocks array
        if (isset($input['allowed_blocks']) && is_array($input['allowed_blocks'])) {
            $validated['allowed_blocks'] = array_map('sanitize_text_field', $input['allowed_blocks']);
        } else {
            $validated['allowed_blocks'] = $defaults['allowed_blocks'];
        }

        // Merge with defaults to ensure all required keys exist
        return wp_parse_args($validated, $defaults);
    }

    /**
     * Get current settings with defaults
     *
     * @since 1.0.0
     * @return array Current settings merged with defaults.
     */
    public static function get_current_settings(): array
    {
        $saved_settings = get_option('orbitools_settings', array());
        return wp_parse_args($saved_settings, self::get_defaults());
    }
}