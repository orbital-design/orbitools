<?php
/**
 * Fired during plugin activation.
 *
 * @package    Orbital_Editor_Suite
 * @subpackage Orbital_Editor_Suite/includes
 */

namespace Orbital\Editor_Suite;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 */
class Activator {

    /**
     * Activate the plugin.
     *
     * Set up default options and perform any necessary setup tasks.
     */
    public static function activate() {
        self::create_default_options();
        self::cleanup_legacy_options();
        
        // Set a flag to show welcome notice
        set_transient('orbital_editor_suite_activation_notice', true, 60);
    }

    /**
     * Create default plugin options.
     */
    private static function create_default_options() {
        $default_options = array(
            'version' => ORBITAL_EDITOR_SUITE_VERSION,
            'settings' => array(
                'enable_plugin' => true,
                'enable_search' => true,
                'allowed_blocks' => array(
                    'core/paragraph',
                    'core/heading',
                    'core/list',
                    'core/button'
                ),
                'utility_categories' => array(
                    'font_family' => true,
                    'font_size' => true,
                    'font_weight' => true,
                    'text_color' => true,
                    'text_align' => true
                ),
                'custom_css' => '',
                'load_custom_css' => false
            )
        );

        // Only set defaults if options don't exist
        if (!get_option('orbital_editor_suite_options')) {
            update_option('orbital_editor_suite_options', $default_options);
        }
    }

    /**
     * Clean up legacy options from old plugin versions.
     */
    private static function cleanup_legacy_options() {
        // Remove old option names
        delete_option('oes_options');
        delete_option('oes_github_token');
    }
}