<?php
/**
 * Typography Presets WP Options Kit Admin Class
 *
 * Admin interface for Typography Presets module using WP Options Kit
 *
 * @package    Orbital_Editor_Suite
 * @subpackage Orbital_Editor_Suite/includes/modules/typography-presets
 */

namespace Orbital\Editor_Suite\Modules\Typography_Presets;

use TDP\OptionsKit;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Typography Presets WP Options Kit Admin Class
 *
 * Provides admin interface using WP Options Kit framework
 */
class Typography_Presets_OptionsKit_Admin {

    /**
     * Module instance.
     */
    private $module;

    /**
     * OptionsKit instance.
     */
    private $options_kit;

    /**
     * Initialize admin properties.
     */
    public function __construct($module) {
        $this->module = $module;
        $this->init_options_kit();
    }

    /**
     * Initialize WP Options Kit.
     */
    private function init_options_kit() {
        // Include WP Options Kit
        require_once ORBITAL_EDITOR_SUITE_PATH . 'vendor/wp-user-manager/wp-optionskit/wp-optionskit.php';

        // Initialize OptionsKit with our configuration
        $this->options_kit = new OptionsKit([
            'page_title' => __('Typography Presets', 'orbital-editor-suite'),
            'menu_title' => __('Typography Presets', 'orbital-editor-suite'),
            'slug' => 'orbital-typography-presets',
            'parent' => 'orbital-editor-suite',
            'capability' => 'manage_options',
            'option_name' => 'orbital_typography_presets_options',
            'plugin_file' => ORBITAL_EDITOR_SUITE_FILE,
        ]);

        // Register settings tabs and fields
        $this->register_settings();
    }

    /**
     * Register settings using WP Options Kit filters.
     */
    private function register_settings() {
        // Add tabs
        add_filter('orbital-typography-presets_settings_tabs', [$this, 'register_tabs']);
        
        // Add settings sections
        add_filter('orbital-typography-presets_registered_settings_sections', [$this, 'register_sections']);
        
        // Add settings fields
        add_filter('orbital-typography-presets_registered_settings', [$this, 'register_settings_fields']);
    }

    /**
     * Register settings tabs.
     */
    public function register_tabs($tabs) {
        $tabs['general'] = [
            'title' => __('General Settings', 'orbital-editor-suite'),
            'description' => __('Configure how the Typography Presets module behaves.', 'orbital-editor-suite')
        ];

        $tabs['presets'] = [
            'title' => __('Preset Management', 'orbital-editor-suite'),
            'description' => __('Create and manage your typography presets.', 'orbital-editor-suite')
        ];

        $tabs['css'] = [
            'title' => __('Generated CSS', 'orbital-editor-suite'),
            'description' => __('View and copy the CSS generated for your presets.', 'orbital-editor-suite')
        ];

        $tabs['instructions'] = [
            'title' => __('Theme.json Instructions', 'orbital-editor-suite'),
            'description' => __('How to configure presets using theme.json (Advanced users only).', 'orbital-editor-suite')
        ];

        return $tabs;
    }

    /**
     * Register settings sections.
     */
    public function register_sections($sections) {
        // General settings section
        $sections['general']['typography_general'] = [
            'title' => __('Typography Settings', 'orbital-editor-suite'),
            'description' => __('Configure the basic behavior of the typography presets system.', 'orbital-editor-suite'),
            'tab' => 'general'
        ];

        // Preset management section
        $sections['presets']['preset_management'] = [
            'title' => __('Manage Presets', 'orbital-editor-suite'),
            'description' => __('Add, edit, and delete typography presets.', 'orbital-editor-suite'),
            'tab' => 'presets'
        ];

        return $sections;
    }

    /**
     * Register settings fields.
     */
    public function register_settings_fields($fields) {
        // General Settings Fields
        $fields['preset_generation_method'] = [
            'tab' => 'general',
            'section' => 'typography_general',
            'id' => 'preset_generation_method',
            'title' => __('Preset Generation Method', 'orbital-editor-suite'),
            'description' => __('Choose how presets are defined and managed.', 'orbital-editor-suite'),
            'type' => 'select',
            'options' => [
                'admin' => __('Admin Interface (User-friendly)', 'orbital-editor-suite'),
                'theme_json' => __('theme.json (Developer/Advanced)', 'orbital-editor-suite')
            ],
            'default' => 'admin'
        ];

        $fields['replace_core_controls'] = [
            'tab' => 'general',
            'section' => 'typography_general',
            'id' => 'replace_core_controls',
            'title' => __('Replace Core Typography Controls', 'orbital-editor-suite'),
            'description' => __('Remove WordPress core typography controls and replace with preset system.', 'orbital-editor-suite'),
            'type' => 'checkbox',
            'default' => false
        ];

        $fields['show_groups'] = [
            'tab' => 'general',
            'section' => 'typography_general',
            'id' => 'show_groups',
            'title' => __('Show Groups in Dropdown', 'orbital-editor-suite'),
            'description' => __('Organize presets into groups in the block editor dropdown.', 'orbital-editor-suite'),
            'type' => 'checkbox',
            'default' => true
        ];

        $fields['output_preset_css'] = [
            'tab' => 'general',
            'section' => 'typography_general',
            'id' => 'output_preset_css',
            'title' => __('Output Preset CSS', 'orbital-editor-suite'),
            'description' => __('Automatically generate and include CSS for all presets.', 'orbital-editor-suite'),
            'type' => 'checkbox',
            'default' => true
        ];

        $fields['allowed_blocks'] = [
            'tab' => 'general',
            'section' => 'typography_general',
            'id' => 'allowed_blocks',
            'title' => __('Allowed Blocks', 'orbital-editor-suite'),
            'description' => __('Select which blocks should have typography preset controls.', 'orbital-editor-suite'),
            'type' => 'multicheck',
            'options' => [
                'core/paragraph' => __('Paragraph', 'orbital-editor-suite'),
                'core/heading' => __('Heading', 'orbital-editor-suite'),
                'core/list' => __('List', 'orbital-editor-suite'),
                'core/quote' => __('Quote', 'orbital-editor-suite'),
                'core/button' => __('Button', 'orbital-editor-suite'),
                'core/pullquote' => __('Pullquote', 'orbital-editor-suite'),
                'core/group' => __('Group', 'orbital-editor-suite'),
                'core/column' => __('Column', 'orbital-editor-suite')
            ],
            'default' => ['core/paragraph', 'core/heading', 'core/list', 'core/quote', 'core/button']
        ];

        return $fields;
    }
}