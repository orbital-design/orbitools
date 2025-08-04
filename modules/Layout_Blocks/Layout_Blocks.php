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
        \add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        \add_action('enqueue_block_editor_assets', [$this, 'enqueue_editor_assets']);
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

    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets()
    {
        $base_css = ORBITOOLS_DIR . 'build/frontend/css/base.css';
        if (file_exists($base_css)) {
            \wp_enqueue_style(
                'orbitools-layout-blocks-frontend',
                ORBITOOLS_URL . 'build/frontend/css/base.css',
                [],
                filemtime($base_css)
            );
        }
    }

    /**
     * Enqueue editor assets
     */
    public function enqueue_editor_assets()
    {
        $editor_css = ORBITOOLS_DIR . 'build/admin/css/editor.css';
        if (file_exists($editor_css)) {
            \wp_enqueue_style(
                'orbitools-layout-blocks-editor',
                ORBITOOLS_URL . 'build/admin/css/editor.css',
                ['wp-edit-blocks'],
                filemtime($editor_css)
            );
        }
        
        $admin_css = ORBITOOLS_DIR . 'build/admin/css/admin.css';
        if (file_exists($admin_css)) {
            \wp_enqueue_style(
                'orbitools-layout-blocks-admin',
                ORBITOOLS_URL . 'build/admin/css/admin.css',
                [],
                filemtime($admin_css)
            );
        }
    }
}