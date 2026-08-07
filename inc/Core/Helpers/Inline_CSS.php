<?php
/**
 * Inline CSS consolidator
 *
 * The plugin emits a few small dynamic stylesheets (gap classes, aspect-ratio
 * classes, the content-width constraint). Rather than each shipping its own
 * `<style id="orbitools-…-inline-css">` tag, this helper folds them into one
 * block on the frontend.
 *
 * It attaches to WordPress's own `global-styles` handle when registered, so the
 * CSS lands inside `<style id="global-styles-inline-css">` alongside the rest
 * of the global styles. If that handle isn't available (e.g. the Editor
 * Settings global-styles-in-head feature is off), it falls back to a single
 * shared `orbitools-inline` handle — still one tag, not many.
 *
 * @since 1.0.0
 */

namespace Orbitools\Core\Helpers;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Inline_CSS
{
    /**
     * Whether our own fallback handle has been registered this request.
     *
     * @var bool
     */
    private static $own_registered = false;

    /**
     * Attach frontend inline CSS to the consolidated handle.
     *
     * @param string $css CSS to add.
     */
    public static function add_frontend(string $css): void
    {
        if (trim($css) === '') {
            return;
        }

        \wp_add_inline_style(self::frontend_handle(), $css);
    }

    /**
     * The handle frontend inline CSS should attach to — WordPress's
     * `global-styles` if it's registered, otherwise our own shared handle.
     *
     * @return string
     */
    private static function frontend_handle(): string
    {
        if (\wp_style_is('global-styles', 'registered')) {
            return 'global-styles';
        }

        if (!self::$own_registered) {
            \wp_register_style('orbitools-inline', false);
            \wp_enqueue_style('orbitools-inline');
            self::$own_registered = true;
        }

        return 'orbitools-inline';
    }
}
