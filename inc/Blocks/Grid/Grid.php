<?php

namespace Orbitools\Blocks\Grid;

use Orbitools\Core\Abstracts\Module_Base;
use Orbitools\Controls\Spacings_Controls\SpacingsRenderer;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Grid Block
 *
 * Registers and manages the Grid block — a CSS-Grid container whose cells
 * carry responsive column spans.
 */
class Grid extends Module_Base
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
        return 'grid-block';
    }

    /**
     * Get the module's display name
     */
    public function get_name(): string
    {
        return \__('Grid', 'orbitools');
    }

    /**
     * Get the module's description
     */
    public function get_description(): string
    {
        return \__('CSS-Grid layout container with responsive column spans', 'orbitools');
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
     * Initialize the Grid block
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
     * Register the Grid block
     */
    public function register_block(): void
    {
        $block_dir = ORBITOOLS_DIR . 'build/blocks/grid/';

        if (file_exists($block_dir . 'block.json')) {
            \register_block_type($block_dir, [
                'render_callback' => [$this, 'render_callback']
            ]);
        }
    }

    /**
     * Render callback for Grid block
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
            'columnSystem'  => 12,
            'stackOnMobile' => true,
        ];

        // Merge attributes with defaults
        $attributes = array_merge($defaults, $attributes);

        // Extract attributes
        $column_system  = (int) $attributes['columnSystem'];
        $stack_on_mobile = (bool) $attributes['stackOnMobile'];

        // Let WordPress compose the wrapper attributes, merging our semantic
        // class + the grid column-count custom property with whatever the block
        // supports emit (background / colour / border, plus the has-gap classes
        // from SpacingsRenderer). Passing class + style through here avoids the
        // duplicate `style` attribute a manual rebuild would produce.
        $extra = [
            'class' => SpacingsRenderer::add_spacings('orb-grid', $attributes),
            'style' => '--orb-grid-cols:' . $column_system . ';',
        ];
        if ($stack_on_mobile) {
            // Drives the mobile single-column collapse in CSS.
            $extra['data-stacked'] = 'true';
        }
        $wrapper_attributes = \get_block_wrapper_attributes($extra);

        // Render inner blocks
        $inner_blocks_content = '';
        if (!empty($block->inner_blocks)) {
            foreach ($block->inner_blocks as $inner_block) {
                $inner_blocks_content .= $inner_block->render();
            }
        }

        return sprintf('<div %s>%s</div>', $wrapper_attributes, $inner_blocks_content);
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
