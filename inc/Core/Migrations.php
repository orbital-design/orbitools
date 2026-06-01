<?php

namespace Orbitools\Core;

/**
 * One-time data migrations for Orbitools.
 *
 * Each migration is gated by an option flag so it runs exactly once per
 * install, no matter how many requests pass through plugins_loaded.
 * Migrations must be idempotent: re-running them on already-migrated data
 * is a no-op.
 *
 * @package Orbitools
 * @since 2.0.0
 */
final class Migrations
{
    /**
     * Run all pending migrations. Called once per request from
     * {@see Loader::init()} before any other settings reads.
     */
    public static function run(): void
    {
        self::maybe_run_slug_migration();
        self::maybe_drop_toolbar_fab();
    }

    /**
     * v2 slug migration: rename legacy underscore-keyed `_enabled` settings
     * to their hyphenated manifest-slug counterparts.
     *
     * Pre-Phase 4, the toggle UI wrote to underscore-prefixed keys
     * (e.g. `typography_presets_enabled`) while `Module_Base::is_enabled()`
     * read from hyphen-prefixed keys derived from `get_slug()`. The
     * mismatch was masked by Settings_Manager's enabled-by-default
     * fallback. After Phase 4 the toggle UI writes the correct
     * hyphen-keyed value, but installs upgrading from the old plugin
     * have data stored under the old keys — without this migration,
     * every module they had enabled would appear disabled.
     *
     * If both old and new keys exist (e.g. the user re-toggled via the
     * new UI before this migration ran), the new value wins and the old
     * key is simply removed.
     */
    private static function maybe_run_slug_migration(): void
    {
        $flag_option = 'orbitools_v2_slug_migration_done';

        if (\get_option($flag_option)) {
            return;
        }

        $legacy_to_new = [
            'typography_presets_enabled' => 'typography-presets_enabled',
            'menu_dividers_enabled'      => 'menu-dividers_enabled',
            'menu_groups_enabled'        => 'menu-groups_enabled',
            'user_avatars_enabled'       => 'user-avatars_enabled',
            'layout_guides_enabled'      => 'layout-guides_enabled',
            // analytics_enabled — slug already matches, no migration needed.
        ];

        $settings = \get_option('orbitools_settings', []);
        if (!is_array($settings)) {
            $settings = [];
        }

        $changed = false;

        foreach ($legacy_to_new as $old_key => $new_key) {
            if (!array_key_exists($old_key, $settings)) {
                continue;
            }

            // Only seed the new key if it isn't already set — re-toggles
            // via the new UI take precedence over stale legacy data.
            if (!array_key_exists($new_key, $settings)) {
                $settings[$new_key] = $settings[$old_key];
            }

            unset($settings[$old_key]);
            $changed = true;
        }

        if ($changed) {
            \update_option('orbitools_settings', $settings);
        }

        // Mark migration complete. autoload=false: only read once at the
        // top of Loader::init() per request, no point paying the autoload tax.
        \update_option($flag_option, '1', false);
    }

    /**
     * Drop the stored `toolbar-fab_enabled` key after the Toolbar FAB
     * module was removed in favour of Toolbar Reveal.
     */
    private static function maybe_drop_toolbar_fab(): void
    {
        $flag_option = 'orbitools_drop_toolbar_fab_done';

        if (\get_option($flag_option)) {
            return;
        }

        $settings = \get_option('orbitools_settings', []);
        if (is_array($settings) && array_key_exists('toolbar-fab_enabled', $settings)) {
            unset($settings['toolbar-fab_enabled']);
            \update_option('orbitools_settings', $settings);
        }

        \update_option($flag_option, '1', false);
    }
}
