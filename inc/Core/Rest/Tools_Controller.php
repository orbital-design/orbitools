<?php

namespace Orbitools\Core\Rest;

use Orbitools\Core\Module_Manager;
use WP_Error;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Tools REST Controller
 *
 * Import / export the plugin's settings as a single JSON blob, so a
 * fresh install can be primed from another site's saved configuration.
 *
 * Routes:
 *   GET  /orbitools/v1/tools/export — bundle of settings + metadata
 *   POST /orbitools/v1/tools/import — restore from a previously
 *                                     exported bundle, optionally
 *                                     filtered to specific slugs.
 *
 * Entity-ID fields (`page`, `media`) are blanked on export by default
 * — page / media IDs don't transfer between sites. Repeater sub-
 * fields of those types are walked recursively. The set of stripped
 * storage keys ships with the payload so the UI can surface "you
 * need to re-pick these on the new site".
 *
 * @package Orbitools
 * @since 3.0.0
 */
final class Tools_Controller extends WP_REST_Controller
{
    /**
     * Field types that store a WordPress entity ID and therefore
     * shouldn't transfer between installs.
     */
    private const ENTITY_FIELD_TYPES = ['page', 'media'];

    public function __construct()
    {
        $this->namespace = Rest_Server::REST_NAMESPACE;
        $this->rest_base = 'tools';
    }

    public function register_routes(): void
    {
        \register_rest_route($this->namespace, '/' . $this->rest_base . '/export', [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$this, 'export'],
                'permission_callback' => [$this, 'permissions_check'],
            ],
        ]);

        \register_rest_route($this->namespace, '/' . $this->rest_base . '/import', [
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'import'],
                'permission_callback' => [$this, 'permissions_check'],
                'args'                => [
                    'payload' => [
                        'description' => 'The export payload to restore.',
                        'required'    => true,
                    ],
                    'apply_slugs' => [
                        'description' => 'Optional whitelist of slugs to restore. Omit to apply everything in the payload.',
                        'required'    => false,
                    ],
                ],
            ],
        ]);
    }

    public function permissions_check(): bool
    {
        return \current_user_can('manage_options');
    }

    // =========================================================================
    // Export
    // =========================================================================

    /**
     * Build the export payload — see class docblock for the entity-ID
     * stripping rationale.
     *
     * @return WP_REST_Response
     */
    public function export(WP_REST_Request $request)
    {
        $settings    = (array) \get_option('orbitools_settings', []);
        $field_index = $this->build_field_index();

        $stripped_keys = [];
        $sanitized     = [];
        foreach ($settings as $key => $value) {
            if (isset($field_index[$key])) {
                $sanitized[$key] = $this->strip_entity_ids($value, $field_index[$key], $key, $stripped_keys);
            } else {
                // No matching schema (orphaned key, or `{slug}_enabled`
                // which isn't part of any schema). Keep verbatim.
                $sanitized[$key] = $value;
            }
        }

        return new WP_REST_Response([
            'version'       => $this->plugin_version(),
            'exported_at'   => \gmdate('c'),
            'source'        => [
                'site_url'   => \home_url(),
                'wp_version' => \get_bloginfo('version'),
            ],
            'modules'       => $this->module_index(),
            'theme_pages'   => $this->theme_pages_index(),
            'settings'      => $sanitized,
            'stripped_keys' => $stripped_keys,
        ]);
    }

    /**
     * Recursively strip entity-ID values from a stored value, given
     * its field schema. Tracks the storage path of each stripped
     * value in `$stripped_keys` so the UI can surface them.
     *
     * @param mixed              $value
     * @param array<string,mixed> $schema
     * @param string             $path           Storage path for reporting (e.g. "site-settings_share_links[0].label")
     * @param array<int,string>  $stripped_keys  By-ref accumulator
     * @return mixed
     */
    private function strip_entity_ids($value, array $schema, string $path, array &$stripped_keys)
    {
        $type = (string) ($schema['type'] ?? '');

        if (in_array($type, self::ENTITY_FIELD_TYPES, true)) {
            if ($value !== 0 && $value !== '' && $value !== null) {
                $stripped_keys[] = $path;
            }
            return 0;
        }

        if ($type === 'repeater' && is_array($value)) {
            $sub_fields = $schema['sub_fields'] ?? [];
            if (!is_array($sub_fields)) {
                return $value;
            }
            $sub_index = [];
            foreach ($sub_fields as $sf) {
                if (is_array($sf) && isset($sf['id'])) {
                    $sub_index[(string) $sf['id']] = $sf;
                }
            }

            $out = [];
            foreach ($value as $row_idx => $row) {
                if (!is_array($row)) {
                    $out[] = $row;
                    continue;
                }
                foreach ($sub_index as $sub_id => $sub_schema) {
                    if (array_key_exists($sub_id, $row)) {
                        $row[$sub_id] = $this->strip_entity_ids(
                            $row[$sub_id],
                            $sub_schema,
                            "{$path}[{$row_idx}].{$sub_id}",
                            $stripped_keys
                        );
                    }
                }
                $out[] = $row;
            }
            return $out;
        }

        return $value;
    }

    /**
     * Build a flat map of `{slug}_{field_id}` → field schema across
     * every module manifest + theme page. Used by `strip_entity_ids()`
     * to know what to walk.
     *
     * @return array<string, array<string,mixed>>
     */
    private function build_field_index(): array
    {
        $index = [];

        $manager = Module_Manager::instance();
        if ($manager !== null) {
            foreach ($manager->get_manifests() as $manifest) {
                foreach ($manifest->settings as $field) {
                    if (!is_array($field) || !isset($field['id'])) {
                        continue;
                    }
                    $key         = $manifest->slug . '_' . (string) $field['id'];
                    $index[$key] = $field;
                }
            }
        }

        $theme_pages = $this->theme_pages_raw();
        foreach ($theme_pages as $page) {
            if (!is_array($page) || empty($page['slug']) || empty($page['fields']) || !is_array($page['fields'])) {
                continue;
            }
            $slug = (string) $page['slug'];
            foreach ($page['fields'] as $field) {
                if (!is_array($field) || !isset($field['id'])) {
                    continue;
                }
                $key         = $slug . '_' . (string) $field['id'];
                $index[$key] = $field;
            }
        }

        return $index;
    }

    /**
     * Build a UI-friendly module list keyed by slug — surfaces the
     * `category` so the React side can group + filter on import.
     *
     * @return array<int, array<string,string>>
     */
    private function module_index(): array
    {
        $manager = Module_Manager::instance();
        if ($manager === null) {
            return [];
        }
        $out = [];
        foreach ($manager->get_modules_metadata() as $meta) {
            $out[] = [
                'slug'     => (string) $meta['slug'],
                'name'     => (string) $meta['name'],
                'category' => (string) $meta['category'],
            ];
        }
        return $out;
    }

    /**
     * @return array<int, array<string,string>>
     */
    private function theme_pages_index(): array
    {
        $out = [];
        foreach ($this->theme_pages_raw() as $page) {
            if (!is_array($page) || empty($page['slug'])) {
                continue;
            }
            $out[] = [
                'slug'  => (string) $page['slug'],
                'label' => (string) ($page['label'] ?? $page['slug']),
            ];
        }
        return $out;
    }

    /**
     * Resolve raw theme-page registrations via the same filter the
     * Theme_Pages_Controller uses, before they're translated to
     * Theme_Page DTOs. Falls back to an empty list if the filter
     * isn't applied to anything.
     *
     * @return array<int|string, array<string,mixed>>
     */
    private function theme_pages_raw(): array
    {
        $raw = \apply_filters('orbitools/register_theme_pages', []);
        return is_array($raw) ? array_values($raw) : [];
    }

    private function plugin_version(): string
    {
        if (defined('ORBITOOLS_VERSION')) {
            return (string) constant('ORBITOOLS_VERSION');
        }
        if (\function_exists('get_plugin_data') && defined('ORBITOOLS_FILE')) {
            $data = \get_plugin_data((string) constant('ORBITOOLS_FILE'), false, false);
            return (string) ($data['Version'] ?? '');
        }
        return '';
    }

    // =========================================================================
    // Import
    // =========================================================================

    /**
     * Restore an export bundle, optionally filtered to a subset of
     * slugs. The current `orbitools_settings` is merged with the
     * incoming values (incoming wins) rather than replaced wholesale,
     * so non-Orbitools options live elsewhere on the row stay
     * untouched and an import scoped to one category doesn't blow
     * away the others.
     *
     * @return WP_REST_Response|WP_Error
     */
    public function import(WP_REST_Request $request)
    {
        $payload = $request->get_param('payload');
        if (!is_array($payload) || !isset($payload['settings']) || !is_array($payload['settings'])) {
            return new WP_Error(
                'orbitools_invalid_payload',
                \__('The import payload is missing a `settings` object.', 'orbitools'),
                ['status' => 400]
            );
        }

        $incoming    = $payload['settings'];
        $apply_slugs = $request->get_param('apply_slugs');

        if (is_array($apply_slugs) && $apply_slugs !== []) {
            $incoming = $this->filter_by_slugs($incoming, array_map('strval', $apply_slugs));
        }

        $existing = (array) \get_option('orbitools_settings', []);
        $merged   = array_merge($existing, $incoming);

        \update_option('orbitools_settings', $merged);

        return new WP_REST_Response([
            'applied' => count($incoming),
        ]);
    }

    /**
     * Keep only keys whose `{slug}_…` prefix is in the allowed slug
     * list. Bare `{slug}_enabled` keys count under their slug.
     *
     * @param array<string,mixed> $settings
     * @param array<int,string>   $slugs
     * @return array<string,mixed>
     */
    private function filter_by_slugs(array $settings, array $slugs): array
    {
        $out = [];
        foreach ($settings as $key => $value) {
            foreach ($slugs as $slug) {
                if ($slug === '') {
                    continue;
                }
                if ($key === $slug . '_enabled' || strpos($key, $slug . '_') === 0) {
                    $out[$key] = $value;
                    break;
                }
            }
        }
        return $out;
    }
}
