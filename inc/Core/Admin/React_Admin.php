<?php

namespace Orbitools\Core\Admin;

/**
 * React Admin
 *
 * Registers the v3 top-level WP admin menu page and enqueues the
 * React admin bundle on it. The page itself outputs a single mount
 * div; everything visible is rendered by the React app at
 * src/admin/index.tsx.
 *
 * Runs in parallel with the AdminKit-driven Admin class until the
 * Phase 7 retirement of AdminKit. Both write to the same
 * orbitools_settings option.
 *
 * @package Orbitools
 * @since 3.0.0
 */
final class React_Admin
{
    /**
     * Page slug for the React admin top-level menu.
     *
     * Distinct from AdminKit's slug — its root page already uses
     * `orbitools` (under Settings), so a collision there would route
     * both menu entries to the same URL. Phase 7 retires AdminKit at
     * which point this can be renamed to plain `orbitools`.
     */
    public const PAGE_SLUG    = 'orbitools-app';
    public const SCRIPT_HANDLE = 'orbitools-admin';
    public const STYLE_HANDLE  = 'orbitools-admin';

    public function __construct()
    {
        \add_action('admin_menu', [$this, 'register_menu']);
        \add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    /**
     * Register the top-level "Orbitools" admin menu.
     */
    public function register_menu(): void
    {
        \add_menu_page(
            \__('Orbitools', 'orbitools'),
            \__('Orbitools', 'orbitools'),
            'manage_options',
            self::PAGE_SLUG,
            [$this, 'render_page'],
            $this->get_menu_icon(),
            /* position */ 80
        );
    }

    /**
     * Render the mount point. Everything else is React-driven.
     */
    public function render_page(): void
    {
        echo '<div id="orbitools-admin-root"></div>';
    }

    /**
     * Enqueue the admin bundle on the Orbitools page only.
     *
     * @param string $hook_suffix Current admin page hook.
     */
    public function enqueue_assets(string $hook_suffix): void
    {
        if ($hook_suffix !== 'toplevel_page_' . self::PAGE_SLUG) {
            return;
        }

        $asset_file = ORBITOOLS_DIR . 'build/admin/index.asset.php';
        if (!file_exists($asset_file)) {
            // Build hasn't been run; bail rather than emitting broken script tags.
            \add_action('admin_notices', [$this, 'render_build_missing_notice']);
            return;
        }

        $asset = require $asset_file;

        \wp_enqueue_script(
            self::SCRIPT_HANDLE,
            \plugins_url('build/admin/index.js', ORBITOOLS_FILE),
            $asset['dependencies'],
            $asset['version'],
            true
        );

        // Localise the bootstrap data the React app needs to talk to
        // the REST API and resolve plugin-relative URLs.
        \wp_add_inline_script(
            self::SCRIPT_HANDLE,
            'window.orbitools = ' . \wp_json_encode([
                'restUrl'    => \esc_url_raw(\rest_url('orbitools/v1/')),
                'restNonce'  => \wp_create_nonce('wp_rest'),
                'adminUrl'   => \esc_url_raw(\admin_url()),
                'pluginUrl'  => \esc_url_raw(\plugins_url('', ORBITOOLS_FILE)),
                'version'    => ORBITOOLS_VERSION,
            ]) . ';',
            'before'
        );

        // Styles. wp-components carries the design system the app builds on.
        $style_file = ORBITOOLS_DIR . 'build/admin/index.css';
        if (file_exists($style_file)) {
            \wp_enqueue_style(
                self::STYLE_HANDLE,
                \plugins_url('build/admin/index.css', ORBITOOLS_FILE),
                ['wp-components'],
                $asset['version']
            );
        } else {
            // Even with no app CSS, we still want wp-components' styles loaded.
            \wp_enqueue_style('wp-components');
        }
    }

    /**
     * Admin notice shown when the React bundle hasn't been built.
     */
    public function render_build_missing_notice(): void
    {
        echo '<div class="notice notice-error"><p>';
        echo \esc_html__(
            'Orbitools: the admin bundle has not been built. Run "npm run build:admin" from the plugin directory.',
            'orbitools'
        );
        echo '</p></div>';
    }

    /**
     * Menu icon. Inline SVG keeps it crisp at all sizes and tints to
     * match the admin colour scheme via currentColor.
     */
    private function get_menu_icon(): string
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M192 104.8c0-9.2-5.8-17.3-13.2-22.8C167.2 73.3 160 61.3 160 48c0-26.5 28.7-48 64-48s64 21.5 64 48c0 13.3-7.2 25.3-18.8 34c-7.4 5.5-13.2 13.6-13.2 22.8c0 12.8 10.4 23.2 23.2 23.2l56.8 0c26.5 0 48 21.5 48 48l0 56.8c0 12.8 10.4 23.2 23.2 23.2c9.2 0 17.3-5.8 22.8-13.2c8.7-11.6 20.7-18.8 34-18.8c26.5 0 48 28.7 48 64s-21.5 64-48 64c-13.3 0-25.3-7.2-34-18.8c-5.5-7.4-13.6-13.2-22.8-13.2c-12.8 0-23.2 10.4-23.2 23.2L384 464c0 26.5-21.5 48-48 48l-56.8 0c-12.8 0-23.2-10.4-23.2-23.2c0-9.2 5.8-17.3 13.2-22.8c11.6-8.7 18.8-20.7 18.8-34c0-26.5-28.7-48-64-48s-64 21.5-64 48c0 13.3 7.2 25.3 18.8 34c7.4 5.5 13.2 13.6 13.2 22.8c0 12.8-10.4 23.2-23.2 23.2L48 512c-26.5 0-48-21.5-48-48L0 343.2C0 330.4 10.4 320 23.2 320c9.2 0 17.3 5.8 22.8 13.2C54.7 344.8 66.7 352 80 352c26.5 0 48-28.7 48-64s-21.5-64-48-64c-13.3 0-25.3 7.2-34 18.8C40.5 250.2 32.4 256 23.2 256C10.4 256 0 245.6 0 232.8L0 176c0-26.5 21.5-48 48-48l120.8 0c12.8 0 23.2-10.4 23.2-23.2z"/></svg>';
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}
