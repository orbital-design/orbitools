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
 * @package Orbitools
 * @since 2.0.0
 */
final class Module_Manifest
{
    public string $slug;
    public string $name;
    public string $description;
    public string $version;
    public string $category;
    public string $class;
    public bool $default_enabled;
    public array $requires;
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
        $m->source_path     = $source;

        return $m;
    }
}
