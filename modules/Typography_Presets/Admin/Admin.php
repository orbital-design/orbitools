<?php

/**
 * Typography Presets Admin Handler
 *
 * Handles all admin-related functionality for the Typography Presets module,
 * including module registration, settings integration, and admin notices.
 *
 * @package    Orbitools
 * @subpackage Modules/Typography_Presets/Admin
 * @since      1.0.0
 */

namespace Orbitools\Modules\Typography_Presets\Admin;

use Orbitools\Modules\Typography_Presets\Admin\Settings;
use Orbitools\Modules\Typography_Presets\Admin\Settings_Helper;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Typography Presets Admin Class
 *
 * Manages admin interface integration for the Typography Presets module.
 *
 * @since 1.0.0
 */
class Admin
{
    /**
     * Module version
     *
     * @since 1.0.0
     * @var string
     */
    const VERSION = '1.0.0';

    /**
     * Module slug identifier
     *
     * @since 1.0.0
     * @var string
     */
    const MODULE_SLUG = 'typography-presets';

    /**
     * Initialize admin functionality
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        // Register module metadata
        add_filter('orbitools_available_modules', array($this, 'register_module_metadata'));

        // Register with admin framework
        add_filter('orbitools_admin_structure', array($this, 'register_new_framework_structure'));
        add_filter('orbitools_settings', array($this, 'register_new_framework_settings'));

        // Add admin notices for missing presets
        add_action('admin_notices', array($this, 'check_and_show_missing_presets_notice'));
    }

    /**
     * Check if the Typography Presets module is enabled
     *
     * @since 1.0.0
     * @return bool True if module is enabled, false otherwise.
     */
    public function is_module_enabled(): bool
    {
        return Settings_Helper::is_module_enabled();
    }

    /**
     * Register module metadata for the admin interface
     *
     * @since 1.0.0
     * @param array $modules Existing modules array.
     * @return array Modified modules array with Typography Presets metadata.
     */
    public function register_module_metadata(array $modules): array
    {
        $modules['typography_presets'] = array(
            'name'        => 'Typography Presets',
            'subtitle'    => 'Advanced text styling system',
            'description' => 'Replace WordPress core typography controls with a comprehensive preset system for consistent text styling across your site.',
            'version'     => self::VERSION,
            'category'    => 'Editor Enhancement',
            'icon'        => 'dashicons-editor-textcolor',
            'author'      => 'Orbitools',
            'docs_url'    => '',
            'configure_url' => admin_url('admin.php?page=orbitools&tab=modules&section=typography'),
            'requires'    => array(
                'wp_version' => '5.0',
                'php_version' => '7.4',
            ),
            'features'    => array(
                'Theme.json integration',
                'Visual preset management',
                'Block editor controls',
                'CSS auto-generation',
            ),
        );

        return $modules;
    }

    /**
     * Register admin structure for the new framework
     *
     * @since 1.0.0
     * @param array $structure Existing structure array.
     * @return array Modified structure array.
     */
    public function register_new_framework_structure(array $structure): array
    {
        if (!isset($structure['modules']['sections'])) {
            $structure['modules']['sections'] = array();
        }

        // Get structure from Settings class
        $settings_structure = Settings::get_admin_structure();
        $structure['modules']['sections'] = array_merge(
            $structure['modules']['sections'],
            $settings_structure['sections']
        );

        return $structure;
    }

    /**
     * Register settings for the new framework
     *
     * @since 1.0.0
     * @param array $settings Existing settings array.
     * @return array Modified settings array.
     */
    public function register_new_framework_settings(array $settings): array
    {
        if (!isset($settings['modules'])) {
            $settings['modules'] = array();
        }

        // Get settings from Settings class
        $module_settings = Settings::get_field_definitions();
        $settings['modules'] = array_merge($settings['modules'], $module_settings);

        return $settings;
    }

    /**
     * Check for missing typography presets and show admin notice
     *
     * Displays a warning notice in the admin if the module is enabled
     * but no typography presets are found in the theme.json file.
     *
     * @since 1.0.0
     * @return void
     */
    public function check_and_show_missing_presets_notice(): void
    {
        // Only show on our admin pages
        if (!isset($_GET['page']) || $_GET['page'] !== 'orbitools') {
            return;
        }

        // Check if we're on the modules tab and typography section
        if (!isset($_GET['tab']) || $_GET['tab'] !== 'modules') {
            return;
        }

        // Only show if module is enabled
        if (!$this->is_module_enabled()) {
            return;
        }

        // Get the admin framework instance
        $admin_framework = orbi_admin_kit('orbitools');
        if (!$admin_framework) {
            return;
        }

        // Check for theme.json presets
        if (!$this->has_theme_presets()) {
            $admin_framework->add_notice(
                '<strong>Typography Presets:</strong> No presets found in theme.json. Add typography presets to your theme\'s <code>theme.json</code> file to use this feature.',
                'warning'
            );
        }
    }

    /**
     * Check if theme has typography presets defined
     *
     * @since 1.0.0
     * @return bool True if presets exist, false otherwise.
     */
    private function has_theme_presets(): bool
    {
        $theme_json_path = get_template_directory() . '/theme.json';
        
        if (!file_exists($theme_json_path)) {
            return false;
        }

        $theme_json_content = file_get_contents($theme_json_path);
        $theme_json = json_decode($theme_json_content, true);
        
        if (!$theme_json || JSON_ERROR_NONE !== json_last_error()) {
            return false;
        }

        return isset($theme_json['settings']['custom']['orbital']['plugins']['oes']['Typography_Presets']['items']);
    }
}