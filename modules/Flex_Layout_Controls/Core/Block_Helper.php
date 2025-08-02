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
        'columnCount' => 2,
        'flexDirection' => 'row',
        'flexWrap' => 'nowrap',
        'alignItems' => 'stretch',
        'justifyContent' => 'flex-start',
        'alignContent' => 'stretch',
        'gapSize' => null,
        'restrictContentWidth' => false,
        'stackOnMobile' => true,
        'columnLayout' => 'fit',
        'gridSystem' => '5'
    ];


    /**
     * Get default gap value from block supports
     *
     * @since 1.0.0
     * @param string $block_name Block name to check supports for
     * @return string|null Default gap value or null
     */
    private static function get_default_gap_value(string $block_name): ?string
    {
        $block_type = \WP_Block_Type_Registry::get_instance()->get_registered($block_name);
        if (!$block_type) {
            return self::$defaults['gapSize'];
        }
        
        $flex_supports = $block_type->supports['flexControls'] ?? null;
        if (is_array($flex_supports) && isset($flex_supports['defaultGapValue'])) {
            return $flex_supports['defaultGapValue'];
        }
        
        return self::$defaults['gapSize'];
    }

    /**
     * Generate flex layout classes from block attributes
     *
     * @since 1.0.0
     * @param array $attributes Block attributes containing orbitoolsFlexControls
     * @param array $existing_classes Optional. Existing CSS classes to preserve
     * @param string $block_name Optional. Block name for getting default gap value
     * @return string CSS classes string
     */
    public static function get_flex_classes(array $attributes, array $existing_classes = [], string $block_name = ''): string
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
        $default_gap_size = !empty($block_name) ? self::get_default_gap_value($block_name) : self::$defaults['gapSize'];
        $gap_size = $flex_controls['gapSize'] ?? $default_gap_size;
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
            // Map align items values to simplified class names
            $align_items_map = [
                'flex-start' => 'start',
                'flex-end' => 'end',
                'center' => 'center'
            ];
            
            $class_suffix = $align_items_map[$align_items] ?? $align_items;
            $classes[] = "flex-items-{$class_suffix}";
        }
        
        // Add justify content classes (skip defaults)
        if ($justify_content !== 'flex-start') {
            // Map justify content values to simplified class names
            $justify_content_map = [
                'flex-start' => 'start',
                'flex-end' => 'end',
                'center' => 'center',
                'space-between' => 'between',
                'space-around' => 'around',
                'space-evenly' => 'evenly'
            ];
            
            $class_suffix = $justify_content_map[$justify_content] ?? $justify_content;
            $classes[] = "flex-justify-{$class_suffix}";
        }
        
        // Add align content classes (only if wrapping and not default)
        if ($wrap !== 'nowrap' && $align_content !== 'stretch') {
            // Map align content values to simplified class names
            $align_content_map = [
                'flex-start' => 'start',
                'flex-end' => 'end',
                'center' => 'center',
                'space-between' => 'between',
                'space-around' => 'around',
                'space-evenly' => 'evenly'
            ];
            
            $class_suffix = $align_content_map[$align_content] ?? $align_content;
            $classes[] = "flex-content-{$class_suffix}";
        }
        
        // Add gap class (when gap size is set)
        if (!empty($gap_size)) {
            $classes[] = 'flex-gap';
            // Add specific gap size as CSS custom property or class
            $classes[] = 'flex-gap-' . \sanitize_html_class(str_replace(['rem', 'px', 'em', '%'], '', $gap_size));
        }
        
        // Add constrain content width class (when enabled and block is full width)
        if ($restrict_content_width && ($attributes['align'] ?? '') === 'full') {
            $classes[] = 'flex-constrain';
        }
        
        // Add stack on mobile class (when enabled)
        if ($stack_on_mobile) {
            $classes[] = 'flex-stack-mobile';
        }
        
        // Add column layout classes (skip defaults)
        if ($column_layout !== 'fit') {
            if ($column_layout === 'custom') {
                // For custom layout, add the specific grid system class (no need for generic flex-lyt-custom)
                $grid_class_map = [
                    '5' => 'flex-lyt-penta',
                    '12' => 'flex-lyt-dodeca'
                ];
                
                if (isset($grid_class_map[$grid_system])) {
                    $classes[] = $grid_class_map[$grid_system];
                }
            } else {
                // For other layouts, use the standard mapping
                $layout_class_map = [
                    'grow' => 'flex-lyt-equal',
                ];
                
                if (isset($layout_class_map[$column_layout])) {
                    $classes[] = $layout_class_map[$column_layout];
                }
            }
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