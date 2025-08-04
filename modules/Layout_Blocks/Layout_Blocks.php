<?php

namespace Orbitools\Modules\Layout_Blocks;

use Orbitools\Abstracts\Module_Base;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Layout Blocks Module
 *
 * Registers and manages the layout blocks system (collection and entry blocks)
 */
class Layout_Blocks extends Module_Base
{
    /**
     * Module version
     */
    protected const VERSION = '1.0.0';
    /**
     * Initialize the Layout Blocks module
     *
     * Sets up the module by calling the parent constructor which handles
     * the initialization logic via the Module_Base system.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        // Call parent constructor which handles initialization
        parent::__construct();
    }

    /**
     * Get the module's unique slug
     * 
     * @return string
     */
    public function get_slug(): string
    {
        return 'layout-blocks';
    }

    /**
     * Get the module's display name
     * 
     * @return string
     */
    public function get_name(): string
    {
        return __('Layout Blocks', 'orbitools');
    }

    /**
     * Get the module's description
     * 
     * @return string
     */
    public function get_description(): string
    {
        return __('Custom Gutenberg blocks for advanced layout and content organization.', 'orbitools');
    }

    /**
     * Get module's default settings
     * 
     * @return array
     */
    public function get_default_settings(): array
    {
        return [
            'layout-blocks_enabled' => true
        ];
    }

    /**
     * Initialize the module
     * Called by Module_Base when module should be initialized
     * 
     * @return void
     */
    public function init(): void
    {
        // Register blocks immediately (we're already in the init hook)
        $this->register_blocks();
    }

    /**
     * Register blocks
     */
    public function register_blocks()
    {
        $blocks_dir = ORBITOOLS_DIR . 'build/blocks/';

        // Register Collection block
        if (file_exists($blocks_dir . 'collection/block.json')) {
            register_block_type($blocks_dir . 'collection/');
        }

        // Register Entry block
        if (file_exists($blocks_dir . 'entry/block.json')) {
            register_block_type($blocks_dir . 'entry/');
        }
    }
}
