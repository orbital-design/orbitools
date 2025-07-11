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
            'system_info' => $this->get_system_info(),
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
     * Get comprehensive system information.
     */
    private function get_system_info() {
        global $wp_version;
        
        // WordPress Environment
        $wp_environment = array(
            'wp_version' => $wp_version,
            'php_version' => PHP_VERSION,
            'wp_debug' => defined('WP_DEBUG') && WP_DEBUG,
            'wp_debug_log' => defined('WP_DEBUG_LOG') && WP_DEBUG_LOG,
            'wp_debug_display' => defined('WP_DEBUG_DISPLAY') && WP_DEBUG_DISPLAY,
            'wp_memory_limit' => WP_MEMORY_LIMIT,
            'php_memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'current_theme' => wp_get_theme()->get('Name'),
            'current_theme_version' => wp_get_theme()->get('Version'),
            'is_multisite' => is_multisite(),
            'user_can_manage_options' => current_user_can('manage_options')
        );
        
        // Plugin Files Status
        $files_to_check = array(
            'Main Plugin File' => ORBITAL_EDITOR_SUITE_PATH . 'orbital-editor-suite.php',
            'Main Vue Admin' => ORBITAL_EDITOR_SUITE_PATH . 'includes/admin/class-main-vue-admin.php',
            'Updates Vue Admin' => ORBITAL_EDITOR_SUITE_PATH . 'includes/admin/class-updates-vue-admin.php',
            'Typography Vue Admin' => ORBITAL_EDITOR_SUITE_PATH . 'includes/modules/typography-presets/class-typography-presets-vue-admin.php',
            'Typography Vue JS' => ORBITAL_EDITOR_SUITE_PATH . 'assets/js/typography-presets-vue-app.js',
            'Typography Vue CSS' => ORBITAL_EDITOR_SUITE_PATH . 'assets/css/typography-presets-vue-styles.css',
            'Main Vue JS' => ORBITAL_EDITOR_SUITE_PATH . 'assets/js/main-vue-app.js',
            'Main Vue CSS' => ORBITAL_EDITOR_SUITE_PATH . 'assets/css/main-vue-styles.css',
            'Updates Vue JS' => ORBITAL_EDITOR_SUITE_PATH . 'assets/js/updates-vue-app.js',
            'Updates Vue CSS' => ORBITAL_EDITOR_SUITE_PATH . 'assets/css/updates-vue-styles.css',
            'Vue Components' => ORBITAL_EDITOR_SUITE_PATH . 'assets/js/vue-components.js',
            'Vue Components CSS' => ORBITAL_EDITOR_SUITE_PATH . 'assets/css/vue-components-styles.css'
        );
        
        $file_status = array();
        foreach ($files_to_check as $name => $file_path) {
            $file_status[$name] = array(
                'exists' => file_exists($file_path),
                'readable' => file_exists($file_path) && is_readable($file_path),
                'size' => file_exists($file_path) ? size_format(filesize($file_path)) : 'N/A',
                'modified' => file_exists($file_path) ? date('Y-m-d H:i:s', filemtime($file_path)) : 'N/A'
            );
        }
        
        // Module Status
        $options = get_option('orbital_editor_suite_options', array());
        $settings = isset($options['settings']) ? $options['settings'] : array();
        $enabled_modules = isset($settings['enabled_modules']) ? $settings['enabled_modules'] : array();
        
        $module_status = array();
        foreach ($this->get_available_modules() as $module_id => $module_info) {
            $module_status[$module_id] = array(
                'enabled' => in_array($module_id, $enabled_modules),
                'class_exists' => false,
                'vue_class_exists' => false
            );
            
            // Check if classes exist for typography presets
            if ($module_id === 'typography-presets') {
                $module_status[$module_id]['class_exists'] = class_exists('\Orbital\Editor_Suite\Modules\Typography_Presets\Typography_Presets');
                $module_status[$module_id]['vue_class_exists'] = class_exists('\Orbital\Editor_Suite\Modules\Typography_Presets\Typography_Presets_Vue_Admin');
            }
        }
        
        // Active Plugins
        $active_plugins = get_option('active_plugins', array());
        $plugin_info = array();
        foreach ($active_plugins as $plugin) {
            $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);
            $plugin_info[] = array(
                'name' => $plugin_data['Name'],
                'version' => $plugin_data['Version'],
                'file' => $plugin
            );
        }
        
        // Server Information
        $server_info = array(
            'server_software' => isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : 'Unknown',
            'php_sapi' => php_sapi_name(),
            'mysql_version' => $this->get_mysql_version(),
            'curl_version' => function_exists('curl_version') ? curl_version()['version'] : 'Not available',
            'openssl_version' => defined('OPENSSL_VERSION_TEXT') ? OPENSSL_VERSION_TEXT : 'Not available',
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Unknown'
        );
        
        return array(
            'wp_environment' => $wp_environment,
            'file_status' => $file_status,
            'module_status' => $module_status,
            'active_plugins' => $plugin_info,
            'server_info' => $server_info,
            'constants' => array(
                'ABSPATH' => ABSPATH,
                'WP_CONTENT_DIR' => WP_CONTENT_DIR,
                'WP_PLUGIN_DIR' => WP_PLUGIN_DIR,
                'ORBITAL_EDITOR_SUITE_PATH' => ORBITAL_EDITOR_SUITE_PATH,
                'ORBITAL_EDITOR_SUITE_URL' => ORBITAL_EDITOR_SUITE_URL,
                'ORBITAL_EDITOR_SUITE_VERSION' => ORBITAL_EDITOR_SUITE_VERSION
            )
        );
    }
    
    /**
     * Get MySQL version.
     */
    private function get_mysql_version() {
        global $wpdb;
        return $wpdb->get_var('SELECT VERSION()');
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
                                    <template v-for="(module, moduleId) in availableModules" :key="moduleId">
                                        <div v-if="isModuleEnabled(moduleId)" class="module-card active">
                                            <div class="module-header">
                                                <span class="dashicons" :class="module.icon"></span>
                                                <h4>{{ module.name }}</h4>
                                                <div class="module-actions-inline">
                                                    <span class="dashicons dashicons-info module-info-icon module-tooltip">
                                                        <span class="tooltip-text">{{ module.description }}</span>
                                                    </span>
                                                    <a :href="module.admin_url" class="dashicons dashicons-admin-generic module-settings-icon" title="Configure module"></a>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                    
                                    <!-- No active modules message -->
                                    <div v-if="enabledModulesCount === 0" class="no-modules-message">
                                        <div class="empty-state">
                                            <span class="dashicons dashicons-admin-plugins"></span>
                                            <h4>No Active Modules</h4>
                                            <p>Enable modules from the <a href="#" @click="activeTab = 'modules'">Modules tab</a> to get started.</p>
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
                            <p>Comprehensive system diagnostics and plugin status information.</p>
                            
                            <!-- WordPress Environment -->
                            <div class="info-card">
                                <h3>
                                    <span class="dashicons dashicons-wordpress"></span>
                                    WordPress Environment
                                </h3>
                                <table class="system-table">
                                    <tr>
                                        <td>WordPress Version</td>
                                        <td>{{ systemInfo.wp_environment?.wp_version || 'Unknown' }}</td>
                                    </tr>
                                    <tr>
                                        <td>PHP Version</td>
                                        <td>{{ systemInfo.wp_environment?.php_version || 'Unknown' }}</td>
                                    </tr>
                                    <tr>
                                        <td>Active Theme</td>
                                        <td>{{ systemInfo.wp_environment?.current_theme || 'Unknown' }} 
                                            ({{ systemInfo.wp_environment?.current_theme_version || 'Unknown' }})</td>
                                    </tr>
                                    <tr>
                                        <td>Multisite</td>
                                        <td>{{ systemInfo.wp_environment?.is_multisite ? 'Yes' : 'No' }}</td>
                                    </tr>
                                    <tr>
                                        <td>User Can Manage Options</td>
                                        <td>{{ systemInfo.wp_environment?.user_can_manage_options ? '✅ Yes' : '❌ No' }}</td>
                                    </tr>
                                </table>
                            </div>

                            <!-- Debug Settings -->
                            <div class="info-card">
                                <h3>
                                    <span class="dashicons dashicons-admin-tools"></span>
                                    Debug Configuration
                                </h3>
                                <table class="system-table">
                                    <tr>
                                        <td>WP_DEBUG</td>
                                        <td>{{ systemInfo.wp_environment?.wp_debug ? '✅ Enabled' : '❌ Disabled' }}</td>
                                    </tr>
                                    <tr>
                                        <td>WP_DEBUG_LOG</td>
                                        <td>{{ systemInfo.wp_environment?.wp_debug_log ? '✅ Enabled' : '❌ Disabled' }}</td>
                                    </tr>
                                    <tr>
                                        <td>WP_DEBUG_DISPLAY</td>
                                        <td>{{ systemInfo.wp_environment?.wp_debug_display ? '✅ Enabled' : '❌ Disabled' }}</td>
                                    </tr>
                                </table>
                                <div v-if="!systemInfo.wp_environment?.wp_debug" class="debug-notice">
                                    <p><strong>💡 Debug Tip:</strong> Enable WP_DEBUG for better troubleshooting. Add this to wp-config.php:</p>
                                    <pre><code>define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);</code></pre>
                                </div>
                            </div>

                            <!-- Memory & Performance -->
                            <div class="info-card">
                                <h3>
                                    <span class="dashicons dashicons-performance"></span>
                                    Memory & Performance
                                </h3>
                                <table class="system-table">
                                    <tr>
                                        <td>WP Memory Limit</td>
                                        <td>{{ systemInfo.wp_environment?.wp_memory_limit || 'Unknown' }}</td>
                                    </tr>
                                    <tr>
                                        <td>PHP Memory Limit</td>
                                        <td>{{ systemInfo.wp_environment?.php_memory_limit || 'Unknown' }}</td>
                                    </tr>
                                    <tr>
                                        <td>Max Execution Time</td>
                                        <td>{{ systemInfo.wp_environment?.max_execution_time || 'Unknown' }}s</td>
                                    </tr>
                                    <tr>
                                        <td>Upload Max Filesize</td>
                                        <td>{{ systemInfo.wp_environment?.upload_max_filesize || 'Unknown' }}</td>
                                    </tr>
                                    <tr>
                                        <td>Post Max Size</td>
                                        <td>{{ systemInfo.wp_environment?.post_max_size || 'Unknown' }}</td>
                                    </tr>
                                </table>
                            </div>

                            <!-- Plugin Information -->
                            <div class="info-card">
                                <h3>
                                    <span class="dashicons dashicons-admin-plugins"></span>
                                    Plugin Information
                                </h3>
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

                            <!-- Module Status -->
                            <div class="info-card">
                                <h3>
                                    <span class="dashicons dashicons-admin-settings"></span>
                                    Module Status
                                </h3>
                                <table class="system-table">
                                    <template v-for="(status, moduleId) in systemInfo.module_status" :key="moduleId">
                                        <tr>
                                            <td>{{ availableModules[moduleId]?.name || moduleId }}</td>
                                            <td>
                                                <span :class="['status-badge', status.enabled ? 'enabled' : 'disabled']">
                                                    {{ status.enabled ? '✅ Enabled' : '❌ Disabled' }}
                                                </span>
                                            </td>
                                        </tr>
                                        <tr v-if="moduleId === 'typography-presets'">
                                            <td style="padding-left: 20px;">Classes Loaded</td>
                                            <td>
                                                Main: {{ status.class_exists ? '✅' : '❌' }} |
                                                Vue: {{ status.vue_class_exists ? '✅' : '❌' }}
                                            </td>
                                        </tr>
                                    </template>
                                </table>
                            </div>

                            <!-- File Status -->
                            <div class="info-card">
                                <h3>
                                    <span class="dashicons dashicons-media-document"></span>
                                    Plugin Files
                                </h3>
                                <table class="system-table">
                                    <tr v-for="(fileInfo, fileName) in systemInfo.file_status" :key="fileName">
                                        <td>{{ fileName }}</td>
                                        <td>
                                            <span :class="['status-badge', fileInfo.exists ? 'enabled' : 'disabled']">
                                                {{ fileInfo.exists ? '✅ Exists' : '❌ Missing' }}
                                            </span>
                                            <span v-if="fileInfo.exists" class="file-details">
                                                ({{ fileInfo.size }}, {{ fileInfo.modified }})
                                            </span>
                                        </td>
                                    </tr>
                                </table>
                            </div>

                            <!-- Server Information -->
                            <div class="info-card">
                                <h3>
                                    <span class="dashicons dashicons-admin-site-alt3"></span>
                                    Server Information
                                </h3>
                                <table class="system-table">
                                    <tr>
                                        <td>Server Software</td>
                                        <td>{{ systemInfo.server_info?.server_software || 'Unknown' }}</td>
                                    </tr>
                                    <tr>
                                        <td>PHP SAPI</td>
                                        <td>{{ systemInfo.server_info?.php_sapi || 'Unknown' }}</td>
                                    </tr>
                                    <tr>
                                        <td>MySQL Version</td>
                                        <td>{{ systemInfo.server_info?.mysql_version || 'Unknown' }}</td>
                                    </tr>
                                    <tr>
                                        <td>cURL Version</td>
                                        <td>{{ systemInfo.server_info?.curl_version || 'Not available' }}</td>
                                    </tr>
                                    <tr>
                                        <td>OpenSSL Version</td>
                                        <td>{{ systemInfo.server_info?.openssl_version || 'Not available' }}</td>
                                    </tr>
                                </table>
                            </div>

                            <!-- Active Plugins -->
                            <div class="info-card">
                                <h3>
                                    <span class="dashicons dashicons-admin-plugins"></span>
                                    Active Plugins ({{ systemInfo.active_plugins?.length || 0 }})
                                </h3>
                                <div class="plugins-list">
                                    <div v-for="plugin in systemInfo.active_plugins" :key="plugin.file" class="plugin-item">
                                        <strong>{{ plugin.name }}</strong> v{{ plugin.version }}
                                        <br><small>{{ plugin.file }}</small>
                                    </div>
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