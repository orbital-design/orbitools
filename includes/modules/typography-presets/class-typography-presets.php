<?php
/**
 * Typography Presets Module
 *
 * Replaces core typography controls with preset utility classes that combine
 * multiple typography properties (font-size, line-height, font-weight, etc.)
 * into cohesive, pre-designed styles.
 *
 * @package    Orbital_Editor_Suite
 * @subpackage Orbital_Editor_Suite/includes/modules/typography-presets
 */

namespace Orbital\Editor_Suite\Modules\Typography_Presets;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Typography Presets Module Class
 *
 * Manages typography preset functionality including:
 * - Preset definitions and storage
 * - Block editor integration
 * - CSS generation
 * - Admin management interface
 */
class Typography_Presets {

    /**
     * Module version.
     */
    const VERSION = '1.0.0';

    /**
     * Module slug.
     */
    const MODULE_SLUG = 'typography-presets';

    /**
     * Default presets.
     */
    private $default_presets;

    /**
     * Current presets.
     */
    private $presets;

    /**
     * Module settings.
     */
    private $settings;

    /**
     * Initialize the module.
     */
    public function __construct() {
        // Check if module is enabled before initializing
        if (!$this->is_module_enabled()) {
            return;
        }
        
        // Delay initialization until after translations are loaded
        add_action('init', array($this, 'delayed_init'));
    }
    
    /**
     * Initialize after translations are loaded.
     */
    public function delayed_init() {
        $this->load_default_presets();
        $this->load_settings();
        $this->load_presets();
        $this->init_hooks();
    }

    /**
     * Check if this module is enabled in global settings.
     */
    private function is_module_enabled() {
        $global_options = get_option('orbital_editor_suite_options', array());
        $global_settings = isset($global_options['settings']) ? $global_options['settings'] : array();
        $enabled_modules = isset($global_settings['enabled_modules']) ? $global_settings['enabled_modules'] : array();
        
        return in_array(self::MODULE_SLUG, $enabled_modules);
    }

    /**
     * Initialize WordPress hooks.
     */
    private function init_hooks() {
        add_action('enqueue_block_editor_assets', array($this, 'enqueue_editor_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Register admin pages using WordPress hooks
        add_action('orbital_editor_suite_admin_pages', array($this, 'register_admin_pages'));
        
        // AJAX handlers for preset management
        add_action('wp_ajax_orbital_save_typography_preset', array($this, 'ajax_save_preset'));
        add_action('wp_ajax_orbital_delete_typography_preset', array($this, 'ajax_delete_preset'));
        add_action('wp_ajax_orbital_get_typography_presets', array($this, 'ajax_get_presets'));
        
        // CSS output hooks - use WordPress proper hooks
        add_action('wp_head', array($this, 'output_frontend_css'), 5);
        add_action('admin_head', array($this, 'output_editor_css'), 5);
        
        // Theme.json caching and change detection
        add_action('wp_loaded', array($this, 'check_theme_json_changed'));
        add_action('after_switch_theme', array($this, 'clear_preset_cache'));
    }

    /**
     * Load default presets.
     */
    private function load_default_presets() {
        $this->default_presets = array(
            'heading-xl' => array(
                'label' => __('Heading XL', 'orbital-editor-suite'),
                'description' => __('Extra large heading with strong presence', 'orbital-editor-suite'),
                'properties' => array(
                    'font-size' => '3rem',
                    'line-height' => '1.1',
                    'font-weight' => '700',
                    'letter-spacing' => '-0.02em',
                    'margin-bottom' => '1.5rem'
                ),
                'group' => 'headings',
                'is_default' => true
            ),
            'heading-lg' => array(
                'label' => __('Heading Large', 'orbital-editor-suite'),
                'description' => __('Large heading for section titles', 'orbital-editor-suite'),
                'properties' => array(
                    'font-size' => '2.25rem',
                    'line-height' => '1.2',
                    'font-weight' => '600',
                    'letter-spacing' => '-0.01em',
                    'margin-bottom' => '1.25rem'
                ),
                'group' => 'headings',
                'is_default' => true
            ),
            'heading-md' => array(
                'label' => __('Heading Medium', 'orbital-editor-suite'),
                'description' => __('Medium heading for subsections', 'orbital-editor-suite'),
                'properties' => array(
                    'font-size' => '1.5rem',
                    'line-height' => '1.3',
                    'font-weight' => '600',
                    'letter-spacing' => '0',
                    'margin-bottom' => '1rem'
                ),
                'group' => 'headings',
                'is_default' => true
            ),
            'body-lg' => array(
                'label' => __('Body Large', 'orbital-editor-suite'),
                'description' => __('Large body text for emphasis', 'orbital-editor-suite'),
                'properties' => array(
                    'font-size' => '1.125rem',
                    'line-height' => '1.6',
                    'font-weight' => '400',
                    'letter-spacing' => '0',
                    'margin-bottom' => '1rem'
                ),
                'group' => 'body',
                'is_default' => true
            ),
            'body-base' => array(
                'label' => __('Body Base', 'orbital-editor-suite'),
                'description' => __('Standard body text', 'orbital-editor-suite'),
                'properties' => array(
                    'font-size' => '1rem',
                    'line-height' => '1.6',
                    'font-weight' => '400',
                    'letter-spacing' => '0',
                    'margin-bottom' => '1rem'
                ),
                'group' => 'body',
                'is_default' => true
            ),
            'body-sm' => array(
                'label' => __('Body Small', 'orbital-editor-suite'),
                'description' => __('Small body text for captions', 'orbital-editor-suite'),
                'properties' => array(
                    'font-size' => '0.875rem',
                    'line-height' => '1.5',
                    'font-weight' => '400',
                    'letter-spacing' => '0',
                    'margin-bottom' => '0.75rem'
                ),
                'group' => 'body',
                'is_default' => true
            ),
            'caption' => array(
                'label' => __('Caption', 'orbital-editor-suite'),
                'description' => __('Caption text for images and quotes', 'orbital-editor-suite'),
                'properties' => array(
                    'font-size' => '0.75rem',
                    'line-height' => '1.4',
                    'font-weight' => '500',
                    'letter-spacing' => '0.05em',
                    'text-transform' => 'uppercase',
                    'color' => '#6b7280'
                ),
                'group' => 'utility',
                'is_default' => true
            ),
            'button' => array(
                'label' => __('Button Text', 'orbital-editor-suite'),
                'description' => __('Text style for buttons and CTAs', 'orbital-editor-suite'),
                'properties' => array(
                    'font-size' => '0.875rem',
                    'line-height' => '1.25',
                    'font-weight' => '600',
                    'letter-spacing' => '0.025em',
                    'text-transform' => 'uppercase'
                ),
                'group' => 'utility',
                'is_default' => true
            )
        );
    }

    /**
     * Load module settings.
     */
    private function load_settings() {
        $options = get_option('orbital_editor_suite_options', array());
        $this->settings = isset($options['modules'][self::MODULE_SLUG]) ? 
            $options['modules'][self::MODULE_SLUG] : array();
        
        // Set default settings
        $this->settings = wp_parse_args($this->settings, array(
            'preset_generation_method' => 'admin', // 'admin' or 'theme_json'
            'replace_core_controls' => true,
            'allowed_blocks' => array(
                'core/paragraph',
                'core/heading',
                'core/list',
                'core/quote',
                'core/button'
            ),
            'show_groups' => true,
            'output_preset_css' => true
        ));
    }

    /**
     * Refresh settings from database.
     */
    public function refresh_settings() {
        $this->load_settings();
    }

    /**
     * Load presets from database or theme.json.
     */
    private function load_presets() {
        if ($this->settings['preset_generation_method'] === 'theme_json') {
            $this->load_presets_from_theme_json();
        } else {
            $this->load_presets_from_admin();
        }
    }
    
    /**
     * Load presets from admin interface (database).
     */
    private function load_presets_from_admin() {
        $saved_presets = get_option('orbital_typography_presets', array());
        
        // Merge default presets with saved custom presets
        $this->presets = array_merge($this->default_presets, $saved_presets);
    }
    
    /**
     * Load presets from theme.json.
     */
    private function load_presets_from_theme_json() {
        $theme_data = $this->get_theme_json_data();
        
        if (!$theme_data) {
            // Fallback to default presets if theme.json data not found
            $this->presets = $this->default_presets;
            return;
        }
        
        // Override settings from theme.json
        if (isset($theme_data['settings'])) {
            $theme_settings = $theme_data['settings'];
            
            if (isset($theme_settings['replace_core_controls'])) {
                $this->settings['replace_core_controls'] = $theme_settings['replace_core_controls'];
            }
            if (isset($theme_settings['show_groups'])) {
                $this->settings['show_groups'] = $theme_settings['show_groups'];
            }
            if (isset($theme_settings['output_preset_css'])) {
                $this->settings['output_preset_css'] = $theme_settings['output_preset_css'];
            }
        }
        
        // Parse presets based on structure
        $this->presets = $this->parse_theme_json_presets($theme_data);
    }
    
    /**
     * Get Typography_Presets data from theme.json.
     */
    private function get_theme_json_data() {
        $theme_json_path = get_template_directory() . '/theme.json';
        
        if (!file_exists($theme_json_path)) {
            return false;
        }
        
        $theme_json_content = file_get_contents($theme_json_path);
        $theme_json = json_decode($theme_json_content, true);
        
        if (!$theme_json || json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }
        
        // Navigate to our plugin data: settings -> custom -> orbital -> plugins -> oes -> Typography_Presets
        if (!isset($theme_json['settings']['custom']['orbital']['plugins']['oes']['Typography_Presets'])) {
            return false;
        }
        
        return $theme_json['settings']['custom']['orbital']['plugins']['oes']['Typography_Presets'];
    }
    
    /**
     * Parse presets from theme.json data.
     */
    private function parse_theme_json_presets($theme_data) {
        if (!isset($theme_data['items'])) {
            return $this->default_presets;
        }
        
        $parsed_presets = array();
        
        // Get group definitions if they exist
        $group_definitions = isset($theme_data['groups']) ? $theme_data['groups'] : array();
        
        // Unified format: always same structure
        foreach ($theme_data['items'] as $preset_id => $preset_data) {
            $group_id = isset($preset_data['group']) ? $preset_data['group'] : 'theme';
            
            // Get group title from definitions or fallback
            $group_title = null;
            if (isset($group_definitions[$group_id]['title'])) {
                $group_title = $group_definitions[$group_id]['title'];
            } elseif (isset($preset_data['group_title'])) {
                $group_title = $preset_data['group_title'];
            }
            
            $parsed_presets[$preset_id] = array(
                'label' => isset($preset_data['label']) ? $preset_data['label'] : $this->generate_preset_label($preset_id),
                'description' => isset($preset_data['description']) ? $preset_data['description'] : __('From theme.json', 'orbital-editor-suite'),
                'properties' => $this->normalize_css_properties($preset_data['properties']),
                'group' => $group_id,
                'group_title' => $group_title,
                'is_theme_json' => true
            );
        }
        
        return $parsed_presets;
    }
    
    /**
     * Generate a readable label from preset ID.
     */
    private function generate_preset_label($preset_id) {
        // Convert "termina-16-400" to "Termina • 16px • Regular"
        $parts = explode('-', $preset_id);
        
        if (count($parts) >= 3) {
            $font = ucwords($parts[0]);
            $size = $parts[1] . 'px';
            $weight = $this->convert_weight_to_name($parts[2]);
            return "{$font} • {$size} • {$weight}";
        }
        
        // Fallback for unexpected formats
        return ucwords(implode(' • ', $parts));
    }
    
    /**
     * Convert numeric weight to readable name.
     */
    private function convert_weight_to_name($weight) {
        $weight_map = array(
            '100' => 'Thin',
            '200' => 'Extra Light',
            '300' => 'Light',
            '400' => 'Regular',
            '500' => 'Medium',
            '600' => 'Semi Bold',
            '700' => 'Bold',
            '800' => 'Extra Bold',
            '900' => 'Black'
        );
        
        return isset($weight_map[$weight]) ? $weight_map[$weight] : $weight;
    }
    
    /**
     * Normalize CSS properties from theme.json format.
     */
    private function normalize_css_properties($properties) {
        $normalized = array();
        
        foreach ($properties as $property => $value) {
            // Convert camelCase to kebab-case
            $css_property = strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $property));
            $normalized[$css_property] = $value;
        }
        
        return $normalized;
    }

    /**
     * Get cached CSS or generate if needed.
     */
    private function get_cached_css() {
        $cache_key = 'orbital_typography_css_' . $this->get_preset_hash();
        $cached_css = get_transient($cache_key);

        if ($cached_css !== false) {
            return $cached_css;
        }

        // Generate CSS and cache it
        $css = $this->generate_css();
        set_transient($cache_key, $css, WEEK_IN_SECONDS);

        return $css;
    }

    /**
     * Get hash of current presets for cache invalidation.
     */
    private function get_preset_hash() {
        $preset_data = array(
            'presets' => $this->presets,
            'settings' => $this->settings,
            'method' => $this->settings['preset_generation_method']
        );
        
        return md5(serialize($preset_data));
    }

    /**
     * Check if theme.json has changed since last check.
     */
    public function check_theme_json_changed() {
        // Only run in admin and only for theme.json method
        if (!is_admin() || $this->settings['preset_generation_method'] !== 'theme_json') {
            return;
        }

        $current_hash = $this->get_theme_json_hash();
        $stored_hash = get_option('orbital_typography_theme_json_hash', '');

        // If hash has changed, clear cache and update stored hash
        if ($current_hash !== $stored_hash) {
            $this->clear_preset_cache();
            update_option('orbital_typography_theme_json_hash', $current_hash);
        }
    }

    /**
     * Get hash of theme.json data for change detection.
     */
    private function get_theme_json_hash() {
        $theme_data = $this->get_theme_json_data();
        return md5(serialize($theme_data ?: array()));
    }

    /**
     * Clear all preset caches.
     */
    public function clear_preset_cache() {
        // Clear CSS cache
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_orbital_typography_css_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_orbital_typography_css_%'");

        // Clear WordPress object cache
        wp_cache_delete('orbital_typography_presets', 'theme_json');
        
        // Clear theme.json related transients
        delete_transient('theme_json_data_user');
        delete_transient('theme_json_data_theme');

        // Refresh presets
        $this->load_presets();
    }

    /**
     * Save presets to database.
     */
    private function save_presets($presets) {
        // Only save custom presets (non-default ones)
        $custom_presets = array();
        foreach ($presets as $id => $preset) {
            if (empty($preset['is_default'])) {
                $custom_presets[$id] = $preset;
            }
        }
        
        update_option('orbital_typography_presets', $custom_presets);
        $this->presets = $presets;
    }

    /**
     * Get all presets.
     */
    public function get_presets() {
        return $this->presets;
    }

    /**
     * Get presets by group.
     */
    public function get_presets_by_group($group = null) {
        if (!$group) {
            return $this->presets;
        }
        
        return array_filter($this->presets, function($preset) use ($group) {
            return isset($preset['group']) && $preset['group'] === $group;
        });
    }

    /**
     * Get preset groups.
     */
    public function get_groups() {
        $groups = array();
        foreach ($this->presets as $preset) {
            if (isset($preset['group'])) {
                $groups[$preset['group']] = $preset['group'];
            }
        }
        return $groups;
    }

    /**
     * Add or update a preset.
     */
    public function save_preset($id, $preset_data) {
        $preset = wp_parse_args($preset_data, array(
            'label' => '',
            'description' => '',
            'properties' => array(),
            'group' => 'custom',
            'is_default' => false
        ));
        
        $this->presets[$id] = $preset;
        $this->save_presets($this->presets);
        
        return true;
    }

    /**
     * Delete a preset.
     */
    public function delete_preset($id) {
        if (isset($this->presets[$id]) && empty($this->presets[$id]['is_default'])) {
            unset($this->presets[$id]);
            $this->save_presets($this->presets);
            return true;
        }
        
        return false;
    }

    /**
     * Generate CSS for all presets.
     */
    public function generate_css() {
        $css = "/* Typography Presets - Generated by Orbital Editor Suite */\n";
        
        foreach ($this->presets as $id => $preset) {
            if (!empty($preset['properties'])) {
                // Generate CSS for the specific preset class
                $css .= ".has-type-preset-{$id} {\n";
                
                foreach ($preset['properties'] as $property => $value) {
                    $css .= "  {$property}: {$value};\n";
                }
                
                $css .= "}\n\n";
            }
        }
        
        return $css;
    }

    /**
     * Check if module is enabled.
     */
    public function is_enabled() {
        return true; // Module is enabled if instantiated
    }

    /**
     * Check if core controls should be replaced.
     */
    public function should_replace_core_controls() {
        return !empty($this->settings['replace_core_controls']);
    }

    /**
     * Get allowed blocks for this module.
     */
    public function get_allowed_blocks() {
        return $this->settings['allowed_blocks'];
    }

    /**
     * Enqueue editor assets.
     */
    public function enqueue_editor_assets() {
        wp_enqueue_script(
            'orbital-typography-presets',
            ORBITAL_EDITOR_SUITE_URL . 'assets/js/typography-presets.js',
            array('wp-hooks', 'wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor'),
            self::VERSION,
            true
        );

        wp_localize_script(
            'orbital-typography-presets',
            'orbitalTypographyPresets',
            array(
                'presets' => $this->get_presets(),
                'groups' => $this->get_groups(),
                'settings' => $this->settings,
                'strings' => array(
                    'selectPreset' => __('Select Typography Preset', 'orbital-editor-suite'),
                    'customPreset' => __('Custom Preset', 'orbital-editor-suite'),
                    'noPreset' => __('No Preset', 'orbital-editor-suite')
                )
            )
        );

        // Localize global settings for debug logging
        $global_options = get_option('orbital_editor_suite_options', array());
        $global_settings = isset($global_options['settings']) ? $global_options['settings'] : array();
        
        wp_localize_script(
            'orbital-typography-presets',
            'orbitalEditorSuiteGlobal',
            array(
                'settings' => $global_settings
            )
        );

    }

    /**
     * Output CSS in frontend head (utility classes approach).
     */
    public function output_frontend_css() {
        if (empty($this->settings['output_preset_css'])) {
            return;
        }

        $css = $this->get_cached_css();
        if (!empty($css)) {
            echo "<style id='orbital-typography-presets-css'>\n" . $css . "\n</style>\n";
        }
    }

    /**
     * Output CSS in editor head.
     */
    public function output_editor_css() {
        if (!$this->is_block_editor() || empty($this->settings['output_preset_css'])) {
            return;
        }

        $css = $this->get_cached_css();
        if (!empty($css)) {
            echo "<style id='orbital-typography-presets-editor-css'>\n" . $css . "\n</style>\n";
        }
    }

    /**
     * Check if we're in the block editor.
     */
    private function is_block_editor() {
        global $pagenow;
        return ($pagenow === 'post.php' || $pagenow === 'post-new.php' || $pagenow === 'site-editor.php');
    }

    /**
     * Enqueue admin assets.
     */
    public function enqueue_admin_assets($hook) {
        if (!$this->is_admin_page($hook)) {
            return;
        }

        wp_enqueue_script(
            'orbital-typography-presets-admin',
            ORBITAL_EDITOR_SUITE_URL . 'assets/js/typography-presets-admin.js',
            array('wp-util'),
            self::VERSION,
            true
        );

        wp_localize_script(
            'orbital-typography-presets-admin',
            'orbitalTypographyPresetsAdmin',
            array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('orbital_typography_presets_nonce'),
                'strings' => array(
                    'savePreset' => __('Save Preset', 'orbital-editor-suite'),
                    'deletePreset' => __('Delete Preset', 'orbital-editor-suite'),
                    'confirmDelete' => __('Are you sure you want to delete this preset?', 'orbital-editor-suite')
                )
            )
        );
    }

    /**
     * Check if we're on an admin page for this module.
     */
    private function is_admin_page($hook) {
        return strpos($hook, 'orbital-editor-suite') !== false;
    }

    /**
     * AJAX: Save preset.
     */
    public function ajax_save_preset() {
        check_ajax_referer('orbital_typography_presets_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions.', 'orbital-editor-suite'));
        }

        $id = sanitize_key($_POST['id']);
        $preset_data = array(
            'label' => sanitize_text_field($_POST['label']),
            'description' => sanitize_textarea_field($_POST['description']),
            'category' => sanitize_text_field($_POST['category']),
            'properties' => array()
        );

        // Sanitize properties
        if (isset($_POST['properties']) && is_array($_POST['properties'])) {
            foreach ($_POST['properties'] as $property => $value) {
                $preset_data['properties'][sanitize_key($property)] = sanitize_text_field($value);
            }
        }

        if ($this->save_preset($id, $preset_data)) {
            wp_send_json_success(__('Preset saved successfully.', 'orbital-editor-suite'));
        } else {
            wp_send_json_error(__('Failed to save preset.', 'orbital-editor-suite'));
        }
    }

    /**
     * AJAX: Delete preset.
     */
    public function ajax_delete_preset() {
        check_ajax_referer('orbital_typography_presets_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions.', 'orbital-editor-suite'));
        }

        $id = sanitize_key($_POST['id']);

        if ($this->delete_preset($id)) {
            wp_send_json_success(__('Preset deleted successfully.', 'orbital-editor-suite'));
        } else {
            wp_send_json_error(__('Failed to delete preset or preset is default.', 'orbital-editor-suite'));
        }
    }

    /**
     * AJAX: Get presets.
     */
    public function ajax_get_presets() {
        check_ajax_referer('orbital_typography_presets_nonce', 'nonce');
        
        wp_send_json_success($this->get_presets());
    }

    /**
     * Register admin pages for this module.
     * Called via orbital_editor_suite_admin_pages hook.
     */
    public function register_admin_pages() {
        // Only register admin pages if module is enabled
        if (!$this->is_module_enabled()) {
            return;
        }
        
        // Load admin class
        if (!class_exists('\Orbital\Editor_Suite\Admin\Module_Admin')) {
            require_once plugin_dir_path(dirname(dirname(__FILE__))) . 'admin/class-module-admin.php';
        }
        
        if (!class_exists('\Orbital\Editor_Suite\Modules\Typography_Presets\Typography_Presets_Admin')) {
            require_once plugin_dir_path(__FILE__) . 'class-typography-presets-admin.php';
        }

        $admin = new Typography_Presets_Admin($this);
        $admin->register_admin_page();
    }

    /**
     * Get module slug.
     */
    public function get_slug() {
        return self::MODULE_SLUG;
    }

    /**
     * Get module settings.
     */
    public function get_settings() {
        return $this->settings;
    }
}