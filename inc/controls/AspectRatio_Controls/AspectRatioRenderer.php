<?php
/**
 * Aspect Ratio Renderer
 *
 * Generates responsive aspect ratio CSS classes in PHP render callbacks.
 * Mirrors the JavaScript class generation for server-side rendering.
 *
 * @since 1.4.0
 */

namespace Orbitools\Controls\AspectRatio_Controls;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class AspectRatioRenderer
{
    /**
     * Generate responsive aspect ratio classes from attribute data
     *
     * @param array $aspectRatio Responsive aspect ratio data e.g. { base: '16-9', sm: '1-1' }
     * @return string Generated classes
     */
    public static function get_aspect_ratio_classes($aspectRatio): string
    {
        if (!$aspectRatio || !is_array($aspectRatio)) {
            return '';
        }

        $classes = ['has-aspect-ratio'];

        foreach ($aspectRatio as $breakpoint => $slug) {
            if ($slug === null || $slug === '' || $slug === false) {
                continue;
            }

            if ($breakpoint === 'base') {
                $classes[] = "has-aspect-ratio--{$slug}";
            } else {
                $classes[] = "{$breakpoint}:has-aspect-ratio--{$slug}";
            }
        }

        // Only return classes if we have modifiers (not just the base class)
        return count($classes) > 1 ? implode(' ', $classes) : '';
    }

    /**
     * Check if block has aspect ratio support
     *
     * @param string $block_name Block name (e.g., 'orb/group')
     * @return bool
     */
    public static function block_has_aspect_ratio_support(string $block_name): bool
    {
        $block_type = \WP_Block_Type_Registry::get_instance()->get_registered($block_name);

        if (!$block_type || !isset($block_type->supports['orbitools']['aspectRatio'])) {
            return false;
        }

        $supports = $block_type->supports['orbitools']['aspectRatio'];
        return $supports && $supports !== false &&
               ($supports === true || (is_array($supports) && count($supports) > 0));
    }

    /**
     * Apply aspect ratio classes to existing class string
     *
     * @param string $existing_classes Existing CSS classes
     * @param array $attributes Block attributes
     * @return string Combined classes
     */
    public static function apply_aspect_ratio_classes(string $existing_classes, array $attributes): string
    {
        $ar_classes = self::get_aspect_ratio_classes($attributes['orbAspectRatio'] ?? []);

        if (empty($ar_classes)) {
            return $existing_classes;
        }

        return trim($existing_classes . ' ' . $ar_classes);
    }

    /**
     * Helper for blocks to easily add aspect ratio classes in render callbacks
     *
     * @param string $base_classes Block's base CSS classes
     * @param array $attributes Block attributes from render callback
     * @return string Classes with aspect ratio added
     */
    public static function add_aspect_ratio(string $base_classes, array $attributes): string
    {
        return self::apply_aspect_ratio_classes($base_classes, $attributes);
    }
}
