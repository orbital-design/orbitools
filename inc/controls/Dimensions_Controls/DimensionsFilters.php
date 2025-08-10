<?php
/**
 * Dimensions Filters
 *
 * Automatically applies dimension classes to any block with dimensions support
 * during server-side rendering via WordPress filters.
 *
 * @since 1.0.0
 */

namespace Orbitools\Controls\Dimensions_Controls;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class DimensionsFilters
{
    /**
     * Initialize the dimensions filter system
     */
    public static function init()
    {
        // Hook into block rendering to automatically add dimensions classes
        \add_filter('render_block', [self::class, 'apply_dimensions_to_blocks'], 10, 2);
    }

    /**
     * Automatically apply dimensions classes to blocks during rendering
     * 
     * @param string $block_content The rendered block content
     * @param array  $block         The block data
     * @return string Modified block content with dimensions classes
     */
    public static function apply_dimensions_to_blocks($block_content, $block)
    {
        // Skip if no block name or attributes
        if (empty($block['blockName']) || empty($block['attrs'])) {
            return $block_content;
        }

        // Check if block has dimensions support
        if (!DimensionsRenderer::block_has_dimensions_support($block['blockName'])) {
            return $block_content;
        }

        // Check if block has any dimensions attributes
        $has_dimensions = !empty($block['attrs']['orbGap']) || 
                         !empty($block['attrs']['orbPadding']) || 
                         !empty($block['attrs']['orbMargin']);

        if (!$has_dimensions) {
            return $block_content;
        }

        // Generate dimensions classes
        $dimensions_classes = DimensionsRenderer::get_all_dimensions_classes($block['attrs']);
        
        if (empty($dimensions_classes)) {
            return $block_content;
        }

        // Add classes to the block content
        // Look for the first opening div tag and add our classes
        $pattern = '/^(<div[^>]*class=["\'])([^"\']*)/';
        if (preg_match($pattern, $block_content, $matches)) {
            $existing_classes = $matches[2];
            $new_classes = trim($existing_classes . ' ' . $dimensions_classes);
            $block_content = preg_replace($pattern, '$1' . $new_classes, $block_content, 1);
        }

        return $block_content;
    }
}