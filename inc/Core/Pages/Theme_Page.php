<?php

namespace Orbitools\Core\Pages;

/**
 * Theme Page value object.
 *
 * One registered top-level admin page contributed by a theme (or any
 * other plugin) via the `orbitools/register_theme_pages` filter.
 * Mirrors the shape of a module's manifest but carries the additional
 * page-level metadata the React TopNav needs to render an entry
 * (label, icon, position).
 *
 * Field schemas use the same shape as Module_Manifest's settings —
 * the React renderer can't tell the difference between a module
 * settings page and a theme page once the data has been normalised.
 *
 * @package Orbitools
 * @since 3.1.0
 */
final class Theme_Page
{
    public string $slug;
    public string $label;
    public string $description;
    public string $icon;
    public int $position;

    /** @var array<int,array<string,mixed>> */
    public array $sections;

    /** @var array<int,array<string,mixed>> */
    public array $fields;

    /**
     * @param array<string,mixed> $config Raw config array from the filter.
     */
    private function __construct(array $config)
    {
        $this->slug        = (string) $config['slug'];
        $this->label       = (string) $config['label'];
        $this->description = (string) ($config['description'] ?? '');
        $this->icon        = (string) ($config['icon'] ?? '');
        $this->position    = (int) ($config['position'] ?? 50);
        $this->sections    = is_array($config['sections'] ?? null) ? $config['sections'] : [];
        $this->fields      = is_array($config['fields'] ?? null) ? $config['fields'] : [];
    }

    /**
     * Build a Theme_Page from a raw config array. Returns null if the
     * config is missing the minimum fields (slug + label). Under
     * WP_DEBUG, a malformed entry triggers a notice so authors find
     * out about typos at dev time.
     *
     * @param array<string,mixed> $config
     */
    public static function from_array(array $config): ?self
    {
        if (!isset($config['slug'], $config['label'])) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                \trigger_error(
                    'orbitools/register_theme_pages: page is missing slug or label.',
                    E_USER_WARNING
                );
            }
            return null;
        }

        if (!\preg_match('/^[a-z0-9\-]+$/', (string) $config['slug'])) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                \trigger_error(
                    sprintf(
                        'orbitools/register_theme_pages: invalid slug %s (must be lowercase a-z0-9 + dashes).',
                        \esc_html((string) $config['slug'])
                    ),
                    E_USER_WARNING
                );
            }
            return null;
        }

        return new self($config);
    }
}
