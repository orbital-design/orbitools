<?php

namespace Orbitools\Blocks\Query_Loop;

use Orbitools\Core\Abstracts\Module_Base;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Query Loop Block
 *
 * Registers and manages the Query Loop block for creating and running WP_Queries
 */
class Query_Loop extends Module_Base
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
        return 'query-loop-block';
    }

    /**
     * Get the module's display name
     */
    public function get_name(): string
    {
        return \__('Query Loop Block', 'orbitools');
    }

    /**
     * Get the module's description
     */
    public function get_description(): string
    {
        return \__('Flexible query builder for dynamically displaying posts using a rnage of parameters.', 'orbitools');
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
     * Initialize the Query Loop block
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
     * Register the Query Loop block
     */
    public function register_block(): void
    {
        $block_dir = ORBITOOLS_DIR . 'build/blocks/query-loop/';

        if (file_exists($block_dir . 'block.json')) {
            \register_block_type($block_dir, [
                'render_callback' => [$this, 'render_callback']
            ]);
        }
    }

    /**
     * Render callback for Query Loop block
     *
     * @param array    $attributes Block attributes
     * @param string   $content    Block inner content
     * @param \WP_Block $block      Block instance
     * @return string  Rendered HTML
     */
    public function render_callback(array $attributes, string $content, \WP_Block $block): string
    {
        // Default values - must match block.json defaults
        $defaults = [];

        // Merge attributes with defaults
        $attributes = array_merge($defaults, $attributes);

        // Extract attributes
        $layout_type = $attributes['layoutType'];

        // Get wrapper attributes
        $wrapper_attributes = \get_block_wrapper_attributes();

        // Parse existing class names from wrapper_attributes
        $existing_classes = '';
        if (preg_match('/class=["\']([^"\']*)["\']/', $wrapper_attributes, $matches)) {
            $existing_classes = $matches[1];
        }

        // Check if we need content constraint wrapper for full-width blocks
        $needs_wrapper = ($align === 'full') && $restrict_content_width;
        $is_full_width = strpos($existing_classes, 'alignfull') !== false;

        // Generate semantic data attributes for CSS targeting
        $flex_attributes = $this->generate_flex_attributes($attributes, $is_full_width);

        // Build semantic class names
        $collection_classes = $this->build_collection_classes($layout_type);
        $filtered_classes = $this->filter_wordpress_classes($existing_classes, ['wp-block-orb-query-loop']);
        $base_classes = trim($collection_classes . ' ' . $filtered_classes);

        // Normal output
        $other_attrs = preg_replace('/class=["\'][^"\']*["\']/', '', $wrapper_attributes);
        $content = 'Query Loop';
        return sprintf(
            '<div class="%s">%s</div>',
            \esc_attr($all_classes),
            $content
        );
    }

    /**
     * Build Collection classes
     */
    private function build_collection_classes(string $layout_type, string $base_class = 'orb-query-loop'): string
    {
        $classes = [$base_class];
        $classes[] = $base_class . '--' . $layout_type;
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
        $filtered = array_filter($classes, function ($class) use ($classes_to_filter) {
            return !empty($class) && !in_array($class, $classes_to_filter);
        });

        return implode(' ', $filtered);
    }

    /**
     * Generate flex attributes
     */
    private function generate_flex_attributes(array $attributes, bool $is_full_width = false): array
    {
        $data_attrs = [];

        // Extract values with defaults
        $direction = $attributes['flexDirection'] ?? 'row';
        $flex_wrap = $attributes['flexWrap'] ?? 'nowrap';
        $align_items = $attributes['alignItems'] ?? 'stretch';
        $justify_content = $attributes['justifyContent'] ?? 'flex-start';
        $restrict_content_width = $attributes['restrictContentWidth'] ?? false;
        $stack_on_mobile = $attributes['stackOnMobile'] ?? true;
        $item_width = $attributes['itemWidth'] ?? 'fit';
        $column_system = $attributes['columnSystem'] ?? 12;

        // Value mappings
        $align_mappings = [
            'flex-start' => 'start',
            'flex-end' => 'end',
            'center' => 'center'
        ];

        $justify_mappings = [
            'flex-start' => 'start',
            'flex-end' => 'end',
            'center' => 'center',
            'space-between' => 'between',
            'space-around' => 'around',
            'space-evenly' => 'evenly'
        ];

        $grid_system_mappings = [
            5 => 'penta',
            12 => 'dodeca'
        ];

        // Flex flow: Only add if not default
        $is_default_flow = $direction === 'row' && $flex_wrap === 'nowrap';
        if (!$is_default_flow) {
            $data_attrs['data-flow'] = $direction . ' ' . $flex_wrap;
        }

        // Cross-axis alignment
        if ($align_items !== 'stretch') {
            $data_attrs['data-align'] = $align_mappings[$align_items] ?? $align_items;
        }

        // Main-axis alignment
        if ($justify_content !== 'flex-start') {
            $data_attrs['data-justify'] = $justify_mappings[$justify_content] ?? $justify_content;
        }

        // Content constraint
        if ($restrict_content_width && $is_full_width) {
            $data_attrs['data-constrain'] = 'true';
        }

        // Mobile stacking
        if ($stack_on_mobile) {
            $data_attrs['data-stacked'] = 'true';
        }

        // Layout mode
        if ($item_width !== 'fit') {
            if ($item_width === 'custom') {
                $data_attrs['data-layout'] = $grid_system_mappings[$column_system] ?? 'custom';
            } else {
                $data_attrs['data-layout'] = $item_width; // 'equal'
            }
        }

        return $data_attrs;
    }

    /**
     * Get default settings
     */
    public function get_default_settings(): array
    {
        return [];
    }
}
