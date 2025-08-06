<?php

/**
 * Typography Presets Manager
 *
 * Handles loading, parsing, and managing typography presets from config/orbitools.json.
 * This class is responsible for all preset-related data operations.
 *
 * @package    Orbitools
 * @subpackage Modules/Typography_Presets/Core
 * @since      1.0.0
 */

namespace Orbitools\Modules\Typography_Presets\Core;

use Orbitools\Modules\Typography_Presets\Admin\Settings;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Preset Manager Class
 *
 * Manages typography preset data loading and processing.
 *
 * @since 1.0.0
 */
class Preset_Manager
{
    /**
     * Current loaded presets from config/orbitools.json
     *
     * @since 1.0.0
     * @var array
     */
    private $presets = array();

    /**
     * Module settings
     *
     * @since 1.0.0
     * @var array
     */
    private $settings = array();

    /**
     * Initialize the Preset Manager
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->load_settings();
        $this->load_presets();
    }

    /**
     * Get all loaded presets
     *
     * @since 1.0.0
     * @return array Array of typography presets.
     */
    public function get_presets(): array
    {
        return $this->presets;
    }

    /**
     * Get a specific preset by ID
     *
     * @since 1.0.0
     * @param string $preset_id The preset identifier.
     * @return array|null Preset data or null if not found.
     */
    public function get_preset(string $preset_id): ?array
    {
        return $this->presets[$preset_id] ?? null;
    }

    /**
     * Check if presets are available
     *
     * @since 1.0.0
     * @return bool True if presets exist, false otherwise.
     */
    public function has_presets(): bool
    {
        return !empty($this->presets);
    }

    /**
     * Get presets grouped by their group identifier
     *
     * @since 1.0.0
     * @return array Presets organized by group.
     */
    public function get_presets_by_group(): array
    {
        $grouped = array();
        
        foreach ($this->presets as $preset_id => $preset) {
            $group_id = $preset['group'] ?? 'default';
            if (!isset($grouped[$group_id])) {
                $grouped[$group_id] = array(
                    'title' => $preset['group_title'] ?? ucfirst($group_id),
                    'presets' => array()
                );
            }
            $grouped[$group_id]['presets'][$preset_id] = $preset;
        }

        return $grouped;
    }

    /**
     * Load module settings from WordPress options
     *
     * @since 1.0.0
     */
    private function load_settings(): void
    {
        $admin_settings = get_option('orbitools_settings', array());

        $defaults = array(
            'typography_show_groups_in_dropdown' => false,
            'typography_output_preset_css' => true,
        );

        $this->settings = wp_parse_args($admin_settings, $defaults);
    }

    /**
     * Load presets from config/orbitools.json only
     *
     * This module exclusively uses config/orbitools.json for preset definitions.
     *
     * @since 1.0.0
     */
    private function load_presets(): void
    {
        $this->load_presets_from_config();
    }

    /**
     * Load presets from config/orbitools.json file
     *
     * Attempts to read typography presets from the active theme's config/orbitools.json file.
     * Sets empty array if config file is not available or invalid.
     *
     * @since 1.0.0
     */
    private function load_presets_from_config(): void
    {
        $config_data = $this->get_config_data();

        if (!$config_data) {
            // No config presets found - set empty array
            $this->presets = array();
            return;
        }

        // Parse and load presets
        $this->presets = $this->parse_config_presets($config_data);
    }

    /**
     * Get typography presets data from config/orbitools.json
     *
     * Attempts to read and parse the config/orbitools.json file to extract typography
     * preset definitions specific to this plugin.
     *
     * @since 1.0.0
     * @return array|false Config data array or false if not found/invalid
     */
    private function get_config_data()
    {
        $config_path = get_template_directory() . '/config/orbitools.json';

        if (!file_exists($config_path)) {
            return false;
        }

        $config_content = file_get_contents($config_path);
        $config_json = json_decode($config_content, true);

        if (!$config_json || JSON_ERROR_NONE !== json_last_error()) {
            return false;
        }

        // Navigate to modules -> typographyPresets
        if (!isset($config_json['modules']['typographyPresets'])) {
            return false;
        }

        return $config_json['modules']['typographyPresets'];
    }

    /**
     * Parse typography presets from config data
     *
     * @since 1.0.0
     * @param array $config_data Raw config data.
     * @return array Parsed presets array
     */
    private function parse_config_presets(array $config_data): array
    {
        if (!isset($config_data['items'])) {
            return array();
        }

        $parsed_presets = array();
        $group_definitions = $config_data['groups'] ?? array();

        // Process each preset from config
        foreach ($config_data['items'] as $preset_id => $preset_data) {
            $group_id = $preset_data['group'] ?? 'theme';

            // Determine group title
            $group_title = $this->get_group_title($group_id, $preset_data, $group_definitions);

            $parsed_presets[$preset_id] = array(
                'label'         => $preset_data['label'] ?? $this->generate_preset_label($preset_id),
                'description'   => $preset_data['description'] ?? 'From config/orbitools.json',
                'properties'    => $this->normalize_css_properties($preset_data['properties']),
                'group'         => $group_id,
                'group_title'   => $group_title,
                'is_theme_json' => false,
            );
        }

        return $parsed_presets;
    }

    /**
     * Get group title for a preset
     *
     * @since 1.0.0
     * @param string $group_id Group identifier.
     * @param array  $preset_data Preset data array.
     * @param array  $group_definitions Group definitions from config.
     * @return string|null Group title or null
     */
    private function get_group_title(string $group_id, array $preset_data, array $group_definitions): ?string
    {
        if (isset($group_definitions[$group_id]['title'])) {
            return $group_definitions[$group_id]['title'];
        }

        if (isset($preset_data['group_title'])) {
            return $preset_data['group_title'];
        }

        return null;
    }

    /**
     * Generate a readable label from preset ID
     *
     * Converts preset IDs like "termina-16-400" to "Termina • 16px • Regular"
     *
     * @since 1.0.0
     * @param string $preset_id The preset identifier.
     * @return string Human-readable label
     */
    private function generate_preset_label(string $preset_id): string
    {
        // Basic transformation - can be enhanced as needed
        $label = str_replace(array('-', '_'), ' ', $preset_id);
        return ucwords($label);
    }

    /**
     * Normalize CSS properties for consistent usage
     *
     * Ensures all CSS properties are in a consistent format for the frontend.
     *
     * @since 1.0.0
     * @param array $properties Raw CSS properties from config.
     * @return array Normalized CSS properties.
     */
    private function normalize_css_properties(array $properties): array
    {
        $normalized = array();

        foreach ($properties as $property => $value) {
            // Convert camelCase to kebab-case for CSS
            $css_property = strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $property));
            $normalized[$css_property] = $value;
        }

        return $normalized;
    }
}