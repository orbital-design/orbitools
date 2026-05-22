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
 * Modules REST Controller
 *
 * Routes:
 *   GET  /orbitools/v1/modules                 — list every registered module
 *   GET  /orbitools/v1/modules/{slug}          — single module
 *   POST /orbitools/v1/modules/{slug}/enabled  — toggle enabled state
 *
 * @package Orbitools
 * @since 3.0.0
 */
final class Modules_Controller extends WP_REST_Controller
{
    /**
     * Slug pattern: lowercase alphanumeric + hyphens (matches manifest slug shape).
     */
    private const SLUG_PATTERN = '[a-z0-9\-]+';

    public function __construct()
    {
        $this->namespace = Rest_Server::REST_NAMESPACE;
        $this->rest_base = 'modules';
    }

    public function register_routes(): void
    {
        \register_rest_route($this->namespace, '/' . $this->rest_base, [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$this, 'get_items'],
                'permission_callback' => [$this, 'permissions_check'],
            ],
        ]);

        \register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<slug>' . self::SLUG_PATTERN . ')', [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$this, 'get_item'],
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

        \register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<slug>' . self::SLUG_PATTERN . ')/enabled', [
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'update_enabled'],
                'permission_callback' => [$this, 'permissions_check'],
                'args'                => [
                    'slug' => [
                        'description' => 'Module slug.',
                        'type'        => 'string',
                        'required'    => true,
                    ],
                    'enabled' => [
                        'description' => 'New enabled state.',
                        'type'        => 'boolean',
                        'required'    => true,
                    ],
                ],
            ],
        ]);
    }

    /**
     * All Orbitools REST endpoints require manage_options. The wp_rest
     * nonce check is handled by the REST infrastructure itself when
     * cookie auth is used; tokens / app passwords bypass it cleanly.
     */
    public function permissions_check(): bool
    {
        return \current_user_can('manage_options');
    }

    /**
     * GET /modules
     */
    public function get_items($request)
    {
        $manager = Module_Manager::instance();
        if ($manager === null) {
            return new WP_Error('orbitools_no_manager', 'Module manager unavailable', ['status' => 500]);
        }

        $modules = [];
        foreach (array_keys($manager->get_registered()) as $slug) {
            $modules[] = $this->prepare_module_for_response($slug, $manager);
        }

        return new WP_REST_Response(['modules' => $modules]);
    }

    /**
     * GET /modules/{slug}
     */
    public function get_item($request)
    {
        $slug    = (string) $request['slug'];
        $manager = Module_Manager::instance();

        if ($manager === null) {
            return new WP_Error('orbitools_no_manager', 'Module manager unavailable', ['status' => 500]);
        }
        if (!isset($manager->get_registered()[$slug])) {
            return new WP_Error('orbitools_module_not_found', 'Module not found', ['status' => 404]);
        }

        return new WP_REST_Response($this->prepare_module_for_response($slug, $manager));
    }

    /**
     * POST /modules/{slug}/enabled
     */
    public function update_enabled($request)
    {
        $slug    = (string) $request['slug'];
        $enabled = (bool) $request['enabled'];
        $manager = Module_Manager::instance();

        if ($manager === null) {
            return new WP_Error('orbitools_no_manager', 'Module manager unavailable', ['status' => 500]);
        }
        if (!isset($manager->get_registered()[$slug])) {
            return new WP_Error('orbitools_module_not_found', 'Module not found', ['status' => 404]);
        }

        // update_module_setting refreshes Settings_Manager's static cache
        // internally, so subsequent is_enabled() reads see the new value.
        $settings_manager = new Settings_Manager();
        $settings_manager->update_module_setting($slug, 'enabled', $enabled);

        return new WP_REST_Response($this->prepare_module_for_response($slug, $manager));
    }

    /**
     * Serialise a module for output. Manifest-less modules (external,
     * registered via the orbitools/register_modules action) degrade
     * gracefully — slug becomes the name, category defaults to "modules".
     *
     * @return array<string,mixed>
     */
    private function prepare_module_for_response(string $slug, Module_Manager $manager): array
    {
        $manifest = $manager->get_manifest($slug);

        if ($manifest !== null) {
            return [
                'slug'               => $manifest->slug,
                'name'               => \__($manifest->name, 'orbitools'),
                'description'        => \__($manifest->description, 'orbitools'),
                'version'            => $manifest->version,
                'category'           => $manifest->category,
                'default_enabled'    => $manifest->default_enabled,
                'enabled'            => $manager->is_enabled($slug),
                'has_custom_page'    => false, // Phase 4 populates from discovery manifest.
                'has_dashboard_card' => false, // Phase 4 populates from discovery manifest.
                'requires'           => (object) $manifest->requires,
                'settings_schema'    => [], // Phase 3 populates from manifest.settings.
            ];
        }

        return [
            'slug'               => $slug,
            'name'               => $slug,
            'description'        => '',
            'version'            => '',
            'category'           => 'modules',
            'default_enabled'    => false,
            'enabled'            => $manager->is_enabled($slug),
            'has_custom_page'    => false,
            'has_dashboard_card' => false,
            'requires'           => (object) [],
            'settings_schema'    => [],
        ];
    }
}
