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
        // Use Settings_Manager to get settings from database (no hardcoded defaults)
        $settings_manager = new \Orbitools\Helpers\Settings_Manager();
        $all_settings = $settings_manager->get_all_settings();

        // FIXED: Access settings directly by their field ID, not module-prefixed
        $allowed_blocks = $all_settings['typography_allowed_blocks'] ?? array();

        // If setting is empty (not configured yet), use sensible defaults
        if (empty($allowed_blocks)) {
            $allowed_blocks = \Orbitools\Modules\Typography_Presets\Typography_Presets::DEFAULT_ALLOWED_BLOCKS;
        }

        // FIXED: Access the setting directly by its field ID (not module-prefixed)
        $show_groups_raw = $all_settings['typography_show_groups_in_dropdown'] ?? false;

        // Normalize the setting value - AdminKit stores checkboxes as '1' or '' (empty string)
        $show_groups_setting = !empty($show_groups_raw) && $show_groups_raw !== '0';

        $this->settings = array(
            'typography_allowed_blocks' => $allowed_blocks,
            'typography_show_groups_in_dropdown' => $show_groups_setting,
        );
    }

    /**
     * Get blocks allowed to use typography presets
     *
     * @since 1.0.0
     * @return array Array of allowed block names
     */
    public function get_allowed_blocks(): array
    {
        return $this->settings['typography_allowed_blocks'];
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
        \wp_enqueue_script(
            'orbitools-typography-attribute-registration',
            ORBITOOLS_URL . 'build/admin/js/modules/typography-presets/editor-presets-attribute-registration.js',
            array('wp-hooks'),
            self::VERSION,
            true
        );

        // Localize data to the first script so all scripts can access it
        \wp_localize_script(
            'orbitools-typography-attribute-registration',
            'orbitoolsTypographyPresets',
            $this->get_localized_data()
        );

        // Enqueue core controls removal
        \wp_enqueue_script(
            'orbitools-typography-core-removal',
            ORBITOOLS_URL . 'build/admin/js/modules/typography-presets/editor-disable-core-typography-controls.js',
            array('wp-hooks', 'wp-blocks', 'orbitools-typography-attribute-registration'),
            self::VERSION,
            true
        );


        // Enqueue editor controls
        \wp_enqueue_script(
            'orbitools-typography-editor-controls',
            ORBITOOLS_URL . 'build/admin/js/modules/typography-presets/editor-presets-register-controls.js',
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

        // Enqueue class application
        \wp_enqueue_script(
            'orbitools-typography-class-application',
            ORBITOOLS_URL . 'build/admin/js/modules/typography-presets/editor-presets-classname-application.js',
            array(
                'wp-hooks',
                'wp-element',
                'wp-compose',
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
        // Enqueue attribute registration first (needed for both empty and full states)
        \wp_enqueue_script(
            'orbitools-typography-attribute-registration',
            ORBITOOLS_URL . 'build/admin/js/modules/typography-presets/editor-presets-attribute-registration.js',
            array('wp-hooks'),
            self::VERSION,
            true
        );

        // Localize data to the attribute registration script
        \wp_localize_script(
            'orbitools-typography-attribute-registration',
            'orbitoolsTypographyPresets',
            array(
                'presets'  => array(),
                'groups'   => array(),
                'settings' => $this->settings,
                'strings'  => array(
                    'selectPreset' => \__('Select Typography Preset', 'orbitools'),
                    'customPreset' => \__('Custom Preset', 'orbitools'),
                    'noPreset'     => \__('No Preset', 'orbitools'),
                    'noPresetsFound' => \__('No typography presets found. Add presets to your theme.json file to use this feature.', 'orbitools'),
                ),
            )
        );

        // Enqueue core controls removal (needed even in empty state)
        \wp_enqueue_script(
            'orbitools-typography-core-removal',
            ORBITOOLS_URL . 'build/admin/js/modules/typography-presets/editor-disable-core-typography-controls.js',
            array('wp-hooks', 'wp-blocks'),
            self::VERSION,
            true
        );

        // Enqueue empty state controls
        \wp_enqueue_script(
            'orbitools-typography-editor-controls',
            ORBITOOLS_URL . 'build/admin/js/modules/typography-presets/editor-presets-register-controls.js',
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
     * Get localized data for JavaScript
     *
     * @since 1.0.0
     * @return array Localized data array.
     */
    private function get_localized_data(): array
    {
        // Normalize settings for JavaScript
        $normalized_settings = $this->normalize_settings_for_js($this->settings);

        $localized_data = array(
            'presets'  => $this->preset_manager->get_presets(),
            'groups'   => $this->get_preset_groups(),
            'settings' => $normalized_settings,
            'strings'  => array(
                'selectPreset' => \__('Select Typography Preset', 'orbitools'),
                'customPreset' => \__('Custom Preset', 'orbitools'),
                'noPreset'     => \__('No Preset', 'orbitools'),
                'noPresetsFound' => \__('No typography presets found. Add presets to your theme.json file to use this feature.', 'orbitools'),
            ),
        );


        return $localized_data;
    }

    /**
     * Normalize settings for JavaScript consumption
     *
     * @since 1.0.0
     * @param array $settings Raw settings array.
     * @return array Normalized settings.
     */
    private function normalize_settings_for_js(array $settings): array
    {
        $normalized = array();

        foreach ($settings as $key => $value) {
            // Handle arrays differently based on the setting type
            if ($key === 'typography_allowed_blocks' && is_array($value)) {
                // Keep typography_allowed_blocks as an array
                $normalized[$key] = $value;
            } elseif (is_array($value)) {
                // For checkbox arrays, convert to boolean
                $normalized[$key] = !empty($value[0]) && $value[0] !== '0';
            } elseif (is_string($value)) {
                // For checkbox strings, convert to boolean
                if (in_array($key, ['typography_show_groups_in_dropdown', 'typography_output_preset_css'])) {
                    $normalized[$key] = !empty($value) && $value !== '0';
                } else {
                    $normalized[$key] = $value;
                }
            } else {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
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
