<?php

namespace Orbitools\Core\Rest;

use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Field Types REST Controller
 *
 * Routes:
 *   GET /orbitools/v1/field-types — list registered field types
 *
 * Phase 1 stub: returns an empty array. Phase 3 introduces the
 * server-side field type registry that this endpoint surfaces, at
 * which point each entry will carry its TypeScript-mirroring schema
 * (id, label, value type, per-type validation hints) so the React
 * client can render appropriate controls.
 *
 * @package Orbitools
 * @since 3.0.0
 */
final class Field_Types_Controller extends WP_REST_Controller
{
    public function __construct()
    {
        $this->namespace = Rest_Server::REST_NAMESPACE;
        $this->rest_base = 'field-types';
    }

    public function register_routes(): void
    {
        \register_rest_route($this->namespace, '/' . $this->rest_base, [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$this, 'get_field_types'],
                'permission_callback' => [$this, 'permissions_check'],
            ],
        ]);
    }

    public function permissions_check(): bool
    {
        return \current_user_can('manage_options');
    }

    /**
     * GET /field-types
     */
    public function get_field_types($request)
    {
        // Phase 3 will populate this from the field type registry.
        return new WP_REST_Response(['field_types' => []]);
    }
}
