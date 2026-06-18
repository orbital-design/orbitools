<?php
/**
 * Content Width Renderer
 *
 * PHP twin of core/utils/content-width.js — resolves a block's effective
 * content width and the data-constrain attribute value for server-side render
 * callbacks. Single source of truth for the back-compat rule (legacy
 * `restrictContentWidth` boolean true => 'standard').
 *
 * @since 1.0.0
 */

namespace Orbitools\Controls\Content_Width_Controls;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Content_Width_Renderer
{
    /**
     * Valid stored widths.
     */
    public const VALUES = ['full', 'wide', 'standard'];

    /**
     * Resolve a block's effective content width.
     *
     * @param array $attributes Block attributes.
     * @return string One of 'full' | 'wide' | 'standard'.
     */
    public static function resolve(array $attributes): string
    {
        $w = $attributes['orbContentWidth'] ?? null;
        if (in_array($w, self::VALUES, true)) {
            return $w;
        }

        // Legacy Row/Grid boolean: true meant "constrain to content width".
        if (!empty($attributes['restrictContentWidth'])) {
            return 'standard';
        }

        // Default: constrain to the content width. Full-bleed content is opt-in
        // via an explicit orbContentWidth: 'full'. The attribute is
        // JS-registered (absent server-side), so this fallback is what new
        // blocks resolve to here — matching the editor.
        return 'standard';
    }

    /**
     * Does this block need the constrain wrapper?
     *
     * Only full-aligned blocks constrained to something other than 'full'.
     *
     * @param array $attributes Block attributes (should include `align`).
     * @return bool
     */
    public static function needs_constraint(array $attributes): bool
    {
        $align = $attributes['align'] ?? '';
        return $align === 'full' && self::resolve($attributes) !== 'full';
    }

    /**
     * The data-constrain attribute value for these attributes.
     *
     * @param array $attributes Block attributes.
     * @return string 'standard' | 'wide', or '' when not constrained.
     */
    public static function constrain_value(array $attributes): string
    {
        $w = self::resolve($attributes);
        return ($w === 'wide' || $w === 'standard') ? $w : '';
    }

    /**
     * Check if a block declares content-width support.
     *
     * @param string $block_name Block name (e.g. 'orb/collection').
     * @return bool
     */
    public static function block_has_support(string $block_name): bool
    {
        $block_type = \WP_Block_Type_Registry::get_instance()->get_registered($block_name);

        if (!$block_type || !isset($block_type->supports['orbitools']['contentWidth'])) {
            return false;
        }

        return (bool) $block_type->supports['orbitools']['contentWidth'];
    }
}
