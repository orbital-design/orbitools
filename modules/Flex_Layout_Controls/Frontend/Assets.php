<?php

/**
 * Flex Layout Controls Asset Management
 *
 * Handles loading and management of CSS and JavaScript assets for the Flex Layout Controls module.
 * This class centralizes all asset-related functionality for better organization.
 *
 * @package    Orbitools
 * @subpackage Modules/Flex_Layout_Controls/Frontend
 * @since      1.0.0
 */

namespace Orbitools\Modules\Flex_Layout_Controls\Frontend;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Assets Management Class
 *
 * Manages CSS and JavaScript asset loading for the Flex Layout Controls module.
 *
 * @since 1.0.0
 */
class Assets
{
    /**
     * Module version
     *
     * @since 1.0.0
     * @var string
     */
    const VERSION = '1.0.0';

    /**
     * Asset base URL
     *
     * @since 1.0.0
     * @var string
     */
    private $asset_url;

    /**
     * Initialize Assets management
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->asset_url = ORBITOOLS_URL . 'modules/Flex_Layout_Controls/';
        
        // Hook into WordPress asset loading
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }

    /**
     * Enqueue frontend assets
     *
     * @since 1.0.0
     */
    public function enqueue_frontend_assets(): void
    {
        // Only load frontend assets if needed
        if (!$this->should_load_frontend_assets()) {
            return;
        }

        $this->enqueue_frontend_styles();
    }

    /**
     * Enqueue admin assets
     *
     * @since 1.0.0
     * @param string $hook Current admin page hook.
     */
    public function enqueue_admin_assets(string $hook): void
    {
        // Only load on relevant admin pages
        if (!$this->should_load_admin_assets($hook)) {
            return;
        }

        $this->enqueue_admin_styles();
        $this->enqueue_admin_scripts($hook);
    }

    /**
     * Enqueue frontend styles
     *
     * @since 1.0.0
     */
    private function enqueue_frontend_styles(): void
    {
        // Check if frontend CSS file exists
        $css_file = $this->asset_url . 'css/frontend.css';
        $css_path = ORBITOOLS_DIR . 'modules/Flex_Layout_Controls/css/frontend.css';

        if (file_exists($css_path)) {
            wp_enqueue_style(
                'orbitools-flex-layout-controls-frontend',
                $css_file,
                array(),
                self::VERSION
            );
        }
    }

    /**
     * Enqueue admin styles
     *
     * @since 1.0.0
     */
    private function enqueue_admin_styles(): void
    {
        // Check if admin CSS file exists
        $css_file = $this->asset_url . 'css/admin.css';
        $css_path = ORBITOOLS_DIR . 'modules/Flex_Layout_Controls/css/admin.css';

        if (file_exists($css_path)) {
            wp_enqueue_style(
                'orbitools-flex-layout-controls-admin',
                $css_file,
                array(),
                self::VERSION
            );
        }
    }

    /**
     * Enqueue admin scripts
     *
     * @since 1.0.0
     * @param string $hook Current admin page hook.
     */
    private function enqueue_admin_scripts(string $hook): void
    {
        // Enqueue alignment icons first (needed by editor controls)
        $this->enqueue_script(
            'orbitools-flex-alignment-icons',
            'js/alignment-icons.js',
            array(),
            true
        );

        // Only load admin scripts on orbitools admin pages
        if (strpos($hook, 'orbitools') !== false) {
            $this->enqueue_script(
                'orbitools-flex-layout-controls-admin',
                'js/admin-flex-controls.js',
                array('orbitools-flex-alignment-icons'),
                true
            );
        }
    }

    /**
     * Check if frontend assets should be loaded
     *
     * @since 1.0.0
     * @return bool True if should load, false otherwise.
     */
    private function should_load_frontend_assets(): bool
    {
        // Don't load on admin pages
        if (is_admin()) {
            return false;
        }

        // Add additional logic here as needed
        // For example, check if any blocks on the page use flex layout controls
        
        return true;
    }

    /**
     * Check if admin assets should be loaded
     *
     * @since 1.0.0
     * @param string $hook Current admin page hook.
     * @return bool True if should load, false otherwise.
     */
    private function should_load_admin_assets(string $hook): bool
    {
        // Load on orbitools admin pages
        if (strpos($hook, 'orbitools') !== false) {
            return true;
        }

        // Load on post editor pages
        if (in_array($hook, array('post.php', 'post-new.php'))) {
            return true;
        }

        // Load on widgets page
        if ($hook === 'widgets.php') {
            return true;
        }

        // Load on customizer
        if ($hook === 'customize.php') {
            return true;
        }

        return false;
    }

    /**
     * Get asset URL for a specific file
     *
     * @since 1.0.0
     * @param string $file Relative file path.
     * @return string Full asset URL.
     */
    public function get_asset_url(string $file): string
    {
        return $this->asset_url . ltrim($file, '/');
    }

    /**
     * Get asset path for a specific file
     *
     * @since 1.0.0
     * @param string $file Relative file path.
     * @return string Full asset path.
     */
    public function get_asset_path(string $file): string
    {
        return ORBITOOLS_DIR . 'modules/Flex_Layout_Controls/' . ltrim($file, '/');
    }

    /**
     * Check if an asset file exists
     *
     * @since 1.0.0
     * @param string $file Relative file path.
     * @return bool True if file exists, false otherwise.
     */
    public function asset_exists(string $file): bool
    {
        return file_exists($this->get_asset_path($file));
    }

    /**
     * Get versioned asset URL
     *
     * @since 1.0.0
     * @param string $file Relative file path.
     * @return array Array with 'url' and 'version' keys.
     */
    public function get_versioned_asset(string $file): array
    {
        $path = $this->get_asset_path($file);
        $url = $this->get_asset_url($file);
        
        $version = self::VERSION;
        
        // Use file modification time as version if file exists
        if (file_exists($path)) {
            $version = filemtime($path);
        }

        return array(
            'url' => $url,
            'version' => $version,
        );
    }

    /**
     * Enqueue a script with automatic versioning
     *
     * @since 1.0.0
     * @param string $handle Script handle.
     * @param string $file Relative file path.
     * @param array  $deps Dependencies.
     * @param bool   $in_footer Whether to load in footer.
     */
    public function enqueue_script(string $handle, string $file, array $deps = array(), bool $in_footer = true): void
    {
        if (!$this->asset_exists($file)) {
            return;
        }

        $asset = $this->get_versioned_asset($file);
        
        wp_enqueue_script(
            $handle,
            $asset['url'],
            $deps,
            $asset['version'],
            $in_footer
        );
    }

    /**
     * Enqueue a style with automatic versioning
     *
     * @since 1.0.0
     * @param string $handle Style handle.
     * @param string $file Relative file path.
     * @param array  $deps Dependencies.
     * @param string $media Media type.
     */
    public function enqueue_style(string $handle, string $file, array $deps = array(), string $media = 'all'): void
    {
        if (!$this->asset_exists($file)) {
            return;
        }

        $asset = $this->get_versioned_asset($file);
        
        wp_enqueue_style(
            $handle,
            $asset['url'],
            $deps,
            $asset['version'],
            $media
        );
    }
}