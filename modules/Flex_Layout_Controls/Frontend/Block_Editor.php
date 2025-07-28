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

        // Enqueue JavaScript files in order: config, icons, then functionality
        $this->enqueue_script(
            'orbitools-flex-config',
            $module_url . 'js/flex-config.js',
            array('wp-element')
        );

        $this->enqueue_script(
            'orbitools-flex-icons',
            $module_url . 'js/flex-icons.js',
            array('wp-element', 'orbitools-flex-config')
        );

        $this->enqueue_script(
            'orbitools-flex-attribute-registration',
            $module_url . 'js/attribute-registration.js',
            array_merge($deps, array('orbitools-flex-config'))
        );

        $this->enqueue_script(
            'orbitools-flex-editor-controls',
            $module_url . 'js/editor-controls.js',
            array_merge($deps, array('orbitools-flex-config', 'orbitools-flex-icons', 'orbitools-flex-attribute-registration'))
        );

        $this->enqueue_script(
            'orbitools-flex-class-application',
            $module_url . 'js/class-application.js',
            array_merge($deps, array('orbitools-flex-config', 'orbitools-flex-attribute-registration'))
        );

        // Localize data for JavaScript
        wp_localize_script(
            'orbitools-flex-editor-controls',
            'orbitoolsFlexLayout',
            array(
                'flexControls' => $this->get_flex_controls_config(),
                'isEnabled' => Settings_Helper::is_module_enabled(),
                'outputCSS' => Settings_Helper::output_flex_css(),
            )
        );

        // Enqueue editor styles
        $this->enqueue_editor_styles($module_url);
    }

    /**
     * Get flex controls configuration for JavaScript
     *
     * @since 1.0.0
     * @return array Flex controls configuration
     */
    private function get_flex_controls_config(): array
    {
        return array(
            'flex_direction' => array(
                'label' => __('Orientation (flex-direction)', 'orbitools'),
                'description' => __('Controls the main axis direction', 'orbitools'),
                'choices' => array(
                    'row' => __('Horizontal', 'orbitools'),
                    'column' => __('Vertical', 'orbitools'),
                ),
                'default' => 'row',
            ),
            'flex_wrap' => array(
                'label' => __('Wrapping (flex-wrap)', 'orbitools'),
                'description' => __('Controls whether items wrap to new lines', 'orbitools'),
                'choices' => array(
                    'nowrap' => __('No Wrap', 'orbitools'),
                    'wrap' => __('Wrap', 'orbitools'),
                    'wrap-reverse' => __('Wrap Reverse', 'orbitools'),
                ),
                'default' => 'nowrap',
            ),
            'align_items' => array(
                'label' => __('Cross-Axis Alignment (align-items)', 'orbitools'),
                'description' => __('How items align on the cross axis (perpendicular to flex direction)', 'orbitools'),
                'choices' => array(
                    'stretch' => __('Stretch', 'orbitools'),
                    'center' => __('Center', 'orbitools'),
                    'flex-start' => __('Start', 'orbitools'),
                    'flex-end' => __('End', 'orbitools'),
                    'baseline' => __('Baseline', 'orbitools'),
                ),
                'default' => 'stretch',
            ),
            'justify_content' => array(
                'label' => __('Main-Axis Alignment (justify-content)', 'orbitools'),
                'description' => __('How items align on the main axis (along flex direction)', 'orbitools'),
                'choices' => array(
                    'flex-start' => __('Start', 'orbitools'),
                    'center' => __('Center', 'orbitools'),
                    'flex-end' => __('End', 'orbitools'),
                    'space-between' => __('Space Between', 'orbitools'),
                    'space-around' => __('Space Around', 'orbitools'),
                    'space-evenly' => __('Space Evenly', 'orbitools'),
                ),
                'default' => 'flex-start',
            ),
            'align_content' => array(
                'label' => __('Multi-line Alignment (align-content)', 'orbitools'),
                'description' => __('Controls spacing between wrapped flex lines', 'orbitools'),
                'choices' => array(
                    'stretch' => __('Stretch', 'orbitools'),
                    'center' => __('Center', 'orbitools'),
                    'flex-start' => __('Start', 'orbitools'),
                    'flex-end' => __('End', 'orbitools'),
                    'space-between' => __('Space Between', 'orbitools'),
                    'space-around' => __('Space Around', 'orbitools'),
                    'space-evenly' => __('Space Evenly', 'orbitools'),
                ),
                'default' => 'stretch',
            ),
        );
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