<?php

namespace Orbitools\Core;

use Orbitools\Core\Helpers\Settings_Manager;
use Orbitools\Core\Interfaces\Module_Interface;
use Orbitools\Core\Module\Module_Manifest;
use Throwable;

/**
 * Module Manager
 *
 * Registry-based lifecycle for Orbitools modules. Built-in modules are
 * discovered by scanning module.json files alongside each module's class.
 * External code can register additional modules via the
 * `orbitools/register_modules` action.
 *
 * Disabled modules are never autoloaded — their classes are touched only
 * when {@see boot()} confirms the module's `_enabled` setting is true.
 *
 * @package Orbitools
 * @since 2.0.0
 */
final class Module_Manager
{
    /**
     * Glob patterns for built-in module manifests. Evaluated relative to
     * ORBITOOLS_DIR. Order is unimportant — slug uniqueness wins.
     *
     * @var string[]
     */
    private const MANIFEST_PATTERNS = [
        'inc/blocks/*/module.json',
        'inc/controls/*/module.json',
        'inc/modules/*/module.json',
    ];

    /**
     * Most-recently-constructed Module_Manager. Set by the constructor so
     * downstream code (admin field renderers, etc.) can reach the manager
     * without threading it through every callsite.
     *
     * Not a strict singleton — multiple constructions are allowed but the
     * last wins. Loader builds exactly one per request.
     *
     * @var self|null
     */
    private static ?self $instance = null;

    /**
     * Registered modules: slug => fully-qualified class name.
     *
     * @var array<string, string>
     */
    private array $registry = [];

    /**
     * Loaded manifests keyed by slug. Built-in modules populate this map
     * during register_built_in(); external modules registered via the
     * action hook have no manifest entry.
     *
     * @var array<string, Module_Manifest>
     */
    private array $manifests = [];

    /**
     * Instantiated (enabled) modules: slug => instance.
     *
     * @var array<string, Module_Interface>
     */
    private array $instances = [];

    private Settings_Manager $settings_manager;

    public function __construct()
    {
        self::$instance = $this;
        $this->settings_manager = new Settings_Manager();

        // Defer the default-enabled lookup for any slug to manifest data when
        // available, else fall back to enabled-by-default.
        Settings_Manager::set_default_enabled_resolver(function (string $slug): bool {
            $manifest = $this->get_manifest($slug);
            return $manifest !== null ? $manifest->default_enabled : true;
        });
    }

    /**
     * @return self|null The active Module_Manager, or null if none has been
     *                   constructed yet (e.g. before Loader::init() runs).
     */
    public static function instance(): ?self
    {
        return self::$instance;
    }

    /**
     * Register a module by slug and class name.
     *
     * Does not autoload — class loading is deferred until boot() confirms
     * the module is enabled. First registration wins so external code
     * cannot override built-ins.
     *
     * @param string $slug       Module slug. Must match the class's get_slug() return value.
     * @param string $class_name Fully-qualified class name.
     */
    public function register(string $slug, string $class_name): void
    {
        if (isset($this->registry[$slug])) {
            return;
        }

        $this->registry[$slug] = $class_name;
    }

    /**
     * Discover built-in modules by scanning module.json manifests and fire
     * the extension hook for external code to register additional modules.
     */
    public function register_built_in(): void
    {
        foreach ($this->load_built_in_manifests() as $manifest) {
            $this->manifests[$manifest->slug] = $manifest;
            $this->register($manifest->slug, $manifest->class);
        }

        /**
         * Fires after built-in modules are registered, before any are booted.
         *
         * Use this to register additional modules from themes or plugins.
         *
         * @since 2.0.0
         *
         * @param Module_Manager $manager The module manager instance.
         */
        \do_action('orbitools/register_modules', $this);
    }

    /**
     * Instantiate enabled modules. Disabled modules are skipped — their
     * classes are never autoloaded.
     */
    public function boot(): void
    {
        foreach ($this->registry as $slug => $class_name) {
            if (!$this->settings_manager->is_module_enabled($slug)) {
                continue;
            }

            if (!class_exists($class_name)) {
                continue;
            }

            $instance = new $class_name();
            $this->instances[$slug] = $instance;

            $this->maybe_warn_slug_mismatch($slug, $instance);
        }
    }

    /**
     * @return array<string, string> Registry of slug => class name.
     */
    public function get_registered(): array
    {
        return $this->registry;
    }

    /**
     * @return array<string, Module_Interface> Instantiated modules.
     */
    public function get_enabled(): array
    {
        return $this->instances;
    }

    public function is_enabled(string $slug): bool
    {
        return $this->settings_manager->is_module_enabled($slug);
    }

    /**
     * @return Module_Manifest|null Manifest for the given slug, or null if
     *                              the slug has no manifest (e.g. external
     *                              modules registered via the action hook).
     */
    public function get_manifest(string $slug): ?Module_Manifest
    {
        return $this->manifests[$slug] ?? null;
    }

    /**
     * @return array<string, Module_Manifest> All loaded manifests by slug.
     */
    public function get_manifests(): array
    {
        return $this->manifests;
    }

    /**
     * Build an admin-friendly array of module metadata keyed by slug.
     *
     * Each entry contains the manifest-sourced display data plus a
     * `category` field used by the admin UI to group cards. Modules
     * registered without a manifest (external) are still returned with
     * the slug and class as the name fallback, and category "modules".
     *
     * @return array<string, array{slug:string,name:string,description:string,category:string,version:string}>
     */
    public function get_modules_metadata(): array
    {
        $metadata = [];

        foreach ($this->registry as $slug => $class_name) {
            $manifest = $this->get_manifest($slug);

            if ($manifest !== null) {
                $metadata[$slug] = [
                    'slug'        => $manifest->slug,
                    'name'        => \__($manifest->name, 'orbitools'), // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
                    'description' => \__($manifest->description, 'orbitools'), // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
                    'category'    => $manifest->category,
                    'version'     => $manifest->version,
                ];
                continue;
            }

            // External (manifest-less) module — degrade gracefully.
            $metadata[$slug] = [
                'slug'        => $slug,
                'name'        => $slug,
                'description' => '',
                'category'    => 'modules',
                'version'     => '',
            ];
        }

        return $metadata;
    }

    /**
     * Scan the built-in manifest paths and return parsed manifests.
     *
     * In-memory only — Phase 3 starts without a persistent cache. Glob
     * over 16 small JSON files is sub-millisecond.
     *
     * @return Module_Manifest[]
     */
    private function load_built_in_manifests(): array
    {
        $manifests = [];

        foreach (self::MANIFEST_PATTERNS as $pattern) {
            $paths = glob(ORBITOOLS_DIR . $pattern) ?: [];

            foreach ($paths as $path) {
                try {
                    $manifests[] = Module_Manifest::from_file($path);
                } catch (Throwable $e) {
                    // Skip the malformed manifest; surface in WP_DEBUG.
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        \error_log('[Orbitools] ' . $e->getMessage());
                    }
                }
            }
        }

        return $manifests;
    }

    /**
     * In WP_DEBUG mode, warn if a module's get_slug() return value does not
     * match the slug under which it was registered (which is the manifest
     * slug for built-ins). A drift here means settings keys would point at
     * the wrong module.
     */
    private function maybe_warn_slug_mismatch(string $registered_slug, Module_Interface $instance): void
    {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }

        $actual = $instance->get_slug();
        if ($actual !== $registered_slug) {
            \error_log(sprintf(
                '[Orbitools] Slug mismatch: module %s registered as "%s" but get_slug() returned "%s"',
                get_class($instance),
                $registered_slug,
                $actual
            ));
        }
    }
}
