<?php

declare(strict_types=1);

namespace Orbitools\Modules\Head_Cleanup;

use Orbitools\Core\Abstracts\Module_Base;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Head Cleanup — two concerns, each toggle-controlled so admins can
 * opt out of any single rule without losing the rest:
 *
 *   1. **`<head>` bloat** — `wp_head` actions WordPress hangs on for
 *      legacy clients (feed discovery, RSD / WLW manifest, oEmbed
 *      discovery, prev/next rel links, generator tag, shortlink,
 *      REST discovery, recent-comments widget CSS).
 *
 *   2. **Frontend assets** — features WP ships globally that most
 *      Orbital sites don't use: the emoji polyfill (script + inline
 *      style + s.w.org DNS-prefetch hint), the wp-embed.min.js that
 *      lets *other* sites embed *yours*, and the pingback surface
 *      (`X-Pingback` HTTP header + the XML-RPC pingback methods,
 *      which double as a known DDoS reflection vector).
 *
 * Ported from the dream-and-leap-sage theme's
 * `App\Providers\HtmlHeadOptimizerServiceProvider`. The theme also
 * stripped stylesheet boilerplate + the s.w.org DNS-prefetch + the
 * WP Popular Posts inline CSS — the first two are effectively zero-
 * impact after gzip, and WPPP-specific cruft doesn't belong as a
 * sitewide toggle, so they were dropped on the port. The s.w.org
 * removal still happens, but rolled into the emoji disable (the
 * hint exists solely for emoji).
 *
 * @package Orbitools
 * @since   1.0.0
 */
final class Head_Cleanup extends Module_Base
{
    protected const VERSION = '1.0.0';

    public function get_slug(): string
    {
        return 'head-cleanup';
    }

    public function get_name(): string
    {
        return \__('Head Cleanup', 'orbitools');
    }

    public function get_description(): string
    {
        return \__('Trim WordPress\'s default <head> output — drop discovery tags, strip stylesheet boilerplate, kill stray hints.', 'orbitools');
    }

    public function init(): void
    {
        \add_action('init', [$this, 'remove_head_actions']);

        if ($this->is_setting_on('disable_emoji', true)) {
            $this->register_emoji_disable();
        }

        if ($this->is_setting_on('disable_wp_embed_script', true)) {
            \add_action('wp_enqueue_scripts', [$this, 'dequeue_wp_embed_script'], 100);
        }

        if ($this->is_setting_on('disable_pingback', true)) {
            \add_filter('wp_headers',     [$this, 'strip_pingback_header']);
            \add_filter('xmlrpc_methods', [$this, 'remove_pingback_xmlrpc_methods']);
        }
    }

    public function get_default_settings(): array
    {
        return [
            'disable_feed_links'          => true,
            'disable_editor_discovery'    => true,
            'disable_relational_links'    => true,
            'disable_wp_identity'         => true,
            'disable_oembed_discovery'    => true,
            'disable_recent_comments_css' => true,
            'disable_emoji'               => true,
            'disable_wp_embed_script'     => true,
            'disable_pingback'            => true,
        ];
    }

    // =========================================================================
    // <head> bloat removal
    // =========================================================================

    /**
     * Run on `init` (priority 10) so we land after WordPress core
     * has registered its default `wp_head` callbacks but before any
     * theme / plugin code that depends on them.
     */
    public function remove_head_actions(): void
    {
        if ($this->is_setting_on('disable_feed_links', true)) {
            \remove_action('wp_head', 'feed_links_extra', 3);
            \remove_action('wp_head', 'feed_links', 2);
        }

        if ($this->is_setting_on('disable_editor_discovery', true)) {
            \remove_action('wp_head', 'rsd_link');
            \remove_action('wp_head', 'wlwmanifest_link');
        }

        if ($this->is_setting_on('disable_relational_links', true)) {
            \remove_action('wp_head', 'index_rel_link');
            \remove_action('wp_head', 'parent_post_rel_link', 10, 0);
            \remove_action('wp_head', 'start_post_rel_link', 10, 0);
            \remove_action('wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0);
        }

        if ($this->is_setting_on('disable_wp_identity', true)) {
            \remove_action('wp_head', 'wp_generator');
            \remove_action('wp_head', 'wp_shortlink_wp_head', 10);
            \remove_action('wp_head', 'rest_output_link_wp_head', 10);
        }

        if ($this->is_setting_on('disable_oembed_discovery', true)) {
            \remove_action('wp_head', 'wp_oembed_add_discovery_links');
            \remove_action('wp_head', 'wp_oembed_add_host_js');
        }

        if ($this->is_setting_on('disable_recent_comments_css', true)) {
            // Registered as a filter under the hood, not an action.
            if (\has_filter('wp_head', 'wp_widget_recent_comments_style')) {
                \remove_filter('wp_head', 'wp_widget_recent_comments_style');
            }
        }
    }

    // =========================================================================
    // Emoji
    // =========================================================================

    /**
     * Strip WordPress's emoji polyfill end-to-end:
     *
     *   * The detection `<script>` on wp_head + admin_print_scripts
     *   * The inline emoji `<style>` on wp_print_styles + admin_print_styles
     *   * The `wp_staticize_emoji` content filter on RSS / comments
     *   * `wp_staticize_emoji_for_email` on outbound mail
     *   * The TinyMCE `wpemoji` plugin (legacy classic editor)
     *   * The s.w.org `dns-prefetch` hint (only added for emoji)
     *
     * Removing the prefetch hint here too is what lets us drop the
     * standalone `drop_sw_org_prefetch` toggle — the hint exists
     * solely because of the emoji polyfill.
     */
    private function register_emoji_disable(): void
    {
        \remove_action('wp_head',             'print_emoji_detection_script', 7);
        \remove_action('admin_print_scripts', 'print_emoji_detection_script');
        \remove_action('wp_print_styles',     'print_emoji_styles');
        \remove_action('admin_print_styles',  'print_emoji_styles');

        \remove_filter('the_content_feed',  'wp_staticize_emoji');
        \remove_filter('comment_text_rss',  'wp_staticize_emoji');
        \remove_filter('wp_mail',           'wp_staticize_emoji_for_email');

        \add_filter('tiny_mce_plugins',   [$this, 'strip_tinymce_emoji_plugin']);
        \add_filter('wp_resource_hints',  [$this, 'strip_sw_org_prefetch'], 10, 2);
    }

    /**
     * @param mixed $plugins
     * @return mixed
     */
    public function strip_tinymce_emoji_plugin($plugins)
    {
        if (!is_array($plugins)) {
            return $plugins;
        }
        return array_values(array_diff($plugins, ['wpemoji']));
    }

    /**
     * Drop the `s.w.org` DNS-prefetch hint (only added by core to
     * support the emoji polyfill we just disabled).
     *
     * @param mixed $urls
     * @return mixed
     */
    public function strip_sw_org_prefetch($urls, string $relation_type)
    {
        if ($relation_type !== 'dns-prefetch' || !is_array($urls)) {
            return $urls;
        }
        return array_values(array_filter($urls, static function ($url) {
            $host = is_array($url) ? ($url['href'] ?? '') : $url;
            return strpos((string) $host, 's.w.org') === false;
        }));
    }

    // =========================================================================
    // wp-embed.min.js
    // =========================================================================

    /**
     * Dequeue the ~2.5KB `wp-embed.min.js` script. Hooked late on
     * wp_enqueue_scripts so it lands after any theme code that
     * intentionally registers an embed handler — though for Orbital
     * sites this is fine to drop unconditionally.
     */
    public function dequeue_wp_embed_script(): void
    {
        \wp_dequeue_script('wp-embed');
    }

    // =========================================================================
    // Pingback
    // =========================================================================

    /**
     * Strip the `X-Pingback` HTTP header so we're not advertising
     * the XML-RPC endpoint at the response-header level.
     *
     * @param mixed $headers
     * @return mixed
     */
    public function strip_pingback_header($headers)
    {
        if (is_array($headers)) {
            unset($headers['X-Pingback']);
        }
        return $headers;
    }

    /**
     * Drop the pingback XML-RPC methods. Leaves the rest of XML-RPC
     * intact (Jetpack, the mobile app, etc.) — only the pingback
     * methods are stripped, which closes the DDoS reflection vector
     * without breaking anything else.
     *
     * @param mixed $methods
     * @return mixed
     */
    public function remove_pingback_xmlrpc_methods($methods)
    {
        if (is_array($methods)) {
            unset($methods['pingback.ping']);
            unset($methods['pingback.extensions.getPingbacks']);
        }
        return $methods;
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function is_setting_on(string $key, bool $default = false): bool
    {
        $value = $this->get_setting($key, $default);
        if (is_bool($value)) {
            return $value;
        }
        if (is_string($value)) {
            return $value !== '' && $value !== '0' && strtolower($value) !== 'false';
        }
        return (bool) $value;
    }
}
