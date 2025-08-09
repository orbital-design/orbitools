<?php

/**
 * Layout Guides Module
 *
 * Development tool that adds layout guides and visual debugging helpers
 * to assist with theme development and debugging.
 *
 * @package    Orbitools
 * @subpackage Modules/Layout_Guides
 * @since      1.0.0
 */

namespace Orbitools\Modules\Layout_Guides;

use Orbitools\Core\Abstracts\Module_Base;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Layout Guides Module Class
 *
 * Main coordinator class for the Layout Guides module that provides
 * visual debugging tools and layout guides for development.
 *
 * @since 1.0.0
 */
class Layout_Guides extends Module_Base
{
    /**
     * Module version
     */
    protected const VERSION = '1.0.0';

    /**
     * Admin handler instance
     *
     * @since 1.0.0
     * @var Admin\Admin
     */
    private $admin;

    /**
     * Guide renderer instance
     *
     * @since 1.0.0
     * @var Core\Guide_Renderer
     */
    private $guide_renderer;

    /**
     * Assets handler instance
     *
     * @since 1.0.0
     * @var Frontend\Assets
     */
    private $assets;

    /**
     * Prevent multiple initialization
     *
     * @since 1.0.0
     * @var bool
     */
    private static $initialized = false;

    /**
     * Initialize the module
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
        return 'layout-guides';
    }

    /**
     * Get the module's display name
     * 
     * @return string
     */
    public function get_name(): string
    {
        return __('Layout Guides', 'orbitools');
    }

    /**
     * Get the module's description
     * 
     * @return string
     */
    public function get_description(): string
    {
        return __('Visual layout guides and grid systems for consistent design alignment.', 'orbitools');
    }

    /**
     * Get module's default settings
     * 
     * @return array
     */
    public function get_default_settings(): array
    {
        return [
            'layout-guides_enabled' => true,
            'layout-guides_show_grid' => true,
            'layout-guides_show_rulers' => false,
            'layout-guides_admin_only' => true
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
        // Always initialize admin (needed for module registration)
        $this->init_admin();

        // Always initialize core (needed for admin previews)
        $this->init_core();

        // Initialize frontend when module is enabled (this method is only called when enabled)
        $this->init_frontend();
    }

    /**
     * Initialize admin functionality
     *
     * @since 1.0.0
     */
    private function init_admin()
    {
        $this->admin = new Admin\Admin();
        $this->admin->init();
    }

    /**
     * Initialize core functionality
     *
     * @since 1.0.0
     */
    private function init_core()
    {
        $this->guide_renderer = new Core\Guide_Renderer();
        $this->guide_renderer->init();
    }

    /**
     * Initialize frontend functionality
     *
     * @since 1.0.0
     */
    private function init_frontend()
    {
        $this->assets = new Frontend\Assets();
        $this->assets->init();
    }


    /**
     * Get admin handler instance
     *
     * @since 1.0.0
     * @return Admin\Admin
     */
    public function get_admin()
    {
        return $this->admin;
    }

    /**
     * Get guide renderer instance
     *
     * @since 1.0.0
     * @return Core\Guide_Renderer
     */
    public function get_guide_renderer()
    {
        return $this->guide_renderer;
    }

    /**
     * Get assets handler instance
     *
     * @since 1.0.0
     * @return Frontend\Assets
     */
    public function get_assets()
    {
        return $this->assets;
    }

}