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
            // For block-category modules, append the standard
            // asset-disable toggles so every block exposes the same
            // four switches without each manifest having to declare
            // them. Block_Asset_Filter reads the matching keys.
            // We also force section_layout = 'stacked' so block
            // settings render as collapsible cards regardless of
            // section count.
            $manifest_settings = $manifest->settings;
            $manifest_sections = $manifest->sections;
            $section_layout    = $manifest->section_layout;
            if ($manifest->category === 'blocks') {
                $manifest_settings = array_merge(
                    $manifest_settings,
                    self::asset_toggles_for_blocks()
                );
                $manifest_sections = array_merge($manifest_sections, [[
                    'id'          => 'assets',
                    'title'       => 'Assets',
                    'description' => 'Skip individual block assets if the theme provides its own.',
                ]]);
                $section_layout = 'stacked';
            }

            return [
                'slug'               => $manifest->slug,
                'name'               => \__($manifest->name, 'orbitools'),
                'description'        => \__($manifest->description, 'orbitools'),
                'version'            => $manifest->version,
                'category'           => $manifest->category,
                'default_enabled'    => $manifest->default_enabled,
                'enabled'            => $manager->is_enabled($slug),
                'icon'               => $manifest->category === 'blocks' ? $this->resolve_block_icon($manifest->slug) : null,
                'has_custom_page'    => false, // Phase 4 populates from discovery manifest.
                'has_dashboard_card' => false, // Phase 4 populates from discovery manifest.
                'requires'           => (object) $manifest->requires,
                'sections'           => $this->prepare_sections($manifest_sections),
                'settings_schema'    => $this->prepare_settings_schema($manifest_settings),
                'section_layout'     => $section_layout,
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
            'icon'               => null,
            'has_custom_page'    => false,
            'has_dashboard_card' => false,
            'requires'           => (object) [],
            'sections'           => [],
            'settings_schema'    => [],
            'section_layout'     => 'sidebar',
        ];
    }

    /**
     * Apply __() to section titles + descriptions so the React layer
     * just renders whatever the server returns.
     *
     * @param array<int,array<string,mixed>> $sections
     * @return array<int,array<string,mixed>>
     */
    private function prepare_sections(array $sections): array
    {
        $out = [];
        foreach ($sections as $section) {
            if (!is_array($section) || !isset($section['id'], $section['title'])) {
                continue;
            }
            $prepared = [
                'id'    => (string) $section['id'],
                // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
                'title' => \__($section['title'], 'orbitools'),
            ];
            if (isset($section['description'])) {
                // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
                $prepared['description'] = \__((string) $section['description'], 'orbitools');
            }
            $out[] = $prepared;
        }
        return $out;
    }

    /**
     * Apply __() to user-facing strings in field schemas (label,
     * description, placeholder, options[].label) so the React layer
     * just renders whatever the server returns.
     *
     * @param array<int,array<string,mixed>> $fields
     * @return array<int,array<string,mixed>>
     */
    /**
     * Standard asset-disable toggles appended to every `blocks`-
     * category module's settings_schema. Block_Asset_Filter reads
     * the resulting `{slug}_disable_…` settings to short-circuit
     * the block's manifest asset keys at registration time.
     *
     * Public + static so other code (e.g. tests, the manifest
     * validator) can ask for the same shape without duplicating it.
     *
     * @return array<int,array<string,mixed>>
     */
    public static function asset_toggles_for_blocks(): array
    {
        return [
            [
                'id'          => 'disable_frontend_css',
                'type'        => 'toggle',
                'label'       => 'Disable frontend CSS',
                'description' => "Skip enqueueing this block's frontend style asset (block.json `style`/`viewStyle`).",
                'default'     => false,
                'section'     => 'assets',
            ],
            [
                'id'          => 'disable_frontend_js',
                'type'        => 'toggle',
                'label'       => 'Disable frontend JS',
                'description' => "Skip enqueueing this block's frontend script asset (block.json `script`/`viewScript`).",
                'default'     => false,
                'section'     => 'assets',
            ],
            [
                'id'          => 'disable_editor_css',
                'type'        => 'toggle',
                'label'       => 'Disable editor CSS',
                'description' => "Skip enqueueing this block's editor style asset (block.json `editorStyle`).",
                'default'     => false,
                'section'     => 'assets',
            ],
            [
                'id'          => 'disable_editor_js',
                'type'        => 'toggle',
                'label'       => 'Disable editor JS',
                'description' => "Skip enqueueing this block's editor script asset (block.json `editorScript`).",
                'default'     => false,
                'section'     => 'assets',
            ],
        ];
    }

    private function prepare_settings_schema(array $fields): array
    {
        $out = [];
        foreach ($fields as $field) {
            if (!is_array($field)) {
                continue;
            }
            $prepared = $field;

            if (isset($field['label']) && is_string($field['label'])) {
                // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
                $prepared['label'] = \__($field['label'], 'orbitools');
            }
            if (isset($field['description']) && is_string($field['description'])) {
                // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
                $prepared['description'] = \__($field['description'], 'orbitools');
            }
            if (isset($field['placeholder']) && is_string($field['placeholder'])) {
                // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
                $prepared['placeholder'] = \__($field['placeholder'], 'orbitools');
            }
            if (isset($field['options']) && is_array($field['options'])) {
                $prepared['options'] = array_map(static function ($opt) {
                    if (!is_array($opt)) {
                        return $opt;
                    }
                    if (isset($opt['label']) && is_string($opt['label'])) {
                        // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
                        $opt['label'] = \__($opt['label'], 'orbitools');
                    }
                    return $opt;
                }, $field['options']);
            }

            $out[] = $prepared;
        }
        return $out;
    }

    /**
     * Resolve the dashicon-or-SVG icon for a block-category module.
     * Convention: module slug `xxx-block` ⇄ block name `orb/xxx`.
     *
     * Looks in three places in order:
     *   1. The Block Manager's editor-side icon cache (covers
     *      React-element icons that block.json doesn't carry).
     *   2. WP_Block_Type_Registry — block.json's icon field via
     *      the live registration. Only present when the module is
     *      enabled (disabled modules don't register their block).
     *   3. The build/blocks/{stem}/block.json file directly —
     *      catches modules that are disabled in this admin (and
     *      therefore aren't in the registry) but still have the
     *      block.json on disk.
     *
     * The cache is populated on every block editor page load
     * (see Block_Manager::enqueue_icon_collector), so once a user
     * has opened the editor once on this site, our dashboard /
     * sidebar / block manager all draw from the same source.
     */
    private function resolve_block_icon(string $module_slug): ?string
    {
        // Strip the conventional `-block` suffix, then try the
        // `orb/` and `orbital/` namespaces — both are recognised
        // across the Orbital site fleet.
        $stem = preg_replace('/-block$/', '', $module_slug);
        if (!is_string($stem) || $stem === '') {
            return null;
        }

        $cached = \get_transient('orbitools_block_icons');
        if (is_array($cached)) {
            foreach (['orb/' . $stem, 'orbital/' . $stem] as $name) {
                if (isset($cached[$name]) && is_string($cached[$name]) && $cached[$name] !== '') {
                    return $cached[$name];
                }
            }
        }

        $registry = \WP_Block_Type_Registry::get_instance();
        foreach (['orb/' . $stem, 'orbital/' . $stem] as $name) {
            $type = $registry->get_registered($name);
            if ($type === null) {
                continue;
            }
            $icon = $this->extract_icon_string($type->icon ?? null);
            if ($icon !== null) {
                return $icon;
            }
        }

        // Filesystem fallback — read build/blocks/{stem}/block.json
        // directly. Handles the case where the module is disabled
        // (so the block isn't in the registry) but a user is still
        // looking at the dashboard card and wants to see the icon.
        $path = ORBITOOLS_DIR . 'build/blocks/' . $stem . '/block.json';
        if (file_exists($path)) {
            $contents = @file_get_contents($path);
            if (is_string($contents)) {
                $data = json_decode($contents, true);
                if (is_array($data) && isset($data['icon'])) {
                    $icon = $this->extract_icon_string($data['icon']);
                    if ($icon !== null) {
                        return $icon;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Unwrap whatever shape block.json's `icon` field is in (string,
     * `{src, background, foreground}` array, or a WP_Block_Type_Icon
     * -style object) to a plain string we can render. Null when the
     * shape doesn't yield one.
     *
     * @param mixed $icon
     */
    private function extract_icon_string($icon): ?string
    {
        if (is_string($icon) && $icon !== '') {
            return $icon;
        }
        if (is_array($icon) && isset($icon['src']) && is_string($icon['src']) && $icon['src'] !== '') {
            return $icon['src'];
        }
        if (is_object($icon) && isset($icon->src) && is_string($icon->src) && $icon->src !== '') {
            return $icon->src;
        }
        return null;
    }
}
