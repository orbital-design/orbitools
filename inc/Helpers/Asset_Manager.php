<?php

namespace Orbitools\Helpers;

/**
 * Asset Manager
 * 
 * Centralized asset management for all OrbiTools modules.
 * Handles consistent enqueueing of styles and scripts with proper
 * versioning and dependency management.
 * 
 * @package Orbitools
 * @since 1.0.0
 */
class Asset_Manager
{
    /**
     * Base URL for build assets
     * 
     * @var string
     */
    private $build_url;

    /**
     * Base path for build assets
     * 
     * @var string
     */
    private $build_path;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->build_url = ORBITOOLS_URL . 'build/';
        $this->build_path = ORBITOOLS_DIR . 'build/';
    }

    /**
     * Enqueue admin stylesheet
     * 
     * @param string $handle Unique handle for the stylesheet
     * @param string $path Path relative to build/admin/css/
     * @param array $deps Dependencies
     * @return bool True if enqueued successfully
     */
    public function enqueue_admin_style(string $handle, string $path, array $deps = []): bool
    {
        return $this->enqueue_style($handle, "admin/css/{$path}", $deps, ['admin_enqueue_scripts']);
    }

    /**
     * Enqueue admin script
     * 
     * @param string $handle Unique handle for the script
     * @param string $path Path relative to build/admin/js/
     * @param array $deps Dependencies
     * @return bool True if enqueued successfully
     */
    public function enqueue_admin_script(string $handle, string $path, array $deps = []): bool
    {
        return $this->enqueue_script($handle, "admin/js/{$path}", $deps, ['admin_enqueue_scripts']);
    }

    /**
     * Enqueue frontend stylesheet
     * 
     * @param string $handle Unique handle for the stylesheet
     * @param string $path Path relative to build/frontend/css/
     * @param array $deps Dependencies
     * @return bool True if enqueued successfully
     */
    public function enqueue_frontend_style(string $handle, string $path, array $deps = []): bool
    {
        return $this->enqueue_style($handle, "frontend/css/{$path}", $deps, ['wp_enqueue_scripts']);
    }

    /**
     * Enqueue frontend script
     * 
     * @param string $handle Unique handle for the script
     * @param string $path Path relative to build/frontend/js/
     * @param array $deps Dependencies
     * @return bool True if enqueued successfully
     */
    public function enqueue_frontend_script(string $handle, string $path, array $deps = []): bool
    {
        return $this->enqueue_script($handle, "frontend/js/{$path}", $deps, ['wp_enqueue_scripts']);
    }

    /**
     * Enqueue editor stylesheet (for block editor)
     * 
     * @param string $handle Unique handle for the stylesheet
     * @param string $path Path relative to build/admin/css/
     * @param array $deps Dependencies
     * @return bool True if enqueued successfully
     */
    public function enqueue_editor_style(string $handle, string $path, array $deps = ['wp-edit-blocks']): bool
    {
        return $this->enqueue_style($handle, "admin/css/{$path}", $deps, ['enqueue_block_editor_assets']);
    }

    /**
     * Enqueue editor script (for block editor)
     * 
     * @param string $handle Unique handle for the script
     * @param string $path Path relative to build/admin/js/
     * @param array $deps Dependencies
     * @return bool True if enqueued successfully
     */
    public function enqueue_editor_script(string $handle, string $path, array $deps = []): bool
    {
        return $this->enqueue_script($handle, "admin/js/{$path}", $deps, ['enqueue_block_editor_assets']);
    }

    /**
     * Generic method to enqueue a stylesheet
     * 
     * @param string $handle Unique handle
     * @param string $relative_path Path relative to build directory
     * @param array $deps Dependencies
     * @param array $hooks WordPress hooks to use for enqueueing
     * @return bool True if file exists and was enqueued
     */
    private function enqueue_style(string $handle, string $relative_path, array $deps, array $hooks): bool
    {
        $file_path = $this->build_path . $relative_path;
        
        // Only enqueue if file exists
        if (!file_exists($file_path)) {
            return false;
        }

        $file_url = $this->build_url . $relative_path;
        $version = $this->get_file_version($file_path);

        // Add to appropriate WordPress hooks
        foreach ($hooks as $hook) {
            add_action($hook, function() use ($handle, $file_url, $deps, $version) {
                wp_enqueue_style($handle, $file_url, $deps, $version);
            });
        }

        return true;
    }

    /**
     * Generic method to enqueue a script
     * 
     * @param string $handle Unique handle
     * @param string $relative_path Path relative to build directory
     * @param array $deps Dependencies
     * @param array $hooks WordPress hooks to use for enqueueing
     * @return bool True if file exists and was enqueued
     */
    private function enqueue_script(string $handle, string $relative_path, array $deps, array $hooks): bool
    {
        $file_path = $this->build_path . $relative_path;
        
        // Only enqueue if file exists
        if (!file_exists($file_path)) {
            return false;
        }

        $file_url = $this->build_url . $relative_path;
        $version = $this->get_file_version($file_path);

        // Add to appropriate WordPress hooks
        foreach ($hooks as $hook) {
            add_action($hook, function() use ($handle, $file_url, $deps, $version) {
                wp_enqueue_script($handle, $file_url, $deps, $version, true);
            });
        }

        return true;
    }

    /**
     * Get file version based on modification time
     * 
     * @param string $file_path Full path to file
     * @return string Version string
     */
    private function get_file_version(string $file_path): string
    {
        if (file_exists($file_path)) {
            return (string) filemtime($file_path);
        }
        
        return ORBITOOLS_VERSION;
    }

    /**
     * Localize script data
     * 
     * @param string $handle Script handle that was already enqueued
     * @param string $object_name JavaScript object name
     * @param array $data Data to pass to JavaScript
     * @return bool True on success
     */
    public function localize_script(string $handle, string $object_name, array $data): bool
    {
        return wp_localize_script($handle, $object_name, $data);
    }

    /**
     * Add inline style to an enqueued stylesheet
     * 
     * @param string $handle Stylesheet handle
     * @param string $css CSS code to add
     * @return bool True on success
     */
    public function add_inline_style(string $handle, string $css): bool
    {
        return wp_add_inline_style($handle, $css);
    }

    /**
     * Add inline script to an enqueued script
     * 
     * @param string $handle Script handle
     * @param string $js JavaScript code to add
     * @param string $position Where to add ('before' or 'after')
     * @return bool True on success
     */
    public function add_inline_script(string $handle, string $js, string $position = 'after'): bool
    {
        return wp_add_inline_script($handle, $js, $position);
    }
}