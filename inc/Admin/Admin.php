<?php

namespace Orbitools\Admin;

/**
 * Class Admin
 *
 * Handles admin functionality for Orbitools.
 */
class Admin
{
    /**
     * Admin constructor.
     *
     * @return void
     */
    public function __construct()
    {
        // Register custom field types for this plugin
        add_action('orbi_register_fields', [$this, 'register_adminkit_custom_fields']);

        add_action('init', [$this, 'init_adminkit']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);

        // Hook into settings save to detect module changes
        // COMMENTED OUT FOR DEBUGGING
        // add_action('orbitools_post_save_settings', [$this, 'detect_module_changes'], 10, 2);

        // Setup filters (these don't use translations immediately)
        add_filter('orbitools_settings_tabs', [$this, 'configure_settings_tabs']);
        add_filter('orbitools_registered_settings_sections', [$this, 'configure_settings_sections']);
        add_filter('orbitools_settings', [$this, 'get_settings_config']);
        add_filter('orbitools_admin_structure', [$this, 'configure_admin_structure']);

        // Override the default AJAX save handler to add module change detection
        // COMMENTED OUT FOR DEBUGGING
        // add_action('wp_ajax_orbi_admin_save_settings_orbitools', [$this, 'custom_ajax_save_settings'], 5);
    }

    /**
     * Register custom field types for AdminKit.
     *
     * @return void
     */
    public function register_adminkit_custom_fields(): void
    {
        if (!class_exists('Orbi\\AdminKit\\Field_Registry')) {
            return;
        }

        $field_file = ORBITOOLS_DIR . 'inc/Admin/adminkit/fields/modules/Orbitools_Modules_Field.php';

        if (file_exists($field_file)) {
            \Orbi\AdminKit\Field_Registry::register_field_type(
                'modules',
                $field_file,
                'Orbitools_Modules_Field'
            );
        }
    }

    /**
     * Initialize AdminKit with configuration.
     *
     * @return void
     */
    public function init_adminkit(): void
    {
        orbi_admin_kit('orbitools')->init(array(
            'title' => __('Orbitools', 'orbitools'),
            'description' => __('Advanced WordPress tools and utilities.', 'orbitools'),
            'header_image' => ORBITOOLS_URL . 'assets/images/orbitools-logo.svg',
            'header_bg_color' => '#32A3E2',
            'menu' => array(
                'parent' => 'options-general.php',
                'capability' => 'manage_options',
            ),
        ));
    }

    /**
     * Configure settings tabs.
     *
     * @param array $tabs Existing tabs array.
     * @return array Modified tabs array.
     */
    public function configure_settings_tabs(array $tabs): array
    {
        return array(
            'dashboard' => __('Dashboard', 'orbitools'),
            'modules'   => __('Modules', 'orbitools'),
            'settings'  => __('Settings', 'orbitools'),
            'updates'   => __('Updates', 'orbitools'),
        );
    }

    /**
     * Configure settings sections.
     *
     * @param array $sections Existing sections array.
     * @return array Modified sections array.
     */
    public function configure_settings_sections(array $sections): array
    {
        return array(
            'dashboard' => array(
                'modules' => __('Module Management', 'orbitools'),
            ),
            'settings' => array(
                'general'     => __('General Settings', 'orbitools'),
                'performance' => __('Performance', 'orbitools'),
                'cleanup'     => __('Data Cleanup', 'orbitools'),
            ),
            'updates' => array(
                'version' => __('Version Information', 'orbitools'),
                'auto'    => __('Update Management', 'orbitools'),
            ),
        );
    }

    /**
     * Configure admin structure for orbi-admin-kit.
     *
     * @param array $structure Existing structure array.
     * @return array Modified structure array.
     */
    public function configure_admin_structure(array $structure): array
    {
        return array(
            'dashboard' => array(
                'title' => __('Dashboard', 'orbitools'),
                'display_mode' => 'cards',
                'sections' => array(
                    'modules' => __('Module Management', 'orbitools'),
                ),
            ),
            'modules' => array(
                'title' => __('Modules', 'orbitools'),
                'display_mode' => 'tabs',
                'sections' => array(),
            ),
            'tools' => array(
                'title' => __('Tools/Info', 'orbitools'),
                'display_mode' => 'cards',
                'sections' => array(
                    'plugin' => __('Version Information', 'orbitools'),
                    'utils'    => __('Utilities', 'orbitools'),
                ),
            ),
        );
    }

    /**
     * Get settings configuration.
     *
     * @return array
     */
    public function get_settings_config(): array
    {
        return array(
            'dashboard' => array(
                array(
                    'id'      => 'module_management',
                    'name'    => __('Available Modules', 'orbitools'),
                    'desc'    => __('Enable, disable, and configure the various modules.', 'orbitools'),
                    'type'    => 'modules',
                    'section' => 'modules',
                )
            ),
            'tools' => array(
                array(
                    'id'      => 'current_version',
                    'name'    => __('Version Information', 'orbitools'),
                    'type'    => 'html',
                    'std'     => $this->get_version_info_html(),
                    'section' => 'plugin',
                ),
                array(
                    'id'      => 'reset_on_deactivation',
                    'name'    => __('Reset Data on Deactivation', 'orbitools'),
                    'desc'    => __('Remove all plugin data when deactivating.', 'orbitools'),
                    'type'    => 'checkbox',
                    'std'     => false,
                    'section' => 'utils',
                ),
            ),
        );
    }

    /**
     * Get version information HTML.
     *
     * @since 1.0.0
     * @return string HTML for version information display.
     */
    private function get_version_info_html(): string
    {
        $html = '<div id="orbitools-version-info">';
        $html .= '<p><strong>' . __('Current Version:', 'orbitools') . '</strong> ' . ORBITOOLS_VERSION . '</p>';

        // Get last checked time
        $last_checked = get_transient('orbitools_last_checked');
        if ($last_checked) {
            $html .= '<p><strong>' . __('Last Checked:', 'orbitools') . '</strong> ' . $last_checked . '</p>';
        }

        // Get remote version if available
        $remote_version = get_transient('orbitools_remote_version');
        if ($remote_version) {
            $html .= '<p><strong>' . __('Latest Available:', 'orbitools') . '</strong> ' . $remote_version . '</p>';

            $has_update = version_compare(ORBITOOLS_VERSION, $remote_version, '<');
            if ($has_update) {
                $html .= '<p style="color: #d63638;"><strong>' . __('Status:', 'orbitools') . '</strong> ' . __('Update Available', 'orbitools') . '</p>';
            } else {
                $html .= '<p style="color: #00a32a;"><strong>' . __('Status:', 'orbitools') . '</strong> ' . __('Up to Date', 'orbitools') . '</p>';
            }
        }

        $html .= '<p><strong>' . __('Repository:', 'orbitools') . '</strong> <a href="https://github.com/orbital-design/orbitools" target="_blank">GitHub</a></p>';
        $html .= '</div>';

        return $html;
    }


    /**
     * Get module status HTML for display.
     *
     * NOTE: This method appears to be unused and may be legacy code.
     * Consider removing if not needed.
     *
     * @return string HTML markup for module status.
     */
    private function get_module_status_html(): string
    {
        $settings = get_option('orbitools_settings', array());
        $html = '<div class="orbitools-module-status">';

        // Typography Presets status
        $typography_enabled = !empty($settings['typography_presets_enabled']);
        $typography_loaded = class_exists('\\Orbitools\\Modules\\Typography_Presets\\Typography_Presets');

        if ($typography_enabled && $typography_loaded) {
            $status = '<span style="color: green;">✓ ' . __('Active', 'orbitools') . '</span>';
        } elseif ($typography_enabled && !$typography_loaded) {
            $status = '<span style="color: orange;">⚠ ' . __('Enabled but not loaded', 'orbitools') . '</span>';
        } else {
            $status = '<span style="color: red;">✗ ' . __('Disabled', 'orbitools') . '</span>';
        }

        $html .= '<p>' . __('Typography Presets', 'orbitools') . ': ' . $status . '</p>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Enqueue admin scripts and styles.
     *
     * @param string $hook Current admin page hook.
     * @return void
     */
    public function enqueue_scripts(string $hook): void
    {
        // Only load on our admin pages
        if (strpos($hook, 'orbitools') === false) {
            return;
        }

        wp_enqueue_style(
            'orbitools-admin',
            ORBITOOLS_URL . 'assets/css/admin.css',
            array(),
            ORBITOOLS_VERSION
        );

        wp_enqueue_script(
            'orbitools-admin',
            ORBITOOLS_URL . 'assets/js/admin.js',
            array(),
            ORBITOOLS_VERSION,
            true
        );
    }

    /**
     * Detect module state changes during settings save.
     *
     * Compares previous and new module states and stores change information
     * in a transient for the AJAX response to trigger page reload.
     *
     * @param array $new_settings The new settings being saved.
     * @param bool $save_result The result of the save operation.
     * @return void
     */
    public function detect_module_changes(array $new_settings, bool $save_result): void
    {
        if (!$save_result) {
            return;
        }

        $previous_settings = get_option('orbitools_settings', array());
        $module_changes = $this->compare_module_states($previous_settings, $new_settings);

        if (!empty($module_changes)) {
            // Store changes in transient for AJAX response (60 second expiry)
            set_transient('orbitools_modules_changed_' . get_current_user_id(), $module_changes, 60);
        }
    }

    /**
     * Compare module states between old and new settings.
     *
     * Identifies modules that have been enabled or disabled by comparing
     * settings that end with '_enabled'.
     *
     * @param array $old_settings Previous settings.
     * @param array $new_settings New settings.
     * @return array Array of changed modules with from/to states.
     */
    private function compare_module_states(array $old_settings, array $new_settings): array
    {
        $changes = array();
        $all_keys = array_unique(array_merge(array_keys($old_settings), array_keys($new_settings)));

        foreach ($all_keys as $key) {
            if (substr($key, -8) !== '_enabled') {
                continue;
            }

            $old_value = $old_settings[$key] ?? '';
            $new_value = $new_settings[$key] ?? '';

            // Normalize boolean values for comparison
            $old_enabled = !empty($old_value) && $old_value !== '0';
            $new_enabled = !empty($new_value) && $new_value !== '0';

            if ($old_enabled !== $new_enabled) {
                $module_id = str_replace('_enabled', '', $key);
                $changes[$module_id] = array(
                    'from' => $old_enabled,
                    'to' => $new_enabled,
                    'action' => $new_enabled ? 'enabled' : 'disabled'
                );
            }
        }

        return $changes;
    }

    /**
     * Custom AJAX save settings handler with module change detection.
     *
     * Overrides the default AdminKit AJAX handler to add module change detection
     * and communicate changes back to the frontend for auto-reload functionality.
     *
     * @return void
     */
    public function custom_ajax_save_settings(): void
    {
        // Prevent double execution
        remove_action('wp_ajax_orbi_admin_save_settings_orbitools', [$this, 'custom_ajax_save_settings'], 5);

        // Security checks
        $nonce = $_POST['nonce'] ?? '';
        if (!wp_verify_nonce($nonce, 'orbi_admin_orbitools')) {
            wp_send_json_error('Invalid nonce');
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        // Process settings data
        $settings_json = $_POST['settings'] ?? '{}';
        $settings_data = json_decode(stripslashes($settings_json), true);

        if (!is_array($settings_data)) {
            $settings_data = array();
        }

        // Save settings and trigger hooks
        $result = $this->save_orbitools_settings($settings_data);

        if ($result) {
            // Check for module changes via transient
            $module_changes = get_transient('orbitools_modules_changed_' . get_current_user_id());

            if ($module_changes) {
                delete_transient('orbitools_modules_changed_' . get_current_user_id());
            }

            wp_send_json_success(array(
                'message' => 'Settings saved successfully',
                'modules_changed' => !empty($module_changes),
                'module_changes' => $module_changes ?: array()
            ));
        } else {
            wp_send_json_error('Failed to save settings');
        }
    }

    /**
     * Save orbitools settings using the same logic as AdminKit.
     *
     * @param array $settings_data Settings data to save.
     * @return bool Success status.
     */
    private function save_orbitools_settings(array $settings_data): bool
    {
        // Apply pre-save filters (similar to AdminKit)
        $sanitized_data = apply_filters('orbitools_pre_save_settings', $settings_data);

        // Save settings
        $result = update_option('orbitools_settings', $sanitized_data);

        // Trigger post-save action (this will run our module detection)
        do_action('orbitools_post_save_settings', $sanitized_data, $result);

        return $result;
    }
}