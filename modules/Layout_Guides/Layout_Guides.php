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
class Layout_Guides
{
    /**
     * Module version
     *
     * @since 1.0.0
     * @var string
     */
    const VERSION = '1.0.0';

    /**
     * Module slug
     *
     * @since 1.0.0
     * @var string
     */
    const SLUG = 'layout_guides';

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
        if (self::$initialized) {
            return;
        }

        self::$initialized = true;

        // Always initialize admin (needed for module registration)
        $this->init_admin();

        // Always initialize core (needed for admin previews)
        $this->init_core();

        // Only initialize frontend when module is enabled
        if ($this->is_enabled()) {
            $this->init_frontend();
        }
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
     * Check if module is enabled
     *
     * @since 1.0.0
     * @return bool
     */
    public function is_enabled()
    {
        $settings = get_option('orbitools_settings', array());
        return !empty($settings['layout_guides_enabled']);
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

    /**
     * Get module version
     *
     * @since 1.0.0
     * @return string
     */
    public function get_version()
    {
        return self::VERSION;
    }

    /**
     * Get module slug
     *
     * @since 1.0.0
     * @return string
     */
    public function get_slug()
    {
        return self::SLUG;
    }
}