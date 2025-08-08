<?php
/**
 * Spacing Configuration Resolution System
 * 
 * Handles the priority resolution of spacing and breakpoint configurations
 * from multiple sources with caching for performance.
 * 
 * Priority Order:
 * 1. Block Supports (if using block-level configuration)
 * 2. Theme - theme.json - Custom spacingSizes
 * 3. Theme - theme.json - defaultSpacingSizes (if enabled)
 * 4. Plugin - defaults.json - fallback
 * 
 * @since 1.0.0
 */

namespace Orbitools;

class SpacingConfig {
    
    /**
     * Cache key for resolved spacing configuration
     */
    const CACHE_KEY = 'orbitools_spacing_config';
    
    /**
     * Cache key for resolved breakpoints configuration  
     */
    const BREAKPOINTS_CACHE_KEY = 'orbitools_breakpoints_config';
    
    /**
     * Cache expiration time (1 week)
     */
    const CACHE_EXPIRATION = 604800;
    
    /**
     * Plugin defaults file path
     */
    private static $defaults_file = null;
    
    /**
     * Initialize the spacing config system
     */
    public static function init() {
        self::$defaults_file = \plugin_dir_path(dirname(__FILE__)) . 'config/defaults.json';
        
        // Clear cache when theme.json might change
        \add_action('after_switch_theme', [self::class, 'clear_cache']);
        \add_action('customize_save_after', [self::class, 'clear_cache']);
        
        // Clear cache on admin pages (for development)
        if (is_admin() && defined('WP_DEBUG') && WP_DEBUG) {
            \add_action('admin_init', function() {
                // Check if orbitools.json file has been modified recently
                $theme_orbitools_file = \get_template_directory() . '/config/orbitools.json';
                if (file_exists($theme_orbitools_file)) {
                    $file_modified = filemtime($theme_orbitools_file);
                    $last_check = \get_transient('orbitools_config_check');
                    
                    if (!$last_check || $file_modified > $last_check) {
                        self::clear_cache();
                        \set_transient('orbitools_config_check', time(), 300); // Check every 5 minutes
                    }
                }
            });
        }
    }
    
    /**
     * Get resolved spacing configuration with caching
     * 
     * @param array|null $block_supports Block-level supports override
     * @return array Resolved spacing configuration
     */
    public static function get_spacing_config($block_supports = null) {
        // Check cache first (unless we have block-specific overrides)
        if (!$block_supports) {
            $cached = \wp_cache_get(self::CACHE_KEY, 'orbitools');
            if ($cached !== false) {
                return $cached;
            }
        }
        
        $config = self::resolve_spacing_priority($block_supports);
        
        // Cache the result (only if no block-specific overrides)
        if (!$block_supports) {
            \wp_cache_set(self::CACHE_KEY, $config, 'orbitools', self::CACHE_EXPIRATION);
        }
        
        return $config;
    }
    
    /**
     * Get resolved breakpoints configuration with caching
     * 
     * @return array Resolved breakpoints configuration
     */
    public static function get_breakpoints_config() {
        $cached = \wp_cache_get(self::BREAKPOINTS_CACHE_KEY, 'orbitools');
        if ($cached !== false) {
            return $cached;
        }
        
        $config = self::resolve_breakpoints_priority();
        
        \wp_cache_set(self::BREAKPOINTS_CACHE_KEY, $config, 'orbitools', self::CACHE_EXPIRATION);
        
        return $config;
    }
    
    /**
     * Validate spacing configuration format
     * Each item must have size, slug, and name properties
     * 
     * @param array $spacings Raw spacing configuration
     * @return bool Whether the spacing configuration is valid
     */
    private static function validate_spacing_format($spacings) {
        if (!is_array($spacings) || empty($spacings)) {
            return false;
        }
        
        foreach ($spacings as $spacing) {
            if (!is_array($spacing)) {
                return false;
            }
            
            // Check required properties exist and are not empty
            if (!isset($spacing['size']) || !isset($spacing['slug']) || !isset($spacing['name'])) {
                return false;
            }
            
            // Check properties have values
            if (empty($spacing['size']) && $spacing['size'] !== '0') {
                return false;
            }
            if (empty($spacing['slug']) && $spacing['slug'] !== '0') {
                return false;
            }
            if (empty($spacing['name']) && $spacing['name'] !== '0') {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Resolve spacing configuration based on priority order
     * 
     * @param array|null $block_supports Block-level supports override
     * @return array Resolved spacing configuration
     */
    private static function resolve_spacing_priority($block_supports = null) {
        // 1. Block Supports (highest priority)
        if ($block_supports && isset($block_supports['customSpacings']) && !empty($block_supports['customSpacings'])) {
            if (self::validate_spacing_format($block_supports['customSpacings'])) {
                return self::normalize_spacings($block_supports['customSpacings']);
            }
        }
        
        // 2. Theme - theme.json - Custom spacingSizes
        $theme_spacings = self::get_theme_spacing_sizes();
        if (!empty($theme_spacings) && self::validate_spacing_format($theme_spacings)) {
            return self::normalize_spacings($theme_spacings);
        }
        
        // 3. Theme - theme.json - defaultSpacingSizes
        if (self::has_theme_default_spacings()) {
            $default_spacings = self::get_wp_default_spacings();
            if (!empty($default_spacings) && self::validate_spacing_format($default_spacings)) {
                return self::normalize_spacings($default_spacings);
            }
        }
        
        // 4. Plugin - defaults.json (fallback)
        return self::get_plugin_default_spacings();
    }
    
    /**
     * Resolve breakpoints configuration based on priority order
     * 
     * @return array Resolved breakpoints configuration
     */
    private static function resolve_breakpoints_priority() {
        // 1. Theme - config/orbitools.json
        $theme_breakpoints = self::get_theme_breakpoints();
        if (!empty($theme_breakpoints)) {
            return $theme_breakpoints;
        }
        
        // 2. Plugin - defaults.json
        return self::get_plugin_default_breakpoints();
    }
    
    /**
     * Get spacing sizes from theme.json
     * 
     * @return array|null Theme spacing sizes
     */
    private static function get_theme_spacing_sizes() {
        if (!\function_exists('wp_get_global_settings')) {
            error_log('OrbiTools: wp_get_global_settings function not available');
            return null;
        }
        
        $global_settings = \wp_get_global_settings();
        $spacing_data = $global_settings['spacing']['spacingSizes'] ?? null;
        
        // WordPress returns spacing sizes in a nested format: { default: [], theme: [] }
        // We want the theme spacings
        if (is_array($spacing_data) && isset($spacing_data['theme']) && !empty($spacing_data['theme'])) {
            error_log('OrbiTools: Using theme spacings (' . count($spacing_data['theme']) . ' options)');
            return $spacing_data['theme'];
        }
        
        // Fallback to default spacings if no theme spacings
        if (is_array($spacing_data) && isset($spacing_data['default']) && !empty($spacing_data['default'])) {
            error_log('OrbiTools: Using default spacings (' . count($spacing_data['default']) . ' options)');
            return $spacing_data['default'];
        }
        
        error_log('OrbiTools: No theme spacing data found');
        return null;
    }
    
    /**
     * Check if theme has default spacings enabled
     * 
     * @return bool Whether default spacings are enabled
     */
    private static function has_theme_default_spacings() {
        if (!\function_exists('wp_get_global_settings')) {
            return false;
        }
        
        $global_settings = \wp_get_global_settings();
        return $global_settings['spacing']['defaultSpacingSizes'] ?? false;
    }
    
    /**
     * Get WordPress default spacing sizes
     * 
     * @return array WordPress default spacings
     */
    private static function get_wp_default_spacings() {
        // WordPress default spacing scale
        // These are the core defaults when defaultSpacingSizes is true
        return [
            ['slug' => '20', 'size' => '0.44rem', 'name' => 'XS'],
            ['slug' => '30', 'size' => '0.67rem', 'name' => 'S'],
            ['slug' => '40', 'size' => '1rem', 'name' => 'M'],
            ['slug' => '50', 'size' => '1.5rem', 'name' => 'L'],
            ['slug' => '60', 'size' => '2.25rem', 'name' => 'XL'],
            ['slug' => '70', 'size' => '3.38rem', 'name' => 'XXL'],
        ];
    }
    
    /**
     * Get plugin default spacings from defaults.json
     * 
     * @return array Plugin default spacings
     */
    private static function get_plugin_default_spacings() {
        $defaults = self::load_defaults_file();
        
        if (!$defaults || !isset($defaults['defaults']['spacings'])) {
            return [];
        }
        
        $plugin_spacings = $defaults['defaults']['spacings'];
        
        // Plugin defaults should always be valid - if not, return empty to prevent errors
        if (!self::validate_spacing_format($plugin_spacings)) {
            error_log('OrbiTools: Plugin defaults.json has invalid spacing format');
            return [];
        }
        
        return self::normalize_spacings($plugin_spacings);
    }
    
    /**
     * Get breakpoints from theme's orbitools.json file
     * 
     * @return array|null Theme breakpoints or null if not found
     */
    private static function get_theme_breakpoints() {
        $theme_orbitools_file = \get_template_directory() . '/config/orbitools.json';
        
        if (!file_exists($theme_orbitools_file)) {
            return null;
        }
        
        $contents = file_get_contents($theme_orbitools_file);
        if ($contents === false) {
            return null;
        }
        
        $decoded = json_decode($contents, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }
        
        return $decoded['settings']['breakpoints'] ?? null;
    }

    /**
     * Get plugin default breakpoints from defaults.json
     * 
     * @return array Plugin default breakpoints
     */
    private static function get_plugin_default_breakpoints() {
        $defaults = self::load_defaults_file();
        
        if (!$defaults || !isset($defaults['defaults']['breakpoints'])) {
            return [];
        }
        
        return $defaults['defaults']['breakpoints'];
    }
    
    /**
     * Load and parse the defaults.json file
     * 
     * @return array|null Parsed defaults or null on failure
     */
    private static function load_defaults_file() {
        if (!file_exists(self::$defaults_file)) {
            return null;
        }
        
        $contents = file_get_contents(self::$defaults_file);
        if ($contents === false) {
            return null;
        }
        
        $decoded = json_decode($contents, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }
        
        return $decoded;
    }
    
    /**
     * Normalize spacing configuration to ensure consistent format
     * 
     * @param array $spacings Raw spacing configuration
     * @return array Normalized spacing configuration with 0 option
     */
    private static function normalize_spacings($spacings) {
        if (!is_array($spacings)) {
            return [];
        }
        
        // Ensure 0 option exists and is at the beginning
        $has_zero = false;
        foreach ($spacings as $spacing) {
            if (isset($spacing['slug']) && ($spacing['slug'] === '0' || $spacing['slug'] === 0)) {
                $has_zero = true;
                break;
            }
        }
        
        $normalized = [];
        
        // Add 0 option if it doesn't exist
        if (!$has_zero) {
            $normalized[] = [
                'slug' => '0',
                'size' => '0',
                'name' => 'None'
            ];
        }
        
        // Add all other spacings
        foreach ($spacings as $spacing) {
            if (isset($spacing['slug'], $spacing['size'])) {
                $normalized[] = [
                    'slug' => (string) $spacing['slug'],
                    'size' => $spacing['size'],
                    'name' => $spacing['name'] ?? 'Size ' . $spacing['slug']
                ];
            }
        }
        
        return $normalized;
    }
    
    /**
     * Clear all cached configuration
     */
    public static function clear_cache() {
        \wp_cache_delete(self::CACHE_KEY, 'orbitools');
        \wp_cache_delete(self::BREAKPOINTS_CACHE_KEY, 'orbitools');
    }
    
    /**
     * Get configuration for a specific block based on its supports
     * 
     * @param string $block_name Block name (e.g., 'orb/collection')
     * @return array Configuration for the block
     */
    public static function get_block_config($block_name) {
        $block_type = \WP_Block_Type_Registry::get_instance()->get_registered($block_name);
        
        if (!$block_type) {
            return [
                'spacings' => [],
                'breakpoints' => [],
                'dimensions' => [
                    'enabled' => false,
                    'breakpoints' => false,
                    'gap' => false,
                    'margin' => false,
                    'padding' => false
                ]
            ];
        }
        
        $supports = $block_type->supports ?? [];
        $orbitools_supports = $supports['orbitools'] ?? [];
        $dimensions_supports = $orbitools_supports['dimensions'] ?? [];
        
        // Check if dimensions are enabled at all
        if (empty($dimensions_supports) || $dimensions_supports === false) {
            return [
                'spacings' => [],
                'breakpoints' => [],
                'dimensions' => [
                    'enabled' => false,
                    'breakpoints' => false,
                    'gap' => false,
                    'margin' => false,
                    'padding' => false
                ]
            ];
        }
        
        return [
            'spacings' => self::get_spacing_config($dimensions_supports),
            'breakpoints' => self::get_breakpoints_config(),
            'dimensions' => [
                'enabled' => true,
                'breakpoints' => $dimensions_supports['breakpoints'] ?? false,
                'gap' => $dimensions_supports['gap'] ?? false,
                'margin' => $dimensions_supports['margin'] ?? false,
                'padding' => $dimensions_supports['padding'] ?? false
            ]
        ];
    }
}

// Initialize the spacing config system
SpacingConfig::init();

/**
 * Enqueue dimensions configuration for JavaScript
 * 
 * This should be called from a block editor context to provide
 * configuration data to JavaScript.
 */
\add_action('enqueue_block_editor_assets', function() {
    // Get all registered blocks that use orbitools dimensions
    $registry = \WP_Block_Type_Registry::get_instance();
    $blocks_config = [];
    
    foreach ($registry->get_all_registered() as $block_name => $block_type) {
        $supports = $block_type->supports ?? [];
        
        // Check if block uses orbitools dimensions
        if (isset($supports['orbitools']['dimensions'])) {
            $blocks_config[$block_name] = SpacingConfig::get_block_config($block_name);
        }
    }
    
    // Debug the configuration in development
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('OrbiTools Dimensions Config loaded for ' . count($blocks_config) . ' blocks');
    }
    
    // Localize the configuration for JavaScript on multiple possible script handles
    $script_handles = ['wp-blocks', 'orb-collection-editor-script', 'wp-edit-post'];
    
    foreach ($script_handles as $handle) {
        \wp_localize_script(
            $handle,
            'orbitoolsDimensionsConfig',
            $blocks_config
        );
    }
});