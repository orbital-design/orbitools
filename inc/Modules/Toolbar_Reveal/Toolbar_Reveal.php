<?php

namespace Orbitools\Modules\Toolbar_Reveal;

use Orbitools\Core\Abstracts\Module_Base;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Toolbar Reveal module.
 *
 * On the frontend, keep the WordPress admin toolbar mounted (so its
 * "Edit Page" / "Customize" etc. links still work for logged-in
 * editors) but transform it off-screen. A thin invisible hover zone
 * at the very top of the viewport listens for the cursor; when it
 * fires the bar slides into view, and slides back out a moment after
 * the user moves away.
 *
 * Keyboard focus also reveals the bar (via `:focus-within`), and on
 * touch devices — where there's no hover state — the bar stays
 * permanently visible.
 *
 * @package Orbitools
 * @since   3.3.0
 */
final class Toolbar_Reveal extends Module_Base
{
    public function get_slug(): string
    {
        return 'toolbar-reveal';
    }

    public function get_name(): string
    {
        return \__('Toolbar Reveal', 'orbitools');
    }

    public function get_description(): string
    {
        return \__('Hide the WordPress admin toolbar on the frontend; slide it down when the cursor reaches the top of the page.', 'orbitools');
    }

    public function init(): void
    {
        // Frontend-only — admin chrome should stay as-is.
        if (\is_admin()) {
            return;
        }
        \add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function enqueue_assets(): void
    {
        // No toolbar = nothing to hide/reveal. Logged-out visitors,
        // disabled admin bar, etc.
        if (!\is_admin_bar_showing()) {
            return;
        }

        \wp_enqueue_style(
            'orbitools-toolbar-reveal',
            ORBITOOLS_URL . 'build/frontend/css/modules/toolbar-reveal/base.css',
            [],
            ORBITOOLS_VERSION
        );

        \wp_enqueue_script(
            'orbitools-toolbar-reveal',
            ORBITOOLS_URL . 'build/frontend/js/modules/toolbar-reveal/base.js',
            [],
            ORBITOOLS_VERSION,
            true
        );
    }
}
