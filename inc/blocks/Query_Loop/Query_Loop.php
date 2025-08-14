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
        // Get wrapper attributes
        $wrapper_attributes = \get_block_wrapper_attributes([
            'class' => 'orb-query-loop'
        ]);

        // For now, just output placeholder content
        return sprintf(
            '<div %s><p>%s</p></div>',
            $wrapper_attributes,
            \esc_html__('Query Content', 'orbitools')
        );
    }


    /**
     * Get default settings
     */
    public function get_default_settings(): array
    {
        return [];
    }
}
