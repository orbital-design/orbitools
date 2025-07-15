<?php

/**
 * Typography Presets Block Editor Integration
 *
 * Handles all block editor related functionality including script enqueuing,
 * data localization, and block control integration.
 *
 * @package    Orbitools
 * @subpackage Modules/Typography_Presets/Frontend
 * @since      1.0.0
 */

namespace Orbitools\Modules\Typography_Presets\Frontend;

use Orbitools\Modules\Typography_Presets\Core\Preset_Manager;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Block Editor Integration Class
 *
 * Manages block editor integration for typography presets.
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
     * Preset Manager instance
     *
     * @since 1.0.0
     * @var Preset_Manager
     */
    private $preset_manager;

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
     * @param Preset_Manager $preset_manager The preset manager instance.
     */
    public function __construct(Preset_Manager $preset_manager)
    {
        $this->preset_manager = $preset_manager;
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
        $admin_settings = get_option('orbitools_settings', array());

        $defaults = array(
            'allowed_blocks' => array(
                'core/paragraph',
                'core/heading',
                'core/list',
                'core/quote',
                'core/button',
            ),
            'typography_show_groups_in_dropdown' => false,
        );

        $this->settings = wp_parse_args($admin_settings, $defaults);
    }

    /**
     * Get blocks allowed to use typography presets
     *
     * @since 1.0.0
     * @return array Array of allowed block names
     */
    public function get_allowed_blocks(): array
    {
        return $this->settings['allowed_blocks'];
    }

    /**
     * Enqueue block editor assets
     *
     * Loads JavaScript and localizes data for the block editor integration.
     *
     * @since 1.0.0
     */
    public function enqueue_editor_assets(): void
    {
        // Don't load if no presets available
        if (!$this->preset_manager->has_presets()) {
            $this->enqueue_empty_state_assets();
            return;
        }

        $this->enqueue_preset_assets();
    }

    /**
     * Enqueue assets when presets are available
     *
     * @since 1.0.0
     */
    private function enqueue_preset_assets(): void
    {
        // Enqueue attribute registration first
        wp_enqueue_script(
            'orbitools-typography-attribute-registration',
            ORBITOOLS_URL . 'modules/Typography_Presets/js/attribute-registration.js',
            array('wp-hooks'),
            self::VERSION,
            true
        );

        // Localize data to the first script so all scripts can access it
        wp_localize_script(
            'orbitools-typography-attribute-registration',
            'orbitoolsTypographyPresets',
            $this->get_localized_data()
        );

        // Enqueue core controls removal
        wp_enqueue_script(
            'orbitools-typography-core-removal',
            ORBITOOLS_URL . 'modules/Typography_Presets/js/core-controls-removal.js',
            array('wp-hooks', 'wp-blocks'),
            self::VERSION,
            true
        );

        // Enqueue editor controls
        wp_enqueue_script(
            'orbitools-typography-editor-controls',
            ORBITOOLS_URL . 'modules/Typography_Presets/js/editor-controls.js',
            array(
                'wp-hooks',
                'wp-blocks',
                'wp-element',
                'wp-components',
                'wp-block-editor',
                'orbitools-typography-attribute-registration'
            ),
            self::VERSION,
            true
        );
    }

    /**
     * Enqueue assets for empty state (no presets)
     *
     * @since 1.0.0
     */
    private function enqueue_empty_state_assets(): void
    {
        // Enqueue core controls removal even when no presets
        wp_enqueue_script(
            'orbitools-typography-core-removal',
            ORBITOOLS_URL . 'modules/Typography_Presets/js/core-controls-removal.js',
            array('wp-hooks', 'wp-blocks'),
            self::VERSION,
            true
        );

        // Enqueue empty state controls
        wp_enqueue_script(
            'orbitools-typography-editor-controls',
            ORBITOOLS_URL . 'modules/Typography_Presets/js/editor-controls.js',
            array(
                'wp-hooks',
                'wp-blocks',
                'wp-element',
                'wp-components',
                'wp-block-editor'
            ),
            self::VERSION,
            true
        );

        // Localize empty state data
        wp_localize_script(
            'orbitools-typography-editor-controls',
            'orbitoolsTypographyPresets',
            array(
                'presets'  => array(),
                'groups'   => array(),
                'settings' => $this->settings,
                'strings'  => array(
                    'selectPreset' => __('Select Typography Preset', 'orbitools'),
                    'customPreset' => __('Custom Preset', 'orbitools'),
                    'noPreset'     => __('No Preset', 'orbitools'),
                    'noPresetsFound' => __('No typography presets found. Add presets to your theme.json file to use this feature.', 'orbitools'),
                ),
            )
        );
    }

    /**
     * Get localized data for JavaScript
     *
     * @since 1.0.0
     * @return array Localized data array.
     */
    private function get_localized_data(): array
    {
        return array(
            'presets'  => $this->preset_manager->get_presets(),
            'groups'   => $this->get_preset_groups(),
            'settings' => $this->settings,
            'strings'  => array(
                'selectPreset' => __('Select Typography Preset', 'orbitools'),
                'customPreset' => __('Custom Preset', 'orbitools'),
                'noPreset'     => __('No Preset', 'orbitools'),
                'noPresetsFound' => __('No typography presets found. Add presets to your theme.json file to use this feature.', 'orbitools'),
            ),
        );
    }

    /**
     * Get all available preset groups for JavaScript
     *
     * @since 1.0.0
     * @return array Available groups
     */
    private function get_preset_groups(): array
    {
        $groups = array();
        $presets = $this->preset_manager->get_presets();
        
        foreach ($presets as $preset) {
            if (isset($preset['group'])) {
                $groups[$preset['group']] = $preset['group'];
            }
        }
        
        return $groups;
    }
}