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
            'typography_output_preset_css' => true,
            'typography_allowed_blocks' => array(
                'core/paragraph',
                'core/heading',
                'core/list',
                'core/quote',
                'core/button',
            ),
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
                'id'      => 'typography_show_groups_in_dropdown',
                'name'    => __('Show Groups in Dropdown', 'orbitools'),
                'desc'    => __('Display preset groups as separate dropdown options.', 'orbitools'),
                'type'    => 'checkbox',
                'std'     => false,
                'section' => 'typography',
            ),
            array(
                'id'      => 'typography_output_preset_css',
                'name'    => __('Output Preset CSS', 'orbitools'),
                'desc'    => __('Automatically output CSS for typography presets in the page head.', 'orbitools'),
                'type'    => 'checkbox',
                'std'     => true,
                'section' => 'typography',
            ),
            array(
                'id'      => 'typography_allowed_blocks',
                'name'    => __('Allowed Blocks', 'orbitools'),
                'desc'    => __('Select which block types can use typography presets.', 'orbitools'),
                'type'    => 'checkbox',
                'options' => array(
                    'core/paragraph' => __('Paragraph', 'orbitools'),
                    'core/heading' => __('Heading', 'orbitools'),
                    'core/list' => __('List', 'orbitools'),
                    'core/quote' => __('Quote', 'orbitools'),
                    'core/button' => __('Button', 'orbitools'),
                    'core/group' => __('Group', 'orbitools'),
                    'core/column' => __('Column', 'orbitools'),
                    'core/cover' => __('Cover', 'orbitools'),
                ),
                'std'     => array(
                    'core/paragraph',
                    'core/heading',
                    'core/list',
                    'core/quote',
                    'core/button'
                ),
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