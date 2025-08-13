<?php
/**
 * Spacings Renderer
 *
 * Standalone utility for generating spacing CSS classes in PHP render callbacks.
 * This mirrors the JavaScript spacing class generation for server-side rendering.
 *
 * @since 1.0.0
 */

namespace Orbitools\Controls\Spacings_Controls;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class SpacingsRenderer
{
    /**
     * Generate responsive gap classes from gap data using has-gap pattern
     * 
     * @param array $gap Gap configuration data
     * @return string Generated gap classes
     */
    public static function get_gap_classes($gap): string
    {
        if (!$gap || !is_array($gap)) {
            return '';
        }

        $classes = ['has-gap']; // Always include base class
        
        foreach ($gap as $breakpoint => $value) {
            if ($value === null || $value === '' || $value === false) {
                continue;
            }
            
            $class_name = '';
            if ($breakpoint === 'base') {
                $class_name = "has-gap--{$value}";
            } else {
                $class_name = "{$breakpoint}:has-gap--{$value}";
            }
            $classes[] = $class_name;
        }
        
        // Only return classes if we have modifiers (not just the base class)
        return count($classes) > 1 ? implode(' ', $classes) : '';
    }

    /**
     * Generate responsive padding classes from padding data
     * 
     * @param array $padding Padding configuration data
     * @return string Generated padding classes
     */
    public static function get_padding_classes($padding): string
    {
        if (!$padding || !is_array($padding)) {
            return '';
        }

        $classes = [];
        
        foreach ($padding as $breakpoint => $config) {
            if ($config === null || $config === false) {
                continue;
            }
            
            $class_names = [];
            $prefix = $breakpoint === 'base' ? 'p' : "{$breakpoint}:p";

            if (is_string($config)) {
                // Legacy format: just the value
                $class_names[] = "{$prefix}-{$config}";
            } elseif (is_array($config) && isset($config['type'])) {
                // New format: { type: 'all', value: '4' } or { type: 'sides', top: '1', right: '2', ... }
                $type = $config['type'];
                
                switch ($type) {
                    case 'all':
                        if (isset($config['value']) && $config['value'] !== '' && $config['value'] !== null) {
                            $class_names[] = "{$prefix}-{$config['value']}";
                        }
                        break;
                    case 'split':
                        if (isset($config['x']) && $config['x'] !== '' && $config['x'] !== null) {
                            $class_names[] = "{$prefix}x-{$config['x']}";
                        }
                        if (isset($config['y']) && $config['y'] !== '' && $config['y'] !== null) {
                            $class_names[] = "{$prefix}y-{$config['y']}";
                        }
                        break;
                    case 'sides':
                        if (isset($config['top']) && $config['top'] !== '' && $config['top'] !== null) {
                            $class_names[] = "{$prefix}t-{$config['top']}";
                        }
                        if (isset($config['right']) && $config['right'] !== '' && $config['right'] !== null) {
                            $class_names[] = "{$prefix}r-{$config['right']}";
                        }
                        if (isset($config['bottom']) && $config['bottom'] !== '' && $config['bottom'] !== null) {
                            $class_names[] = "{$prefix}b-{$config['bottom']}";
                        }
                        if (isset($config['left']) && $config['left'] !== '' && $config['left'] !== null) {
                            $class_names[] = "{$prefix}l-{$config['left']}";
                        }
                        break;
                }
            }

            $classes = array_merge($classes, $class_names);
        }
        
        return implode(' ', $classes);
    }

    /**
     * Generate responsive margin classes from margin data
     * 
     * @param array $margin Margin configuration data
     * @return string Generated margin classes
     */
    public static function get_margin_classes($margin): string
    {
        if (!$margin || !is_array($margin)) {
            return '';
        }

        $classes = [];
        
        foreach ($margin as $breakpoint => $config) {
            if ($config === null || $config === false) {
                continue;
            }
            
            $class_names = [];
            $prefix = $breakpoint === 'base' ? 'm' : "{$breakpoint}:m";

            if (is_string($config)) {
                // Legacy format: just the value
                $class_names[] = "{$prefix}-{$config}";
            } elseif (is_array($config) && isset($config['type'])) {
                // New format: { type: 'all', value: '4' } or { type: 'sides', top: '1', right: '2', ... }
                $type = $config['type'];
                
                switch ($type) {
                    case 'all':
                        if (isset($config['value']) && $config['value'] !== '' && $config['value'] !== null) {
                            $class_names[] = "{$prefix}-{$config['value']}";
                        }
                        break;
                    case 'split':
                        if (isset($config['x']) && $config['x'] !== '' && $config['x'] !== null) {
                            $class_names[] = "{$prefix}x-{$config['x']}";
                        }
                        if (isset($config['y']) && $config['y'] !== '' && $config['y'] !== null) {
                            $class_names[] = "{$prefix}y-{$config['y']}";
                        }
                        break;
                    case 'sides':
                        if (isset($config['top']) && $config['top'] !== '' && $config['top'] !== null) {
                            $class_names[] = "{$prefix}t-{$config['top']}";
                        }
                        if (isset($config['right']) && $config['right'] !== '' && $config['right'] !== null) {
                            $class_names[] = "{$prefix}r-{$config['right']}";
                        }
                        if (isset($config['bottom']) && $config['bottom'] !== '' && $config['bottom'] !== null) {
                            $class_names[] = "{$prefix}b-{$config['bottom']}";
                        }
                        if (isset($config['left']) && $config['left'] !== '' && $config['left'] !== null) {
                            $class_names[] = "{$prefix}l-{$config['left']}";
                        }
                        break;
                }
            }

            $classes = array_merge($classes, $class_names);
        }
        
        return implode(' ', $classes);
    }

    /**
     * Generate all spacing classes from block attributes
     * 
     * @param array $attributes Block attributes containing orbGap, orbPadding, orbMargin
     * @param bool $as_array Whether to return as array instead of string
     * @return string|array All spacing classes combined as string or array
     */
    public static function get_all_spacings_classes($attributes, $as_array = false)
    {
        $gap_classes = self::get_gap_classes($attributes['orbGap'] ?? []);
        $padding_classes = self::get_padding_classes($attributes['orbPadding'] ?? []);
        $margin_classes = self::get_margin_classes($attributes['orbMargin'] ?? []);
        
        // Check if we have any spacing classes
        $has_spacing_classes = !empty($gap_classes) || !empty($padding_classes) || !empty($margin_classes);
        
        if ($as_array) {
            $all_classes = array_filter(array_merge(
                $gap_classes ? explode(' ', $gap_classes) : [],
                $padding_classes ? explode(' ', $padding_classes) : [],
                $margin_classes ? explode(' ', $margin_classes) : []
            ));
            
            // Add has-spacing class if we have any spacing classes
            if ($has_spacing_classes && !empty($all_classes)) {
                array_unshift($all_classes, 'has-spacing');
            }
            
            return $all_classes;
        }
        
        $all_classes = trim($gap_classes . ' ' . $padding_classes . ' ' . $margin_classes);
        
        // Add has-spacing class if we have any spacing classes
        if ($has_spacing_classes && !empty($all_classes)) {
            $all_classes = 'has-spacing ' . $all_classes;
        }
        
        return $all_classes;
    }

    /**
     * Check if block has spacings support
     * 
     * @param string $block_name Block name (e.g., 'orb/collection')
     * @return bool Whether block supports spacings
     */
    public static function block_has_spacings_support($block_name): bool
    {
        $block_type = \WP_Block_Type_Registry::get_instance()->get_registered($block_name);
        
        if (!$block_type || !isset($block_type->supports['orbitools']['spacings'])) {
            return false;
        }
        
        $spacings_supports = $block_type->supports['orbitools']['spacings'];
        return $spacings_supports && $spacings_supports !== false && 
               ($spacings_supports === true || (is_array($spacings_supports) && count($spacings_supports) > 0));
    }

    /**
     * Apply spacing classes to existing class string
     * 
     * @param string $existing_classes Existing CSS classes
     * @param array $attributes Block attributes
     * @return string Combined classes with spacings
     */
    public static function apply_spacings_classes($existing_classes, $attributes): string
    {
        $spacings_classes = self::get_all_spacings_classes($attributes);
        
        if (empty($spacings_classes)) {
            return $existing_classes;
        }
        
        return trim($existing_classes . ' ' . $spacings_classes);
    }

    /**
     * Helper function for blocks to easily add spacing classes
     * Call this in your block's render callback to add spacings support
     * 
     * @param string $base_classes Your block's base CSS classes
     * @param array $attributes Block attributes from render callback
     * @return string Classes with spacings added
     */
    public static function add_spacings($base_classes, $attributes): string
    {
        return self::apply_spacings_classes($base_classes, $attributes);
    }
}