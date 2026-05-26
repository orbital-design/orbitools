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

namespace Orbitools\Controls\Typography_Presets\Admin;

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
     * Initialize the Settings class
     *
     * @since 1.0.0
     */
    public static function init(): void
    {
        // No-op for now. Kept so the call site in Typography_Presets.php
        // doesn't need a feature flag — adding AJAX/REST endpoints in
        // future iterations slots in here.
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
            'typography_show_groups_in_dropdown' => false,
            'typography_output_preset_css' => true,
            'typography_allowed_blocks' => \Orbitools\Controls\Typography_Presets\Typography_Presets::DEFAULT_ALLOWED_BLOCKS,
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
