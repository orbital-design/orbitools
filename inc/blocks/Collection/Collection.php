<?php

namespace Orbitools\Blocks\Collection;

use Orbitools\Core\Abstracts\Module_Base;

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
            \register_block_type($block_dir);
        }
    }

    /**
     * Get default settings
     */
    public function get_default_settings(): array
    {
        return [];
    }
}