<?php

declare(strict_types=1);

namespace Orbitools\Modules\External_Rewrites;

use Orbitools\Core\Abstracts\Module_Base;
use WP_Post;
use WP_Query;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * External Rewrites — per-rule exclusion + 301 engine driven by the
 * module's repeater settings.
 *
 * Each rule targets every post in a (post_type × taxonomy × term)
 * intersection. A rule can:
 *
 *   * Hide matching posts from Yoast XML sitemaps.
 *   * Hide the matched term itself from the sitemap.
 *   * 301 single posts to a chosen destination.
 *   * 301 the term archive to the same destination.
 *
 * Matching post IDs are queried once per rule per request and cached
 * in the object cache for a week; the cache is invalidated when any
 * post of the configured CPT is saved or deleted.
 *
 * Yoast hooks only register when Yoast SEO is loaded (`WPSEO_Options`
 * exists). The `template_redirect` + cache-invalidation hooks fire
 * regardless of Yoast.
 *
 * Ported from the dream-and-leap-sage theme's `App\Services\ExternalRewrites`
 * (+ its service provider). The PHP config file is replaced by the
 * repeater on the module's settings page.
 *
 * @package Orbitools
 * @since   1.0.0
 */
final class External_Rewrites extends Module_Base
{
    protected const VERSION     = '1.0.0';
    private const CACHE_GROUP   = 'orbitools_external_rewrites';
    private const CACHE_TTL_KEY = 'WEEK_IN_SECONDS';

    /**
     * Normalised rules, keyed numerically.
     *
     * @var array<int, array{
     *     post_type: string,
     *     taxonomy: string,
     *     term: string,
     *     redirect_to: int|string,
     *     sitemap: bool,
     *     redirect: bool,
     *     term_archive: bool,
     *     exclude_term: bool,
     * }>
     */
    private array $rules = [];

    public function get_slug(): string
    {
        return 'external-rewrites';
    }

    public function get_name(): string
    {
        return \__('External Rewrites', 'orbitools');
    }

    public function get_description(): string
    {
        return \__('Per-rule exclusion + 301 engine for posts whose canonical home has moved off WordPress.', 'orbitools');
    }

    public function init(): void
    {
        $this->rules = self::load_rules();

        if ($this->rules === []) {
            return;
        }

        if (class_exists(\WPSEO_Options::class)) {
            \add_filter('wpseo_sitemap_entry', [$this, 'filter_sitemap_entry'], 1, 3);
            \add_filter('wpseo_exclude_from_sitemap_by_term_ids', [$this, 'exclude_terms_from_sitemap']);
        }

        \add_action('template_redirect', [$this, 'handle_redirects']);

        foreach ($this->post_types() as $post_type) {
            \add_action("save_post_{$post_type}", [$this, 'clear_cache_on_save']);
        }

        \add_action('delete_post', [$this, 'clear_cache_on_delete']);
    }

    public function get_default_settings(): array
    {
        return [];
    }

    // =========================================================================
    // Rule loading
    // =========================================================================

    /**
     * Read repeater rows out of `orbitools_settings` and normalise them
     * into the legacy rule shape (with `redirect_to` resolved from
     * `redirect_target` + the relevant follow-up field).
     *
     * Rows missing post_type / taxonomy / term are dropped silently —
     * they can't target anything.
     *
     * @return array<int, array{
     *     post_type: string,
     *     taxonomy: string,
     *     term: string,
     *     redirect_to: int|string,
     *     sitemap: bool,
     *     redirect: bool,
     *     term_archive: bool,
     *     exclude_term: bool,
     * }>
     */
    private static function load_rules(): array
    {
        $settings = \get_option('orbitools_settings', []);
        if (!is_array($settings)) {
            return [];
        }

        $rows = $settings['external-rewrites_rules'] ?? [];
        if (!is_array($rows)) {
            return [];
        }

        $out = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $pt   = isset($row['post_type']) ? (string) $row['post_type'] : '';
            $tax  = isset($row['taxonomy'])  ? (string) $row['taxonomy']  : '';
            $term = isset($row['term'])      ? (string) $row['term']      : '';

            if ($pt === '' || $tax === '' || $term === '') {
                continue;
            }

            $target = isset($row['redirect_target']) ? (string) $row['redirect_target'] : 'term_archive';
            $redirect_to = self::resolve_redirect_to($target, $row);

            $out[] = [
                'post_type'    => $pt,
                'taxonomy'     => $tax,
                'term'         => $term,
                'redirect_to'  => $redirect_to,
                'sitemap'      => !empty($row['sitemap']),
                'redirect'     => !empty($row['redirect']),
                'term_archive' => !empty($row['term_archive']),
                'exclude_term' => !empty($row['exclude_term']),
            ];
        }
        return $out;
    }

    /**
     * Map the UI's split `redirect_target` + page/url fields back to the
     * single-value `redirect_to` shape the engine consumes:
     *
     *   - 'term_archive'      → string literal
     *   - 'post_type_archive' → string literal
     *   - 'page'              → int page ID (from the page picker)
     *   - 'url'               → string URL
     *
     * @param array<string, mixed> $row
     * @return int|string
     */
    private static function resolve_redirect_to(string $target, array $row)
    {
        switch ($target) {
            case 'page':
                return (int) ($row['redirect_page'] ?? 0);
            case 'url':
                return (string) ($row['redirect_url'] ?? '');
            case 'post_type_archive':
                return 'post_type_archive';
            case 'term_archive':
            default:
                return 'term_archive';
        }
    }

    /**
     * Unique CPT slugs across all rules — used to wire the per-CPT
     * save_post invalidation hooks without re-scanning rules.
     *
     * @return array<int, string>
     */
    private function post_types(): array
    {
        return array_values(array_unique(array_column($this->rules, 'post_type')));
    }

    // =========================================================================
    // Yoast sitemap filters
    // =========================================================================

    /**
     * @param mixed $url
     * @param mixed $post
     * @return mixed
     */
    public function filter_sitemap_entry($url, string $type, $post)
    {
        if (!$post instanceof WP_Post) {
            return $url;
        }

        foreach ($this->rules as $rule) {
            if (empty($rule['sitemap']) || $post->post_type !== $rule['post_type']) {
                continue;
            }

            if (in_array($post->ID, $this->excluded_ids($rule), true)) {
                return false;
            }
        }

        return $url;
    }

    /**
     * @param mixed $excluded_term_ids
     * @return array<int, int>
     */
    public function exclude_terms_from_sitemap($excluded_term_ids): array
    {
        $excluded = is_array($excluded_term_ids) ? $excluded_term_ids : [];

        foreach ($this->rules as $rule) {
            if (empty($rule['exclude_term'])) {
                continue;
            }

            $term = \get_term_by('slug', $rule['term'], $rule['taxonomy']);

            if ($term && !\is_wp_error($term)) {
                $excluded[] = $term->term_id;
            }
        }

        return $excluded;
    }

    // =========================================================================
    // Redirects
    // =========================================================================

    /**
     * Walk every rule on `template_redirect` and 301 if the request is
     * a targeted single post or term archive.
     */
    public function handle_redirects(): void
    {
        foreach ($this->rules as $rule) {
            if (!empty($rule['redirect']) && \is_singular($rule['post_type'])) {
                if (in_array(\get_queried_object_id(), $this->excluded_ids($rule), true)) {
                    $url = $this->resolve_redirect_url($rule);

                    if ($url) {
                        \wp_redirect($url, 301);
                        exit;
                    }
                }
            }

            if (!empty($rule['term_archive']) && \is_tax($rule['taxonomy'], $rule['term'])) {
                $url = $this->resolve_redirect_url($rule);

                if ($url) {
                    \wp_redirect($url, 301);
                    exit;
                }
            }
        }
    }

    // =========================================================================
    // Cache invalidation
    // =========================================================================

    /**
     * @param int|string $post_id
     */
    public function clear_cache_on_save($post_id): void
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (\wp_is_post_revision((int) $post_id)) {
            return;
        }

        $this->clear_cache_for_post_type((string) \get_post_type((int) $post_id));
    }

    /**
     * @param int|string $post_id
     */
    public function clear_cache_on_delete($post_id): void
    {
        $this->clear_cache_for_post_type((string) \get_post_type((int) $post_id));
    }

    private function clear_cache_for_post_type(string $post_type): void
    {
        if ($post_type === '') {
            return;
        }

        foreach ($this->rules as $rule) {
            if ($rule['post_type'] === $post_type) {
                \wp_cache_delete($this->cache_key($rule), self::CACHE_GROUP);
            }
        }
    }

    // =========================================================================
    // Internals
    // =========================================================================

    /**
     * IDs of every post matched by the rule's (post_type × term) combo.
     * Cached for a week; invalidated by save/delete on any post of the
     * rule's CPT.
     *
     * @param array<string, mixed> $rule
     * @return array<int, int>
     */
    private function excluded_ids(array $rule): array
    {
        $cache_key = $this->cache_key($rule);
        $cached    = \wp_cache_get($cache_key, self::CACHE_GROUP);

        if ($cached !== false) {
            return $cached;
        }

        $query = new WP_Query([
            'post_type'              => $rule['post_type'],
            'posts_per_page'         => -1,
            'fields'                 => 'ids',
            'no_found_rows'          => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            'tax_query'              => [[
                'taxonomy' => $rule['taxonomy'],
                'field'    => 'slug',
                'terms'    => [$rule['term']],
            ]],
        ]);

        $ids = $query->posts ?: [];

        \wp_cache_set($cache_key, $ids, self::CACHE_GROUP, WEEK_IN_SECONDS);

        return $ids;
    }

    /**
     * @param array<string, mixed> $rule
     */
    private function cache_key(array $rule): string
    {
        return "orb_er_ids_{$rule['post_type']}_{$rule['term']}";
    }

    /**
     * Resolve the rule's `redirect_to` into an absolute URL. Returns
     * null if the destination can't be resolved.
     *
     * @param array<string, mixed> $rule
     */
    private function resolve_redirect_url(array $rule): ?string
    {
        $dest = $rule['redirect_to'] ?? null;

        if ($dest === null || $dest === '' || $dest === 0) {
            return null;
        }

        if (is_int($dest)) {
            $url = \get_permalink($dest);
            return $url ?: null;
        }

        if ($dest === 'term_archive') {
            $term = \get_term_by('slug', $rule['term'], $rule['taxonomy']);

            if (!$term || \is_wp_error($term)) {
                return null;
            }

            $url = \get_term_link($term);
            return \is_wp_error($url) ? null : (string) $url;
        }

        if ($dest === 'post_type_archive') {
            $url = \get_post_type_archive_link($rule['post_type']);
            return $url ?: null;
        }

        if (is_string($dest)) {
            return $dest;
        }

        return null;
    }
}
