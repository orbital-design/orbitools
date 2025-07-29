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
                'columnOptions' => $this->get_column_options(),
            )
        );

        // Enqueue editor styles
        $this->enqueue_editor_styles($module_url);
    }

    /**
     * Get column width options for JavaScript
     *
     * @since 1.0.0
     * @return array Column options configuration
     */
    private function get_column_options(): array
    {
        return array(
            array('label' => __('Auto', 'orbitools'), 'value' => 'auto'),
            array('label' => __('1 of 12 (8.33%)', 'orbitools'), 'value' => '1_col'),
            array('label' => __('2 of 12 (16.67%)', 'orbitools'), 'value' => '2_cols'),
            array('label' => __('3 of 12 (25%)', 'orbitools'), 'value' => '3_cols'),
            array('label' => __('4 of 12 (33.33%)', 'orbitools'), 'value' => '4_cols'),
            array('label' => __('5 of 12 (41.67%)', 'orbitools'), 'value' => '5_cols'),
            array('label' => __('6 of 12 (50%)', 'orbitools'), 'value' => '6_cols'),
            array('label' => __('7 of 12 (58.33%)', 'orbitools'), 'value' => '7_cols'),
            array('label' => __('8 of 12 (66.67%)', 'orbitools'), 'value' => '8_cols'),
            array('label' => __('9 of 12 (75%)', 'orbitools'), 'value' => '9_cols'),
            array('label' => __('10 of 12 (83.33%)', 'orbitools'), 'value' => '10_cols'),
            array('label' => __('11 of 12 (91.67%)', 'orbitools'), 'value' => '11_cols'),
            array('label' => __('12 of 12 (100%)', 'orbitools'), 'value' => '12_cols'),
        );
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
        // Only process if module is enabled
        if (!Settings_Helper::is_module_enabled()) {
            return $block_content;
        }

        // Check if block has column width attributes
        if (empty($block['attrs']['orbitoolsColumnWidths'])) {
            return $block_content;
        }

        $column_widths = $block['attrs']['orbitoolsColumnWidths'];
        
        // Generate CSS classes
        $css_classes = $this->css_generator->generate_css_classes($column_widths);
        
        if (empty($css_classes)) {
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