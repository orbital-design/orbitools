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
 * @package Orbitools
 * @since 3.0.0
 */
final class React_Admin
{
    /**
     * Page slug for the React admin top-level menu. Phase 7 reclaimed
     * the plain `orbitools` slug from the now-deleted AdminKit admin.
     */
    public const PAGE_SLUG    = 'orbitools';
    public const SCRIPT_HANDLE = 'orbitools';
    public const STYLE_HANDLE  = 'orbitools';

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
            // Position 0 to match the priority AdminKit had set, sitting
            // above WP's own Dashboard. Top-level plugin admins live here
            // so users land on them straight after login.
            /* position */ 0
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
     * Menu icon. Inline SVG (the same brand logo AdminKit used as its
     * header image) keeps it crisp at all sizes and tints to match
     * the admin colour scheme via currentColor — fill was '#fff' in
     * the source SVG; swapped here so WP's menu-icon theming applies.
     */
    private function get_menu_icon(): string
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="322" height="322" fill="none"><path fill="currentColor" fill-rule="evenodd" d="M71.096 27.45A160.999 160.999 0 0 1 160.369.013 159.624 159.624 0 0 1 275.03 46.53a159.612 159.612 0 0 1 46.964 114.477 160.99 160.99 0 0 1-99.242 148.678A160.999 160.999 0 0 1 3.171 192.798 160.99 160.99 0 0 1 71.096 27.45Zm45.655 198.564a78.138 78.138 0 0 0 43.409 13.167 78.22 78.22 0 0 0 78.134-78.132 78.133 78.133 0 1 0-121.543 64.965Zm149.52-151.706c0 12.54-10.166 22.705-22.706 22.705-12.539 0-22.705-10.166-22.705-22.705 0-12.54 10.166-22.705 22.705-22.705 12.54 0 22.706 10.165 22.706 22.705Z" clip-rule="evenodd"/></svg>';
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}
