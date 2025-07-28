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
        'flexDirection' => 'row',
        'flexWrap' => 'nowrap',
        'alignItems' => 'stretch',
        'justifyContent' => 'flex-start',
        'alignContent' => 'stretch',
        'stackOnMobile' => true
    ];

    /**
     * CSS mapping configuration (synced with JS config)
     *
     * @since 1.0.0
     * @var array
     */
    private static $css_mapping = [
        'flexDirection' => ['abbrev' => 'flow', 'pattern' => 'flex-flow-{value}', 'skipDefault' => false],
        'flexWrap' => ['abbrev' => 'flow', 'pattern' => 'flex-flow-{value}', 'skipDefault' => true],
        'alignItems' => ['abbrev' => 'items', 'pattern' => 'flex-items-{value}', 'skipDefault' => true],
        'justifyContent' => ['abbrev' => 'justify', 'pattern' => 'flex-justify-{value}', 'skipDefault' => true],
        'alignContent' => ['abbrev' => 'content', 'pattern' => 'flex-content-{value}', 'skipDefault' => true],
        'stackOnMobile' => ['abbrev' => 'stack', 'pattern' => 'flex-stack-mobile', 'skipDefault' => false]
    ];

    /**
     * Control visibility conditions (synced with JS config)
     *
     * @since 1.0.0
     * @var array
     */
    private static $control_conditions = [
        'alignContent' => [
            'showWhen' => [
                'flexWrap' => ['wrap', 'wrap-reverse']
            ]
        ]
    ];

    /**
     * Generate flex layout classes from block attributes using custom naming logic
     *
     * @since 1.0.0
     * @param array $attributes Block attributes containing orbitoolsFlexControls
     * @param array $existing_classes Optional. Existing CSS classes to preserve
     * @return string CSS classes string
     */
    public static function get_flex_classes(array $attributes, array $existing_classes = []): string
    {
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
        $stack_on_mobile = $flex_controls['stackOnMobile'] ?? self::$defaults['stackOnMobile'];
        
        // Process all flex controls using config-based approach
        $all_controls = [
            'flexDirection' => $direction,
            'flexWrap' => $wrap,
            'alignItems' => $align_items,
            'justifyContent' => $justify_content,
            'alignContent' => $align_content,
            'stackOnMobile' => $stack_on_mobile
        ];
        
        foreach ($all_controls as $control_name => $value) {
            // Skip if this control shouldn't be shown based on conditions
            if (!self::should_show_control($control_name, $all_controls)) {
                continue;
            }
            
            // Get CSS mapping config for this control
            $css_config = self::$css_mapping[$control_name] ?? null;
            if (!$css_config) {
                continue;
            }
            
            $default_value = self::$defaults[$control_name];
            
            // Skip if value is default and skipDefault is true
            if ($css_config['skipDefault'] && $value === $default_value) {
                continue;
            }
            
            // Generate class name from pattern
            if ($control_name === 'stackOnMobile') {
                // Special case: stackOnMobile only adds class when true
                if ($value) {
                    $classes[] = $css_config['pattern'];
                }
            } else {
                // Standard pattern replacement
                $class_name = str_replace('{value}', $value, $css_config['pattern']);
                $classes[] = $class_name;
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
        return explode(' ', $class_string);
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
        $flex_controls = $attributes['orbitoolsFlexControls'] ?? [];
        return $flex_controls[$control_name] ?? self::$defaults[$control_name] ?? null;
    }

    /**
     * Check if a control should be shown based on config conditions
     *
     * @since 1.0.0
     * @param string $control_name The control name to check
     * @param array $current_values Current flex control values
     * @return bool True if control should be shown
     */
    private static function should_show_control(string $control_name, array $current_values): bool
    {
        if (!isset(self::$control_conditions[$control_name])) {
            return true; // Show by default if no conditions
        }

        $conditions = self::$control_conditions[$control_name];
        if (!isset($conditions['showWhen'])) {
            return true;
        }

        // Check all conditions in showWhen
        foreach ($conditions['showWhen'] as $dependent_control => $allowed_values) {
            $current_value = $current_values[$dependent_control] ?? null;
            
            if (!is_array($allowed_values)) {
                continue;
            }
            
            // If current value is not in allowed values, don't show control
            if (!in_array($current_value, $allowed_values, true)) {
                return false;
            }
        }

        return true;
    }
}