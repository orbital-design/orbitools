<?php

namespace Orbitools\Core\Rest;

use Orbitools\Core\Helpers\Settings_Manager;
use Orbitools\Core\Module_Manager;
use Orbitools\Core\Pages\Theme_Pages_Registry;
use WP_Error;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Settings REST Controller
 *
 * Routes:
 *   GET   /orbitools/v1/settings/{slug}  — get module's current settings
 *   PUT   /orbitools/v1/settings/{slug}  — replace module's settings
 *   PATCH /orbitools/v1/settings/{slug}  — partial-merge module's settings
 *
 * Storage is the flat orbitools_settings option with keys shaped
 * `{slug}_{key}`. Fields whose schema declares a `wp_option` key are
 * read from / written to that named WP option instead — used by the
 * theme-pages API so Site Title and friends actually update WP's
 * own blogname/blogdescription/etc.
 *
 * The controller accepts both module slugs (from Module_Manager) and
 * theme-page slugs (from Theme_Pages_Registry); the validation step
 * is the only place the two registries are unified.
 *
 * @package Orbitools
 * @since 3.0.0
 */
final class Settings_Controller extends WP_REST_Controller
{
    private const SLUG_PATTERN = '[a-z0-9\-]+';
    private const OPTION_KEY   = 'orbitools_settings';

    public function __construct()
    {
        $this->namespace = Rest_Server::REST_NAMESPACE;
        $this->rest_base = 'settings';
    }

    public function register_routes(): void
    {
        \register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<slug>' . self::SLUG_PATTERN . ')', [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$this, 'get_settings'],
                'permission_callback' => [$this, 'permissions_check'],
                'args'                => [
                    'slug' => [
                        'description' => 'Page or module slug.',
                        'type'        => 'string',
                        'required'    => true,
                    ],
                ],
            ],
            [
                'methods'             => WP_REST_Server::EDITABLE, // PUT, PATCH, POST
                'callback'            => [$this, 'write_settings'],
                'permission_callback' => [$this, 'permissions_check'],
                'args'                => [
                    'slug' => [
                        'description' => 'Page or module slug.',
                        'type'        => 'string',
                        'required'    => true,
                    ],
                ],
            ],
        ]);
    }

    public function permissions_check(): bool
    {
        return \current_user_can('manage_options');
    }

    /**
     * GET /settings/{slug}
     *
     * Returns an object keyed by unprefixed setting name. Fields with
     * `wp_option` overlay their values from the named WP option.
     */
    public function get_settings($request)
    {
        $slug = (string) $request['slug'];

        $error = $this->validate_slug($slug);
        if ($error !== null) {
            return $error;
        }

        $all     = $this->get_all_settings();
        $prefix  = $slug . '_';
        $stripped = [];

        foreach ($all as $key => $value) {
            if (strpos($key, $prefix) === 0) {
                $stripped[substr($key, strlen($prefix))] = $value;
            }
        }

        // Overlay wp_option-bound fields. These never live in
        // orbitools_settings — the schema's wp_option name IS the
        // source of truth. Decode HTML entities for string values
        // because WordPress's sanitize_option runs esc_html on save
        // for several core options (blogname, blogdescription, …),
        // and the editor needs the raw text the user originally
        // typed, not the encoded form.
        foreach ($this->get_fields_with_wp_option($slug) as $field_id => $option_name) {
            $value = \get_option($option_name, null);
            if (is_string($value)) {
                $value = \html_entity_decode($value, ENT_QUOTES, 'UTF-8');
            }
            $stripped[$field_id] = $value;
        }

        // (object) cast ensures empty results serialise as `{}` not `[]`.
        return new WP_REST_Response((object) $stripped);
    }

    /**
     * PUT/PATCH /settings/{slug}
     *
     * PUT replaces every prefix-matching key (those omitted from the
     * body are removed). PATCH merges the body into existing prefix-
     * matching keys. wp_option-bound fields are split out and
     * written via update_option(); regular keys go into the
     * orbitools_settings option.
     */
    public function write_settings($request)
    {
        $slug   = (string) $request['slug'];
        $method = strtoupper($request->get_method());
        $body   = $request->get_json_params();

        if (!is_array($body)) {
            return new WP_Error('orbitools_invalid_body', 'Request body must be a JSON object.', ['status' => 400]);
        }

        $error = $this->validate_slug($slug);
        if ($error !== null) {
            return $error;
        }

        $wp_option_map = $this->get_fields_with_wp_option($slug);

        // Split incoming body: wp-option-bound fields go to update_option;
        // regular fields go into orbitools_settings under the prefix.
        $orbitools_body = [];
        $wp_option_writes = [];

        foreach ($body as $key => $value) {
            if (isset($wp_option_map[$key])) {
                $wp_option_writes[$wp_option_map[$key]] = $value;
            } else {
                $orbitools_body[$key] = $value;
            }
        }

        // Persist the wp_option side first — these are independent of
        // the orbitools_settings row, no batching benefit.
        foreach ($wp_option_writes as $option_name => $value) {
            \update_option($option_name, $value);
        }

        // Persist the orbitools_settings side.
        $all    = $this->get_all_settings();
        $prefix = $slug . '_';

        if ($method === 'PUT') {
            // Full replace: drop every existing prefix-matching key first.
            foreach (array_keys($all) as $key) {
                if (strpos($key, $prefix) === 0) {
                    unset($all[$key]);
                }
            }
        }

        foreach ($orbitools_body as $key => $value) {
            $all[$prefix . $key] = $value;
        }

        \update_option(self::OPTION_KEY, $all);

        // Settings_Manager caches statically; clear so the next read
        // (including the one in get_settings below) sees fresh data.
        (new Settings_Manager())->clear_cache();

        return $this->get_settings($request);
    }

    /**
     * @return array<string,mixed>
     */
    private function get_all_settings(): array
    {
        $opt = \get_option(self::OPTION_KEY, []);
        return is_array($opt) ? $opt : [];
    }

    /**
     * Build a [field_id => wp_option_name] map for every field in the
     * given slug's schema that declares a `wp_option`. Pages without
     * any wp-bound fields return an empty array, so callers can use
     * this as a sentinel for "no wp_option fields to overlay".
     *
     * @return array<string,string>
     */
    private function get_fields_with_wp_option(string $slug): array
    {
        $fields = $this->get_fields_for_slug($slug);
        $map = [];
        foreach ($fields as $field) {
            if (!is_array($field) || !isset($field['id'])) {
                continue;
            }
            if (!empty($field['wp_option']) && is_string($field['wp_option'])) {
                $map[(string) $field['id']] = $field['wp_option'];
            }
        }
        return $map;
    }

    /**
     * Resolve a slug to its field schema array — checks the module
     * registry first, then the theme-pages registry.
     *
     * @return array<int,array<string,mixed>>
     */
    private function get_fields_for_slug(string $slug): array
    {
        $manager = Module_Manager::instance();
        if ($manager !== null) {
            $manifest = $manager->get_manifest($slug);
            if ($manifest !== null) {
                return $manifest->settings;
            }
        }
        $page = Theme_Pages_Registry::instance()->get_page($slug);
        return $page === null ? [] : $page->fields;
    }

    /**
     * Returns a WP_Error if the slug isn't a registered module or
     * theme page, null otherwise.
     */
    private function validate_slug(string $slug): ?WP_Error
    {
        $manager = Module_Manager::instance();
        if ($manager !== null && isset($manager->get_registered()[$slug])) {
            return null;
        }
        if (Theme_Pages_Registry::instance()->get_page($slug) !== null) {
            return null;
        }
        return new WP_Error(
            'orbitools_slug_not_found',
            'No module or theme page registered with that slug.',
            ['status' => 404]
        );
    }
}
