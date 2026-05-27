<?php

namespace Orbitools\Core\Rest;

use Orbitools\Core\Module\Module_Manifest;
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
     *
     * Returns the v1 field type catalog. Source of truth lives in
     * {@see Module_Manifest::FIELD_TYPES}; the React layer ships a
     * matching renderer for each. Clients can sanity-check unknown
     * types from manifests they haven't bundled for (the React layer
     * renders a FieldFallback in that case).
     */
    public function get_field_types($request)
    {
        $catalog = [];
        foreach (Module_Manifest::FIELD_TYPES as $type) {
            $catalog[] = [
                'id'    => $type,
                'label' => $this->describe_type($type),
            ];
        }
        return new WP_REST_Response(['field_types' => $catalog]);
    }

    /**
     * Human-facing label for the catalog entry. Not used for rendering —
     * surfaces in dev tooling and the field-types diagnostic page only.
     */
    private function describe_type(string $type): string
    {
        $labels = [
            'text'           => \__('Text', 'orbitools'),
            'textarea'       => \__('Textarea', 'orbitools'),
            'number'         => \__('Number', 'orbitools'),
            'toggle'         => \__('Toggle', 'orbitools'),
            'select'         => \__('Select', 'orbitools'),
            'multiselect'    => \__('Multi-select', 'orbitools'),
            'radio'          => \__('Radio', 'orbitools'),
            'checkbox-group' => \__('Checkbox group', 'orbitools'),
            'color'          => \__('Color', 'orbitools'),
            'range'          => \__('Range', 'orbitools'),
            'media'          => \__('Media', 'orbitools'),
            'page'           => \__('Page picker', 'orbitools'),
            'repeater'       => \__('Repeater', 'orbitools'),
        ];
        return $labels[$type] ?? $type;
    }
}
