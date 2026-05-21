<?php
namespace Orbitools\Controls\Spacings_Controls;

use Orbitools\Core\Abstracts\Module_Base;
use Orbitools\Core\SpacingConfig;
use Orbitools\Core\Helpers\Gaps_CSS_Generator;

/**
 * Spacings Controls Module
 *
 * Provides automatic responsive spacings controls for blocks with orbitools.spacings support.
 * Blocks only need to add orbitools.spacings to their supports to get automatic:
 * - Attribute registration (orbGap, orbPadding, orbMargin)
 * - Control injection in styles tab 
 * - CSS class application
 *
 * @package Orbitools
 * @since 1.0.0
 */
class Spacings_Controls extends Module_Base {
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * Get the module's unique slug identifier
     */
    public function get_slug(): string {
        return 'spacings-controls';
    }

    /**
     * Get the module's display name
     */
    public function get_name(): string {
        return __('Spacings Controls', 'orbitools');
    }

    /**
     * Get the module's description
     */
    public function get_description(): string {
        return __('Automatic responsive spacings controls for blocks with orbitools.spacings support.', 'orbitools');
    }

    /**
     * Get the module's version
     */
    public function get_version(): string {
        return '1.0.0';
    }

    /**
     * Initialize the module
     */
    public function init(): void {
        // Spacing configuration resolution + gap CSS generation are part of
        // this module — they only run when Spacings_Controls is enabled.
        SpacingConfig::init();
        Gaps_CSS_Generator::init();

        add_action('enqueue_block_editor_assets', [$this, 'enqueue_editor_assets']);
    }

    /**
     * Enqueue editor assets for spacings controls
     */
    public function enqueue_editor_assets(): void {
        $asset_url = ORBITOOLS_URL . 'build/admin/js/controls/spacings/';

        // Enqueue attribute registration (must be loaded first)
        \wp_enqueue_script(
            'orbitools-spacings-attributes',
            $asset_url . 'editor-spacings-attribute-registration.js',
            ['wp-hooks', 'wp-blocks'],
            $this->get_version(),
            true
        );

        // Enqueue class name application
        \wp_enqueue_script(
            'orbitools-spacings-classes',
            $asset_url . 'editor-spacings-classname-application.js',
            ['wp-hooks', 'wp-compose', 'wp-blocks'],
            $this->get_version(),
            true
        );

        // Enqueue control registration (must be loaded after attributes)
        \wp_enqueue_script(
            'orbitools-spacings-controls',
            $asset_url . 'editor-spacings-register-controls.js',
            ['wp-hooks', 'wp-compose', 'wp-element', 'wp-block-editor', 'wp-components'],
            $this->get_version(),
            true
        );

        // Enqueue editor styles
        $css_url = ORBITOOLS_URL . 'build/admin/css/editor.css';
        $css_file = ORBITOOLS_DIR . 'build/admin/css/editor.css';
        if (file_exists($css_file)) {
            \wp_enqueue_style(
                'orbitools-editor',
                $css_url,
                [],
                (string) filemtime($css_file)
            );
        }
    }

    /**
     * Get default settings
     */
    public function get_default_settings(): array {
        return [];
    }
}