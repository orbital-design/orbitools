<?php

/**
 * Column Widths Controls Block Editor Integration
 *
 * Handles all block editor related functionality including script enqueuing,
 * data localization, and block control integration.
 *
 * @package    Orbitools
 * @subpackage Modules/Column_Widths_Controls/Frontend
 * @since      1.0.0
 */

namespace Orbitools\Modules\Column_Widths_Controls\Frontend;

use Orbitools\Modules\Column_Widths_Controls\Admin\Settings_Helper;
use Orbitools\Modules\Column_Widths_Controls\Core\CSS_Generator;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Block Editor Integration Class
 *
 * Manages block editor integration for column width controls.
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
     * CSS Generator instance
     *
     * @since 1.0.0
     * @var CSS_Generator
     */
    private $css_generator;

    /**
     * Initialize Block Editor integration
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->css_generator = new CSS_Generator();
        
        // Hook into block editor
        add_action('enqueue_block_editor_assets', array($this, 'enqueue_editor_assets'));
        
        // Hook into block rendering to add CSS classes
        add_filter('render_block', array($this, 'add_column_width_classes'), 10, 2);
    }

    /**
     * Enqueue block editor assets
     *
     * @since 1.0.0
     */
    public function enqueue_editor_assets(): void
    {
        $module_url = ORBITOOLS_URL . 'modules/Column_Widths_Controls/';
        
        // JavaScript dependencies
        $deps = array(
            'wp-blocks',
            'wp-element',
            'wp-components',
            'wp-block-editor',
            'wp-hooks',
            'wp-compose',
            'wp-i18n',
            'wp-data',
        );

        // Enqueue JavaScript files
        $this->enqueue_script(
            'orbitools-column-widths-attribute-registration',
            $module_url . 'js/attribute-registration.js',
            $deps
        );

        $this->enqueue_script(
            'orbitools-column-widths-editor-controls',
            $module_url . 'js/editor-controls.js',
            array_merge($deps, array('orbitools-column-widths-attribute-registration'))
        );

        // Localize data for JavaScript
        wp_localize_script(
            'orbitools-column-widths-editor-controls',
            'orbitoolsColumnWidths',
            array(
                'isEnabled' => Settings_Helper::is_module_enabled(),
                'outputCSS' => Settings_Helper::output_column_widths_css(),
                'breakpoints' => array(
                    'base' => array('label' => __('Base', 'orbitools'), 'min_width' => '0px'),
                    'sm' => array('label' => __('Small (576px+)', 'orbitools'), 'min_width' => '576px'),
                    'md' => array('label' => __('Medium (768px+)', 'orbitools'), 'min_width' => '768px'),
                    'lg' => array('label' => __('Large (992px+)', 'orbitools'), 'min_width' => '992px'),
                    'xl' => array('label' => __('Extra Large (1200px+)', 'orbitools'), 'min_width' => '1200px'),
                ),
                // Column options are now generated dynamically in JavaScript based on parent grid system
            )
        );

        // Enqueue editor styles
        $this->enqueue_editor_styles($module_url);
    }


    /**
     * Add column width CSS classes to blocks during rendering
     *
     * @since 1.0.0
     * @param string $block_content The block content
     * @param array  $block The block data
     * @return string Modified block content
     */
    public function add_column_width_classes(string $block_content, array $block): string
    {
        // Debug logging
        if (isset($block['attrs']['orbitoolsColumnWidths'])) {
            error_log('COLUMN_WIDTHS_DEBUG: Block has column widths: ' . print_r($block['attrs']['orbitoolsColumnWidths'], true));
        }
        
        // Only process if module is enabled
        if (!Settings_Helper::is_module_enabled()) {
            error_log('COLUMN_WIDTHS_DEBUG: Module not enabled');
            return $block_content;
        }

        // Check if block has column width attributes
        if (empty($block['attrs']['orbitoolsColumnWidths'])) {
            return $block_content;
        }

        $column_widths = $block['attrs']['orbitoolsColumnWidths'];
        
        // Generate CSS classes
        $css_classes = $this->css_generator->generate_css_classes($column_widths);
        
        error_log('COLUMN_WIDTHS_DEBUG: Generated classes: ' . print_r($css_classes, true));
        
        if (empty($css_classes)) {
            error_log('COLUMN_WIDTHS_DEBUG: No CSS classes generated');
            return $block_content;
        }

        // Add classes to the block wrapper
        $classes_string = implode(' ', $css_classes);
        
        // Find the first HTML tag and add our classes
        $pattern = '/^(\s*<[^>]+class=["\'])([^"\']*)/';
        if (preg_match($pattern, $block_content)) {
            // Block already has classes
            $block_content = preg_replace(
                $pattern,
                '$1$2 ' . $classes_string,
                $block_content,
                1
            );
        } else {
            // Block doesn't have classes, try to add them
            $pattern = '/^(\s*<[^>]+)/';
            if (preg_match($pattern, $block_content)) {
                $block_content = preg_replace(
                    $pattern,
                    '$1 class="' . $classes_string . '"',
                    $block_content,
                    1
                );
            }
        }

        return $block_content;
    }

    /**
     * Enqueue a script with version handling
     *
     * @since 1.0.0
     * @param string $handle Script handle
     * @param string $src Script source URL
     * @param array  $deps Script dependencies
     */
    private function enqueue_script(string $handle, string $src, array $deps = array()): void
    {
        $version = defined('WP_DEBUG') && WP_DEBUG ? time() : self::VERSION;
        
        wp_enqueue_script(
            $handle,
            $src,
            $deps,
            $version,
            true
        );
    }

    /**
     * Enqueue editor styles
     *
     * @since 1.0.0
     * @param string $module_url Module base URL
     */
    private function enqueue_editor_styles(string $module_url): void
    {
        $version = defined('WP_DEBUG') && WP_DEBUG ? time() : self::VERSION;
        
        // Enqueue editor-specific styles if they exist
        $editor_css_path = $module_url . 'css/editor.css';
        if (file_exists(str_replace(ORBITOOLS_URL, ORBITOOLS_DIR, $editor_css_path))) {
            wp_enqueue_style(
                'orbitools-column-widths-editor',
                $editor_css_path,
                array(),
                $version
            );
        }
    }
}