<?php

namespace Orbitools\Core\Rest;

use Orbitools\Core\Pages\Theme_Pages_Registry;
use WP_REST_Controller;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Theme Pages REST Controller
 *
 * Route:
 *   GET /orbitools/v1/theme-pages — list every registered theme page,
 *                                   sorted by position.
 *
 * Each entry mirrors the same shape modules use (id, label,
 * description, sections, settings_schema) so the React renderer
 * treats them uniformly. Field-level wp_option bindings travel
 * through unchanged — the front-end doesn't need to know which
 * fields are bound to WP options; Settings_Controller takes care
 * of the read/write side.
 *
 * @package Orbitools
 * @since 3.1.0
 */
final class Theme_Pages_Controller extends WP_REST_Controller
{
    public function __construct()
    {
        $this->namespace = Rest_Server::REST_NAMESPACE;
        $this->rest_base = 'theme-pages';
    }

    public function register_routes(): void
    {
        \register_rest_route($this->namespace, '/' . $this->rest_base, [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$this, 'get_pages'],
                'permission_callback' => [$this, 'permissions_check'],
            ],
        ]);
    }

    public function permissions_check(): bool
    {
        return \current_user_can('manage_options');
    }

    public function get_pages(): WP_REST_Response
    {
        $pages = Theme_Pages_Registry::instance()->get_pages();

        $payload = [];
        foreach ($pages as $page) {
            $payload[] = [
                'slug'            => $page->slug,
                'label'           => $page->label,
                'description'     => $page->description,
                'icon'            => $page->icon,
                'position'        => $page->position,
                'sections'        => $page->sections,
                'settings_schema' => $page->fields,
            ];
        }

        \usort($payload, static function (array $a, array $b): int {
            return $a['position'] <=> $b['position'];
        });

        return new WP_REST_Response(['pages' => $payload]);
    }
}
