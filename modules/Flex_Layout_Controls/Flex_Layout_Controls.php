<?php

/**
 * Flex Layout Controls Module
 *
 * Main coordinator class for the Flex Layout Controls module. This class acts as
 * the primary entry point and orchestrates the various components of the module.
 *
 * @package    Orbitools
 * @subpackage Modules/Flex_Layout_Controls
 * @since      1.0.0
 */

namespace Orbitools\Modules\Flex_Layout_Controls;

use Orbitools\Modules\Flex_Layout_Controls\Admin\Admin;
use Orbitools\Modules\Flex_Layout_Controls\Admin\Settings;
use Orbitools\Modules\Flex_Layout_Controls\Core\CSS_Generator;
use Orbitools\Modules\Flex_Layout_Controls\Core\Block_Helper;
use Orbitools\Modules\Flex_Layout_Controls\Frontend\Block_Editor;
use Orbitools\Modules\Flex_Layout_Controls\Frontend\Assets;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Flex Layout Controls Module Class
 *
 * Coordinates all aspects of the Flex Layout Controls functionality by managing
 * the interaction between admin, core, and frontend components.
 *
 * @since 1.0.0
 */
class Flex_Layout_Controls
{
    /**
     * Module version
     *
     * @since 1.0.0
     * @var string
     */
    const VERSION = '1.0.0';

    /**
     * Module slug identifier
     *
     * @since 1.0.0
     * @var string
     */
    const MODULE_SLUG = 'flex-layout-controls';

    /**
     * Admin handler instance
     *
     * @since 1.0.0
     * @var Admin
     */
    private $admin;

    /**
     * Block editor integration instance
     *
     * @since 1.0.0
     * @var Block_Editor
     */
    private $block_editor;

    /**
     * CSS generator instance
     *
     * @since 1.0.0
     * @var CSS_Generator
     */
    private $css_generator;

    /**
     * Assets manager instance
     *
     * @since 1.0.0
     * @var Assets
     */
    private $assets;

    /**
     * Whether the module has been initialized
     *
     * @since 1.0.0
     * @var bool
     */
    private static $initialized = false;

    /**
     * Initialize the Flex Layout Controls module
     *
     * Sets up the module by initializing admin functionality and,
     * if the module is enabled, the core and frontend components.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        // Prevent multiple initialization
        if (self::$initialized) {
            return;
        }

        // Always initialize admin functionality for module registration
        $this->admin = new Admin();
        
        // Initialize Settings class for AJAX handlers
        Settings::init();

        // Always initialize CSS generator for CSS output
        $this->css_generator = new CSS_Generator();

        // Always initialize frontend functionality so controls are available
        // The controls will check module enabled status internally
        $this->init_frontend_functionality();

        self::$initialized = true;
    }

    /**
     * Initialize frontend module functionality
     *
     * Sets up frontend integration when the module is enabled.
     *
     * @since 1.0.0
     */
    private function init_frontend_functionality(): void
    {
        // Initialize asset management
        $this->assets = new Assets();

        // Initialize block editor integration
        $this->block_editor = new Block_Editor();

        // Set up additional hooks
        $this->setup_hooks();
    }

    /**
     * Set up WordPress hooks
     *
     * @since 1.0.0
     */
    private function setup_hooks(): void
    {
        // Add any additional hooks that require the full module to be loaded
        // For example, CSS output, cache clearing, etc.
    }

    /**
     * Get the block editor integration instance
     *
     * @since 1.0.0
     * @return Block_Editor|null Block editor instance or null if not initialized.
     */
    public function get_block_editor(): ?Block_Editor
    {
        return $this->block_editor;
    }

    /**
     * Get the admin handler instance
     *
     * @since 1.0.0
     * @return Admin Admin instance.
     */
    public function get_admin(): Admin
    {
        return $this->admin;
    }

    /**
     * Get the CSS generator instance
     *
     * @since 1.0.0
     * @return CSS_Generator|null CSS generator instance or null if not initialized.
     */
    public function get_css_generator(): ?CSS_Generator
    {
        return $this->css_generator;
    }

    /**
     * Get the assets manager instance
     *
     * @since 1.0.0
     * @return Assets|null Assets manager instance or null if not initialized.
     */
    public function get_assets(): ?Assets
    {
        return $this->assets;
    }

    /**
     * Check if the module is fully initialized
     *
     * @since 1.0.0
     * @return bool True if core functionality is loaded, false otherwise.
     */
    public function is_fully_initialized(): bool
    {
        return $this->css_generator !== null;
    }

    /**
     * Clear all flex layout related caches
     *
     * @since 1.0.0
     */
    public function clear_flex_cache(): void
    {
        // Use CSS generator's cache clearing if available
        if ($this->css_generator) {
            $this->css_generator->clear_cache();
        } else {
            // Fallback for when CSS generator not initialized
            global $wpdb;
            $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_orbitools_flex_css_%'");
            $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_orbitools_flex_css_%'");
        }

        // Clear WordPress object cache
        wp_cache_delete('orbitools_flex_layout', 'theme_json');
    }
}