<?php
/**
 * Dimensions Renderer
 *
 * Standalone utility for generating dimension CSS classes in PHP render callbacks.
 * This mirrors the JavaScript dimension class generation for server-side rendering.
 *
 * @since 1.0.0
 */

namespace Orbitools\Controls\Dimensions_Controls;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class DimensionsRenderer
{
    /**
     * Generate responsive gap classes from gap data
     * 
     * @param array $gap Gap configuration data
     * @return string Generated gap classes
     */
    public static function get_gap_classes($gap): string
    {
        if (!$gap || !is_array($gap)) {
            return '';
        }

        $classes = [];
        
        foreach ($gap as $breakpoint => $value) {
            if (!$value) {
                continue;
            }
            
            $class_name = '';
            if ($breakpoint === 'base') {
                $class_name = "gap-{$value}";
            } else {
                $class_name = "{$breakpoint}:gap-{$value}";
            }
            $classes[] = $class_name;
        }
        
        return implode(' ', $classes);
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
            if (!$config) {
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
                        if (!empty($config['value'])) {
                            $class_names[] = "{$prefix}-{$config['value']}";
                        }
                        break;
                    case 'split':
                        if (!empty($config['x'])) {
                            $class_names[] = "{$prefix}x-{$config['x']}";
                        }
                        if (!empty($config['y'])) {
                            $class_names[] = "{$prefix}y-{$config['y']}";
                        }
                        break;
                    case 'sides':
                        if (!empty($config['top'])) {
                            $class_names[] = "{$prefix}t-{$config['top']}";
                        }
                        if (!empty($config['right'])) {
                            $class_names[] = "{$prefix}r-{$config['right']}";
                        }
                        if (!empty($config['bottom'])) {
                            $class_names[] = "{$prefix}b-{$config['bottom']}";
                        }
                        if (!empty($config['left'])) {
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
            if (!$config) {
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
                        if (!empty($config['value'])) {
                            $class_names[] = "{$prefix}-{$config['value']}";
                        }
                        break;
                    case 'split':
                        if (!empty($config['x'])) {
                            $class_names[] = "{$prefix}x-{$config['x']}";
                        }
                        if (!empty($config['y'])) {
                            $class_names[] = "{$prefix}y-{$config['y']}";
                        }
                        break;
                    case 'sides':
                        if (!empty($config['top'])) {
                            $class_names[] = "{$prefix}t-{$config['top']}";
                        }
                        if (!empty($config['right'])) {
                            $class_names[] = "{$prefix}r-{$config['right']}";
                        }
                        if (!empty($config['bottom'])) {
                            $class_names[] = "{$prefix}b-{$config['bottom']}";
                        }
                        if (!empty($config['left'])) {
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
     * Generate all dimension classes from block attributes
     * 
     * @param array $attributes Block attributes containing orbGap, orbPadding, orbMargin
     * @return string All dimension classes combined
     */
    public static function get_all_dimensions_classes($attributes): string
    {
        $gap_classes = self::get_gap_classes($attributes['orbGap'] ?? []);
        $padding_classes = self::get_padding_classes($attributes['orbPadding'] ?? []);
        $margin_classes = self::get_margin_classes($attributes['orbMargin'] ?? []);
        
        $all_classes = trim($gap_classes . ' ' . $padding_classes . ' ' . $margin_classes);
        
        return $all_classes;
    }

    /**
     * Check if block has dimensions support
     * 
     * @param string $block_name Block name (e.g., 'orb/collection')
     * @return bool Whether block supports dimensions
     */
    public static function block_has_dimensions_support($block_name): bool
    {
        $block_type = \WP_Block_Type_Registry::get_instance()->get_registered($block_name);
        
        if (!$block_type || !isset($block_type->supports['orbitools']['dimensions'])) {
            return false;
        }
        
        $dimensions_supports = $block_type->supports['orbitools']['dimensions'];
        return $dimensions_supports && $dimensions_supports !== false && 
               ($dimensions_supports === true || (is_array($dimensions_supports) && count($dimensions_supports) > 0));
    }

    /**
     * Apply dimension classes to existing class string
     * 
     * @param string $existing_classes Existing CSS classes
     * @param array $attributes Block attributes
     * @return string Combined classes with dimensions
     */
    public static function apply_dimensions_classes($existing_classes, $attributes): string
    {
        $dimensions_classes = self::get_all_dimensions_classes($attributes);
        
        if (empty($dimensions_classes)) {
            return $existing_classes;
        }
        
        return trim($existing_classes . ' ' . $dimensions_classes);
    }

    /**
     * Helper function for blocks to easily add dimensions classes
     * Call this in your block's render callback to add dimensions support
     * 
     * @param string $base_classes Your block's base CSS classes
     * @param array $attributes Block attributes from render callback
     * @return string Classes with dimensions added
     */
    public static function add_dimensions($base_classes, $attributes): string
    {
        return self::apply_dimensions_classes($base_classes, $attributes);
    }
}