<?php

/**
 * Typography Presets CSS Generator
 *
 * Handles CSS generation and output for typography presets. This class is responsible
 * for converting preset definitions into CSS rules and managing CSS caching.
 *
 * @package    Orbitools
 * @subpackage Modules/Typography_Presets/Core
 * @since      1.0.0
 */

namespace Orbitools\Modules\Typography_Presets\Core;

use Orbitools\Modules\Typography_Presets\Admin\Settings_Helper;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * CSS Generator Class
 *
 * Manages CSS generation and output for typography presets.
 *
 * @since 1.0.0
 */
class CSS_Generator
{
    /**
     * Preset Manager instance
     *
     * @since 1.0.0
     * @var Preset_Manager
     */
    private $preset_manager;

    /**
     * Generated CSS cache
     *
     * @since 1.0.0
     * @var string|null
     */
    private $cached_css = null;

    /**
     * Initialize CSS Generator
     *
     * @since 1.0.0
     * @param Preset_Manager $preset_manager The preset manager instance.
     */
    public function __construct(Preset_Manager $preset_manager)
    {
        $this->preset_manager = $preset_manager;

        // Hook into WordPress to output CSS
        add_action('wp_head', array($this, 'output_preset_css'));
        add_action('admin_head', array($this, 'output_preset_css'));
    }

    /**
     * Generate CSS for all presets
     *
     * @since 1.0.0
     * @return string Generated CSS.
     */
    public function generate_css(): string
    {
        if ($this->cached_css !== null) {
            return $this->cached_css;
        }

        $presets = $this->preset_manager->get_presets();

        if (empty($presets)) {
            $this->cached_css = '';
            return '';
        }

        $css_rules = array();

        foreach ($presets as $preset_id => $preset) {
            $css_rules[] = $this->generate_preset_css($preset_id, $preset);
        }

        $this->cached_css = implode("\n\n", array_filter($css_rules));
        return $this->cached_css;
    }

    /**
     * Generate CSS for a specific preset
     *
     * @since 1.0.0
     * @param string $preset_id The preset identifier.
     * @param array  $preset The preset data.
     * @return string CSS rule for the preset.
     */
    private function generate_preset_css(string $preset_id, array $preset): string
    {
        if (!isset($preset['properties']) || !is_array($preset['properties'])) {
            return '';
        }

        $selector = $this->get_preset_css_selector($preset_id);
        $properties = $this->format_css_properties($preset['properties']);

        if (empty($properties)) {
            return '';
        }

        return sprintf(
            "/* Typography Preset: %s */\n%s {\n%s\n}",
            esc_html($preset['label'] ?? $preset_id),
            $selector,
            $properties
        );
    }

    /**
     * Get CSS selector for a preset
     *
     * @since 1.0.0
     * @param string $preset_id The preset identifier.
     * @return string CSS selector.
     */
    private function get_preset_css_selector(string $preset_id): string
    {
        return sprintf('.has-type-preset-%s', esc_attr($preset_id));
    }

    /**
     * Format CSS properties array into CSS string
     *
     * @since 1.0.0
     * @param array $properties CSS properties array.
     * @return string Formatted CSS properties.
     */
    private function format_css_properties(array $properties): string
    {
        $css_lines = array();

        foreach ($properties as $property => $value) {
            $css_property = $this->sanitize_css_property($property);
            $css_value = $this->process_css_value($property, $value);

            // Debug: Log when letter-spacing is being processed
            if (strpos($property, 'letter') !== false || strpos($property, 'Letter') !== false) {
                error_log("CSS Debug - Property: '$property', Sanitized: '$css_property', Value: '$value', Processed: '$css_value'");
            }

            if ($css_property && $css_value !== null) {
                $css_lines[] = sprintf('    %s: %s;', $css_property, $css_value);
            } else {
                // Debug: Log when properties are skipped
                if (strpos($property, 'letter') !== false || strpos($property, 'Letter') !== false) {
                    error_log("CSS Debug - SKIPPED - Property: '$property', Sanitized: '$css_property', Processed: '$css_value'");
                }
            }
        }

        return implode("\n", $css_lines);
    }

    /**
     * Sanitize CSS property name
     *
     * @since 1.0.0
     * @param string $property CSS property name.
     * @return string|null Sanitized property or null if invalid.
     */
    private function sanitize_css_property(string $property): ?string
    {
        // Convert camelCase to kebab-case
        $property = strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $property));

        // Basic validation - only allow letters, numbers, and hyphens
        if (preg_match('/^[a-z0-9-]+$/', $property)) {
            return $property;
        }

        return null;
    }

    /**
     * Sanitize CSS value
     *
     * @since 1.0.0
     * @param mixed $value CSS value.
     * @return string|null Sanitized value or null if invalid.
     */
    private function sanitize_css_value($value): ?string
    {
        if (is_array($value)) {
            return null; // Arrays not supported in this context
        }

        $value = (string) $value;

        // Basic sanitization - remove dangerous characters
        $value = preg_replace('/[<>"\']/', '', $value);

        return ($value !== '' && $value !== null) ? $value : null;
    }

    /**
     * Process CSS value with property-specific handling
     *
     * @since 1.0.0
     * @param string $property CSS property name.
     * @param mixed $value CSS value.
     * @return string|null Processed value or null if invalid.
     */
    private function process_css_value(string $property, $value): ?string
    {
        if (is_array($value)) {
            return null; // Arrays not supported in this context
        }

        $value = (string) $value;
        
        // Handle empty, null, or 'undefined' values (but allow "0" as valid)
        if ($value === '' || $value === 'undefined' || $value === 'null' || $value === null) {
            return null;
        }

        // Convert line-height "auto" to "normal"
        if ($property === 'line-height' && $value === 'auto') {
            $value = 'normal';
        }

        // Handle letter-spacing percentage values
        if ($property === 'letter-spacing' && preg_match('/^(-?\d+(?:\.\d+)?)%$/', $value, $matches)) {
            $percentage = floatval($matches[1]);
            $em_value = $percentage * 0.01;
            $value = $em_value . 'em';
        }

        // Basic sanitization - remove dangerous characters
        $value = preg_replace('/[<>"\']/', '', $value);

        // Don't return empty values after sanitization (but allow "0" as valid)
        return ($value !== '' && $value !== null) ? $value : null;
    }

    /**
     * Output preset CSS in the page head
     *
     * @since 1.0.0
     */
    public function output_preset_css(): void
    {
        // Check if CSS output is enabled
        if (!Settings_Helper::output_preset_css()) {
            return;
        }

        $css = $this->get_cached_css();

        if (empty($css)) {
            return;
        }

        printf(
            "<style id=\"orbitools-typography-presets-css\">\n%s\n</style>\n",
            $css
        );
    }

    /**
     * Get cached CSS or generate if not cached
     *
     * @since 1.0.0
     * @return string CSS content.
     */
    private function get_cached_css(): string
    {
        $cache_key = 'orbitools_typography_css_' . md5(serialize($this->preset_manager->get_presets()));
        $cached_css = get_transient($cache_key);

        if ($cached_css !== false) {
            return $cached_css;
        }

        $css = $this->generate_css();

        // Cache for 24 hours
        set_transient($cache_key, $css, DAY_IN_SECONDS);

        return $css;
    }

    /**
     * Clear CSS cache
     *
     * @since 1.0.0
     */
    public function clear_cache(): void
    {
        global $wpdb;

        // Clear all typography CSS transients
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_orbitools_typography_css_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_orbitools_typography_css_%'");

        // Clear instance cache
        $this->cached_css = null;
    }

    /**
     * Get CSS for a specific preset
     *
     * @since 1.0.0
     * @param string $preset_id The preset identifier.
     * @return string CSS for the preset.
     */
    public function get_preset_css(string $preset_id): string
    {
        $preset = $this->preset_manager->get_preset($preset_id);

        if (!$preset) {
            return '';
        }

        return $this->generate_preset_css($preset_id, $preset);
    }
}