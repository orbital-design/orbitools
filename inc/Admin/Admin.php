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
        add_action('init', [$this, 'init_admin_page']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);

        // Setup filters (these don't use translations immediately)
        add_filter('orbitools_settings_tabs', [$this, 'configure_settings_tabs']);
        add_filter('orbitools_registered_settings_sections', [$this, 'configure_settings_sections']);
        add_filter('orbitools_settings', [$this, 'get_settings_config']);
        add_filter('orbitools_admin_structure', [$this, 'configure_admin_structure']);
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
     * Load and initialize modules based on settings.
     *
     * @return void
     */
    private function load_modules(): void
    {
        $settings = get_option('orbitools_settings', array());

        // Load Typography Presets module if enabled
        if (!empty($settings['typography_presets_enabled'])) {
            $this->load_typography_presets_module();
        }
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
                'sections' => array(
                    'available' => __('Available Modules', 'orbitools'),
                ),
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
                    'id'      => 'module_status',
                    'name'    => __('Module Status', 'orbitools'),
                    'desc'    => __('Current status of available modules.', 'orbitools'),
                    'type'    => 'html',
                    'std'     => $this->get_module_status_html(),
                    'section' => 'modules',
                ),
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
                    'id'      => 'typography_presets_enabled',
                    'name'    => __('Typography Presets', 'orbitools'),
                    'desc'    => __('Enable typography presets module.', 'orbitools'),
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
     * Load Typography Presets module.
     *
     * @return void
     */
    private function load_typography_presets_module(): void
    {
        $module_path = ORBITOOLS_DIR . 'modules/typography-presets/class-typography-presets.php';

        if (file_exists($module_path)) {
            require_once $module_path;

            if (class_exists('\\Orbitools\\Modules\\TypographyPresets\\TypographyPresets')) {
                new \Orbitools\Modules\TypographyPresets\TypographyPresets();
            }
        }
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
        $typography_loaded = class_exists('\\Orbitools\\Modules\\TypographyPresets\\TypographyPresets');

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
            array('jquery'),
            ORBITOOLS_VERSION,
            true
        );
    }
}