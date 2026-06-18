<?php

namespace Orbitools\Blocks\Grid_Cell;

use Orbitools\Core\Abstracts\Module_Base;
use Orbitools\Controls\Spacings_Controls\SpacingsRenderer;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Grid Cell Block
 *
 * Registers and manages the Grid Cell block — an individual cell within a
 * Grid, carrying a responsive column-span attribute.
 */
class Grid_Cell extends Module_Base
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
        return 'grid-cell-block';
    }

    /**
     * Get the module's display name
     */
    public function get_name(): string
    {
        return \__('Cell', 'orbitools');
    }

    /**
     * Get the module's description
     */
    public function get_description(): string
    {
        return \__('Individual cell block for use within a Grid', 'orbitools');
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
     * Initialize the Grid Cell block
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
     * Register the Grid Cell block
     */
    public function register_block(): void
    {
        $block_dir = ORBITOOLS_DIR . 'build/blocks/grid-cell/';

        if (file_exists($block_dir . 'block.json')) {
            \register_block_type($block_dir, [
                'render_callback' => [$this, 'render_callback']
            ]);
        }
    }

    /**
     * Render callback for Grid Cell block
     *
     * @param array     $attributes Block attributes
     * @param string    $content    Block inner content
     * @param \WP_Block $block      Block instance
     * @return string   Rendered HTML
     */
    public function render_callback(array $attributes, string $content, \WP_Block $block): string
    {
        // Default values - must match block.json defaults
        $defaults = [
            'span'     => [],
            'rowSpan'  => [],
            'colStart' => [],
        ];

        // Merge attributes with defaults
        $attributes = array_merge($defaults, $attributes);

        // Extract attributes
        $span      = is_array($attributes['span']) ? $attributes['span'] : [];
        $row_span  = is_array($attributes['rowSpan']) ? $attributes['rowSpan'] : [];
        $col_start = is_array($attributes['colStart']) ? $attributes['colStart'] : [];

        // Get wrapper attributes
        $wrapper_attributes = \get_block_wrapper_attributes();

        // Parse existing class names from wrapper_attributes
        $existing_classes = '';
        if (preg_match('/class=["\']([^"\']*)["\']/', $wrapper_attributes, $matches)) {
            $existing_classes = $matches[1];
        }

        // Remove WordPress default class while preserving other classes
        $filtered_classes = $this->filter_wordpress_classes($existing_classes, ['wp-block-orb-grid-cell']);

        // Build semantic class names (base + responsive span / row-span / col-start overrides)
        $cell_classes = $this->build_cell_classes($span, $row_span, $col_start);

        // Combine classes and add spacings
        $base_classes = trim($cell_classes . ' ' . $filtered_classes);
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

        return sprintf(
            '<div%s class="%s">%s</div>',
            $other_attrs ? ' ' . $other_attrs : '',
            \esc_attr($all_classes),
            $inner_blocks_content
        );
    }

    /**
     * Build Grid Cell classes from the responsive span / row-span attributes.
     *
     * base   → orb-grid-cell--{modifier}-{n}
     * tablet → tablet:orb-grid-cell--{modifier}-{n}
     * mobile → mobile:orb-grid-cell--{modifier}-{n}
     *
     * where {modifier} is `span` (columns) or `row-span` (rows). Only slugs
     * that carry a positive value emit a class; a cell with no span class
     * defaults to a single grid track.
     */
    private function build_cell_classes(array $span, array $row_span = [], array $col_start = [], string $base_class = 'orb-grid-cell'): string
    {
        $classes = [$base_class];
        $this->append_axis_classes($classes, $span, 'span', $base_class);
        $this->append_axis_classes($classes, $col_start, 'col-start', $base_class);
        $this->append_axis_classes($classes, $row_span, 'row-span', $base_class);

        return implode(' ', $classes);
    }

    /**
     * Append responsive axis classes (column span or row span) to $classes.
     */
    private function append_axis_classes(array &$classes, array $values, string $modifier, string $base_class): void
    {
        foreach ($values as $slug => $value) {
            if ($value === null || $value === '' || (int) $value <= 0) {
                continue;
            }

            $n = (int) $value;
            $classes[] = ($slug === 'base')
                ? $base_class . '--' . $modifier . '-' . $n
                : $slug . ':' . $base_class . '--' . $modifier . '-' . $n;
        }
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
        $filtered = array_filter($classes, function ($class) use ($classes_to_filter) {
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
