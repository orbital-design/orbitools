<?php
namespace Orbitools\Controls\AspectRatio_Controls;

use Orbitools\Core\Abstracts\Module_Base;
use Orbitools\Core\AspectRatioConfig;
use Orbitools\Core\Helpers\AspectRatio_CSS_Generator;

/**
 * Aspect Ratio Controls Module
 *
 * Provides automatic responsive aspect ratio controls for blocks
 * with orbitools.aspectRatio support in block.json.
 *
 * @package Orbitools
 * @since 1.4.0
 */
class AspectRatio_Controls extends Module_Base
{
    public function __construct()
    {
        parent::__construct();
    }

    public function get_slug(): string
    {
        return 'aspect-ratio-controls';
    }

    public function get_name(): string
    {
        return __('Aspect Ratio Controls', 'orbitools');
    }

    public function get_description(): string
    {
        return __('Responsive aspect ratio controls for blocks with orbitools.aspectRatio support.', 'orbitools');
    }

    public function get_version(): string
    {
        return '1.0.0';
    }

    public function init(): void
    {
        // Aspect ratio configuration + CSS generation are part of this module —
        // they only run when AspectRatio_Controls is enabled.
        AspectRatioConfig::init();
        AspectRatio_CSS_Generator::init();

        add_action('enqueue_block_editor_assets', [$this, 'enqueue_editor_assets']);
    }

    public function enqueue_editor_assets(): void
    {
        $asset_url = ORBITOOLS_URL . 'build/admin/js/controls/aspect-ratio/';

        // Attribute registration (must load first)
        \wp_enqueue_script(
            'orbitools-aspect-ratio-attributes',
            $asset_url . 'editor-aspect-ratio-attribute-registration.js',
            ['wp-hooks', 'wp-blocks'],
            $this->get_version(),
            true
        );

        // Class name application
        \wp_enqueue_script(
            'orbitools-aspect-ratio-classes',
            $asset_url . 'editor-aspect-ratio-classname-application.js',
            ['wp-hooks', 'wp-compose', 'wp-blocks'],
            $this->get_version(),
            true
        );

        // Control registration (loads after attributes)
        \wp_enqueue_script(
            'orbitools-aspect-ratio-controls',
            $asset_url . 'editor-aspect-ratio-register-controls.js',
            ['wp-hooks', 'wp-compose', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-data', 'wp-icons'],
            $this->get_version(),
            true
        );
    }

    public function get_default_settings(): array
    {
        return [];
    }
}
