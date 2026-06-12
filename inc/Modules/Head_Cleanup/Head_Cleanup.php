<?php

declare(strict_types=1);

namespace Orbitools\Modules\Head_Cleanup;

use Orbitools\Core\Abstracts\Module_Base;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Head Cleanup — remove the `wp_head` actions WordPress hangs on for
 * legacy clients (feed discovery, RSD / WLW manifest, oEmbed
 * discovery, prev/next rel links, generator tag, shortlink, REST
 * discovery, recent-comments widget CSS). Each is its own toggle on
 * the settings page so admins can opt out of any single rule without
 * losing the rest.
 *
 * Ported from the dream-and-leap-sage theme's
 * `App\Providers\HtmlHeadOptimizerServiceProvider`. The theme version
 * also stripped stylesheet boilerplate + the s.w.org DNS-prefetch +
 * the WP Popular Posts inline CSS — dropped here because their
 * measurable impact was effectively zero (cosmetic byte savings that
 * gzip out, or plugin-specific cruft that doesn't belong as a
 * sitewide toggle).
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
