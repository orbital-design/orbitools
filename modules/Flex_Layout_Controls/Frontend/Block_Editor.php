<?php

/**
 * Flex Layout Controls Block Editor Integration
 *
 * Handles all block editor related functionality including script enqueuing,
 * data localization, and block control integration.
 *
 * @package    Orbitools
 * @subpackage Modules/Flex_Layout_Controls/Frontend
 * @since      1.0.0
 */

namespace Orbitools\Modules\Flex_Layout_Controls\Frontend;

use Orbitools\Modules\Flex_Layout_Controls\Admin\Settings_Helper;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Block Editor Integration Class
 *
 * Manages block editor integration for flex layout controls.
 *
 * @since 1.0.0
 */
class Block_Editor
{
    /**
     * Module version
     *
     * @since 1.0.0
     * @var string
     */
    const VERSION = '1.0.0';

    /**
     * Module settings
     *
     * @since 1.0.0
     * @var array
     */
    private $settings;

    /**
     * Initialize Block Editor integration
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->load_settings();
        
        // Hook into block editor
        add_action('enqueue_block_editor_assets', array($this, 'enqueue_editor_assets'));
    }

    /**
     * Load module settings
     *
     * @since 1.0.0
     */
    private function load_settings(): void
    {
        $this->settings = array(
            'flex_output_css' => Settings_Helper::output_flex_css(),
        );
    }

    /**
     * Enqueue block editor assets
     *
     * @since 1.0.0
     */
    public function enqueue_editor_assets(): void
    {
        $module_url = ORBITOOLS_URL . 'modules/Flex_Layout_Controls/';
        
        // JavaScript dependencies
        $deps = array(
            'wp-blocks',
            'wp-element',
            'wp-components',
            'wp-block-editor',
            'wp-hooks',
            'wp-compose',
            'wp-i18n',
        );

        // Enqueue JavaScript files - simplified after clean rewrite
        $this->enqueue_script(
            'orbitools-flex-alignment-icons',
            $module_url . 'js/alignment-icons.js',
            array()
        );

        $this->enqueue_script(
            'orbitools-flex-attribute-registration',
            $module_url . 'js/attribute-registration.js',
            $deps
        );

        $this->enqueue_script(
            'orbitools-flex-editor-controls',
            $module_url . 'js/editor-controls.js',
            array_merge($deps, array('orbitools-flex-attribute-registration', 'orbitools-flex-alignment-icons'))
        );

        // Localize minimal data for JavaScript
        wp_localize_script(
            'orbitools-flex-editor-controls',
            'orbitoolsFlexLayout',
            array(
                'isEnabled' => Settings_Helper::is_module_enabled(),
                'outputCSS' => Settings_Helper::output_flex_css(),
            )
        );

        // Enqueue editor styles
        $this->enqueue_editor_styles($module_url);
    }


    /**
     * Enqueue a script with file existence check
     *
     * @since 1.0.0
     * @param string $handle Script handle
     * @param string $src Script URL
     * @param array $deps Dependencies
     */
    private function enqueue_script(string $handle, string $src, array $deps = array()): void
    {
        $file_path = str_replace(ORBITOOLS_URL, ORBITOOLS_DIR, $src);
        
        if (file_exists($file_path)) {
            wp_enqueue_script(
                $handle,
                $src,
                $deps,
                self::VERSION,
                true
            );
        }
    }

    /**
     * Enqueue editor styles
     *
     * @since 1.0.0
     * @param string $module_url Module base URL
     */
    private function enqueue_editor_styles(string $module_url): void
    {
        $css_file = $module_url . 'css/editor.css';
        $css_path = ORBITOOLS_DIR . 'modules/Flex_Layout_Controls/css/editor.css';

        if (file_exists($css_path)) {
            wp_enqueue_style(
                'orbitools-flex-layout-controls-editor',
                $css_file,
                array('wp-edit-blocks'),
                self::VERSION
            );
        }
    }

}