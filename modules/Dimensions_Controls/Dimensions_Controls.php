<?php
namespace Orbitools\Modules\Dimensions_Controls;

use Orbitools\Abstracts\Module_Base;

/**
 * Dimensions Controls Module
 *
 * Provides automatic responsive dimensions controls for blocks with orbitools.dimensions support.
 * Blocks only need to add orbitools.dimensions to their supports to get automatic:
 * - Attribute registration (orbGap, orbPadding, orbMargin)
 * - Control injection in styles tab 
 * - CSS class application
 *
 * @package Orbitools
 * @since 1.0.0
 */
class Dimensions_Controls extends Module_Base {
    
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
        return 'dimensions-controls';
    }

    /**
     * Get the module's display name
     */
    public function get_name(): string {
        return __('Dimensions Controls', 'orbitools');
    }

    /**
     * Get the module's description
     */
    public function get_description(): string {
        return __('Automatic responsive dimensions controls for blocks with orbitools.dimensions support.', 'orbitools');
    }

    /**
     * Get the module's version
     */
    public function get_version(): string {
        return '1.0.0';
    }

    /**
     * Check if the module is currently enabled
     */
    public function is_enabled(): bool {
        // Always enabled - this is a core layout feature
        return true;
    }

    /**
     * Initialize the module
     */
    public function init(): void {
        add_action('enqueue_block_editor_assets', [$this, 'enqueue_editor_assets']);
    }

    /**
     * Enqueue editor assets for dimensions controls
     */
    public function enqueue_editor_assets(): void {
        $asset_url = ORBITOOLS_URL . 'build/controls/dimensions/';

        // Enqueue attribute registration (must be loaded first)
        wp_enqueue_script(
            'orbitools-dimensions-attributes',
            $asset_url . 'editor-dimensions-attribute-registration.js',
            ['wp-hooks', 'wp-blocks'],
            $this->get_version(),
            true
        );

        // Enqueue class name application
        wp_enqueue_script(
            'orbitools-dimensions-classes',
            $asset_url . 'editor-dimensions-classname-application.js',
            ['wp-hooks', 'wp-compose', 'wp-blocks'],
            $this->get_version(),
            true
        );

        // Enqueue control registration (must be loaded after attributes)
        wp_enqueue_script(
            'orbitools-dimensions-controls',
            $asset_url . 'editor-dimensions-register-controls.js',
            ['wp-hooks', 'wp-compose', 'wp-element', 'wp-block-editor', 'wp-components'],
            $this->get_version(),
            true
        );
    }

    /**
     * Get default settings
     */
    public function get_default_settings(): array {
        return [];
    }
}