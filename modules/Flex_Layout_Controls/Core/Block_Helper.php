<?php

/**
 * Flex Layout Controls Block Helper
 *
 * Helper functions for applying flex layout classes to blocks in PHP
 *
 * @package    Orbitools
 * @subpackage Modules/Flex_Layout_Controls/Core
 * @since      1.0.0
 */

namespace Orbitools\Modules\Flex_Layout_Controls\Core;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Block Helper Class
 *
 * Provides utility functions for applying flex layout classes in PHP.
 *
 * @since 1.0.0
 */
class Block_Helper
{
    /**
     * Default flex control values (synced with JS config)
     *
     * @since 1.0.0
     * @var array
     */
    private static $defaults = [
        'columnCount' => 3,
        'flexDirection' => 'row',
        'flexWrap' => 'nowrap',
        'alignItems' => 'stretch',
        'justifyContent' => 'flex-start',
        'alignContent' => 'stretch',
        'enableGap' => true,
        'restrictContentWidth' => false,
        'stackOnMobile' => true,
        'columnLayout' => 'fit',
        'gridSystem' => '5'
    ];


    /**
     * Generate flex layout classes from block attributes
     *
     * @since 1.0.0
     * @param array $attributes Block attributes containing orbitoolsFlexControls
     * @param array $existing_classes Optional. Existing CSS classes to preserve
     * @return string CSS classes string
     */
    public static function get_flex_classes(array $attributes, array $existing_classes = []): string
    {
        // Don't process flex classes if module is disabled
        if (!\Orbitools\Modules\Flex_Layout_Controls\Admin\Settings_Helper::is_module_enabled()) {
            return implode(' ', $existing_classes);
        }
        
        // Get flex controls from attributes
        $flex_controls = $attributes['orbitoolsFlexControls'] ?? [];
        
        // If no flex controls, return existing classes
        if (empty($flex_controls)) {
            return implode(' ', $existing_classes);
        }

        $classes = $existing_classes;
        
        // Add base flex class
        $classes[] = 'flex';
        
        // Get values with defaults
        $direction = $flex_controls['flexDirection'] ?? self::$defaults['flexDirection'];
        $wrap = $flex_controls['flexWrap'] ?? self::$defaults['flexWrap'];
        $align_items = $flex_controls['alignItems'] ?? self::$defaults['alignItems'];
        $justify_content = $flex_controls['justifyContent'] ?? self::$defaults['justifyContent'];
        $align_content = $flex_controls['alignContent'] ?? self::$defaults['alignContent'];
        $enable_gap = $flex_controls['enableGap'] ?? self::$defaults['enableGap'];
        $restrict_content_width = $flex_controls['restrictContentWidth'] ?? self::$defaults['restrictContentWidth'];
        $stack_on_mobile = $flex_controls['stackOnMobile'] ?? self::$defaults['stackOnMobile'];
        $column_layout = $flex_controls['columnLayout'] ?? self::$defaults['columnLayout'];
        $grid_system = $flex_controls['gridSystem'] ?? self::$defaults['gridSystem'];
        
        // Add direction classes (skip defaults)
        if ($direction !== 'row') {
            $classes[] = "flex-flow-{$direction}";
        }
        
        // Add wrap classes (skip defaults)
        if ($wrap !== 'nowrap') {
            $classes[] = "flex-flow-{$wrap}";
        }
        
        // Add align items classes (skip defaults)
        if ($align_items !== 'stretch') {
            $classes[] = "flex-items-{$align_items}";
        }
        
        // Add justify content classes (skip defaults)
        if ($justify_content !== 'flex-start') {
            $classes[] = "flex-justify-{$justify_content}";
        }
        
        // Add align content classes (only if wrapping and not default)
        if ($wrap !== 'nowrap' && $align_content !== 'stretch') {
            $classes[] = "flex-content-{$align_content}";
        }
        
        // Add gap class (when enabled)
        if ($enable_gap) {
            $classes[] = 'flex-gap';
        }
        
        // Add restrict content width class (when enabled and block is full width)
        if ($restrict_content_width && ($attributes['align'] ?? '') === 'full') {
            $classes[] = 'flex-restrict-content';
        }
        
        // Add stack on mobile class (when enabled)
        if ($stack_on_mobile) {
            $classes[] = 'flex-stack-mobile';
        }
        
        // Add column layout classes (skip defaults)
        if ($column_layout !== 'fit') {
            $classes[] = "flex-layout-{$column_layout}";
        }
        
        // Add grid system classes (only if column layout is custom)
        if ($column_layout === 'custom') {
            $classes[] = "flex-grid-{$grid_system}col";
        }
        
        return implode(' ', array_filter($classes));
    }

    /**
     * Get flex layout CSS classes as an array
     *
     * @since 1.0.0
     * @param array $attributes Block attributes containing orbitoolsFlexControls
     * @return array CSS classes array
     */
    public static function get_flex_classes_array(array $attributes): array
    {
        $class_string = self::get_flex_classes($attributes);
        return array_filter(explode(' ', $class_string));
    }

    /**
     * Check if block has flex controls configured
     *
     * @since 1.0.0
     * @param array $attributes Block attributes
     * @return bool True if flex controls are configured
     */
    public static function has_flex_controls(array $attributes): bool
    {
        // Don't report flex controls if module is disabled
        if (!\Orbitools\Modules\Flex_Layout_Controls\Admin\Settings_Helper::is_module_enabled()) {
            return false;
        }
        
        return !empty($attributes['orbitoolsFlexControls']);
    }

    /**
     * Get a specific flex control value
     *
     * @since 1.0.0
     * @param array $attributes Block attributes
     * @param string $control_name The flex control name (flexDirection, alignItems, etc.)
     * @return string|null The control value or null if not set
     */
    public static function get_flex_control(array $attributes, string $control_name): ?string
    {
        // Don't return flex control values if module is disabled
        if (!\Orbitools\Modules\Flex_Layout_Controls\Admin\Settings_Helper::is_module_enabled()) {
            return null;
        }
        
        $flex_controls = $attributes['orbitoolsFlexControls'] ?? [];
        return $flex_controls[$control_name] ?? self::$defaults[$control_name] ?? null;
    }

}