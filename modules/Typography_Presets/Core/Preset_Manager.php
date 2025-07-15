<?php

/**
 * Typography Presets Manager
 *
 * Handles loading, parsing, and managing typography presets from theme.json.
 * This class is responsible for all preset-related data operations.
 *
 * @package    Orbitools
 * @subpackage Modules/Typography_Presets/Core
 * @since      1.0.0
 */

namespace Orbitools\Modules\Typography_Presets\Core;

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
     * Current loaded presets from theme.json
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
     * Load presets from theme.json only
     *
     * This module exclusively uses theme.json for preset definitions.
     *
     * @since 1.0.0
     */
    private function load_presets(): void
    {
        $this->load_presets_from_theme_json();
    }

    /**
     * Load presets from theme.json file
     *
     * Attempts to read typography presets from the active theme's theme.json file.
     * Sets empty array if theme.json is not available or invalid.
     *
     * @since 1.0.0
     */
    private function load_presets_from_theme_json(): void
    {
        $theme_data = $this->get_theme_json_data();

        if (!$theme_data) {
            // No theme.json presets found - set empty array
            $this->presets = array();
            return;
        }

        // Parse and load presets
        $this->presets = $this->parse_theme_json_presets($theme_data);
    }

    /**
     * Get typography presets data from theme.json
     *
     * Attempts to read and parse the theme.json file to extract typography
     * preset definitions specific to this plugin.
     *
     * @since 1.0.0
     * @return array|false Theme data array or false if not found/invalid
     */
    private function get_theme_json_data()
    {
        $theme_json_path = get_template_directory() . '/theme.json';

        if (!file_exists($theme_json_path)) {
            return false;
        }

        $theme_json_content = file_get_contents($theme_json_path);
        $theme_json = json_decode($theme_json_content, true);

        if (!$theme_json || JSON_ERROR_NONE !== json_last_error()) {
            return false;
        }

        // Navigate to our plugin data: settings -> custom -> orbital -> plugins -> oes -> Typography_Presets
        $plugin_path = array('settings', 'custom', 'orbital', 'plugins', 'oes', 'Typography_Presets');
        $data = $theme_json;

        foreach ($plugin_path as $key) {
            if (!isset($data[$key])) {
                return false;
            }
            $data = $data[$key];
        }

        return $data;
    }

    /**
     * Parse typography presets from theme.json data
     *
     * @since 1.0.0
     * @param array $theme_data Raw theme.json data.
     * @return array Parsed presets array
     */
    private function parse_theme_json_presets(array $theme_data): array
    {
        if (!isset($theme_data['items'])) {
            return array();
        }

        $parsed_presets = array();
        $group_definitions = $theme_data['groups'] ?? array();

        // Process each preset from theme.json
        foreach ($theme_data['items'] as $preset_id => $preset_data) {
            $group_id = $preset_data['group'] ?? 'theme';

            // Determine group title
            $group_title = $this->get_group_title($group_id, $preset_data, $group_definitions);

            $parsed_presets[$preset_id] = array(
                'label'         => $preset_data['label'] ?? $this->generate_preset_label($preset_id),
                'description'   => $preset_data['description'] ?? 'From theme.json',
                'properties'    => $this->normalize_css_properties($preset_data['properties']),
                'group'         => $group_id,
                'group_title'   => $group_title,
                'is_theme_json' => true,
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
     * @param array  $group_definitions Group definitions from theme.json.
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
     * @param array $properties Raw CSS properties from theme.json.
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