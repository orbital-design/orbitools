<?php

/**
 * Typography Presets Module
 *
 * Provides typography preset functionality by reading preset definitions from theme.json
 * and integrating them into the WordPress block editor. This module replaces core
 * typography controls with a cohesive preset system.
 *
 * @package    Orbital_Editor_Suite
 * @subpackage Modules/Typography_Presets
 * @since      1.0.0
 */

namespace Orbitools\Modules\Typography_Presets;

// Prevent direct access
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Typography Presets Module Class
 *
 * Manages typography preset functionality including:
 * - Reading presets from theme.json files
 * - Block editor integration and controls
 * - CSS generation and output
 * - Settings integration with main plugin
 *
 * @since 1.0.0
 */
class Typography_Presets
{

    /**
     * Module version
     *
     * @since 1.0.0
     * @var string
     */
    const VERSION = '1.0.0';

    /**
     * Module slug identifier
     *
     * @since 1.0.0
     * @var string
     */
    const MODULE_SLUG = 'typography-presets';

    /**
     * Default typography presets
     *
     * @since 1.0.0
     * @var array
     */
    private $default_presets;

    /**
     * Current loaded presets (default + theme.json)
     *
     * @since 1.0.0
     * @var array
     */
    private $presets;

    /**
     * Module settings
     *
     * @since 1.0.0
     * @var array
     */
    private $settings;

    /**
     * Whether the module has been initialized
     *
     * @since 1.0.0
     * @var bool
     */
    private static $initialized = false;

    /**
     * Initialize the Typography Presets module
     *
     * Sets up hooks for OptionsKit integration and initializes module functionality
     * only if the module is enabled in the main plugin settings.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        // Prevent multiple initialization
        if (self::$initialized) {
            return;
        }
        
        // Register module metadata
        add_filter('orbitools_available_modules', array($this, 'register_module_metadata'));

        // Register with admin framework
        add_filter('orbitools_admin_structure', array($this, 'register_new_framework_structure'));
        add_filter('orbitools_settings', array($this, 'register_new_framework_settings'));

        // Only initialize core functionality if module is enabled
        if ($this->is_module_enabled()) {
            $this->init();
        }
        
        self::$initialized = true;
    }

    /**
     * Check if Typography Presets module is enabled
     *
     * @since 1.0.0
     * @return bool True if module is enabled, false otherwise
     */
    private function is_module_enabled()
    {
        $framework_settings = get_option('orbitools_settings', array());

        if (isset($framework_settings['typography_presets_enabled'])) {
            $enabled = $framework_settings['typography_presets_enabled'];
            return ('1' === $enabled || 1 === $enabled);
        }

        return false;
    }

    /**
     * Initialize module functionality
     *
     * Loads presets, settings, and sets up WordPress hooks for editor integration.
     * Only runs when module is enabled.
     *
     * @since 1.0.0
     */
    private function init()
    {
        $this->load_default_presets();
        $this->load_settings();
        $this->load_presets();
        $this->setup_hooks();
    }

    /**
     * Setup WordPress hooks for module functionality
     *
     * @since 1.0.0
     */
    private function setup_hooks()
    {
        // Block editor integration
        add_action('enqueue_block_editor_assets', array($this, 'enqueue_editor_assets'));

        // CSS output hooks
        add_action('wp_head', array($this, 'output_frontend_css'), 5);
        add_action('admin_head', array($this, 'output_editor_css'), 5);

        // Theme.json change detection and caching
        add_action('wp_loaded', array($this, 'check_theme_json_changed'));
        add_action('after_switch_theme', array($this, 'clear_preset_cache'));
    }

    /**
     * Load default typography presets
     *
     * Defines a comprehensive set of default typography presets that work
     * as fallbacks when theme.json doesn't provide custom presets.
     *
     * @since 1.0.0
     */
    private function load_default_presets()
    {
        $this->default_presets = array(
            'heading-xl' => array(
                'label'       => 'Heading XL',
                'description' => 'Extra large heading with strong presence',
                'properties'  => array(
                    'font-size'      => '3rem',
                    'line-height'    => '1.1',
                    'font-weight'    => '700',
                    'letter-spacing' => '-0.02em',
                    'margin-bottom'  => '1.5rem',
                ),
                'group'       => 'headings',
                'is_default'  => true,
            ),
            'heading-lg' => array(
                'label'       => 'Heading Large',
                'description' => 'Large heading for section titles',
                'properties'  => array(
                    'font-size'      => '2.25rem',
                    'line-height'    => '1.2',
                    'font-weight'    => '600',
                    'letter-spacing' => '-0.01em',
                    'margin-bottom'  => '1.25rem',
                ),
                'group'       => 'headings',
                'is_default'  => true,
            ),
            'heading-md' => array(
                'label'       => 'Heading Medium',
                'description' => 'Medium heading for subsections',
                'properties'  => array(
                    'font-size'      => '1.5rem',
                    'line-height'    => '1.3',
                    'font-weight'    => '600',
                    'letter-spacing' => '0',
                    'margin-bottom'  => '1rem',
                ),
                'group'       => 'headings',
                'is_default'  => true,
            ),
            'body-lg' => array(
                'label'       => 'Body Large',
                'description' => 'Large body text for emphasis',
                'properties'  => array(
                    'font-size'      => '1.125rem',
                    'line-height'    => '1.6',
                    'font-weight'    => '400',
                    'letter-spacing' => '0',
                    'margin-bottom'  => '1rem',
                ),
                'group'       => 'body',
                'is_default'  => true,
            ),
            'body-base' => array(
                'label'       => 'Body Base',
                'description' => 'Standard body text',
                'properties'  => array(
                    'font-size'      => '1rem',
                    'line-height'    => '1.6',
                    'font-weight'    => '400',
                    'letter-spacing' => '0',
                    'margin-bottom'  => '1rem',
                ),
                'group'       => 'body',
                'is_default'  => true,
            ),
            'body-sm' => array(
                'label'       => 'Body Small',
                'description' => 'Small body text for captions',
                'properties'  => array(
                    'font-size'      => '0.875rem',
                    'line-height'    => '1.5',
                    'font-weight'    => '400',
                    'letter-spacing' => '0',
                    'margin-bottom'  => '0.75rem',
                ),
                'group'       => 'body',
                'is_default'  => true,
            ),
            'caption' => array(
                'label'       => 'Caption',
                'description' => 'Caption text for images and quotes',
                'properties'  => array(
                    'font-size'      => '0.75rem',
                    'line-height'    => '1.4',
                    'font-weight'    => '500',
                    'letter-spacing' => '0.05em',
                    'text-transform' => 'uppercase',
                    'color'          => '#6b7280',
                ),
                'group'       => 'utility',
                'is_default'  => true,
            ),
            'button' => array(
                'label'       => 'Button Text',
                'description' => 'Text style for buttons and CTAs',
                'properties'  => array(
                    'font-size'      => '0.875rem',
                    'line-height'    => '1.25',
                    'font-weight'    => '600',
                    'letter-spacing' => '0.025em',
                    'text-transform' => 'uppercase',
                ),
                'group'       => 'utility',
                'is_default'  => true,
            ),
        );
    }

    /**
     * Load module settings with defaults
     *
     * @since 1.0.0
     */
    private function load_settings()
    {
        $options        = get_option('orbitools_settings', array());
        $module_options = isset($options[self::MODULE_SLUG]) ? $options[self::MODULE_SLUG] : array();

        // Set default settings - always use theme.json method
        $this->settings = wp_parse_args(
            $module_options,
            array(
                'replace_core_controls' => true,
                'allowed_blocks'        => array(
                    'core/paragraph',
                    'core/heading',
                    'core/list',
                    'core/quote',
                    'core/button',
                ),
                'show_groups'           => true,
                'output_preset_css'     => true,
            )
        );
    }

    /**
     * Load presets from theme.json only
     *
     * This module exclusively uses theme.json for preset definitions.
     *
     * @since 1.0.0
     */
    private function load_presets()
    {
        $this->load_presets_from_theme_json();
    }

    /**
     * Load presets from theme.json file
     *
     * Attempts to read typography presets from the active theme's theme.json file.
     * Falls back to default presets if theme.json is not available or invalid.
     *
     * @since 1.0.0
     */
    private function load_presets_from_theme_json()
    {
        $theme_data = $this->get_theme_json_data();

        if (! $theme_data) {
            // Fallback to default presets if theme.json data not found
            $this->presets = $this->default_presets;
            return;
        }

        // Override settings from theme.json if provided
        $this->maybe_override_settings_from_theme_json($theme_data);

        // Parse and load presets
        $this->presets = $this->parse_theme_json_presets($theme_data);
    }

    /**
     * Override module settings from theme.json configuration
     *
     * @since 1.0.0
     * @param array $theme_data Theme.json data array.
     */
    private function maybe_override_settings_from_theme_json($theme_data)
    {
        if (! isset($theme_data['settings'])) {
            return;
        }

        $theme_settings = $theme_data['settings'];

        $settings_map = array(
            'replace_core_controls' => 'replace_core_controls',
            'show_groups'           => 'show_groups',
            'output_preset_css'     => 'output_preset_css',
        );

        foreach ($settings_map as $theme_key => $setting_key) {
            if (isset($theme_settings[$theme_key])) {
                $this->settings[$setting_key] = $theme_settings[$theme_key];
            }
        }
    }

    /**
     * Get typography presets data from theme.json
     *
     * Attempts to read and parse the theme.json file to extract typography
     * preset definitions specific to this plugin.
     *
     * @since 1.0.0
     * @return array|false Theme data array or false if not found/invalid
     */
    private function get_theme_json_data()
    {
        $theme_json_path = get_template_directory() . '/theme.json';

        if (! file_exists($theme_json_path)) {
            return false;
        }

        $theme_json_content = file_get_contents($theme_json_path);
        $theme_json         = json_decode($theme_json_content, true);

        if (! $theme_json || JSON_ERROR_NONE !== json_last_error()) {
            return false;
        }

        // Navigate to our plugin data: settings -> custom -> orbital -> plugins -> oes -> Typography_Presets
        $plugin_path = array('settings', 'custom', 'orbital', 'plugins', 'oes', 'Typography_Presets');
        $data        = $theme_json;

        foreach ($plugin_path as $key) {
            if (! isset($data[$key])) {
                return false;
            }
            $data = $data[$key];
        }

        return $data;
    }

    /**
     * Parse typography presets from theme.json data
     *
     * @since 1.0.0
     * @param array $theme_data Raw theme.json data.
     * @return array Parsed presets array
     */
    private function parse_theme_json_presets($theme_data)
    {
        if (! isset($theme_data['items'])) {
            return $this->default_presets;
        }

        $parsed_presets    = array();
        $group_definitions = isset($theme_data['groups']) ? $theme_data['groups'] : array();

        // Process each preset from theme.json
        foreach ($theme_data['items'] as $preset_id => $preset_data) {
            $group_id = isset($preset_data['group']) ? $preset_data['group'] : 'theme';

            // Determine group title
            $group_title = $this->get_group_title($group_id, $preset_data, $group_definitions);

            $parsed_presets[$preset_id] = array(
                'label'         => isset($preset_data['label']) ? $preset_data['label'] : $this->generate_preset_label($preset_id),
                'description'   => isset($preset_data['description']) ? $preset_data['description'] : 'From theme.json',
                'properties'    => $this->normalize_css_properties($preset_data['properties']),
                'group'         => $group_id,
                'group_title'   => $group_title,
                'is_theme_json' => true,
            );
        }

        return $parsed_presets;
    }

    /**
     * Get group title for a preset
     *
     * @since 1.0.0
     * @param string $group_id Group identifier.
     * @param array  $preset_data Preset data array.
     * @param array  $group_definitions Group definitions from theme.json.
     * @return string|null Group title or null
     */
    private function get_group_title($group_id, $preset_data, $group_definitions)
    {
        if (isset($group_definitions[$group_id]['title'])) {
            return $group_definitions[$group_id]['title'];
        }

        if (isset($preset_data['group_title'])) {
            return $preset_data['group_title'];
        }

        return null;
    }

    /**
     * Generate a readable label from preset ID
     *
     * Converts preset IDs like "termina-16-400" to "Termina • 16px • Regular"
     *
     * @since 1.0.0
     * @param string $preset_id The preset identifier.
     * @return string Human-readable label
     */
    private function generate_preset_label($preset_id)
    {
        $parts = explode('-', $preset_id);

        if (count($parts) >= 3) {
            $font   = ucwords($parts[0]);
            $size   = $parts[1] . 'px';
            $weight = $this->convert_weight_to_name($parts[2]);
            return "{$font} • {$size} • {$weight}";
        }

        // Fallback for unexpected formats
        return ucwords(implode(' • ', $parts));
    }

    /**
     * Convert numeric font weight to readable name
     *
     * @since 1.0.0
     * @param string $weight Numeric font weight (100-900).
     * @return string Human-readable weight name
     */
    private function convert_weight_to_name($weight)
    {
        $weight_map = array(
            '100' => 'Thin',
            '200' => 'Extra Light',
            '300' => 'Light',
            '400' => 'Regular',
            '500' => 'Medium',
            '600' => 'Semi Bold',
            '700' => 'Bold',
            '800' => 'Extra Bold',
            '900' => 'Black',
        );

        return isset($weight_map[$weight]) ? $weight_map[$weight] : $weight;
    }

    /**
     * Normalize CSS properties from theme.json format
     *
     * Converts camelCase property names to kebab-case CSS properties.
     *
     * @since 1.0.0
     * @param array $properties Raw CSS properties array.
     * @return array Normalized CSS properties
     */
    private function normalize_css_properties($properties)
    {
        $normalized = array();

        foreach ($properties as $property => $value) {
            // Convert camelCase to kebab-case
            $css_property              = strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $property));
            $normalized[$css_property] = $value;
        }

        return $normalized;
    }

    /**
     * Generate CSS for all typography presets
     *
     * Creates CSS rules for each preset that can be applied via CSS classes.
     *
     * @since 1.0.0
     * @return string Generated CSS
     */
    public function generate_css()
    {
        $css = "/* Typography Presets - Generated by Orbitools */\n";

        foreach ($this->presets as $id => $preset) {
            if (empty($preset['properties'])) {
                continue;
            }

            $css .= ".has-type-preset-{$id} {\n";

            foreach ($preset['properties'] as $property => $value) {
                $css .= "  {$property}: {$value};\n";
            }

            $css .= "}\n\n";
        }

        return $css;
    }

    /**
     * Get cached CSS or generate if needed
     *
     * @since 1.0.0
     * @return string CSS content
     */
    private function get_cached_css()
    {
        $cache_key  = 'orbitools_typography_css_' . $this->get_preset_hash();
        $cached_css = get_transient($cache_key);

        if (false !== $cached_css) {
            return $cached_css;
        }

        // Generate CSS and cache it
        $css = $this->generate_css();
        set_transient($cache_key, $css, WEEK_IN_SECONDS);

        return $css;
    }

    /**
     * Get hash of current presets for cache invalidation
     *
     * @since 1.0.0
     * @return string MD5 hash of preset data
     */
    private function get_preset_hash()
    {
        $preset_data = array(
            'presets'  => $this->presets,
            'settings' => $this->settings,
            'method'   => 'theme_json',
        );

        return md5(serialize($preset_data));
    }

    /**
     * Check if theme.json has changed since last check
     *
     * @since 1.0.0
     */
    public function check_theme_json_changed()
    {
        // Only run in admin
        if (! is_admin()) {
            return;
        }

        $current_hash = $this->get_theme_json_hash();
        $stored_hash  = get_option('orbitools_typography_theme_json_hash', '');

        // If hash has changed, clear cache and update stored hash
        if ($current_hash !== $stored_hash) {
            $this->clear_preset_cache();
            update_option('orbitools_typography_theme_json_hash', $current_hash);
        }
    }

    /**
     * Get hash of theme.json data for change detection
     *
     * @since 1.0.0
     * @return string MD5 hash of theme.json data
     */
    private function get_theme_json_hash()
    {
        $theme_data = $this->get_theme_json_data();
        return md5(serialize($theme_data ? $theme_data : array()));
    }

    /**
     * Clear all preset-related caches
     *
     * @since 1.0.0
     */
    public function clear_preset_cache()
    {
        global $wpdb;

        // Clear CSS cache transients
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_orbitools_typography_css_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_orbitools_typography_css_%'");

        // Clear WordPress object cache
        wp_cache_delete('orbitools_typography_presets', 'theme_json');

        // Clear theme.json related transients
        delete_transient('theme_json_data_user');
        delete_transient('theme_json_data_theme');

        // Refresh presets
        $this->load_presets();
    }

    /**
     * Get all loaded presets
     *
     * @since 1.0.0
     * @return array All presets (default + theme.json)
     */
    public function get_presets()
    {
        return $this->presets;
    }

    /**
     * Get presets filtered by group
     *
     * @since 1.0.0
     * @param string|null $group Group to filter by, or null for all.
     * @return array Filtered presets
     */
    public function get_presets_by_group($group = null)
    {
        if (! $group) {
            return $this->presets;
        }

        return array_filter(
            $this->presets,
            function ($preset) use ($group) {
                return isset($preset['group']) && $preset['group'] === $group;
            }
        );
    }

    /**
     * Get all available preset groups
     *
     * @since 1.0.0
     * @return array Available groups
     */
    public function get_groups()
    {
        $groups = array();
        foreach ($this->presets as $preset) {
            if (isset($preset['group'])) {
                $groups[$preset['group']] = $preset['group'];
            }
        }
        return $groups;
    }

    /**
     * Check if module is enabled
     *
     * @since 1.0.0
     * @return bool True if enabled
     */
    public function is_enabled()
    {
        return true; // Module is enabled if instantiated
    }

    /**
     * Check if core typography controls should be replaced
     *
     * @since 1.0.0
     * @return bool True if core controls should be replaced
     */
    public function should_replace_core_controls()
    {
        return ! empty($this->settings['replace_core_controls']);
    }

    /**
     * Get blocks allowed to use typography presets
     *
     * @since 1.0.0
     * @return array Array of allowed block names
     */
    public function get_allowed_blocks()
    {
        return $this->settings['allowed_blocks'];
    }

    /**
     * Enqueue block editor assets
     *
     * Loads JavaScript and localizes data for the block editor integration.
     *
     * @since 1.0.0
     */
    public function enqueue_editor_assets()
    {
        $script_dependencies = array('wp-hooks', 'wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor');
        
        // Enqueue attribute registration first
        wp_enqueue_script(
            'orbitools-typography-attribute-registration',
            ORBITOOLS_URL . 'modules/Typography_Presets/js/attribute-registration.js',
            array('wp-hooks'),
            self::VERSION,
            true
        );
        
        // Localize data to the first script so all scripts can access it
        wp_localize_script(
            'orbitools-typography-attribute-registration',
            'orbitoolsTypographyPresets',
            array(
                'presets'  => $this->get_presets(),
                'groups'   => $this->get_groups(),
                'settings' => $this->settings,
                'strings'  => array(
                    'selectPreset' => __('Select Typography Preset', 'orbitools'),
                    'customPreset' => __('Custom Preset', 'orbitools'),
                    'noPreset'     => __('No Preset', 'orbitools'),
                ),
            )
        );
        
        // Enqueue core controls removal
        wp_enqueue_script(
            'orbitools-typography-core-removal',
            ORBITOOLS_URL . 'modules/Typography_Presets/js/core-controls-removal.js',
            array('wp-hooks', 'orbitools-typography-attribute-registration'),
            self::VERSION,
            true
        );
        
        // Enqueue editor controls
        wp_enqueue_script(
            'orbitools-typography-editor-controls',
            ORBITOOLS_URL . 'modules/Typography_Presets/js/editor-controls.js',
            array_merge($script_dependencies, array('orbitools-typography-attribute-registration')),
            self::VERSION,
            true
        );
        
        // Enqueue class application
        wp_enqueue_script(
            'orbitools-typography-class-application',
            ORBITOOLS_URL . 'modules/Typography_Presets/js/class-application.js',
            array('wp-hooks', 'wp-element', 'wp-compose', 'orbitools-typography-attribute-registration'),
            self::VERSION,
            true
        );
    }

    /**
     * Output CSS in frontend head
     *
     * @since 1.0.0
     */
    public function output_frontend_css()
    {
        if (empty($this->settings['output_preset_css'])) {
            return;
        }

        $css = $this->get_cached_css();
        if (! empty($css)) {
            echo "<style id='orbital-typography-presets-css'>\n" . $css . "\n</style>\n";
        }
    }

    /**
     * Output CSS in editor head
     *
     * @since 1.0.0
     */
    public function output_editor_css()
    {
        if (! $this->is_block_editor() || empty($this->settings['output_preset_css'])) {
            return;
        }

        $css = $this->get_cached_css();
        if (! empty($css)) {
            echo "<style id='orbital-typography-presets-editor-css'>\n" . $css . "\n</style>\n";
        }
    }

    /**
     * Check if we're in the block editor
     *
     * @since 1.0.0
     * @return bool True if in block editor
     */
    private function is_block_editor()
    {
        global $pagenow;
        return in_array($pagenow, array('post.php', 'post-new.php', 'site-editor.php'), true);
    }

    /**
     * Get module slug
     *
     * @since 1.0.0
     * @return string Module slug
     */
    public function get_slug()
    {
        return self::MODULE_SLUG;
    }

    /**
     * Get module settings
     *
     * @since 1.0.0
     * @return array Module settings
     */
    public function get_settings()
    {
        return $this->settings;
    }

    /**
     * Register module metadata for the admin interface
     *
     * @since 1.0.0
     * @param array $modules Existing modules array.
     * @return array Modified modules array with Typography Presets metadata.
     */
    public function register_module_metadata($modules)
    {
        $modules['typography_presets'] = array(
            'name'        => 'Typography Presets',
            'subtitle'    => 'Advanced text styling system',
            'description' => 'Replace WordPress core typography controls with a comprehensive preset system for consistent text styling across your site.',
            'version'     => self::VERSION,
            'category'    => 'Editor Enhancement',
            'icon'        => 'dashicons-editor-textcolor',
            'author'      => 'Orbitools',
            'docs_url'    => 'https://docs.example.com/typography-presets',
            'requires'    => array(
                'wp_version' => '5.0',
                'php_version' => '7.4',
            ),
            'features'    => array(
                'Theme.json integration',
                'Visual preset management',
                'Block editor controls',
                'CSS auto-generation',
            ),
        );

        return $modules;
    }

    /**
     * Register Typography sections in OptionsKit interface
     *
     * Adds the Typography Presets section to the main plugin's
     * OptionsKit admin interface under the Modules tab.
     *
     * @since 1.0.0
     * @param array $sections Existing sections array.
     * @return array Modified sections array
     */
    public function register_sections($sections)
    {
        // Ensure $sections is an array
        if (! is_array($sections)) {
            $sections = array();
        }

        // Ensure modules tab exists
        if (! isset($sections['modules'])) {
            $sections['modules'] = array();
        }

        // Add Typography section to the modules tab
        $sections['modules']['typography'] = __('Typography Presets', 'orbitools');

        return $sections;
    }

    /**
     * Register Typography settings in OptionsKit interface
     *
     * Adds Typography Presets settings fields to the main plugin's
     * OptionsKit admin interface.
     *
     * @since 1.0.0
     * @param array $settings Existing settings array.
     * @return array Modified settings array
     */
    public function register_settings($settings)
    {
        // Ensure $settings is an array
        if (! is_array($settings)) {
            $settings = array();
        }

        // Add Typography settings directly under 'typography' key
        $settings['typography'] = array(
            array(
                'id'      => 'typography_replace_core_controls',
                'name'    => __('Replace Core Controls', 'orbitools'),
                'desc'    => __('Remove WordPress core typography controls and replace with preset system.', 'orbitools'),
                'type'    => 'checkbox',
                'std'     => false,
                'section' => 'typography',
            ),
            array(
                'id'      => 'typography_show_groups',
                'name'    => __('Show Groups in Dropdown', 'orbitools'),
                'desc'    => __('Organize presets into groups in the block editor dropdown.', 'orbitools'),
                'type'    => 'checkbox',
                'std'     => true,
                'section' => 'typography',
            ),
            array(
                'id'      => 'typography_output_preset_css',
                'name'    => __('Output Preset CSS', 'orbitools'),
                'desc'    => __('Automatically generate and include CSS for all presets.', 'orbitools'),
                'type'    => 'checkbox',
                'std'     => true,
                'section' => 'typography',
            ),
            array(
                'id'      => 'typography_allowed_blocks',
                'name'    => __('Allowed Blocks', 'orbitools'),
                'desc'    => __('Select which blocks can use typography presets', 'orbitools'),
                'type'    => 'multicheckbox',
                'options' => array(
                    'core/paragraph' => __('Paragraph', 'orbitools'),
                    'core/heading'   => __('Heading', 'orbitools'),
                    'core/list'      => __('List', 'orbitools'),
                    'core/quote'     => __('Quote', 'orbitools'),
                    'core/button'    => __('Button', 'orbitools'),
                    'core/cover'     => __('Cover', 'orbitools'),
                    'core/group'     => __('Group', 'orbitools'),
                ),
                'std'     => array('core/paragraph', 'core/heading', 'core/list', 'core/quote', 'core/button'),
                'section' => 'typography',
            ),
        );

        return $settings;
    }

    /**
     * Register Typography sections in new admin framework
     *
     * @since 1.0.0
     * @param array $structure Existing structure array.
     * @return array Modified structure array
     */
    public function register_new_framework_structure($structure)
    {
        // Add Typography subsection to the modules tab
        if (! isset($structure['modules']['sections']['typography'])) {
            $structure['modules']['sections']['typography'] = 'Typography Presets';
        }

        return $structure;
    }

    /**
     * Register Typography settings in new admin framework
     *
     * @since 1.0.0
     * @param array $settings Existing settings array.
     * @return array Modified settings array
     */
    public function register_new_framework_settings($settings)
    {
        // Ensure $settings is an array
        if (! is_array($settings)) {
            $settings = array();
        }

        // Ensure modules key exists
        if (! isset($settings['modules'])) {
            $settings['modules'] = array();
        }

        // Add Typography settings to modules tab
        $typography_settings = array(
            array(
                'id'      => 'typography_replace_core_controls',
                'name'    => 'Replace Core Controls',
                'desc'    => 'Remove WordPress core typography controls and replace with preset system.',
                'type'    => 'checkbox',
                'std'     => false,
                'section' => 'typography',
            ),
            array(
                'id'      => 'typography_show_groups',
                'name'    => 'Show Groups in Dropdown',
                'desc'    => 'Organize presets into groups in the block editor dropdown.',
                'type'    => 'checkbox',
                'std'     => true,
                'section' => 'typography',
            ),
            array(
                'id'      => 'typography_output_preset_css',
                'name'    => 'Output Preset CSS',
                'desc'    => 'Automatically generate and include CSS for all presets.',
                'type'    => 'checkbox',
                'std'     => true,
                'section' => 'typography',
            ),
            array(
                'id'      => 'typography_allowed_blocks',
                'name'    => 'Allowed Blocks',
                'desc'    => 'Select which blocks can use typography presets.',
                'type'    => 'checkbox',
                'options' => array(
                    'core/paragraph' => 'Paragraph',
                    'core/heading'   => 'Heading',
                    'core/list'      => 'List',
                    'core/quote'     => 'Quote',
                    'core/button'    => 'Button',
                    'core/cover'     => 'Cover',
                    'core/group'     => 'Group',
                ),
                'std'     => array('core/paragraph', 'core/heading', 'core/list', 'core/quote', 'core/button'),
                'section' => 'typography',
            ),
        );

        // Merge with existing modules settings
        $settings['modules'] = array_merge($settings['modules'], $typography_settings);

        return $settings;
    }
}