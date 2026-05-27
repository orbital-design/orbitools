<?php

namespace Orbitools\Core\Module;

use RuntimeException;

/**
 * Module Manifest
 *
 * Immutable value object wrapping a parsed module.json file. Constructed
 * via {@see Module_Manifest::from_file()} which validates required fields
 * and fails loudly on malformed manifests.
 *
 * v3 (Phase 3) adds optional `settings` and `sections` arrays that
 * describe the module's admin settings page declaratively. The React
 * admin layer auto-renders these into a working SettingsPage.
 *
 * @package Orbitools
 * @since 2.0.0
 */
final class Module_Manifest
{
    /**
     * Field types the v1 catalog supports. The React layer ships a
     * renderer for each. Manifests using an unknown type render as
     * a FieldFallback on the client side.
     */
    public const FIELD_TYPES = [
        'text',
        'textarea',
        'number',
        'toggle',
        'select',
        'multiselect',
        'radio',
        'checkbox-group',
        'color',
        'range',
        'media',
        'page',
        'repeater',
    ];

    public string $slug;
    public string $name;
    public string $description;
    public string $version;
    public string $category;
    public string $class;
    public bool $default_enabled;
    public array $requires;

    /**
     * Optional list of section descriptors for the settings page.
     * Each entry: ['id' => string, 'title' => string, 'description' => ?string].
     *
     * @var array<int, array<string,mixed>>
     */
    public array $sections;

    /**
     * Optional list of field schemas describing the module's settings.
     * Shape per item: ['id', 'type', 'label', 'default', plus per-type
     * keys]. See FIELD_TYPES for the type catalog.
     *
     * @var array<int, array<string,mixed>>
     */
    public array $settings;

    public string $source_path;

    /**
     * @internal Use {@see from_file()} or {@see from_array()}.
     */
    private function __construct() {}

    /**
     * Load and validate a manifest from a JSON file.
     *
     * @throws RuntimeException If the file is missing, unreadable, malformed,
     *                          or missing required fields.
     */
    public static function from_file(string $path): self
    {
        if (!is_readable($path)) {
            throw new RuntimeException("Module manifest not readable: {$path}");
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new RuntimeException("Failed to read module manifest: {$path}");
        }

        $data = json_decode($contents, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException(sprintf(
                'Malformed JSON in module manifest %s: %s',
                $path,
                json_last_error_msg()
            ));
        }

        if (!is_array($data)) {
            throw new RuntimeException("Module manifest must decode to an object: {$path}");
        }

        $manifest = self::from_array($data, $path);
        $manifest->source_path = $path;

        // Warn-only schema validation under WP_DEBUG so authors notice
        // malformed settings without breaking the page in production.
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $manifest->validate_settings_schema();
        }

        return $manifest;
    }

    /**
     * @param array<string,mixed> $data Parsed manifest data.
     * @param string $source Source identifier for error messages (e.g. file path).
     * @throws RuntimeException If required fields are missing or wrong type.
     */
    public static function from_array(array $data, string $source = '<array>'): self
    {
        $required = ['slug', 'name', 'description', 'version', 'category', 'class', 'default_enabled'];
        foreach ($required as $field) {
            if (!array_key_exists($field, $data)) {
                throw new RuntimeException("Module manifest missing required field '{$field}' in {$source}");
            }
        }

        $allowed_categories = ['blocks', 'controls', 'modules'];
        if (!in_array($data['category'], $allowed_categories, true)) {
            throw new RuntimeException(sprintf(
                "Module manifest in %s has invalid category '%s'; expected one of: %s",
                $source,
                (string) $data['category'],
                implode(', ', $allowed_categories)
            ));
        }

        $m = new self();
        $m->slug            = (string) $data['slug'];
        $m->name            = (string) $data['name'];
        $m->description     = (string) $data['description'];
        $m->version         = (string) $data['version'];
        $m->category        = (string) $data['category'];
        $m->class           = (string) $data['class'];
        $m->default_enabled = (bool)   $data['default_enabled'];
        $m->requires        = is_array($data['requires'] ?? null) ? $data['requires'] : [];
        $m->sections        = is_array($data['sections'] ?? null) ? array_values($data['sections']) : [];
        $m->settings        = is_array($data['settings'] ?? null) ? array_values($data['settings']) : [];
        $m->source_path     = $source;

        return $m;
    }

    /**
     * Soft validation of the settings schema. Logs warnings under
     * WP_DEBUG instead of throwing so a typo in one module doesn't
     * break the whole admin page.
     */
    private function validate_settings_schema(): void
    {
        $section_ids = [];
        foreach ($this->sections as $i => $section) {
            if (!is_array($section)) {
                $this->log_schema_warning("section[$i] is not an object");
                continue;
            }
            if (!isset($section['id'], $section['title'])) {
                $this->log_schema_warning("section[$i] missing required id or title");
                continue;
            }
            $section_ids[] = (string) $section['id'];
        }

        $seen_ids = [];
        foreach ($this->settings as $i => $field) {
            if (!is_array($field)) {
                $this->log_schema_warning("settings[$i] is not an object");
                continue;
            }

            $id = $field['id'] ?? null;
            if (!is_string($id) || $id === '') {
                $this->log_schema_warning("settings[$i] missing string `id`");
                continue;
            }
            if (isset($seen_ids[$id])) {
                $this->log_schema_warning("settings[$i] duplicate id `{$id}`");
            }
            $seen_ids[$id] = true;

            $type = $field['type'] ?? null;
            if (!is_string($type) || !in_array($type, self::FIELD_TYPES, true)) {
                $this->log_schema_warning("settings[$i] (`{$id}`) has unknown type `" . (string) $type . "`");
            }

            if (!isset($field['label'])) {
                $this->log_schema_warning("settings[$i] (`{$id}`) missing label");
            }

            if (!array_key_exists('default', $field)) {
                $this->log_schema_warning("settings[$i] (`{$id}`) missing default");
            }

            if (isset($field['section']) && !in_array((string) $field['section'], $section_ids, true)) {
                $this->log_schema_warning("settings[$i] (`{$id}`) references unknown section `{$field['section']}`");
            }
        }
    }

    private function log_schema_warning(string $message): void
    {
        \error_log(sprintf('[Orbitools] %s: %s', basename($this->source_path), $message));
    }
}
