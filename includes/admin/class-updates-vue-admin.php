<?php
/**
 * Updates Vue.js Admin Interface
 *
 * Modern Vue.js-powered updates interface for Orbital Editor Suite
 * that provides GitHub integration and update management.
 *
 * @package    Orbital_Editor_Suite
 * @subpackage Orbital_Editor_Suite/includes/admin
 */

namespace Orbital\Editor_Suite\Admin;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Updates Vue.js Admin Interface Class
 *
 * Provides the updates management interface using Vue.js
 * with GitHub integration and version checking.
 */
class Updates_Vue_Admin {

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
        if (strpos($hook, 'orbital-editor-suite-updates') === false) {
            return;
        }

        // Enqueue Vue.js
        wp_enqueue_script('vue-js', 'https://unpkg.com/vue@3/dist/vue.global.js', array(), '3.0.0', true);
        
        // Enqueue our Vue app
        wp_enqueue_script(
            'orbital-updates-vue-app',
            ORBITAL_EDITOR_SUITE_URL . 'assets/js/updates-vue-app.js',
            array('vue-js'),
            $this->version,
            true
        );

        // Localize script with WordPress data
        wp_localize_script('orbital-updates-vue-app', 'orbitalUpdatesVue', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('orbital_updates_vue_nonce'),
            'current_version' => ORBITAL_EDITOR_SUITE_VERSION,
            'plugin_info' => array(
                'name' => 'Orbital Editor Suite',
                'version' => ORBITAL_EDITOR_SUITE_VERSION,
                'github_repo' => 'orbital-design/orbital-editor-suite',
                'last_checked' => get_option('orbital_editor_suite_last_update_check', 'Never')
            ),
            'strings' => array(
                'loading' => __('Loading...', 'orbital-editor-suite'),
                'checking' => __('Checking for updates...', 'orbital-editor-suite'),
                'upToDate' => __('Plugin is up to date!', 'orbital-editor-suite'),
                'updateAvailable' => __('Update available!', 'orbital-editor-suite'),
                'error' => __('Error checking for updates', 'orbital-editor-suite'),
                'downloading' => __('Downloading update...', 'orbital-editor-suite'),
                'installing' => __('Installing update...', 'orbital-editor-suite'),
                'updateComplete' => __('Update completed successfully!', 'orbital-editor-suite'),
                'updateFailed' => __('Update failed. Please try again.', 'orbital-editor-suite')
            )
        ));

        // Enqueue styles
        wp_enqueue_style(
            'orbital-updates-vue-styles',
            ORBITAL_EDITOR_SUITE_URL . 'assets/css/updates-vue-styles.css',
            array(),
            $this->version
        );
    }

    /**
     * Register AJAX handlers.
     */
    private function register_ajax_handlers() {
        add_action('wp_ajax_orbital_updates_vue_check_version', array($this, 'handle_check_version'));
        add_action('wp_ajax_orbital_updates_vue_get_changelog', array($this, 'handle_get_changelog'));
        add_action('wp_ajax_orbital_updates_vue_download_update', array($this, 'handle_download_update'));
    }

    /**
     * Render the updates admin page.
     */
    public function render_admin_page() {
        ?>
        <div class="wrap">
            <div id="orbital-updates-vue-app">
                <!-- Loading state -->
                <div v-if="loading" class="updates-loading">
                    <div class="spinner is-active"></div>
                    <p>{{ strings.loading }}</p>
                </div>

                <!-- Main app content -->
                <div v-else class="updates-admin-container">
                    <!-- Header -->
                    <div class="updates-header">
                        <div class="header-content">
                            <div class="header-title">
                                <h1>
                                    <span class="dashicons dashicons-update"></span>
                                    Plugin Updates
                                </h1>
                                <span class="current-version">Current: v{{ currentVersion }}</span>
                            </div>
                            <div class="header-actions">
                                <button @click="checkForUpdates" :disabled="checking" class="button button-secondary">
                                    {{ checking ? strings.checking : 'Check for Updates' }}
                                </button>
                                <button v-if="updateAvailable" @click="downloadUpdate" :disabled="updating" 
                                        class="button button-primary">
                                    {{ updating ? strings.downloading : 'Update Now' }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Update Status -->
                    <div class="update-status-section">
                        <div :class="['status-card', updateStatus]">
                            <div class="status-icon">
                                <span v-if="updateStatus === 'up-to-date'" class="dashicons dashicons-yes-alt"></span>
                                <span v-else-if="updateStatus === 'update-available'" class="dashicons dashicons-download"></span>
                                <span v-else-if="updateStatus === 'checking'" class="dashicons dashicons-update"></span>
                                <span v-else class="dashicons dashicons-info"></span>
                            </div>
                            <div class="status-content">
                                <h3>{{ statusTitle }}</h3>
                                <p>{{ statusMessage }}</p>
                                <div v-if="latestVersion && updateAvailable" class="version-info">
                                    <span class="version-current">Current: v{{ currentVersion }}</span>
                                    <span class="version-arrow">→</span>
                                    <span class="version-latest">Latest: v{{ latestVersion }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Update Details -->
                    <div v-if="updateAvailable && updateInfo" class="update-details-section">
                        <div class="details-card">
                            <h3>
                                <span class="dashicons dashicons-clipboard"></span>
                                Update Details
                            </h3>
                            
                            <div class="update-meta">
                                <div class="meta-item">
                                    <strong>Version:</strong> {{ updateInfo.version }}
                                </div>
                                <div class="meta-item">
                                    <strong>Release Date:</strong> {{ formatDate(updateInfo.date) }}
                                </div>
                                <div class="meta-item">
                                    <strong>Size:</strong> {{ updateInfo.size || 'Unknown' }}
                                </div>
                            </div>

                            <div v-if="updateInfo.changelog" class="changelog">
                                <h4>What's New:</h4>
                                <div class="changelog-content" v-html="updateInfo.changelog"></div>
                            </div>

                            <div class="update-actions">
                                <button @click="downloadUpdate" :disabled="updating" class="button button-primary button-large">
                                    {{ updating ? strings.installing : 'Install Update' }}
                                </button>
                                <a :href="updateInfo.github_url" target="_blank" class="button button-secondary">
                                    View on GitHub
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Current Version Info -->
                    <div class="current-version-section">
                        <div class="version-card">
                            <h3>
                                <span class="dashicons dashicons-info"></span>
                                Current Installation
                            </h3>
                            
                            <div class="version-details">
                                <div class="detail-row">
                                    <span class="detail-label">Plugin Version:</span>
                                    <span class="detail-value">{{ currentVersion }}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Last Checked:</span>
                                    <span class="detail-value">{{ lastChecked }}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">GitHub Repository:</span>
                                    <span class="detail-value">
                                        <a :href="'https://github.com/' + githubRepo" target="_blank">
                                            {{ githubRepo }}
                                        </a>
                                    </span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Auto Updates:</span>
                                    <span class="detail-value">
                                        <label class="toggle-switch">
                                            <input type="checkbox" v-model="autoUpdates" @change="toggleAutoUpdates">
                                            <span class="toggle-slider"></span>
                                        </label>
                                        {{ autoUpdates ? 'Enabled' : 'Disabled' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Update History -->
                    <div class="update-history-section">
                        <div class="history-card">
                            <h3>
                                <span class="dashicons dashicons-backup"></span>
                                Update History
                            </h3>
                            
                            <div v-if="updateHistory.length > 0" class="history-list">
                                <div v-for="update in updateHistory" :key="update.version" class="history-item">
                                    <div class="history-version">v{{ update.version }}</div>
                                    <div class="history-date">{{ formatDate(update.date) }}</div>
                                    <div class="history-status">{{ update.status }}</div>
                                </div>
                            </div>
                            <div v-else class="no-history">
                                <p>No update history available.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Status Messages -->
                    <div v-if="message" :class="['updates-message', messageType]">
                        {{ message }}
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Handle check version AJAX request.
     */
    public function handle_check_version() {
        check_ajax_referer('orbital_updates_vue_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        // Simulate GitHub API check (replace with actual implementation)
        $current_version = ORBITAL_EDITOR_SUITE_VERSION;
        $latest_version = '1.1.0'; // This would come from GitHub API
        
        $update_available = version_compare($current_version, $latest_version, '<');
        
        // Update last checked time
        update_option('orbital_editor_suite_last_update_check', current_time('mysql'));
        
        $response_data = array(
            'current_version' => $current_version,
            'latest_version' => $latest_version,
            'update_available' => $update_available,
            'last_checked' => current_time('Y-m-d H:i:s')
        );
        
        if ($update_available) {
            $response_data['update_info'] = array(
                'version' => $latest_version,
                'date' => '2024-01-15',
                'size' => '2.3 MB',
                'changelog' => '<ul><li>New Vue.js admin interface</li><li>Improved performance</li><li>Bug fixes</li></ul>',
                'github_url' => 'https://github.com/orbital-design/orbital-editor-suite/releases/tag/v' . $latest_version
            );
        }
        
        wp_send_json_success($response_data);
    }

    /**
     * Handle get changelog AJAX request.
     */
    public function handle_get_changelog() {
        check_ajax_referer('orbital_updates_vue_nonce', 'nonce');
        
        // This would fetch from GitHub API in real implementation
        $changelog = array(
            array(
                'version' => '1.1.0',
                'date' => '2024-01-15',
                'changes' => array(
                    'Added Vue.js admin interface',
                    'Improved Typography Presets module',
                    'Enhanced performance and caching',
                    'Bug fixes and stability improvements'
                )
            ),
            array(
                'version' => '1.0.0',
                'date' => '2024-01-01',
                'changes' => array(
                    'Initial release',
                    'Typography Presets module',
                    'WordPress admin integration',
                    'GitHub updater functionality'
                )
            )
        );
        
        wp_send_json_success($changelog);
    }

    /**
     * Handle download update AJAX request.
     */
    public function handle_download_update() {
        check_ajax_referer('orbital_updates_vue_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        // This would handle the actual update process
        // For now, we'll simulate success
        sleep(2); // Simulate download time
        
        // Add to update history
        $history = get_option('orbital_editor_suite_update_history', array());
        $history[] = array(
            'version' => '1.1.0',
            'date' => current_time('mysql'),
            'status' => 'Success'
        );
        update_option('orbital_editor_suite_update_history', $history);
        
        wp_send_json_success(array(
            'message' => 'Update completed successfully! Please refresh the page.',
            'new_version' => '1.1.0'
        ));
    }
}