<?php

namespace Orbitools\Blocks\Collection;

use Orbitools\Core\Abstracts\Module_Base;
use Orbitools\Controls\Spacings_Controls\SpacingsRenderer;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Collection Block
 *
 * Registers and manages the Collection block for creating flexible layout containers
 */
class Collection extends Module_Base
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
        return 'collection-block';
    }

    /**
     * Get the module's display name
     */
    public function get_name(): string
    {
        return \__('Collection Block', 'orbitools');
    }

    /**
     * Get the module's description
     */
    public function get_description(): string
    {
        return \__('Flexible layout container block for organizing content', 'orbitools');
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
     * Initialize the Collection block
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
     * Register the Collection block
     */
    public function register_block(): void
    {
        $block_dir = ORBITOOLS_DIR . 'build/blocks/collection/';

        if (file_exists($block_dir . 'block.json')) {
            \register_block_type($block_dir, [
                'render_callback' => [$this, 'render_callback']
            ]);
        }
    }

    /**
     * Render callback for Collection block
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
            'layoutType' => 'row',
            'itemWidth' => 'fit',
            'columnSystem' => 12,
            'columnCount' => 2,
            'flexDirection' => 'row',
            'flexWrap' => 'nowrap',
            'alignItems' => 'stretch',
            'justifyContent' => 'flex-start',
            'restrictContentWidth' => false,
            'stackOnMobile' => true,
            'align' => '',
        ];

        // Merge attributes with defaults
        $attributes = array_merge($defaults, $attributes);

        // Extract attributes
        $layout_type = $attributes['layoutType'];
        $align = $attributes['align'] ?? '';
        $restrict_content_width = $attributes['restrictContentWidth'];

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

        // Remove WordPress default class while preserving other classes
        $filtered_classes = $this->filter_wordpress_classes($existing_classes, ['wp-block-orb-collection']);

        // Generate semantic data attributes for CSS targeting
        $flex_attributes = $this->generate_flex_attributes($attributes, $is_full_width);

        // Build semantic class names
        $collection_classes = $this->build_collection_classes($layout_type);

        // Combine classes and add spacings
        $base_classes = trim($collection_classes . ' ' . $filtered_classes);
        $all_classes = SpacingsRenderer::add_spacings($base_classes, $attributes);

        // Format data attributes as HTML attributes
        $data_attrs_html = '';
        foreach ($flex_attributes as $attr => $value) {
            $data_attrs_html .= ' ' . \esc_attr($attr) . '="' . \esc_attr($value) . '"';
        }

        // Render inner blocks
        $inner_blocks_content = '';
        if (!empty($block->inner_blocks)) {
            foreach ($block->inner_blocks as $inner_block) {
                $inner_blocks_content .= $inner_block->render();
            }
        }

        if ($needs_wrapper) {
            // Full-width with content constraint
            $wrapper_class_attr = !empty($filtered_classes) ? ' class="' . \esc_attr($filtered_classes) . '"' : '';

            // Extract other attributes but replace class
            $other_attrs = preg_replace('/class=["\'][^"\']*["\']/', '', $wrapper_attributes);
            $other_attrs = trim($other_attrs);

            return sprintf(
                '<div%s%s><div class="%s"%s>%s</div></div>',
                $other_attrs ? ' ' . $other_attrs : '',
                $wrapper_class_attr,
                \esc_attr($all_classes), // Inner div gets collection classes + spacing classes
                $data_attrs_html,
                $inner_blocks_content
            );
        } else {
            // Normal output
            $other_attrs = preg_replace('/class=["\'][^"\']*["\']/', '', $wrapper_attributes);
            $other_attrs = trim($other_attrs);

            return sprintf(
                '<div%s class="%s"%s>%s</div>',
                $other_attrs ? ' ' . $other_attrs : '',
                \esc_attr($all_classes),
                $data_attrs_html,
                $inner_blocks_content
            );
        }
    }

    /**
     * Build Collection classes
     */
    private function build_collection_classes(string $layout_type, string $base_class = 'orb-collection'): string
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