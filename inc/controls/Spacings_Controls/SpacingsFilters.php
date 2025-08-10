<?php
/**
 * Spacings Filters
 *
 * Automatically applies spacing classes to any block with spacings support
 * during server-side rendering via WordPress filters.
 *
 * @since 1.0.0
 */

namespace Orbitools\Controls\Spacings_Controls;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class SpacingsFilters
{
    /**
     * Initialize the spacings filter system
     */
    public static function init()
    {
        // Hook into block rendering to automatically add spacings classes
        \add_filter('render_block', [self::class, 'apply_spacings_to_blocks'], 10, 2);
    }

    /**
     * Automatically apply spacings classes to blocks during rendering
     * 
     * @param string $block_content The rendered block content
     * @param array  $block         The block data
     * @return string Modified block content with spacings classes
     */
    public static function apply_spacings_to_blocks($block_content, $block)
    {
        // Skip if no block name or attributes
        if (empty($block['blockName']) || empty($block['attrs'])) {
            return $block_content;
        }

        // Check if block has spacings support
        if (!SpacingsRenderer::block_has_spacings_support($block['blockName'])) {
            return $block_content;
        }

        // Check if block has any spacings attributes
        $has_spacings = !empty($block['attrs']['orbGap']) || 
                         !empty($block['attrs']['orbPadding']) || 
                         !empty($block['attrs']['orbMargin']);

        if (!$has_spacings) {
            return $block_content;
        }

        // Generate spacings classes
        $spacings_classes = SpacingsRenderer::get_all_spacings_classes($block['attrs']);
        
        if (empty($spacings_classes)) {
            return $block_content;
        }

        // Add classes to the block content
        // Look for the first opening div tag and add our classes
        $pattern = '/^(<div[^>]*class=["\'])([^"\']*)/';
        if (preg_match($pattern, $block_content, $matches)) {
            $existing_classes = $matches[2];
            $new_classes = trim($existing_classes . ' ' . $spacings_classes);
            $block_content = preg_replace($pattern, '$1' . $new_classes, $block_content, 1);
        }

        return $block_content;
    }
}