<?php

namespace Orbitools\Blocks\Spacer;

use Orbitools\Core\Abstracts\Module_Base;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Spacer Block
 *
 * Registers and manages the Spacer block for adding responsive spacing between content
 */
class Spacer extends Module_Base
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
        return 'spacer-block';
    }

    /**
     * Get the module's display name
     */
    public function get_name(): string
    {
        return \__('Spacer Block', 'orbitools');
    }

    /**
     * Get the module's description
     */
    public function get_description(): string
    {
        return \__('Responsive spacing block with height controls', 'orbitools');
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
     * Initialize the Spacer block
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
     * Register the Spacer block
     */
    public function register_block(): void
    {
        $block_dir = ORBITOOLS_DIR . 'build/blocks/spacer/';

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