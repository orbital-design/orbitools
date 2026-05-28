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
        return \__('Core Cleanup', 'orbitools');
    }

    public function get_description(): string
    {
        return \__('Strip out built-in WordPress pages and post types the site doesn\'t use.', 'orbitools');
    }

    public function init(): void
    {
        // Priority 999 = run after every other plugin that has added
        // submenu pages; otherwise remove_submenu_page() may run
        // before the entry exists and silently no-op.
        \add_action('admin_menu', [$this, 'apply_overrides'], 999);

        // "Disable posts entirely" — wholesale removal of the default
        // `post` post type's editorial surface. Absorbed from the
        // dream-and-leap-sage theme's BlogDisabledServiceProvider so
        // any Orbitools-using site can opt in without theme code.
        if ($this->is_setting_on('disable_posts', true)) {
            \add_action('admin_menu', [$this, 'remove_posts_menu']);
            \add_action('admin_bar_menu', [$this, 'remove_posts_admin_bar_node'], 999);
            \add_action('admin_init', [$this, 'redirect_posts_screens']);
            \add_action('init', [$this, 'unregister_post_tag']);
        }

        // "Disable Customizer" — patterned on the customizer-disabler
        // plugin by Johannes Siipola (GPL-2.0+), itself based on
        // Customizer Remove All Parts by Petersen & Wilkerson. Three
        // prongs: deny the `customize` capability (kills the menu
        // entry + toolbar shortcut), stop the customizer scripts
        // from being enqueued, and wp_die on any direct hit of
        // customize.php.
        if ($this->is_setting_on('disable_customizer', true)) {
            \add_filter('map_meta_cap', [$this, 'deny_customize_cap'], 10, 2);
            \add_action('admin_init', [$this, 'unhook_customizer']);
            \add_action('load-customize.php', [$this, 'block_customize_screen']);
        }

        // "Disable widgets" — same shape as the Customizer block:
        // strip the menu entry, hard-block the URL, and remove the
        // Customizer's Widgets panel (still relevant when only
        // widgets are disabled, not the whole Customizer).
        if ($this->is_setting_on('disable_widgets', true)) {
            \add_action('admin_menu', [$this, 'remove_widgets_menu'], 999);
            \add_action('load-widgets.php', [$this, 'block_widgets_screen']);
            \add_action('customize_register', [$this, 'remove_customizer_widgets_panel'], 20);
        }
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

    /**
     * Drop the top-level "Posts" admin menu (All Posts / Add New /
     * Categories / Tags all under it).
     */
    public function remove_posts_menu(): void
    {
        \remove_menu_page('edit.php');
    }

    /**
     * Remove the "+ New → Post" item from the admin bar.
     *
     * @param \WP_Admin_Bar $admin_bar
     */
    public function remove_posts_admin_bar_node($admin_bar): void
    {
        if ($admin_bar instanceof \WP_Admin_Bar) {
            $admin_bar->remove_node('new-post');
        }
    }

    /**
     * Bounce any admin request that would land on a `post` / `category`
     * / `post_tag` editing screen back to the dashboard. Capability-
     * gated to edit_posts so subscribers can't hit redirect loops.
     * Other post types and taxonomies pass through unchanged.
     */
    public function redirect_posts_screens(): void
    {
        if (!\is_admin() || !\current_user_can('edit_posts')) {
            return;
        }

        global $pagenow;

        if ($pagenow === 'edit.php' || $pagenow === 'post-new.php') {
            $post_type = isset($_GET['post_type']) ? \sanitize_key((string) $_GET['post_type']) : 'post';
            if ($post_type === 'post') {
                \wp_safe_redirect(\admin_url());
                exit;
            }
            return;
        }

        if ($pagenow === 'post.php' && isset($_GET['post'], $_GET['action']) && $_GET['action'] === 'edit') {
            if (\get_post_type((int) $_GET['post']) === 'post') {
                \wp_safe_redirect(\admin_url());
                exit;
            }
            return;
        }

        if ($pagenow === 'edit-tags.php' || $pagenow === 'term.php') {
            $taxonomy = isset($_GET['taxonomy']) ? \sanitize_key((string) $_GET['taxonomy']) : '';
            if (in_array($taxonomy, ['category', 'post_tag'], true)) {
                \wp_safe_redirect(\admin_url());
                exit;
            }
        }
    }

    /**
     * Detach post_tag from the `post` object type so even if posts
     * sneak back in, the tag pane stays gone.
     */
    public function unregister_post_tag(): void
    {
        \unregister_taxonomy_for_object_type('post_tag', 'post');
    }

    /**
     * Replace the `customize` capability with WP's standard
     * denial sentinel for every user, killing the Customizer menu
     * entry, admin-bar shortcut, and any other UI gated on
     * current_user_can('customize').
     *
     * @param array<int,string> $caps
     * @param string            $cap
     * @return array<int,string>
     */
    public function deny_customize_cap(array $caps, string $cap): array
    {
        if ($cap === 'customize') {
            return ['do_not_allow'];
        }
        return $caps;
    }

    /**
     * Strip the customizer scripts/styles from any admin page that
     * would otherwise enqueue them. `_wp_customize_include` runs
     * on plugins_loaded and has already fired by the time
     * admin_init does — kept here for symmetry with the upstream
     * customizer-disabler plugin's belt-and-braces approach.
     */
    public function unhook_customizer(): void
    {
        \remove_action('plugins_loaded', '_wp_customize_include', 10);
        \remove_action('admin_enqueue_scripts', '_wp_customize_loader_settings', 11);
    }

    /**
     * Hard-block any direct hit on /wp-admin/customize.php — covers
     * bookmarks, deep links, and anything that bypassed the
     * capability-stripped menu entry.
     */
    public function block_customize_screen(): void
    {
        \wp_die(\esc_html__('The Customizer is disabled on this site.', 'orbitools'));
    }

    /**
     * Remove the Appearance → Widgets submenu item.
     */
    public function remove_widgets_menu(): void
    {
        \remove_submenu_page('themes.php', 'widgets.php');
    }

    /**
     * Hard-block direct hits on /wp-admin/widgets.php — same
     * reasoning as the Customizer's load-customize.php block.
     */
    public function block_widgets_screen(): void
    {
        \wp_die(\esc_html__('Widgets are disabled on this site.', 'orbitools'));
    }

    /**
     * Remove the Customizer's "Widgets" panel. Only relevant when
     * the Customizer itself is still enabled (otherwise this
     * action never fires).
     *
     * @param \WP_Customize_Manager $wp_customize
     */
    public function remove_customizer_widgets_panel($wp_customize): void
    {
        if (method_exists($wp_customize, 'remove_panel')) {
            $wp_customize->remove_panel('widgets');
        }
    }

    /**
     * Read a toggle out of the orbitools_settings row. The `$default`
     * argument matches the field's manifest default — when the user
     * hasn't saved anything yet, we want the server-side check to
     * line up with what the React form renders. (Most toggles default
     * off, but a few opt-out switches like `disable_posts` ship with
     * `default: true`.)
     */
    private function is_setting_on(string $key, bool $default = false): bool
    {
        $settings = \get_option('orbitools_settings', []);
        $full_key = $this->get_slug() . '_' . $key;
        if (!array_key_exists($full_key, $settings)) {
            return $default;
        }
        return !empty($settings[$full_key]);
    }
}
