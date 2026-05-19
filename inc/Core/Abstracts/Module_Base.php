<?php

namespace Orbitools\Core\Abstracts;

use Orbitools\Core\Interfaces\Module_Interface;
use Orbitools\Core\Helpers\Settings_Manager;
use Orbitools\Core\Helpers\Asset_Manager;

/**
 * Module Base Class
 *
 * Provides common functionality for all OrbiTools modules.
 * Implements standard patterns for module initialization, settings management,
 * and asset loading to reduce code duplication.
 *
 * @package Orbitools
 * @since 1.0.0
 */
abstract class Module_Base implements Module_Interface
{
    /**
     * Module version - override in child classes
     */
    protected const VERSION = '1.0.0';

    /**
     * Settings manager instance
     *
     * @var Settings_Manager
     */
    protected $settings_manager;

    /**
     * Asset manager instance
     *
     * @var Asset_Manager
     */
    protected $asset_manager;

    /**
     * Whether the module has been initialized
     *
     * @var bool
     */
    private $initialized = false;

    /**
     * Constructor
     *
     * Sets up the module with dependency injection for settings and assets
     */
    public function __construct()
    {
        $this->settings_manager = new Settings_Manager();
        $this->asset_manager = new Asset_Manager();

        // Defer initialization to WordPress init hook to avoid early execution
        add_action('setup_theme', [$this, 'initialize_module']);
    }

    /**
     * Initialize the module after WordPress is ready
     *
     * @return void
     */
    public function initialize_module(): void
    {
        // Prevent double initialization
        if ($this->initialized) {
            return;
        }

        $this->initialized = true;

        // Only initialize if module is enabled
        if ($this->is_enabled()) {
            $this->init();
        }
    }

    /**
     * Get the module's version
     *
     * @return string
     */
    public function get_version(): string
    {
        return static::VERSION;
    }

    /**
     * Check if the module is enabled
     *
     * @return bool
     */
    public function is_enabled(): bool
    {
        return $this->settings_manager->is_module_enabled($this->get_slug());
    }

    /**
     * Get module's default settings
     * Override in child classes if module has settings
     *
     * @return array
     */
    public function get_default_settings(): array
    {
        return [
            $this->get_slug() . '_enabled' => true
        ];
    }

    /**
     * Enqueue admin styles
     *
     * @param string $handle Unique handle for the stylesheet
     * @param string $path Path relative to build/admin/css/modules/{module-slug}/
     * @param array $deps Dependencies
     * @return void
     */
    protected function enqueue_admin_style(string $handle, string $path, array $deps = []): void
    {
        $this->asset_manager->enqueue_admin_style(
            $handle,
            "modules/{$this->get_slug()}/{$path}",
            $deps
        );
    }

    /**
     * Enqueue admin scripts
     *
     * @param string $handle Unique handle for the script
     * @param string $path Path relative to build/admin/js/modules/{module-slug}/
     * @param array $deps Dependencies
     * @return void
     */
    protected function enqueue_admin_script(string $handle, string $path, array $deps = []): void
    {
        $this->asset_manager->enqueue_admin_script(
            $handle,
            "modules/{$this->get_slug()}/{$path}",
            $deps
        );
    }

    /**
     * Enqueue frontend styles
     *
     * @param string $handle Unique handle for the stylesheet
     * @param string $path Path relative to build/frontend/css/modules/{module-slug}/
     * @param array $deps Dependencies
     * @return void
     */
    protected function enqueue_frontend_style(string $handle, string $path, array $deps = []): void
    {
        $this->asset_manager->enqueue_frontend_style(
            $handle,
            "modules/{$this->get_slug()}/{$path}",
            $deps
        );
    }

    /**
     * Enqueue frontend scripts
     *
     * @param string $handle Unique handle for the script
     * @param string $path Path relative to build/frontend/js/modules/{module-slug}/
     * @param array $deps Dependencies
     * @return void
     */
    protected function enqueue_frontend_script(string $handle, string $path, array $deps = []): void
    {
        $this->asset_manager->enqueue_frontend_script(
            $handle,
            "modules/{$this->get_slug()}/{$path}",
            $deps
        );
    }

    /**
     * Enqueue block editor styles
     *
     * @param string $handle Unique handle for the stylesheet
     * @param string $path Path relative to build/admin/css/modules/{module-slug}/
     * @param array $deps Dependencies
     * @return void
     */
    protected function enqueue_editor_style(string $handle, string $path, array $deps = []): void
    {
        $this->asset_manager->enqueue_editor_style(
            $handle,
            "modules/{$this->get_slug()}/{$path}",
            $deps
        );
    }

    /**
     * Enqueue block editor scripts
     *
     * @param string $handle Unique handle for the script
     * @param string $path Path relative to build/admin/js/modules/{module-slug}/
     * @param array $deps Dependencies
     * @return void
     */
    protected function enqueue_editor_script(string $handle, string $path, array $deps = []): void
    {
        $this->asset_manager->enqueue_editor_script(
            $handle,
            "modules/{$this->get_slug()}/{$path}",
            $deps
        );
    }

    /**
     * Get module setting value
     *
     * @param string $key Setting key
     * @param mixed $default Default value if setting doesn't exist
     * @return mixed Setting value
     */
    protected function get_setting(string $key, $default = null)
    {
        return $this->settings_manager->get_module_setting($this->get_slug(), $key, $default);
    }

    /**
     * Update module setting value
     *
     * @param string $key Setting key
     * @param mixed $value New value
     * @return bool True on success
     */
    protected function update_setting(string $key, $value): bool
    {
        return $this->settings_manager->update_module_setting($this->get_slug(), $key, $value);
    }

    /**
     * Localize script data
     *
     * @param string $handle Script handle that was already enqueued
     * @param string $object_name JavaScript object name
     * @param array $data Data to pass to JavaScript
     * @return bool True on success
     */
    protected function localize_script(string $handle, string $object_name, array $data): bool
    {
        return $this->asset_manager->localize_script($handle, $object_name, $data);
    }
}
