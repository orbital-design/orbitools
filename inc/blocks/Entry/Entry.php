<?php

namespace Orbitools\Blocks\Entry;

use Orbitools\Core\Abstracts\Module_Base;
use Orbitools\Controls\Spacings_Controls\SpacingsRenderer;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Entry Block
 *
 * Registers and manages the Entry block for individual content items within collections
 */
class Entry extends Module_Base
{
    /**
     * Module version
     */
    protected const VERSION = '1.0.0';

    /**
     * Get the module's unique slug identifier
     */
    public function get_slug(): string
    {
        return 'entry-block';
    }

    /**
     * Get the module's display name
     */
    public function get_name(): string
    {
        return \__('Entry Block', 'orbitools');
    }

    /**
     * Get the module's description
     */
    public function get_description(): string
    {
        return \__('Individual content item block for use within collections', 'orbitools');
    }

    /**
     * Get the module's version
     */
    public function get_version(): string
    {
        return self::VERSION;
    }

    /**
     * Check if the module is currently enabled
     */
    public function is_enabled(): bool
    {
        return true;
    }

    /**
     * Initialize the Entry block
     */
    public function init(): void
    {
        // Prevent multiple registrations
        static $registered = false;
        if ($registered) {
            return;
        }
        $registered = true;
        
        // Register immediately if init has already fired, otherwise hook into it
        if (\did_action('init')) {
            $this->register_block();
        } else {
            \add_action('init', [$this, 'register_block']);
        }
    }

    /**
     * Register the Entry block
     */
    public function register_block(): void
    {
        $block_dir = ORBITOOLS_DIR . 'build/blocks/entry/';

        if (file_exists($block_dir . 'block.json')) {
            \register_block_type($block_dir, [
                'render_callback' => [$this, 'render_callback']
            ]);
        }
    }

    /**
     * Render callback for Entry block
     * 
     * @param array    $attributes Block attributes
     * @param string   $content    Block inner content 
     * @param \WP_Block $block      Block instance
     * @return string  Rendered HTML
     */
    public function render_callback(array $attributes, string $content, \WP_Block $block): string
    {
        // Default values - must match block.json defaults
        $defaults = [
            'width' => '',
            'parentItemWidth' => 'equal'
        ];

        // Merge attributes with defaults
        $attributes = array_merge($defaults, $attributes);

        // Extract attributes
        $width = $attributes['width'];
        $parent_item_width = $attributes['parentItemWidth'];

        // Get context from parent Collection block
        $context = $block->context ?? [];
        $parent_item_width_from_context = $context['orb/itemWidth'] ?? $parent_item_width;

        // Get wrapper attributes
        $wrapper_attributes = \get_block_wrapper_attributes();

        // Parse existing class names from wrapper_attributes 
        $existing_classes = '';
        if (preg_match('/class=["\']([^"\']*)["\']/', $wrapper_attributes, $matches)) {
            $existing_classes = $matches[1];
        }

        // Remove WordPress default class while preserving other classes
        $filtered_classes = $this->filter_wordpress_classes($existing_classes, ['wp-block-orb-entry']);

        // Conditionally include width classes based on parent layout settings
        $should_output_width_class = ($parent_item_width_from_context === 'custom') && !empty($width);

        // Build semantic class names
        $entry_classes = $this->build_entry_classes($width, $should_output_width_class);

        // Combine classes and add spacings  
        $base_classes = trim($entry_classes . ' ' . $filtered_classes);
        $all_classes = SpacingsRenderer::add_spacings($base_classes, $attributes);

        // Extract other attributes but replace class
        $other_attrs = preg_replace('/class=["\'][^"\']*["\']/', '', $wrapper_attributes);
        $other_attrs = trim($other_attrs);

        // Render inner blocks
        $inner_blocks_content = '';
        if (!empty($block->inner_blocks)) {
            foreach ($block->inner_blocks as $inner_block) {
                $inner_blocks_content .= $inner_block->render();
            }
        }

        // Render the block
        return sprintf(
            '<div%s class="%s">%s</div>',
            $other_attrs ? ' ' . $other_attrs : '',
            \esc_attr($all_classes),
            $inner_blocks_content
        );
    }

    /**
     * Build Entry classes
     */
    private function build_entry_classes(string $width = '', bool $should_include_width = true, string $base_class = 'orb-entry'): string
    {
        $classes = [$base_class];
        
        // Only add width class if enabled and width is provided
        if ($should_include_width && !empty($width)) {
            $classes[] = $base_class . '--' . $width;
        }
        
        return implode(' ', $classes);
    }

    /**
     * Filter WordPress classes
     */
    private function filter_wordpress_classes(string $class_names, array $classes_to_filter = []): string
    {
        if (empty($class_names)) {
            return '';
        }
        
        $classes = explode(' ', $class_names);
        $filtered = array_filter($classes, function($class) use ($classes_to_filter) {
            return !empty($class) && !in_array($class, $classes_to_filter);
        });
        
        return implode(' ', $filtered);
    }

    /**
     * Get default settings
     */
    public function get_default_settings(): array
    {
        return [];
    }
}