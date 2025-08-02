<?php

/**
 * Column Widths Controls Block Helper
 *
 * Helper functions for applying column width classes to blocks in PHP
 *
 * @package    Orbitools
 * @subpackage Modules/Column_Widths_Controls/Core
 * @since      1.0.0
 */

namespace Orbitools\Modules\Column_Widths_Controls\Core;

use Orbitools\Modules\Column_Widths_Controls\Admin\Settings_Helper;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Block Helper Class
 *
 * Provides utility functions for applying column width classes in PHP.
 *
 * @since 1.0.0
 */
class Block_Helper
{
    /**
     * Generate column width classes from block attributes
     *
     * @since 1.0.0
     * @param array $attributes Block attributes containing orbitoolsColumnWidths
     * @param array $existing_classes Optional. Existing CSS classes to preserve
     * @return string CSS classes string
     */
    public static function get_column_width_classes(array $attributes, array $existing_classes = []): string
    {
        // Don't process column width classes if module is disabled
        if (!Settings_Helper::is_module_enabled()) {
            return implode(' ', $existing_classes);
        }
        
        // Get column widths from attributes
        $column_widths = $attributes['orbitoolsColumnWidths'] ?? [];
        
        // If no column widths, return existing classes
        if (empty($column_widths)) {
            return implode(' ', $existing_classes);
        }

        $classes = $existing_classes;
        
        // Generate CSS classes using the CSS_Generator
        $css_generator = new CSS_Generator();
        $column_width_classes = $css_generator->generate_css_classes($column_widths);
        
        // Add generated classes to existing classes
        $classes = array_merge($classes, $column_width_classes);
        
        return implode(' ', array_filter($classes));
    }

    /**
     * Get column width CSS classes as an array
     *
     * @since 1.0.0
     * @param array $attributes Block attributes containing orbitoolsColumnWidths
     * @return array CSS classes array
     */
    public static function get_column_width_classes_array(array $attributes): array
    {
        $class_string = self::get_column_width_classes($attributes);
        return array_filter(explode(' ', $class_string));
    }

    /**
     * Check if block has column width controls configured
     *
     * @since 1.0.0
     * @param array $attributes Block attributes
     * @return bool True if column width controls are configured
     */
    public static function has_column_width_controls(array $attributes): bool
    {
        // Don't report column width controls if module is disabled
        if (!Settings_Helper::is_module_enabled()) {
            return false;
        }
        
        return !empty($attributes['orbitoolsColumnWidths']);
    }

    /**
     * Get a specific column width value for a breakpoint
     *
     * @since 1.0.0
     * @param array $attributes Block attributes
     * @param string $breakpoint The breakpoint name (base, sm, md, lg, xl)
     * @return string|null The column width value or null if not set
     */
    public static function get_column_width_value(array $attributes, string $breakpoint): ?string
    {
        // Don't return column width values if module is disabled
        if (!Settings_Helper::is_module_enabled()) {
            return null;
        }
        
        $column_widths = $attributes['orbitoolsColumnWidths'] ?? [];
        return $column_widths[$breakpoint] ?? null;
    }

    /**
     * Check if block should show column width controls based on parent
     *
     * @since 1.0.0
     * @param array $parent_attributes Parent block attributes (usually orbital/row)
     * @return bool True if column width controls should be available
     */
    public static function should_show_column_width_controls(array $parent_attributes): bool
    {
        // Don't show controls if module is disabled
        if (!Settings_Helper::is_module_enabled()) {
            return false;
        }
        
        // Check if parent has flex controls with custom column layout
        $flex_controls = $parent_attributes['orbitoolsFlexControls'] ?? [];
        $column_layout = $flex_controls['columnLayout'] ?? 'fit';
        
        // Only show column width controls if parent is using custom (grid) layout
        return $column_layout === 'custom';
    }

    /**
     * Get the grid system from parent block
     *
     * @since 1.0.0
     * @param array $parent_attributes Parent block attributes (usually orbital/row)
     * @return string Grid system ('5' or '12')
     */
    public static function get_parent_grid_system(array $parent_attributes): string
    {
        $flex_controls = $parent_attributes['orbitoolsFlexControls'] ?? [];
        return $flex_controls['gridSystem'] ?? '5'; // Default to 5-column grid
    }
}