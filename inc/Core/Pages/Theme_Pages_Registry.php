<?php

namespace Orbitools\Core\Pages;

/**
 * Theme Pages registry.
 *
 * Single source of truth for pages registered via the
 * `orbitools/register_theme_pages` filter. Bootstrapped lazily on
 * first access — themes hook the filter during `after_setup_theme`
 * (or earlier), and by the time Loader::init() spins up the REST
 * server and React_Admin the filter has already fired.
 *
 * Mirrors the read-only surface Module_Manager exposes for module
 * manifests so the rest of the codebase can treat them uniformly:
 * get_page(slug), get_pages(), and a helper for the field schema
 * lookups Settings_Controller needs.
 *
 * @package Orbitools
 * @since 3.1.0
 */
final class Theme_Pages_Registry
{
    private static ?self $instance = null;

    /** @var array<string,Theme_Page>|null */
    private ?array $pages = null;

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * @return array<string,Theme_Page>
     */
    public function get_pages(): array
    {
        if ($this->pages === null) {
            $this->load();
        }
        return $this->pages ?? [];
    }

    public function get_page(string $slug): ?Theme_Page
    {
        $pages = $this->get_pages();
        return $pages[$slug] ?? null;
    }

    /**
     * Return the field schema for a given page slug, or [] if no page
     * is registered under that slug. Used by Settings_Controller for
     * wp_option resolution.
     *
     * @return array<int,array<string,mixed>>
     */
    public function get_fields(string $slug): array
    {
        $page = $this->get_page($slug);
        return $page === null ? [] : $page->fields;
    }

    private function load(): void
    {
        /**
         * Fires the registration filter and stores normalised pages.
         *
         * @param array<string,array<string,mixed>> $pages
         */
        $raw = (array) \apply_filters('orbitools/register_theme_pages', []);

        $pages = [];
        foreach ($raw as $key => $config) {
            if (!is_array($config)) {
                continue;
            }
            // Allow either array-keyed or list-style registrations:
            // `'site-settings' => [...]` and `[..., 'slug' => 'site-settings'...]`.
            if (!isset($config['slug']) && is_string($key)) {
                $config['slug'] = $key;
            }
            $page = Theme_Page::from_array($config);
            if ($page !== null) {
                $pages[$page->slug] = $page;
            }
        }

        $this->pages = $pages;
    }
}
