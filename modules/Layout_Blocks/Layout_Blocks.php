<?php

namespace Orbitools\Modules\Layout_Blocks;

/**
 * Layout Blocks Module
 *
 * Registers and manages the layout blocks system (collection and entry blocks)
 */
class Layout_Blocks
{
    /**
     * Initialize the module
     */
    public function __construct()
    {
        \add_action('init', [$this, 'register_blocks']);
    }

    /**
     * Register blocks
     */
    public function register_blocks()
    {
        $blocks_dir = ORBITOOLS_DIR . 'build/blocks/';

        // Register Collection block
        if (file_exists($blocks_dir . 'collection/block.json')) {
            \register_block_type($blocks_dir . 'collection/');
        }

        // Register Entry block
        if (file_exists($blocks_dir . 'entry/block.json')) {
            \register_block_type($blocks_dir . 'entry/');
        }
    }
}
