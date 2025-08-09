<?php

/**
 * Typography Presets Asset Management
 *
 * Handles loading and management of CSS and JavaScript assets for the Typography Presets module.
 * This class centralizes all asset-related functionality for better organization.
 *
 * @package    Orbitools
 * @subpackage Modules/Typography_Presets/Frontend
 * @since      1.0.0
 */

namespace Orbitools\Controls\Typography_Presets\Frontend;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Assets Management Class
 *
 * Manages CSS and JavaScript asset loading for the Typography Presets module.
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
        $this->asset_url = ORBITOOLS_URL . 'build/admin/css/controls/typography-presets/';

        // Hook into WordPress asset loading
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
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
    }


    /**
     * Enqueue admin styles
     *
     * @since 1.0.0
     */
    private function enqueue_admin_styles(): void
    {
        // Check if admin CSS file exists
        $css_file = $this->asset_url . 'typography-presets.css';
        $css_path = ORBITOOLS_URL . 'build/admin/css/controls/typography-presets/admin.css';

        if (file_exists($css_path)) {
            wp_enqueue_style(
                'orbitools-typography-presets-admin',
                $css_file,
                array(),
                self::VERSION
            );
        }
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
        return ORBITOOLS_DIR . 'build/admin/css/controls/typography-presets/' . ltrim($file, '/');
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
