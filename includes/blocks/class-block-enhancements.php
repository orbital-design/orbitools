<?php
/**
 * Block enhancements functionality.
 *
 * @package    Orbital_Editor_Suite
 * @subpackage Orbital_Editor_Suite/includes/blocks
 */

namespace Orbital\Editor_Suite\Blocks;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Block enhancements functionality.
 *
 * Handles all block editor enhancements and typography controls.
 */
class Block_Enhancements {

    /**
     * The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     */
    private $version;

    /**
     * Plugin options.
     */
    private $options;

    /**
     * Initialize the class and set its properties.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->load_options();
    }

    /**
     * Load plugin options.
     */
    private function load_options() {
        $options = get_option('orbital_editor_suite_options', array());
        $this->options = isset($options['settings']) ? $options['settings'] : array();
    }

    /**
     * Enqueue block editor assets.
     */
    public function enqueue_editor_assets() {
        // Check if plugin is enabled
        if (empty($this->options['enable_plugin'])) {
            return;
        }

        wp_enqueue_script(
            $this->plugin_name . '-blocks',
            plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/js/typography-controls.js',
            array(
                'wp-dom-ready',
                'wp-blocks',
                'wp-element',
                'wp-block-editor',
                'wp-components',
                'wp-compose',
                'wp-hooks'
            ),
            $this->version,
            true
        );

        // Pass options to JavaScript
        wp_localize_script(
            $this->plugin_name . '-blocks',
            'orbitalEditorSuite',
            array(
                'options' => $this->options,
                'nonce' => wp_create_nonce('orbital_editor_suite_nonce'),
                'strings' => array(
                    'typographyControls' => __('Typography Controls', 'orbital-editor-suite'),
                    'fontFamily' => __('Font Family', 'orbital-editor-suite'),
                    'fontSize' => __('Font Size', 'orbital-editor-suite'),
                    'fontWeight' => __('Font Weight', 'orbital-editor-suite'),
                    'textColor' => __('Text Color', 'orbital-editor-suite'),
                    'textAlign' => __('Text Alignment', 'orbital-editor-suite')
                )
            )
        );

        // Enqueue custom CSS if enabled
        if (!empty($this->options['load_custom_css']) && !empty($this->options['custom_css'])) {
            $this->enqueue_custom_css();
        }
    }

    /**
     * Enqueue custom CSS.
     */
    private function enqueue_custom_css() {
        wp_add_inline_style('wp-edit-blocks', $this->options['custom_css']);
    }

    /**
     * Get allowed blocks.
     */
    public function get_allowed_blocks() {
        return isset($this->options['allowed_blocks']) ? 
            $this->options['allowed_blocks'] : 
            array('core/paragraph', 'core/heading');
    }

    /**
     * Get enabled utility categories.
     */
    public function get_utility_categories() {
        return isset($this->options['utility_categories']) ? 
            $this->options['utility_categories'] : 
            array();
    }

    /**
     * Check if search is enabled.
     */
    public function is_search_enabled() {
        return !empty($this->options['enable_search']);
    }
}