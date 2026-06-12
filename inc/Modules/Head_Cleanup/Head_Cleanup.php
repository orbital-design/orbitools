<?php

declare(strict_types=1);

namespace Orbitools\Modules\Head_Cleanup;

use Orbitools\Core\Abstracts\Module_Base;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Head Cleanup — trim the WordPress `<head>` to what modern sites
 * actually use.
 *
 * Three concerns, each split into individual toggles on the settings
 * page so admins can opt out of any single rule without losing the
 * rest:
 *
 *   1. **`<head>` bloat** — `wp_head` actions WordPress hangs on for
 *      legacy clients (feed discovery, RSD / WLW manifest, oEmbed
 *      discovery, prev/next rel links, generator tag, shortlink,
 *      REST discovery, recent-comments widget CSS).
 *
 *   2. **Stylesheet markup** — rewrites `<link rel=stylesheet>`
 *      tags to drop the `id=`, `type=`, and `media="all"` attributes.
 *      The regex is intentionally narrow and falls back to the
 *      original tag if WordPress's output ever shifts.
 *
 *   3. **Resource hints** — drops the default `s.w.org` DNS-prefetch
 *      hint (used for an emoji CDN we don't ship), and the inline
 *      loader CSS the WordPress Popular Posts plugin injects.
 *
 * Ported from the dream-and-leap-sage theme's
 * `App\Providers\HtmlHeadOptimizerServiceProvider`.
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

        if ($this->is_setting_on('clean_stylesheet_links', true)) {
            \add_filter('style_loader_tag', [$this, 'clean_css_link_tag']);
        }

        if ($this->is_setting_on('drop_sw_org_prefetch', true)) {
            \add_filter('wp_resource_hints', [$this, 'filter_resource_hints'], 10, 2);
        }
    }

    public function get_default_settings(): array
    {
        return [
            'disable_feed_links'           => true,
            'disable_editor_discovery'     => true,
            'disable_relational_links'     => true,
            'disable_wp_identity'          => true,
            'disable_oembed_discovery'     => true,
            'disable_recent_comments_css'  => true,
            'clean_stylesheet_links'       => true,
            'drop_sw_org_prefetch'         => true,
            'disable_wpp_inline_css'       => true,
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

        if ($this->is_setting_on('disable_wpp_inline_css', true)) {
            // No-op when WP Popular Posts isn't active — remove_action
            // is safe to call against a hook nothing's bound to.
            \remove_action('wp_head', 'WordPressPopularPosts\Front\Front::inline_loading_css');
        }
    }

    // =========================================================================
    // Stylesheet boilerplate strip
    // =========================================================================

    /**
     * Rewrite `<link rel=stylesheet>` tags emitted by `wp_head` to
     * drop the `id=`, `type=`, and `media="all"` attributes — browsers
     * default `media` to `all`, `type=text/css` has been the default
     * since HTML5, and the per-stylesheet `id` is only useful to
     * WordPress's own enqueue tracking which doesn't read it back.
     *
     * The regex is intentionally narrow against the exact format
     * WordPress currently emits. On any mismatch we return the input
     * untouched, so a future core change to that markup can't break
     * the page.
     */
    public function clean_css_link_tag(string $input): string
    {
        \preg_match_all(
            "!<link rel='stylesheet'\s?(id='[^']+')?\s+href='(.*)' type='text/css' media='(.*)' />!",
            $input,
            $matches,
        );

        if (empty($matches[2])) {
            return $input;
        }

        $media = '';
        if (isset($matches[3][0]) && $matches[3][0] !== '' && $matches[3][0] !== 'all') {
            $media = ' media="' . \esc_attr($matches[3][0]) . '"';
        }

        return '<link rel="stylesheet" href="' . \esc_url($matches[2][0]) . '"' . $media . '>' . "\n";
    }

    // =========================================================================
    // Resource hints
    // =========================================================================

    /**
     * Drop WordPress's default `s.w.org` DNS-prefetch hint while
     * letting any custom hints through unchanged.
     *
     * @param mixed  $urls
     * @return mixed
     */
    public function filter_resource_hints($urls, string $relation_type)
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
