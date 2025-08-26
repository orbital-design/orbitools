<?php
/**
 * Block Utilities Trait
 *
 * Provides common utility methods for block rendering including
 * CSS class generation, style formatting, and validation helpers.
 * 
 * @package Orbitools
 * @since 1.0.0
 */

namespace Orbitools\Core\Traits;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

trait Block_Utilities
{
    /**
     * Convert array to CSS class string
     *
     * Handles both numeric and associative arrays for flexible class generation.
     * Numeric arrays: ['class-a', 'class-b']
     * Associative arrays: ['class-a' => true, 'class-b' => false, 'class-c' => $condition]
     *
     * @param array<int|string, mixed> $classes_array CSS classes array
     * @return string Space-separated CSS classes
     * @since 1.0.0
     */
    protected function get_css_classes(array $classes_array = []): string
    {
        $classes = [];
        
        foreach ($classes_array as $class_name => $should_include) {
            // Handle numeric array: ['class-a', 'class-b']
            if (is_int($class_name)) {
                if (is_string($should_include) && !$this->is_empty_string($should_include)) {
                    $classes[] = $should_include;
                }
                continue;
            }

            // Handle associative array based on condition
            if ($this->should_include_class($should_include)) {
                $classes[] = $class_name;
            }
        }

        return implode(' ', array_unique($classes));
    }

    /**
     * Generate inline style string from array
     *
     * Converts associative array to CSS inline style string.
     * Filters out null, boolean, array, and empty string values.
     *
     * @param array<string, mixed> $styles_array Inline styles as array
     * @return string CSS inline style string
     * @since 1.0.0
     */
    protected function get_inline_styles(array $styles_array = []): string
    {
        $styles = [];

        foreach ($styles_array as $property => $value) {
            if ($this->is_valid_style_value($value)) {
                $styles[] = sprintf('%s: %s;', \esc_attr($property), \esc_attr($value));
            }
        }

        return implode(' ', array_unique($styles));
    }

    /**
     * Get KSES allowed HTML for block content
     *
     * Returns array of allowed HTML tags and attributes for wp_kses().
     * Extends default post context with SVG support.
     *
     * @param array<string, mixed> $additional_tags Additional allowed tags
     * @return array<string, mixed> Allowed HTML configuration
     * @since 1.0.0
     */
    protected function get_kses_allowed_html(array $additional_tags = []): array
    {
        $defaults = \wp_kses_allowed_html('post');

        $svg_tags = [
            'svg' => [
                'class' => true,
                'aria-hidden' => true,
                'aria-labelledby' => true,
                'role' => true,
                'xmlns' => true,
                'width' => true,
                'height' => true,
                'viewbox' => true,
            ],
            'g' => ['fill' => true],
            'title' => ['title' => true],
            'path' => ['d' => true, 'fill' => true],
        ];

        return array_merge($defaults, $svg_tags, $additional_tags);
    }

    /**
     * Validate enum value
     *
     * Ensures value is within allowed set, returns default if not.
     *
     * @param mixed $value Value to validate
     * @param array $allowed_values Allowed values
     * @param mixed $default Default value if invalid
     * @return mixed Validated value or default
     * @since 1.0.0
     */
    protected function validate_enum($value, array $allowed_values, $default)
    {
        return in_array($value, $allowed_values, true) ? $value : $default;
    }

    /**
     * Validate speed/duration format
     *
     * Validates CSS duration values (e.g., '10s', '500ms').
     *
     * @param string $speed Speed value to validate
     * @param string $default Default value if invalid
     * @return string Validated speed or default
     * @since 1.0.0
     */
    protected function validate_speed(string $speed, string $default = '10s'): string
    {
        // Match patterns like '10s', '500ms', '0.5s'
        if (preg_match('/^\d+(\.\d+)?(s|ms)$/', $speed)) {
            return \sanitize_text_field($speed);
        }
        return $default;
    }

    /**
     * Check if string is empty after trimming
     *
     * @param string $value String to check
     * @return bool True if empty
     * @since 1.0.0
     */
    protected function is_empty_string(string $value = ''): bool
    {
        return strlen(trim($value)) === 0;
    }

    /**
     * Check if array is empty
     *
     * @param array $items Array to check
     * @return bool True if empty
     * @since 1.0.0
     */
    protected function is_empty_array(array $items = []): bool
    {
        return count($items) === 0;
    }

    /**
     * Determine if class should be included
     *
     * @param mixed $condition Condition to evaluate
     * @return bool True if class should be included
     * @since 1.0.0
     */
    private function should_include_class($condition): bool
    {
        // Exclude false values
        if ($condition === false) {
            return false;
        }

        // Exclude null values
        if ($condition === null) {
            return false;
        }

        // Exclude empty strings
        if (is_string($condition) && $this->is_empty_string($condition)) {
            return false;
        }

        // Exclude empty arrays
        if (is_array($condition) && $this->is_empty_array($condition)) {
            return false;
        }

        // Include everything else (true, non-empty strings, non-empty arrays, etc.)
        return true;
    }

    /**
     * Check if value is valid for CSS style
     *
     * @param mixed $value Value to check
     * @return bool True if valid style value
     * @since 1.0.0
     */
    private function is_valid_style_value($value): bool
    {
        // Exclude null, boolean, array values
        if ($value === null || is_bool($value) || is_array($value)) {
            return false;
        }

        // Exclude empty strings
        if (is_string($value) && $this->is_empty_string($value)) {
            return false;
        }

        return true;
    }
}