<?php

namespace Orbitools\Core;

use Orbitools\Core\Admin\Admin;
use Orbitools\Core\Updater\Updater;
// use Orbitools\Core\Toolbar_FAB;
use Orbitools\Core\SpacingConfig;
use Orbitools\Core\Helpers\Gaps_CSS_Generator;
use Orbitools\Core\AspectRatioConfig;
use Orbitools\Core\Helpers\AspectRatio_CSS_Generator;

/**
 * Class Loader
 *
 * Loads core classes and modules for Orbitools.
 */
class Loader
{
    /**
     * Module manager instance.
     *
     * @var Module_Manager
     */
    private Module_Manager $module_manager;

    /**
     * Admin instance.
     *
     * @var Admin
     */
    private $admin;

    /**
     * Updater instance.
     *
     * @var Updater
     */
    private $updater;

    /**
     * Block slugs that have frontend CSS.
     *
     * @var string[]
     */
    private const STYLED_BLOCKS = [
        'collection',
        'entry',
        'marquee',
        'group',
        'query-loop',
        'read-more',
    ];

    /**
     * Check if block CSS loading is disabled via settings.
     *
     * @return bool
     */
    private function is_block_css_disabled(): bool
    {
        $settings = get_option('orbitools_settings', []);
        return !empty($settings['disable_block_css']);
    }

    /**
     * Register block frontend styles (without enqueuing).
     *
     * Styles are registered here and enqueued per-block during render_block,
     * so they output in the footer via print_late_styles() (non-render-blocking).
     *
     * @return void
     */
    public function register_block_styles(): void
    {
        if ($this->is_block_css_disabled()) {
            return;
        }

        foreach (self::STYLED_BLOCKS as $block) {
            $css_file = ORBITOOLS_DIR . "build/blocks/{$block}/index.css";

            if (!file_exists($css_file)) {
                continue;
            }

            wp_register_style(
                "orb-{$block}-style",
                plugins_url("build/blocks/{$block}/index.css", ORBITOOLS_FILE),
                [],
                (string) filemtime($css_file)
            );
        }
    }

    /**
     * Enqueue a block's frontend style when that block is rendered.
     *
     * Fires on render_block — after wp_head has already printed, so styles
     * output via print_late_styles() in wp_footer (non-render-blocking).
     *
     * @param string $content      The block HTML content.
     * @param array  $parsed_block The parsed block data.
     * @return string Unmodified block content.
     */
    public function enqueue_rendered_block_style(string $content, array $parsed_block): string
    {
        $block_name = $parsed_block['blockName'] ?? '';

        if (strpos($block_name, 'orb/') === 0) {
            $slug   = substr($block_name, 4);
            $handle = "orb-{$slug}-style";

            if (wp_style_is($handle, 'registered') && !wp_style_is($handle, 'enqueued')) {
                wp_enqueue_style($handle);
            }
        }

        return $content;
    }

    /**
     * Make block <link> tags non-render-blocking by applying
     * the media="print" onload async pattern.
     *
     * @param string $tag    The <link> tag HTML.
     * @param string $handle The style handle.
     * @return string Modified HTML.
     */
    public function async_block_styles(string $tag, string $handle): string
    {
        if (strpos($handle, 'orb-') === 0 && substr($handle, -6) === '-style') {
            $tag = preg_replace(
                '/(?<=\s)media=[\'"]all[\'"]/',
                'media="print" onload="this.media=\'all\'"',
                $tag
            );
        }

        return $tag;
    }

    /**
     * Enqueue block frontend styles in the editor so previews match the frontend.
     *
     * @return void
     */
    public function enqueue_editor_block_styles(): void
    {
        if ($this->is_block_css_disabled()) {
            // Dequeue editor styles registered via block.json editorStyle
            foreach (self::STYLED_BLOCKS as $block) {
                wp_dequeue_style("orb-{$block}-editor-style");
            }
            return;
        }

        foreach (self::STYLED_BLOCKS as $block) {
            $css_file = ORBITOOLS_DIR . "build/blocks/{$block}/index.css";

            if (!file_exists($css_file)) {
                continue;
            }

            wp_enqueue_style(
                "orb-{$block}-style",
                plugins_url("build/blocks/{$block}/index.css", ORBITOOLS_FILE),
                [],
                (string) filemtime($css_file)
            );
        }
    }

    /**
     * Initializes core classes and modules.
     *
     * @return void
     */
    public function init()
    {
        // Load the OrbiTools AdminKit
        if (file_exists(ORBITOOLS_DIR . 'vendor/orbitools/adminkit/adminkit.php')) {
            require_once ORBITOOLS_DIR . 'vendor/orbitools/adminkit/adminkit.php';
        }

        // Initialize core classes.
        $this->admin = new Admin();
        $this->updater = new Updater();

        // Initialize Spacing Configuration system
        SpacingConfig::init();

        // Initialize Gaps CSS generation
        Gaps_CSS_Generator::init();

        // Initialize Aspect Ratio Configuration and CSS generation
        AspectRatioConfig::init();
        AspectRatio_CSS_Generator::init();

        // Initialize Toolbar FAB
        // new Toolbar_FAB();

        // Register block frontend styles (enqueued per-block via render_block → footer).
        add_action('wp_enqueue_scripts', [$this, 'register_block_styles']);
        add_filter('render_block', [$this, 'enqueue_rendered_block_style'], 10, 2);
        add_filter('style_loader_tag', [$this, 'async_block_styles'], 10, 2);

        // Load frontend CSS in the editor so block previews are styled correctly.
        // Fires before editorStyle assets, so editor.css can override.
        add_action('enqueue_block_editor_assets', [$this, 'enqueue_editor_block_styles']);

        // Initialize modules via the registry. Disabled modules are never
        // autoloaded; their constructors and asset registrations are skipped.
        $this->module_manager = new Module_Manager();
        $this->module_manager->register_built_in();
        $this->module_manager->boot();
    }
}
