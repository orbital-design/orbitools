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

namespace Orbitools\Controls\Typography_Presets\Admin;

use Orbitools\Core\Admin\Module_Admin_Base;
use Orbitools\Controls\Typography_Presets\Admin\Settings;
use Orbitools\Controls\Typography_Presets\Admin\Settings_Helper;

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
class Admin extends Module_Admin_Base
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
        // Call parent constructor with module info
        parent::__construct(self::MODULE_SLUG, Settings::class);

        // Register module metadata
        add_filter('orbitools_available_modules', array($this, 'register_module_metadata'));
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
            'name'        => __('Typography Presets', 'orbitools'),
            'subtitle'    => __('Advanced text styling system', 'orbitools'),
            'description' => __('Replace WordPress core typography controls with a comprehensive preset system for consistent text styling across your site.', 'orbitools'),
            'icon'        => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><path fill="#32a3e2" d="M64 128V96h96v320h-32c-17.7 0-32 14.3-32 32s14.3 32 32 32h128c17.7 0 32-14.3 32-32s-14.3-32-32-32h-32V96h96v32c0 17.7 14.3 32 32 32s32-14.3 32-32V80c0-26.5-21.5-48-48-48H48C21.5 32 0 53.5 0 80v48c0 17.7 14.3 32 32 32s32-14.3 32-32zm320 176v-16h64v128h-16c-17.7 0-32 14.3-32 32s14.3 32 32 32h96c17.7 0 32-14.3 32-32s-14.3-32-32-32h-16V288h64v16c0 17.7 14.3 32 32 32s32-14.3 32-32v-32c0-26.5-21.5-48-48-48H368c-26.5 0-48 21.5-48 48v32c0 17.7 14.3 32 32 32s32-14.3 32-32z"/></svg>',
            'configure_url' => admin_url('admin.php?page=orbitools&tab=modules&section=typography'),
        );

        return $modules;
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
        $admin_framework = AdminKit('orbitools');
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

        // Get configurable path from settings
        $plugin_path = Settings::get_theme_json_path();
        $data = $theme_json;

        foreach ($plugin_path as $key) {
            if (!isset($data[$key])) {
                return false;
            }
            $data = $data[$key];
        }

        return isset($data['items']);
    }
}