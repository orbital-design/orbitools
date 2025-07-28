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
        'stackOnMobile' => true
    ];

    /**
     * CSS mapping configuration (synced with JS config)
     *
     * @since 1.0.0
     * @var array
     */
    private static $css_mapping = [
        'flexDirection' => ['abbrev' => 'flow', 'pattern' => 'flex-flow-{value}', 'skipDefault' => true],
        'flexWrap' => ['abbrev' => 'flow', 'pattern' => 'flex-flow-{value}', 'skipDefault' => true],
        'alignItems' => ['abbrev' => 'items', 'pattern' => 'flex-items-{value}', 'skipDefault' => true],
        'justifyContent' => ['abbrev' => 'justify', 'pattern' => 'flex-justify-{value}', 'skipDefault' => true],
        'alignContent' => ['abbrev' => 'content', 'pattern' => 'flex-content-{value}', 'skipDefault' => true],
        'enableGap' => ['abbrev' => 'gap', 'pattern' => 'flex-gap', 'skipDefault' => false],
        'restrictContentWidth' => ['abbrev' => 'restrict', 'pattern' => 'flex-restrict-content', 'skipDefault' => false],
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
        ],
        'restrictContentWidth' => [
            'showWhen' => [
                'align' => ['full']
            ]
        ]
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

    /**
     * Generate flex classes for JavaScript (to avoid duplication)
     *
     * @since 1.0.0
     * @param array $flex_controls Flex control values
     * @return string CSS classes string
     */
    public static function generate_flex_classes_for_js(array $flex_controls): string
    {
        // Don't process flex classes if module is disabled
        if (!\Orbitools\Modules\Flex_Layout_Controls\Admin\Settings_Helper::is_module_enabled()) {
            return '';
        }

        $classes = ['flex'];
        
        // Get values with defaults
        $all_controls = [
            'columnCount' => $flex_controls['columnCount'] ?? self::$defaults['columnCount'],
            'flexDirection' => $flex_controls['flexDirection'] ?? self::$defaults['flexDirection'],
            'flexWrap' => $flex_controls['flexWrap'] ?? self::$defaults['flexWrap'],
            'alignItems' => $flex_controls['alignItems'] ?? self::$defaults['alignItems'],
            'justifyContent' => $flex_controls['justifyContent'] ?? self::$defaults['justifyContent'],
            'alignContent' => $flex_controls['alignContent'] ?? self::$defaults['alignContent'],
            'enableGap' => $flex_controls['enableGap'] ?? self::$defaults['enableGap'],
            'restrictContentWidth' => $flex_controls['restrictContentWidth'] ?? self::$defaults['restrictContentWidth'],
            'stackOnMobile' => $flex_controls['stackOnMobile'] ?? self::$defaults['stackOnMobile']
        ];
        
        foreach ($all_controls as $control_name => $value) {
            // Skip columnCount as it doesn't generate CSS classes
            if ($control_name === 'columnCount') {
                continue;
            }
            
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
            if (in_array($control_name, ['enableGap', 'restrictContentWidth', 'stackOnMobile'])) {
                // Boolean controls: only add class when true
                if ($value) {
                    $classes[] = $css_config['pattern'];
                }
            } else {
                // Standard pattern replacement for non-boolean controls
                $class_name = str_replace('{value}', $value, $css_config['pattern']);
                $classes[] = $class_name;
            }
        }
        
        return implode(' ', array_filter($classes));
    }
}