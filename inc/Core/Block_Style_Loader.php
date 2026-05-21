<?php

namespace Orbitools\Core;

/**
 * Block Style Loader
 *
 * Registers and enqueues frontend CSS for Orbitools blocks using a
 * non-render-blocking pattern: styles are registered up front, then
 * enqueued per-block during render_block so they output in wp_footer
 * via print_late_styles(). The style_loader_tag filter applies the
 * media="print" onload="this.media='all'" pattern to defer download.
 *
 * Behaviour identical to the inline implementation that previously
 * lived in Loader.php — extracted to keep Loader focused on lifecycle.
 *
 * @package Orbitools
 * @since 2.0.0
 */
final class Block_Style_Loader
{
    /**
     * Block slugs that ship with frontend CSS.
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
     * Wire up the four WordPress hooks. Idempotent — calling twice
     * registers the same callbacks twice, so call exactly once from
     * Loader::init().
     */
    public static function init(): void
    {
        \add_action('wp_enqueue_scripts', [self::class, 'register_block_styles']);
        \add_filter('render_block', [self::class, 'enqueue_rendered_block_style'], 10, 2);
        \add_filter('style_loader_tag', [self::class, 'async_block_styles'], 10, 2);

        // Load frontend CSS in the editor so block previews are styled
        // correctly. Fires before editorStyle assets, so editor.css can
        // override.
        \add_action('enqueue_block_editor_assets', [self::class, 'enqueue_editor_block_styles']);
    }

    /**
     * Register block frontend styles (without enqueuing).
     *
     * Styles are registered here and enqueued per-block during render_block,
     * so they output in the footer via print_late_styles() (non-render-blocking).
     */
    public static function register_block_styles(): void
    {
        if (self::is_block_css_disabled()) {
            return;
        }

        foreach (self::STYLED_BLOCKS as $block) {
            $css_file = ORBITOOLS_DIR . "build/blocks/{$block}/index.css";

            if (!file_exists($css_file)) {
                continue;
            }

            \wp_register_style(
                "orb-{$block}-style",
                \plugins_url("build/blocks/{$block}/index.css", ORBITOOLS_FILE),
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
     */
    public static function enqueue_rendered_block_style(string $content, array $parsed_block): string
    {
        $block_name = $parsed_block['blockName'] ?? '';

        if (strpos($block_name, 'orb/') === 0) {
            $slug   = substr($block_name, 4);
            $handle = "orb-{$slug}-style";

            if (\wp_style_is($handle, 'registered') && !\wp_style_is($handle, 'enqueued')) {
                \wp_enqueue_style($handle);
            }
        }

        return $content;
    }

    /**
     * Make block <link> tags non-render-blocking by applying
     * the media="print" onload async pattern.
     */
    public static function async_block_styles(string $tag, string $handle): string
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
     */
    public static function enqueue_editor_block_styles(): void
    {
        if (self::is_block_css_disabled()) {
            // Dequeue editor styles registered via block.json editorStyle
            foreach (self::STYLED_BLOCKS as $block) {
                \wp_dequeue_style("orb-{$block}-editor-style");
            }
            return;
        }

        foreach (self::STYLED_BLOCKS as $block) {
            $css_file = ORBITOOLS_DIR . "build/blocks/{$block}/index.css";

            if (!file_exists($css_file)) {
                continue;
            }

            \wp_enqueue_style(
                "orb-{$block}-style",
                \plugins_url("build/blocks/{$block}/index.css", ORBITOOLS_FILE),
                [],
                (string) filemtime($css_file)
            );
        }
    }

    /**
     * Check if block CSS loading is disabled via settings.
     */
    private static function is_block_css_disabled(): bool
    {
        $settings = \get_option('orbitools_settings', []);
        return !empty($settings['disable_block_css']);
    }
}
