<?php
/**
 * Main Vue.js Admin Interface
 *
 * Modern Vue.js-powered main admin interface for Orbital Editor Suite
 * that provides a comprehensive dashboard and settings management.
 *
 * @package    Orbital_Editor_Suite
 * @subpackage Orbital_Editor_Suite/includes/admin
 */

namespace Orbital\Editor_Suite\Admin;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main Vue.js Admin Interface Class
 *
 * Provides the main dashboard and settings interface using Vue.js
 * with a modern, reactive user experience.
 */
class Main_Vue_Admin {

    /**
     * Plugin name.
     */
    private $plugin_name;

    /**
     * Plugin version.
     */
    private $version;

    /**
     * Initialize admin properties.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        $this->register_ajax_handlers();
    }

    /**
     * Enqueue scripts and styles.
     */
    public function enqueue_scripts($hook) {
        if (strpos($hook, 'orbital-editor-suite') === false) {
            return;
        }

        // Enqueue Vue.js
        wp_enqueue_script('vue-js', 'https://unpkg.com/vue@3/dist/vue.global.js', array(), '3.0.0', true);
        
        // Enqueue our Vue app
        wp_enqueue_script(
            'orbital-main-vue-app',
            ORBITAL_EDITOR_SUITE_URL . 'assets/js/main-vue-app.js',
            array('vue-js'),
            $this->version,
            true
        );

        // Localize script with WordPress data
        wp_localize_script('orbital-main-vue-app', 'orbitalMainVue', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('orbital_main_vue_nonce'),
            'options' => get_option('orbital_editor_suite_options', array()),
            'plugin_info' => array(
                'name' => 'Orbital Editor Suite',
                'version' => ORBITAL_EDITOR_SUITE_VERSION,
                'path' => ORBITAL_EDITOR_SUITE_PATH,
                'url' => ORBITAL_EDITOR_SUITE_URL
            ),
            'available_modules' => $this->get_available_modules(),
            'strings' => array(
                'loading' => __('Loading...', 'orbital-editor-suite'),
                'saving' => __('Saving...', 'orbital-editor-suite'),
                'saved' => __('Settings saved successfully!', 'orbital-editor-suite'),
                'error' => __('Error saving settings', 'orbital-editor-suite'),
                'confirmReset' => __('Are you sure you want to reset all settings? This cannot be undone.', 'orbital-editor-suite'),
                'settingsReset' => __('Settings have been reset to defaults', 'orbital-editor-suite')
            )
        ));

        // Enqueue styles
        wp_enqueue_style(
            'orbital-main-vue-styles',
            ORBITAL_EDITOR_SUITE_URL . 'assets/css/main-vue-styles.css',
            array(),
            $this->version
        );
    }

    /**
     * Register AJAX handlers.
     */
    private function register_ajax_handlers() {
        add_action('wp_ajax_orbital_main_vue_save_settings', array($this, 'handle_save_settings'));
        add_action('wp_ajax_orbital_main_vue_reset_settings', array($this, 'handle_reset_settings'));
        add_action('wp_ajax_orbital_main_vue_get_module_info', array($this, 'handle_get_module_info'));
        add_action('wp_ajax_orbital_main_vue_toggle_module', array($this, 'handle_toggle_module'));
    }

    /**
     * Get available modules information.
     */
    private function get_available_modules() {
        return array(
            'typography-presets' => array(
                'name' => __('Typography Presets', 'orbital-editor-suite'),
                'description' => __('Replace core typography controls with preset utility classes', 'orbital-editor-suite'),
                'icon' => 'dashicons-editor-textcolor',
                'version' => '1.0.0',
                'admin_url' => admin_url('admin.php?page=orbital-typography-vue-new'),
                'status' => 'stable',
                'category' => 'typography'
            )
        );
    }

    /**
     * Render the main admin page.
     */
    public function render_admin_page() {
        ?>
        <div class="wrap">
            <div id="orbital-main-vue-app">
                <!-- Loading state -->
                <div v-if="loading" class="orbital-loading">
                    <div class="spinner is-active"></div>
                    <p>{{ strings.loading }}</p>
                </div>

                <!-- Main app content -->
                <div v-else class="orbital-admin-container">
                    <!-- Header -->
                    <div class="orbital-header">
                        <div class="header-content">
                            <div class="header-title">
                                <h1>
                                    <span class="dashicons dashicons-admin-customizer"></span>
                                    Orbital Editor Suite
                                </h1>
                                <span class="version-badge">v{{ pluginInfo.version }}</span>
                            </div>
                            <div class="header-actions">
                                <button @click="resetSettings" class="button button-secondary">
                                    Reset All Settings
                                </button>
                                <button @click="saveSettings" :disabled="saving" class="button button-primary">
                                    {{ saving ? strings.saving : 'Save Settings' }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Navigation Tabs -->
                    <div class="orbital-tabs">
                        <button 
                            v-for="tab in tabs" 
                            :key="tab.id"
                            @click="activeTab = tab.id"
                            :class="['orbital-tab', { active: activeTab === tab.id }]"
                        >
                            <span class="dashicons" :class="tab.icon"></span>
                            {{ tab.title }}
                        </button>
                    </div>

                    <!-- Tab Content -->
                    <div class="orbital-tab-content">
                        <!-- Dashboard Tab -->
                        <div v-if="activeTab === 'dashboard'" class="orbital-section">
                            <h2>Dashboard</h2>
                            <p>Welcome to Orbital Editor Suite - your comprehensive WordPress editor enhancement toolkit.</p>
                            
                            <div class="dashboard-grid">
                                <!-- Quick Stats -->
                                <div class="stats-card">
                                    <h3>
                                        <span class="dashicons dashicons-chart-area"></span>
                                        Quick Stats
                                    </h3>
                                    <div class="stats-content">
                                        <div class="stat-item">
                                            <span class="stat-number">{{ enabledModulesCount }}</span>
                                            <span class="stat-label">Active Modules</span>
                                        </div>
                                        <div class="stat-item">
                                            <span class="stat-number">{{ Object.keys(availableModules).length }}</span>
                                            <span class="stat-label">Total Modules</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Recent Activity -->
                                <div class="activity-card">
                                    <h3>
                                        <span class="dashicons dashicons-clock"></span>
                                        System Status
                                    </h3>
                                    <div class="activity-content">
                                        <div class="status-item">
                                            <span class="status-indicator good"></span>
                                            <span>Plugin Status: Active</span>
                                        </div>
                                        <div class="status-item">
                                            <span class="status-indicator good"></span>
                                            <span>WordPress Version: Compatible</span>
                                        </div>
                                        <div class="status-item">
                                            <span class="status-indicator good"></span>
                                            <span>PHP Version: Compatible</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Active Modules Overview -->
                            <div class="modules-overview">
                                <h3>Active Modules</h3>
                                <div class="modules-grid">
                                    <div v-for="(module, moduleId) in availableModules" :key="moduleId" 
                                         v-if="isModuleEnabled(moduleId)" class="module-card active">
                                        <div class="module-header">
                                            <span class="dashicons" :class="module.icon"></span>
                                            <h4>{{ module.name }}</h4>
                                            <span class="module-status enabled">Enabled</span>
                                        </div>
                                        <p>{{ module.description }}</p>
                                        <div class="module-actions">
                                            <a :href="module.admin_url" class="button button-secondary">
                                                Configure
                                            </a>
                                            <button @click="toggleModule(moduleId)" class="button button-small">
                                                Disable
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Modules Tab -->
                        <div v-if="activeTab === 'modules'" class="orbital-section">
                            <h2>Module Management</h2>
                            <p>Enable, disable, and configure the various editor enhancement modules.</p>
                            
                            <div class="modules-grid">
                                <div v-for="(module, moduleId) in availableModules" :key="moduleId" class="module-card">
                                    <div class="module-header">
                                        <span class="dashicons" :class="module.icon"></span>
                                        <h4>{{ module.name }}</h4>
                                        <span :class="['module-status', isModuleEnabled(moduleId) ? 'enabled' : 'disabled']">
                                            {{ isModuleEnabled(moduleId) ? 'Enabled' : 'Disabled' }}
                                        </span>
                                    </div>
                                    <p>{{ module.description }}</p>
                                    <div class="module-meta">
                                        <span class="module-version">v{{ module.version }}</span>
                                        <span class="module-category">{{ module.category }}</span>
                                    </div>
                                    <div class="module-actions">
                                        <a v-if="isModuleEnabled(moduleId)" :href="module.admin_url" class="button button-secondary">
                                            Configure
                                        </a>
                                        <button @click="toggleModule(moduleId)" 
                                                :class="['button', isModuleEnabled(moduleId) ? 'button-small' : 'button-primary']">
                                            {{ isModuleEnabled(moduleId) ? 'Disable' : 'Enable' }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Settings Tab -->
                        <div v-if="activeTab === 'settings'" class="orbital-section">
                            <h2>Global Settings</h2>
                            <p>Configure global settings that affect all modules.</p>
                            
                            <div class="settings-grid">
                                <div class="setting-field">
                                    <label class="setting-checkbox">
                                        <input 
                                            type="checkbox" 
                                            v-model="settings.enable_debug"
                                            @change="onSettingsChange"
                                        >
                                        Enable Debug Mode
                                    </label>
                                    <p class="setting-description">Show debug information and additional logging for troubleshooting.</p>
                                </div>
                            </div>
                        </div>

                        <!-- System Info Tab -->
                        <div v-if="activeTab === 'system'" class="orbital-section">
                            <h2>System Information</h2>
                            <p>View system information and plugin diagnostics.</p>
                            
                            <div class="system-info-grid">
                                <div class="info-section">
                                    <h3>Plugin Information</h3>
                                    <table class="system-table">
                                        <tr>
                                            <td>Plugin Name</td>
                                            <td>{{ pluginInfo.name }}</td>
                                        </tr>
                                        <tr>
                                            <td>Version</td>
                                            <td>{{ pluginInfo.version }}</td>
                                        </tr>
                                        <tr>
                                            <td>Plugin Path</td>
                                            <td><code>{{ pluginInfo.path }}</code></td>
                                        </tr>
                                        <tr>
                                            <td>Plugin URL</td>
                                            <td><code>{{ pluginInfo.url }}</code></td>
                                        </tr>
                                    </table>
                                </div>

                                <div class="info-section">
                                    <h3>WordPress Information</h3>
                                    <table class="system-table">
                                        <tr>
                                            <td>WordPress Version</td>
                                            <td>{{ wpInfo.version }}</td>
                                        </tr>
                                        <tr>
                                            <td>PHP Version</td>
                                            <td>{{ wpInfo.php_version }}</td>
                                        </tr>
                                        <tr>
                                            <td>Active Theme</td>
                                            <td>{{ wpInfo.theme }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Status Messages -->
                    <div v-if="message" :class="['orbital-message', messageType]">
                        {{ message }}
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Handle save settings AJAX request.
     */
    public function handle_save_settings() {
        check_ajax_referer('orbital_main_vue_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        $settings = isset($_POST['settings']) ? json_decode(stripslashes($_POST['settings']), true) : array();
        
        // Get current options
        $options = get_option('orbital_editor_suite_options', array());
        
        // Update global settings
        $options['settings'] = array(
            'enable_debug' => !empty($settings['enable_debug']),
            'enabled_modules' => isset($settings['enabled_modules']) ? 
                array_map('sanitize_text_field', (array) $settings['enabled_modules']) : array()
        );
        
        // Save options
        update_option('orbital_editor_suite_options', $options);
        
        wp_send_json_success(array(
            'message' => 'Settings saved successfully!',
            'settings' => $options['settings']
        ));
    }

    /**
     * Handle reset settings AJAX request.
     */
    public function handle_reset_settings() {
        check_ajax_referer('orbital_main_vue_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        // Reset to default options
        $default_options = array(
            'settings' => array(
                'enable_debug' => false,
                'enabled_modules' => array()
            ),
            'modules' => array(),
            'version' => ORBITAL_EDITOR_SUITE_VERSION
        );
        
        update_option('orbital_editor_suite_options', $default_options);
        
        wp_send_json_success(array(
            'message' => 'Settings have been reset to defaults',
            'options' => $default_options
        ));
    }

    /**
     * Handle get module info AJAX request.
     */
    public function handle_get_module_info() {
        check_ajax_referer('orbital_main_vue_nonce', 'nonce');
        
        wp_send_json_success(array(
            'modules' => $this->get_available_modules()
        ));
    }

    /**
     * Handle toggle module AJAX request.
     */
    public function handle_toggle_module() {
        check_ajax_referer('orbital_main_vue_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        $module_id = sanitize_key($_POST['module_id']);
        $enabled = !empty($_POST['enabled']);
        
        // Get current options
        $options = get_option('orbital_editor_suite_options', array());
        
        if (!isset($options['settings']['enabled_modules'])) {
            $options['settings']['enabled_modules'] = array();
        }
        
        if ($enabled) {
            if (!in_array($module_id, $options['settings']['enabled_modules'])) {
                $options['settings']['enabled_modules'][] = $module_id;
            }
        } else {
            $options['settings']['enabled_modules'] = array_diff($options['settings']['enabled_modules'], array($module_id));
            $options['settings']['enabled_modules'] = array_values($options['settings']['enabled_modules']);
        }
        
        update_option('orbital_editor_suite_options', $options);
        
        wp_send_json_success(array(
            'message' => $enabled ? 'Module enabled successfully' : 'Module disabled successfully',
            'enabled_modules' => $options['settings']['enabled_modules']
        ));
    }
}