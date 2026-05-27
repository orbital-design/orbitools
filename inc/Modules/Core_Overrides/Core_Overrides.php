<?php

namespace Orbitools\Modules\Core_Overrides;

use Orbitools\Core\Abstracts\Module_Base;

/**
 * Core Overrides module.
 *
 * Hides built-in WordPress admin submenu pages the site doesn't need
 * (Settings → Discussion, Tools → Site Health, etc.). Each toggle in
 * the module's settings maps to a `remove_submenu_page()` call at
 * admin_menu priority 999, after WP has registered everything.
 *
 * The slug → submenu-page map is intentionally exhaustive of the
 * stock-WP defaults. New WP versions occasionally add submenus (e.g.
 * options-privacy.php in 4.9.6, site-health.php in 5.2); add a new
 * field + map entry to expose them.
 *
 * @package Orbitools
 * @since 3.1.0
 */
final class Core_Overrides extends Module_Base
{
    /**
     * Setting key → list of [parent slug, submenu slug] tuples. Most
     * toggles correspond to a single submenu entry, but WP 7.0
     * registers some pages twice (e.g. Connectors lives as the
     * legacy `options-connectors.php` file *and* as the new
     * plugin-style `options-connectors-wp-admin` slug). One toggle =
     * one user intent, even if WP exposes it via multiple slugs.
     *
     * Settings keys are the raw IDs from module.json (without the
     * `core-overrides_` storage prefix); is_setting_on() applies
     * the prefix when reading the option.
     *
     * @var array<string,array<int,array{0:string,1:string}>>
     */
    private const SUBMENU_MAP = [
        'disable_general'              => [['options-general.php', 'options-general.php']],
        'disable_writing'              => [['options-general.php', 'options-writing.php']],
        'disable_connectors'           => [
            ['options-general.php', 'options-connectors.php'],
            ['options-general.php', 'options-connectors-wp-admin'],
        ],
        'disable_reading'              => [['options-general.php', 'options-reading.php']],
        'disable_discussion'           => [['options-general.php', 'options-discussion.php']],
        'disable_media'                => [['options-general.php', 'options-media.php']],
        'disable_permalinks'           => [['options-general.php', 'options-permalink.php']],
        'disable_privacy'              => [['options-general.php', 'options-privacy.php']],

        'disable_available_tools'      => [['tools.php',           'tools.php']],
        'disable_import'               => [['tools.php',           'import.php']],
        'disable_export'               => [['tools.php',           'export.php']],
        'disable_site_health'          => [['tools.php',           'site-health.php']],
        'disable_export_personal_data' => [['tools.php',           'export-personal-data.php']],
        'disable_erase_personal_data'  => [['tools.php',           'erase-personal-data.php']],
    ];

    public function get_slug(): string
    {
        return 'core-overrides';
    }

    public function get_name(): string
    {
        return \__('Core Overrides', 'orbitools');
    }

    public function get_description(): string
    {
        return \__('Hide built-in WordPress admin pages that the site doesn\'t need.', 'orbitools');
    }

    public function init(): void
    {
        // Priority 999 = run after every other plugin that has added
        // submenu pages; otherwise remove_submenu_page() may run
        // before the entry exists and silently no-op.
        \add_action('admin_menu', [$this, 'apply_overrides'], 999);
    }

    public function apply_overrides(): void
    {
        foreach (self::SUBMENU_MAP as $setting_key => $pairs) {
            if (!$this->is_setting_on($setting_key)) {
                continue;
            }
            foreach ($pairs as [$parent, $child]) {
                \remove_submenu_page($parent, $child);
            }
        }
    }

    private function is_setting_on(string $key): bool
    {
        $settings = \get_option('orbitools_settings', []);
        $full_key = $this->get_slug() . '_' . $key;
        return !empty($settings[$full_key]);
    }
}
