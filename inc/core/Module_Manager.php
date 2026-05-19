<?php

namespace Orbitools\Core;

use Orbitools\Core\Helpers\Settings_Manager;
use Orbitools\Core\Interfaces\Module_Interface;

/**
 * Module Manager
 *
 * Registry-based lifecycle for Orbitools modules. Modules are registered by
 * slug => fully-qualified class name and only instantiated when their
 * `_enabled` setting is true. Disabled modules incur no autoload cost,
 * no constructor cost, and no asset registration cost.
 *
 * External code (themes, plugins) can register additional modules via the
 * `orbitools/register_modules` action, which fires after built-in modules
 * are registered and before any are booted.
 *
 * @package Orbitools
 * @since 2.0.0
 */
final class Module_Manager
{
    /**
     * Registered modules: slug => fully-qualified class name.
     *
     * @var array<string, string>
     */
    private array $registry = [];

    /**
     * Instantiated (enabled) modules: slug => instance.
     *
     * @var array<string, Module_Interface>
     */
    private array $instances = [];

    /**
     * @var Settings_Manager
     */
    private Settings_Manager $settings_manager;

    public function __construct()
    {
        $this->settings_manager = new Settings_Manager();
    }

    /**
     * Register a module.
     *
     * Stores slug => class name. Does not autoload the class — the autoloader
     * only runs in {@see boot()} if the module is enabled.
     *
     * First registration wins: duplicate slugs are ignored silently so
     * external registrations cannot override built-ins.
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
     * Register all built-in modules and fire the extension hook.
     *
     * The hardcoded slug => class map below is replaced with a manifest
     * scan in Phase 3.
     */
    public function register_built_in(): void
    {
        $this->register('typography-presets',     \Orbitools\Controls\Typography_Presets\Typography_Presets::class);
        $this->register('layout-guides',          \Orbitools\Modules\Layout_Guides\Layout_Guides::class);
        $this->register('menu-groups',            \Orbitools\Modules\Menu_Groups\Menu_Groups::class);
        $this->register('menu-dividers',          \Orbitools\Modules\Menu_Dividers\Menu_Dividers::class);
        $this->register('analytics',              \Orbitools\Modules\Analytics\Analytics::class);
        $this->register('user-avatars',           \Orbitools\Modules\User_Avatars\User_Avatars::class);
        $this->register('collection-block',       \Orbitools\Blocks\Collection\Collection::class);
        $this->register('entry-block',            \Orbitools\Blocks\Entry\Entry::class);
        $this->register('query-loop-block',       \Orbitools\Blocks\Query_Loop\Query_Loop::class);
        $this->register('spacer-block',           \Orbitools\Blocks\Spacer\Spacer::class);
        $this->register('read-more-block',        \Orbitools\Blocks\Read_More\Read_More::class);
        $this->register('marquee-block',          \Orbitools\Blocks\Marquee\Marquee::class);
        $this->register('group-block',            \Orbitools\Blocks\Group\Group::class);
        $this->register('spacings-controls',      \Orbitools\Controls\Spacings_Controls\Spacings_Controls::class);
        $this->register('aspect-ratio-controls',  \Orbitools\Controls\AspectRatio_Controls\AspectRatio_Controls::class);
        $this->register('toolbar-fab',            \Orbitools\Modules\Toolbar_FAB\Toolbar_FAB::class);

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

            $this->instances[$slug] = new $class_name();
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
}
