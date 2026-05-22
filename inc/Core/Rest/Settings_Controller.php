<?php

namespace Orbitools\Core\Rest;

use Orbitools\Core\Helpers\Settings_Manager;
use Orbitools\Core\Module_Manager;
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
 * `{slug}_{key}` (Phase 1 preserves the existing flat shape — a
 * future migration could nest by slug). This controller hides the
 * prefix from clients: requests and responses speak unprefixed keys.
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
                        'description' => 'Module slug.',
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
                        'description' => 'Module slug.',
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
     * Returns an object keyed by unprefixed setting name.
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

        // (object) cast ensures empty results serialise as `{}` not `[]`.
        return new WP_REST_Response((object) $stripped);
    }

    /**
     * PUT/PATCH /settings/{slug}
     *
     * PUT replaces every prefix-matching key (those omitted from the
     * body are removed). PATCH merges the body into existing prefix-
     * matching keys.
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

        foreach ($body as $key => $value) {
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
     * Returns a WP_Error if the slug is not registered, null otherwise.
     */
    private function validate_slug(string $slug): ?WP_Error
    {
        $manager = Module_Manager::instance();

        if ($manager === null) {
            return new WP_Error('orbitools_no_manager', 'Module manager unavailable', ['status' => 500]);
        }
        if (!isset($manager->get_registered()[$slug])) {
            return new WP_Error('orbitools_module_not_found', 'Module not found', ['status' => 404]);
        }
        return null;
    }
}
