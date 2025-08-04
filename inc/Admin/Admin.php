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
        add_filter('orbitools_adminkit_structure', [$this, 'configure_admin_structure']);
        add_filter('orbitools_adminkit_fields', [$this, 'get_settings_config']);


        // Override the default AJAX save handler to add module change detection
        // COMMENTED OUT FOR DEBUGGING
        // add_action('wp_ajax_orbitools_adminkit_save_settings_orbitools', [$this, 'custom_ajax_save_settings'], 5);
    }

    /**
     * Register custom field types for AdminKit.
     *
     * @return void
     */
    public function register_adminkit_custom_fields(): void
    {
        if (!class_exists('Orbitools\AdminKit\\Field_Registry')) {
            return;
        }

        $field_file = ORBITOOLS_DIR . 'inc/Admin/adminkit/fields/modules/Orbitools_Modules_Field.php';

        if (file_exists($field_file)) {
            \Orbitools\AdminKit\Field_Registry::register_field_type(
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
        AdminKit('orbitools')->init(array(
            'title' => __('Orbitools', 'orbitools'),
            'description' => __('Advanced WordPress tools and utilities.', 'orbitools'),
            'hide_title_description' => true,
            'header_image' => ORBITOOLS_URL . 'assets/images/orbitools-logo.svg',
            'header_bg_color' => '#32A3E2',
            'menu' => array(
                'menu_type' => 'menu',
                'menu_title' => 'OrbiTools',
                'position' => 0,
                'parent' => 'options-general.php',
                'capability' => 'manage_options',
            ),
        ));
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
                'title' => array(
                    'title' => __('Dashboard', 'orbitools'),
                    'icon' => array(
                        'type' => 'svg',
                        'value' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M0 256a256 256 0 1 1 512 0 256 256 0 1 1-512 0zm320 96c0-26.9-16.5-49.9-40-59.3V88c0-13.3-10.7-24-24-24s-24 10.7-24 24v204.7c-23.5 9.5-40 32.5-40 59.3 0 35.3 28.7 64 64 64s64-28.7 64-64zM144 176a32 32 0 1 0 0-64 32 32 0 1 0 0 64zm-16 80a32 32 0 1 0-64 0 32 32 0 1 0 64 0zm288 32a32 32 0 1 0 0-64 32 32 0 1 0 0 64zm-16-144a32 32 0 1 0-64 0 32 32 0 1 0 64 0z"/></svg>'
                    )
                ),
                'display_mode' => 'cards',
                'sections' => array(
                    'modules' => array(
                        'title' => __('Module Management', 'orbitools'),
                        'icon' => array(
                            'type' => 'svg',
                            'value' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M192 104.8c0-9.2-5.8-17.3-13.2-22.8C167.2 73.3 160 61.3 160 48c0-26.5 28.7-48 64-48s64 21.5 64 48c0 13.3-7.2 25.3-18.8 34c-7.4 5.5-13.2 13.6-13.2 22.8c0 12.8 10.4 23.2 23.2 23.2l56.8 0c26.5 0 48 21.5 48 48l0 56.8c0 12.8 10.4 23.2 23.2 23.2c9.2 0 17.3-5.8 22.8-13.2c8.7-11.6 20.7-18.8 34-18.8c26.5 0 48 28.7 48 64s-21.5 64-48 64c-13.3 0-25.3-7.2-34-18.8c-5.5-7.4-13.6-13.2-22.8-13.2c-12.8 0-23.2 10.4-23.2 23.2L384 464c0 26.5-21.5 48-48 48l-56.8 0c-12.8 0-23.2-10.4-23.2-23.2c0-9.2 5.8-17.3 13.2-22.8c11.6-8.7 18.8-20.7 18.8-34c0-26.5-28.7-48-64-48s-64 21.5-64 48c0 13.3 7.2 25.3 18.8 34c7.4 5.5 13.2 13.6 13.2 22.8c0 12.8-10.4 23.2-23.2 23.2L48 512c-26.5 0-48-21.5-48-48L0 343.2C0 330.4 10.4 320 23.2 320c9.2 0 17.3 5.8 22.8 13.2C54.7 344.8 66.7 352 80 352c26.5 0 48-28.7 48-64s-21.5-64-48-64c-13.3 0-25.3 7.2-34 18.8C40.5 250.2 32.4 256 23.2 256C10.4 256 0 245.6 0 232.8L0 176c0-26.5 21.5-48 48-48l120.8 0c12.8 0 23.2-10.4 23.2-23.2z"/></svg>'
                        )
                    ),
                ),
            ),
            'modules' => array(
                'title' => array(
                    'title' => __('Modules', 'orbitools'),
                    'icon' => array(
                        'type' => 'svg',
                        'value' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M192 104.8c0-9.2-5.8-17.3-13.2-22.8C167.2 73.3 160 61.3 160 48c0-26.5 28.7-48 64-48s64 21.5 64 48c0 13.3-7.2 25.3-18.8 34c-7.4 5.5-13.2 13.6-13.2 22.8c0 12.8 10.4 23.2 23.2 23.2l56.8 0c26.5 0 48 21.5 48 48l0 56.8c0 12.8 10.4 23.2 23.2 23.2c9.2 0 17.3-5.8 22.8-13.2c8.7-11.6 20.7-18.8 34-18.8c26.5 0 48 28.7 48 64s-21.5 64-48 64c-13.3 0-25.3-7.2-34-18.8c-5.5-7.4-13.6-13.2-22.8-13.2c-12.8 0-23.2 10.4-23.2 23.2L384 464c0 26.5-21.5 48-48 48l-56.8 0c-12.8 0-23.2-10.4-23.2-23.2c0-9.2 5.8-17.3 13.2-22.8c11.6-8.7 18.8-20.7 18.8-34c0-26.5-28.7-48-64-48s-64 21.5-64 48c0 13.3 7.2 25.3 18.8 34c7.4 5.5 13.2 13.6 13.2 22.8c0 12.8-10.4 23.2-23.2 23.2L48 512c-26.5 0-48-21.5-48-48L0 343.2C0 330.4 10.4 320 23.2 320c9.2 0 17.3 5.8 22.8 13.2C54.7 344.8 66.7 352 80 352c26.5 0 48-28.7 48-64s-21.5-64-48-64c-13.3 0-25.3 7.2-34 18.8C40.5 250.2 32.4 256 23.2 256C10.4 256 0 245.6 0 232.8L0 176c0-26.5 21.5-48 48-48l120.8 0c12.8 0 23.2-10.4 23.2-23.2z"/></svg>'
                    )
                ),
                'display_mode' => 'tabs',
                'sections' => array(),
            ),
            'tools' => array(
                'title' => array(
                    'title' => __('Tools/Info', 'orbitools'),
                    'icon' => array(
                        'type' => 'svg',
                        'value' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M78.6 5C69.1-2.4 55.6-1.5 47 7L7 47c-8.5 8.5-9.4 22-2.1 31.6l80 104c4.5 5.9 11.6 9.4 19 9.4l54.1 0 109 109c-14.7 29-10 65.4 14.3 89.6l112 112c12.5 12.5 32.8 12.5 45.3 0l64-64c12.5-12.5 12.5-32.8 0-45.3l-112-112c-24.2-24.2-60.6-29-89.6-14.3l-109-109 0-54.1c0-7.5-3.5-14.5-9.4-19L78.6 5zM19.9 396.1C7.2 408.8 0 426.1 0 444.1C0 481.6 30.4 512 67.9 512c18 0 35.3-7.2 48-19.9L233.7 374.3c-7.8-20.9-9-43.6-3.6-65.1l-61.7-61.7L19.9 396.1zM512 144c0-10.5-1.1-20.7-3.2-30.5c-2.4-11.2-16.1-14.1-24.2-6l-63.9 63.9c-3 3-7.1 4.7-11.3 4.7L352 176c-8.8 0-16-7.2-16-16l0-57.4c0-4.2 1.7-8.3 4.7-11.3l63.9-63.9c8.1-8.1 5.2-21.8-6-24.2C388.7 1.1 378.5 0 368 0C288.5 0 224 64.5 224 144l0 .8 85.3 85.3c36-9.1 75.8 .5 104 28.7L429 274.5c49-23 83-72.8 83-130.5zM56 432a24 24 0 1 1 48 0 24 24 0 1 1 -48 0z"/></svg>'
                    )
                ),
                'display_mode' => 'cards',
                'sections' => array(
                    'plugin' => array(
                        'title' => __('Version Information', 'orbitools'),
                        'icon' => 'dashicons-info'
                    ),
                    'utils' => array(
                        'title' => __('Utilities', 'orbitools'),
                        'icon' => 'dashicons-admin-generic'
                    ),
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
            ORBITOOLS_URL . 'build/admin/css/admin.css',
            array(),
            ORBITOOLS_VERSION
        );

        wp_enqueue_script(
            'orbitools-admin',
            ORBITOOLS_URL . 'build/admin/js/admin.js',
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
        remove_action('wp_ajax_orbitools_adminkit_save_settings_orbitools', [$this, 'custom_ajax_save_settings'], 5);

        // Security checks
        $nonce = $_POST['nonce'] ?? '';
        if (!wp_verify_nonce($nonce, 'orbitools_adminkit_orbitools')) {
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
