<?php
/**
 * Aspect Ratio Configuration Resolution System
 *
 * Reads aspect ratio presets from theme.json and provides them
 * to JavaScript for the responsive aspect ratio control.
 *
 * @since 1.4.0
 */

namespace Orbitools\Core;

class AspectRatioConfig
{
    /**
     * Cache key for resolved aspect ratio configuration
     */
    const CACHE_KEY = 'orbitools_aspect_ratio_config';

    /**
     * Initialize the aspect ratio config system
     */
    public static function init()
    {
        // Clear cache when theme.json might change
        \add_action('after_switch_theme', [self::class, 'clear_cache']);
        \add_action('customize_save_after', [self::class, 'clear_cache']);

        // Enqueue aspect ratio configuration for JavaScript
        \add_action('enqueue_block_editor_assets', [self::class, 'enqueue_aspect_ratio_config']);
    }

    /**
     * Get resolved aspect ratio presets with caching
     *
     * @return array Array of { name, slug, ratio }
     */
    public static function get_aspect_ratio_config(): array
    {
        $cached = \wp_cache_get(self::CACHE_KEY, 'orbitools');
        if ($cached !== false) {
            return $cached;
        }

        $config = self::resolve_aspect_ratios();

        \wp_cache_set(self::CACHE_KEY, $config, 'orbitools', 604800);

        return $config;
    }

    /**
     * Resolve aspect ratios from theme.json with priority
     *
     * @return array Resolved aspect ratios
     */
    private static function resolve_aspect_ratios(): array
    {
        if (!\function_exists('wp_get_global_settings')) {
            return self::get_wp_defaults();
        }

        $global_settings = \wp_get_global_settings();
        $dimensions = $global_settings['dimensions'] ?? [];
        $ratio_data = $dimensions['aspectRatios'] ?? null;
        $use_defaults = $dimensions['defaultAspectRatios'] ?? true;

        // Theme custom aspect ratios (highest priority)
        if (is_array($ratio_data) && isset($ratio_data['theme']) && !empty($ratio_data['theme'])) {
            $ratios = self::normalize($ratio_data['theme']);
            // If theme disables defaults, use only theme ratios
            if (!$use_defaults) {
                return $ratios;
            }
            // Merge theme + defaults (theme first)
            $defaults = isset($ratio_data['default']) ? self::normalize($ratio_data['default']) : self::get_wp_defaults();
            return self::merge_ratios($ratios, $defaults);
        }

        // Default aspect ratios from WordPress
        if (is_array($ratio_data) && isset($ratio_data['default']) && !empty($ratio_data['default'])) {
            return self::normalize($ratio_data['default']);
        }

        return self::get_wp_defaults();
    }

    /**
     * WordPress default aspect ratios fallback
     *
     * @return array
     */
    private static function get_wp_defaults(): array
    {
        return [
            ['name' => 'Square - 1:1', 'slug' => 'square', 'ratio' => '1'],
            ['name' => 'Standard - 4:3', 'slug' => '4-3', 'ratio' => '4/3'],
            ['name' => 'Portrait - 3:4', 'slug' => '3-4', 'ratio' => '3/4'],
            ['name' => 'Classic - 3:2', 'slug' => '3-2', 'ratio' => '3/2'],
            ['name' => 'Classic Portrait - 2:3', 'slug' => '2-3', 'ratio' => '2/3'],
            ['name' => 'Wide - 16:9', 'slug' => '16-9', 'ratio' => '16/9'],
            ['name' => 'Tall - 9:16', 'slug' => '9-16', 'ratio' => '9/16'],
        ];
    }

    /**
     * Normalize aspect ratio entries to consistent format
     *
     * @param array $ratios Raw ratio entries
     * @return array Normalized entries with name, slug, ratio
     */
    private static function normalize(array $ratios): array
    {
        $normalized = [];
        foreach ($ratios as $entry) {
            if (!isset($entry['slug'], $entry['ratio'])) {
                continue;
            }
            $normalized[] = [
                'slug'  => (string) $entry['slug'],
                'ratio' => (string) $entry['ratio'],
                'name'  => $entry['name'] ?? $entry['slug'],
            ];
        }
        return $normalized;
    }

    /**
     * Merge two ratio arrays, theme ratios take precedence by slug
     *
     * @param array $primary Theme ratios
     * @param array $secondary Default ratios
     * @return array Merged ratios
     */
    private static function merge_ratios(array $primary, array $secondary): array
    {
        $slugs = array_column($primary, 'slug');
        foreach ($secondary as $entry) {
            if (!in_array($entry['slug'], $slugs, true)) {
                $primary[] = $entry;
            }
        }
        return $primary;
    }

    /**
     * Clear cached configuration
     */
    public static function clear_cache(): void
    {
        \wp_cache_delete(self::CACHE_KEY, 'orbitools');
    }

    /**
     * Get configuration for a specific block
     *
     * @param string $block_name Block name (e.g., 'orb/group')
     * @return array Configuration for the block
     */
    public static function get_block_config(string $block_name): array
    {
        $block_type = \WP_Block_Type_Registry::get_instance()->get_registered($block_name);

        if (!$block_type) {
            return [
                'ratios'      => [],
                'breakpoints' => [],
                'supports'    => ['enabled' => false, 'breakpoints' => false],
            ];
        }

        $supports = $block_type->supports ?? [];
        $ar_supports = $supports['orbitools']['aspectRatio'] ?? [];

        if (empty($ar_supports) || $ar_supports === false) {
            return [
                'ratios'      => [],
                'breakpoints' => [],
                'supports'    => ['enabled' => false, 'breakpoints' => false],
            ];
        }

        return [
            'ratios'      => self::get_aspect_ratio_config(),
            'breakpoints' => SpacingConfig::get_breakpoints_config(),
            'supports'    => [
                'enabled'     => true,
                'breakpoints' => $ar_supports['breakpoints'] ?? false,
            ],
        ];
    }

    /**
     * Enqueue aspect ratio configuration for JavaScript
     */
    public static function enqueue_aspect_ratio_config(): void
    {
        $registry = \WP_Block_Type_Registry::get_instance();
        $blocks_config = [];

        foreach ($registry->get_all_registered() as $block_name => $block_type) {
            if (strpos($block_name, 'orb/') !== 0) {
                continue;
            }

            $supports = $block_type->supports ?? [];
            if (isset($supports['orbitools']['aspectRatio'])) {
                $blocks_config[$block_name] = self::get_block_config($block_name);
            }
        }

        if (empty($blocks_config)) {
            return;
        }

        $inline_js = sprintf(
            'var orbitoolsAspectRatioConfig = %s;',
            \wp_json_encode($blocks_config)
        );

        \wp_add_inline_script('wp-blocks', $inline_js, 'before');
    }
}
