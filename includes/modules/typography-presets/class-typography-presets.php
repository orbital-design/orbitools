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
        $this->load_default_presets();
        $this->load_settings();
        $this->load_presets();
        $this->init_hooks();
    }

    /**
     * Initialize WordPress hooks.
     */
    private function init_hooks() {
        add_action('enqueue_block_editor_assets', array($this, 'enqueue_editor_assets'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_styles'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Register admin pages using WordPress hooks
        add_action('orbital_editor_suite_admin_pages', array($this, 'register_admin_pages'));
        
        // AJAX handlers for preset management
        add_action('wp_ajax_orbital_save_typography_preset', array($this, 'ajax_save_preset'));
        add_action('wp_ajax_orbital_delete_typography_preset', array($this, 'ajax_delete_preset'));
        add_action('wp_ajax_orbital_get_typography_presets', array($this, 'ajax_get_presets'));
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
                'category' => 'headings',
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
                'category' => 'headings',
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
                'category' => 'headings',
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
                'category' => 'body',
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
                'category' => 'body',
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
                'category' => 'body',
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
                'category' => 'utility',
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
                'category' => 'utility',
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
            'enabled' => true,
            'replace_core_controls' => true,
            'allowed_blocks' => array(
                'core/paragraph',
                'core/heading',
                'core/list',
                'core/quote',
                'core/button'
            ),
            'show_categories' => true,
            'custom_css_output' => true
        ));
    }

    /**
     * Refresh settings from database.
     */
    public function refresh_settings() {
        $this->load_settings();
    }

    /**
     * Load presets from database.
     */
    private function load_presets() {
        $saved_presets = get_option('orbital_typography_presets', array());
        
        // Merge default presets with saved custom presets
        $this->presets = array_merge($this->default_presets, $saved_presets);
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
     * Get presets by category.
     */
    public function get_presets_by_category($category = null) {
        if (!$category) {
            return $this->presets;
        }
        
        return array_filter($this->presets, function($preset) use ($category) {
            return isset($preset['category']) && $preset['category'] === $category;
        });
    }

    /**
     * Get preset categories.
     */
    public function get_categories() {
        $categories = array();
        foreach ($this->presets as $preset) {
            if (isset($preset['category'])) {
                $categories[$preset['category']] = $preset['category'];
            }
        }
        return $categories;
    }

    /**
     * Add or update a preset.
     */
    public function save_preset($id, $preset_data) {
        $preset = wp_parse_args($preset_data, array(
            'label' => '',
            'description' => '',
            'properties' => array(),
            'category' => 'custom',
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
                $css .= ".orbital-preset-{$id} {\n";
                
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
        return !empty($this->settings['enabled']);
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
        if (!$this->is_enabled()) {
            return;
        }

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
                'categories' => $this->get_categories(),
                'settings' => $this->settings,
                'strings' => array(
                    'selectPreset' => __('Select Typography Preset', 'orbital-editor-suite'),
                    'customPreset' => __('Custom Preset', 'orbital-editor-suite'),
                    'noPreset' => __('No Preset', 'orbital-editor-suite')
                )
            )
        );

        // Enqueue CSS for editor
        if (!empty($this->settings['custom_css_output'])) {
            wp_add_inline_style('wp-edit-blocks', $this->generate_css());
        }
    }

    /**
     * Enqueue frontend styles.
     */
    public function enqueue_frontend_styles() {
        if (!$this->is_enabled() || empty($this->settings['custom_css_output'])) {
            return;
        }

        wp_add_inline_style('wp-block-library', $this->generate_css());
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
            array('jquery', 'wp-util'),
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