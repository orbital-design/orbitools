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

        add_action('init', [$this, 'init_admin_page']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        
        // Hook into settings save to detect module changes
        add_action('orbitools_post_save_settings', [$this, 'detect_module_changes'], 10, 2);

        // Setup filters (these don't use translations immediately)
        add_filter('orbitools_settings_tabs', [$this, 'configure_settings_tabs']);
        add_filter('orbitools_registered_settings_sections', [$this, 'configure_settings_sections']);
        add_filter('orbitools_settings', [$this, 'get_settings_config']);
        add_filter('orbitools_admin_structure', [$this, 'configure_admin_structure']);

        // Hook into settings save to detect module changes
        add_action('orbitools_post_save_settings', [$this, 'detect_module_changes'], 10, 2);
        
        // Override the default AJAX save handler to add module change detection
        add_action('wp_ajax_orbi_admin_save_settings_orbitools', [$this, 'custom_ajax_save_settings'], 5);
    }

    public function register_adminkit_custom_fields()
    {
        if (class_exists('Orbi\\AdminKit\\Field_Registry')) {
            $field_file = ORBITOOLS_DIR . 'inc/Admin/adminkit/fields/modules/Orbitools_Modules_Field.php';

            if (file_exists($field_file)) {
                \Orbi\AdminKit\Field_Registry::register_field_type(
                    'modules',
                    $field_file,
                    'Orbitools_Modules_Field'
                );
            }
        }
    }

    /**
     * Initialize admin page after translations are loaded.
     *
     * @return void
     */
    public function init_admin_page(): void
    {
        $orbitools_page = orbi_admin_kit('orbitools');
        $orbitools_page->set_page_title(__('Orbitools', 'orbitools'));
        $orbitools_page->set_page_description(__('Advanced WordPress tools and utilities.', 'orbitools'));

        // Configure menu
        $orbitools_page->set_menu_config(array(
            'parent'     => 'options-general.php',
            'page_title' => __('Orbitools', 'orbitools'),
            'menu_title' => __('Orbitools', 'orbitools'),
            'capability' => 'manage_options',
        ));
    }

    /**
     * Configure settings tabs.
     *
     * @param array $tabs
     * @return array
     */
    public function configure_settings_tabs($tabs)
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
     * @param array $sections
     * @return array
     */
    public function configure_settings_sections($sections)
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
                'auto'    => __('Automatic Updates', 'orbitools'),
            ),
        );
    }

    /**
     * Configure admin structure for orbi-admin-kit.
     *
     * @param array $structure
     * @return array
     */
    public function configure_admin_structure($structure)
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
            'settings' => array(
                'title' => __('Settings', 'orbitools'),
                'display_mode' => 'tabs',
                'sections' => array(
                    'general'     => __('General Settings', 'orbitools'),
                    'performance' => __('Performance', 'orbitools'),
                    'cleanup'     => __('Data Cleanup', 'orbitools'),
                ),
            ),
            'updates' => array(
                'title' => __('Updates', 'orbitools'),
                'display_mode' => 'cards',
                'sections' => array(
                    'version' => __('Version Information', 'orbitools'),
                    'auto'    => __('Automatic Updates', 'orbitools'),
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
            'settings' => array(
                array(
                    'id'      => 'debug_mode',
                    'name'    => __('Debug Mode', 'orbitools'),
                    'desc'    => __('Enable debug logging for troubleshooting.', 'orbitools'),
                    'type'    => 'checkbox',
                    'std'     => false,
                    'section' => 'general',
                ),
                array(
                    'id'      => 'cache_css',
                    'name'    => __('Cache Generated CSS', 'orbitools'),
                    'desc'    => __('Cache CSS output for better performance.', 'orbitools'),
                    'type'    => 'checkbox',
                    'std'     => true,
                    'section' => 'performance',
                ),
                array(
                    'id'      => 'reset_on_deactivation',
                    'name'    => __('Reset Data on Deactivation', 'orbitools'),
                    'desc'    => __('Remove all plugin data when deactivating.', 'orbitools'),
                    'type'    => 'checkbox',
                    'std'     => false,
                    'section' => 'cleanup',
                ),
            ),
            'updates' => array(
                array(
                    'id'      => 'current_version',
                    'name'    => __('Current Version', 'orbitools'),
                    'type'    => 'html',
                    'std'     => '<p>' . sprintf(__('Version: %s', 'orbitools'), ORBITOOLS_VERSION) . '</p>',
                    'section' => 'version',
                ),
                array(
                    'id'      => 'auto_updates',
                    'name'    => __('Automatic Updates', 'orbitools'),
                    'desc'    => __('Enable automatic updates for this plugin.', 'orbitools'),
                    'type'    => 'checkbox',
                    'std'     => false,
                    'section' => 'auto',
                ),
            ),
        );
    }

    /**
     * Get module status HTML.
     *
     * @return string
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
     * @param array $new_settings The new settings being saved.
     * @param bool $save_result The result of the save operation.
     * @return void
     */
    public function detect_module_changes(array $new_settings, bool $save_result): void
    {
        if (!$save_result) {
            return;
        }

        // Get the previous settings
        $previous_settings = get_option('orbitools_settings', array());

        // Find all module enable/disable settings
        $module_changes = $this->compare_module_states($previous_settings, $new_settings);

        if (!empty($module_changes)) {
            // Store a flag that modules have changed for the AJAX response
            set_transient('orbitools_modules_changed_' . get_current_user_id(), $module_changes, 60);
        }
    }

    /**
     * Compare module states between old and new settings.
     *
     * @param array $old_settings Previous settings.
     * @param array $new_settings New settings.
     * @return array Array of changed modules.
     */
    private function compare_module_states(array $old_settings, array $new_settings): array
    {
        $changes = array();

        // Get all settings that end with '_enabled' (module settings)
        $all_keys = array_unique(array_merge(array_keys($old_settings), array_keys($new_settings)));

        foreach ($all_keys as $key) {
            if (substr($key, -8) === '_enabled') {
                $old_value = isset($old_settings[$key]) ? $old_settings[$key] : '';
                $new_value = isset($new_settings[$key]) ? $new_settings[$key] : '';

                // Normalize values for comparison (1, '1', true should all be considered enabled)
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
        }

        return $changes;
    }

    /**
     * Custom AJAX save settings handler that includes module change detection.
     *
     * @return void
     */
    public function custom_ajax_save_settings(): void
    {
        // Remove the default handler to prevent double execution
        remove_action('wp_ajax_orbi_admin_save_settings_orbitools', [$this, 'custom_ajax_save_settings'], 5);
        
        // Verify nonce
        $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : '';
        $nonce_action = 'orbi_admin_orbitools';
        
        if (!wp_verify_nonce($nonce, $nonce_action)) {
            wp_send_json_error('Invalid nonce');
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        // Process and save settings
        $settings_json = isset($_POST['settings']) ? $_POST['settings'] : '{}';
        $settings_json = stripslashes($settings_json);
        $settings_data = json_decode($settings_json, true);
        
        if (!is_array($settings_data)) {
            $settings_data = array();
        }

        // Get previous settings before saving for comparison
        $previous_settings = get_option('orbitools_settings', array());
        
        // Save the settings (this will trigger our post-save hook)
        $result = $this->save_orbitools_settings($settings_data);

        if ($result) {
            // Check if modules changed by looking for the transient we set
            $module_changes = get_transient('orbitools_modules_changed_' . get_current_user_id());
            
            // Clean up the transient
            if ($module_changes) {
                delete_transient('orbitools_modules_changed_' . get_current_user_id());
            }

            // Prepare success response with module change information
            $response_data = array(
                'message' => 'Settings saved successfully',
                'modules_changed' => !empty($module_changes),
                'module_changes' => $module_changes ?: array()
            );

            wp_send_json_success($response_data);
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