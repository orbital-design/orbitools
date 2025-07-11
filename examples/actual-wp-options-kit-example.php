<?php
/**
 * Actual WP Options Kit Example
 *
 * This demonstrates the real WP Options Kit approach using Vue.js
 * for creating modern, reactive admin interfaces.
 *
 * @package Orbital_Editor_Suite
 * @subpackage Examples
 */

namespace Orbital\Editor_Suite\Examples;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * WP Options Kit Implementation Example
 * 
 * This shows how to use the actual WP Options Kit library
 * which provides Vue.js-based admin interfaces.
 */
class Actual_WP_Options_Kit_Example {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_orbital_wok_save_options', array($this, 'handle_save_options'));
        add_action('wp_ajax_orbital_wok_get_options', array($this, 'handle_get_options'));
    }

    public function add_admin_menu() {
        add_submenu_page(
            'orbital-editor-suite',
            'WP Options Kit (Vue.js)',
            'WP Options Kit (Vue.js)',
            'manage_options',
            'orbital-wp-options-kit-vue',
            array($this, 'render_admin_page')
        );
    }

    public function enqueue_scripts($hook) {
        if (strpos($hook, 'orbital-wp-options-kit-vue') === false) {
            return;
        }

        // Enqueue Vue.js
        wp_enqueue_script('vue-js', 'https://unpkg.com/vue@3/dist/vue.global.js', array(), '3.0.0', true);
        
        // Enqueue our Vue app
        wp_enqueue_script(
            'orbital-wok-vue-app',
            plugin_dir_url(__FILE__) . '../assets/js/wok-vue-app.js',
            array('vue-js'),
            '1.0.0',
            true
        );

        // Localize script with WordPress data
        wp_localize_script('orbital-wok-vue-app', 'orbitalWOK', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('orbital_wok_nonce'),
            'options' => $this->get_options()
        ));

        // Enqueue styles
        wp_enqueue_style(
            'orbital-wok-vue-styles',
            plugin_dir_url(__FILE__) . '../assets/css/wok-vue-styles.css',
            array(),
            '1.0.0'
        );
    }

    public function render_admin_page() {
        ?>
        <div class="wrap">
            <h1>WP Options Kit (Vue.js) Example</h1>
            <p>This demonstrates a Vue.js-based admin interface using WP Options Kit patterns.</p>
            
            <div id="orbital-wok-vue-app">
                <!-- Loading state -->
                <div v-if="loading" class="wok-loading">
                    <div class="spinner is-active"></div>
                    <p>Loading settings...</p>
                </div>

                <!-- Main app content -->
                <div v-else class="wok-admin-container">
                    <!-- Header -->
                    <div class="wok-header">
                        <h2>{{ appTitle }}</h2>
                        <button @click="saveOptions" :disabled="saving" class="button button-primary">
                            {{ saving ? 'Saving...' : 'Save Changes' }}
                        </button>
                    </div>

                    <!-- Settings Tabs -->
                    <div class="wok-tabs">
                        <button 
                            v-for="tab in tabs" 
                            :key="tab.id"
                            @click="activeTab = tab.id"
                            :class="['wok-tab', { active: activeTab === tab.id }]"
                        >
                            <span class="dashicons" :class="tab.icon"></span>
                            {{ tab.title }}
                        </button>
                    </div>

                    <!-- Tab Content -->
                    <div class="wok-tab-content">
                        <!-- General Settings Tab -->
                        <div v-if="activeTab === 'general'" class="wok-section">
                            <h3>General Settings</h3>
                            
                            <div class="wok-field">
                                <label class="wok-label">
                                    <input 
                                        type="checkbox" 
                                        v-model="options.enable_module"
                                        @change="markChanged"
                                    >
                                    Enable Module
                                </label>
                                <p class="wok-description">Enable the typography presets module</p>
                            </div>

                            <div class="wok-field">
                                <label class="wok-label">Preset Generation Method</label>
                                <select v-model="options.preset_method" @change="markChanged">
                                    <option value="admin">Admin Interface</option>
                                    <option value="theme_json">Theme.json</option>
                                </select>
                                <p class="wok-description">Choose how presets are defined and managed</p>
                            </div>

                            <div class="wok-field">
                                <label class="wok-label">Allowed Blocks</label>
                                <div class="wok-checkbox-grid">
                                    <label v-for="block in availableBlocks" :key="block.value" class="wok-checkbox-item">
                                        <input 
                                            type="checkbox" 
                                            :value="block.value"
                                            v-model="options.allowed_blocks"
                                            @change="markChanged"
                                        >
                                        {{ block.label }}
                                    </label>
                                </div>
                                <p class="wok-description">Select which blocks can use typography presets</p>
                            </div>
                        </div>

                        <!-- Typography Settings Tab -->
                        <div v-if="activeTab === 'typography'" class="wok-section">
                            <h3>Typography Settings</h3>
                            
                            <div class="wok-field">
                                <label class="wok-label">
                                    <input 
                                        type="checkbox" 
                                        v-model="options.replace_core_controls"
                                        @change="markChanged"
                                    >
                                    Replace Core Typography Controls
                                </label>
                                <p class="wok-description">Remove WordPress core typography controls</p>
                            </div>

                            <div class="wok-field">
                                <label class="wok-label">
                                    <input 
                                        type="checkbox" 
                                        v-model="options.output_css"
                                        @change="markChanged"
                                    >
                                    Output Preset CSS
                                </label>
                                <p class="wok-description">Automatically generate CSS for presets</p>
                            </div>

                            <div class="wok-field">
                                <label class="wok-label">Default Font Size</label>
                                <input 
                                    type="text" 
                                    v-model="options.default_font_size"
                                    placeholder="16px"
                                    @input="markChanged"
                                >
                                <p class="wok-description">Default font size for new presets</p>
                            </div>
                        </div>

                        <!-- Preview Tab -->
                        <div v-if="activeTab === 'preview'" class="wok-section">
                            <h3>Live Preview</h3>
                            
                            <div class="wok-preview-container">
                                <div class="wok-preview-item" :style="previewStyles">
                                    <h4>Preview Heading</h4>
                                    <p>This is how your typography settings will look. The preview updates in real-time as you change settings.</p>
                                </div>
                            </div>
                            
                            <div class="wok-field">
                                <label class="wok-label">Preview Text</label>
                                <textarea 
                                    v-model="previewText"
                                    rows="4"
                                    placeholder="Enter text to preview..."
                                ></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Status Messages -->
                    <div v-if="message" :class="['wok-message', messageType]">
                        {{ message }}
                    </div>

                    <!-- Debug Info (if WP_DEBUG is enabled) -->
                    <div v-if="debugMode" class="wok-debug">
                        <h4>Debug Information</h4>
                        <pre>{{ JSON.stringify(options, null, 2) }}</pre>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    public function handle_get_options() {
        check_ajax_referer('orbital_wok_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        wp_send_json_success($this->get_options());
    }

    public function handle_save_options() {
        check_ajax_referer('orbital_wok_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        $options = isset($_POST['options']) ? json_decode(stripslashes($_POST['options']), true) : array();
        $sanitized = $this->sanitize_options($options);
        
        update_option('orbital_wok_vue_options', $sanitized);
        
        wp_send_json_success(array(
            'message' => 'Settings saved successfully!',
            'options' => $sanitized
        ));
    }

    private function get_options() {
        return get_option('orbital_wok_vue_options', array(
            'enable_module' => true,
            'preset_method' => 'admin',
            'allowed_blocks' => array('core/paragraph', 'core/heading'),
            'replace_core_controls' => true,
            'output_css' => true,
            'default_font_size' => '16px'
        ));
    }

    private function sanitize_options($options) {
        $sanitized = array();
        
        $sanitized['enable_module'] = !empty($options['enable_module']);
        $sanitized['preset_method'] = in_array($options['preset_method'], array('admin', 'theme_json')) ? $options['preset_method'] : 'admin';
        $sanitized['allowed_blocks'] = is_array($options['allowed_blocks']) ? array_map('sanitize_text_field', $options['allowed_blocks']) : array();
        $sanitized['replace_core_controls'] = !empty($options['replace_core_controls']);
        $sanitized['output_css'] = !empty($options['output_css']);
        $sanitized['default_font_size'] = sanitize_text_field($options['default_font_size']);
        
        return $sanitized;
    }
}

// Initialize the example (commented out - uncomment to test)
new Actual_WP_Options_Kit_Example();