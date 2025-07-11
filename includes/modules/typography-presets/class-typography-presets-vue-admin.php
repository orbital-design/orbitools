<?php
/**
 * Typography Presets Vue.js Admin Class
 *
 * Modern Vue.js-powered admin interface for Typography Presets module
 * that replicates all existing functionality with a reactive interface.
 *
 * @package    Orbital_Editor_Suite
 * @subpackage Orbital_Editor_Suite/includes/modules/typography-presets
 */

namespace Orbital\Editor_Suite\Modules\Typography_Presets;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Typography Presets Vue.js Admin Class
 *
 * Provides a modern, reactive admin interface using Vue.js that matches
 * all functionality of the original admin interface.
 */
class Typography_Presets_Vue_Admin {

    /**
     * Module instance.
     */
    private $module;

    /**
     * Initialize admin properties.
     */
    public function __construct($module) {
        $this->module = $module;
        // Note: add_admin_menu() will be called directly from the main module
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        $this->register_ajax_handlers();
        
        // Debug: Log that the Vue admin is being constructed
        error_log('Vue.js Typography Presets Admin: Class constructed');
        
        // Debug: Add admin notice
        add_action('admin_notices', function() {
            echo '<div class="notice notice-info is-dismissible">';
            echo '<p><strong>DEBUG:</strong> Vue.js Typography Presets Admin class has been constructed. Menu will be added directly.</p>';
            echo '</div>';
        });
    }

    /**
     * Add admin menu.
     */
    public function add_admin_menu() {
        // Debug: Log that the Vue admin menu method is being called
        error_log('Vue.js Typography Presets Admin: add_admin_menu() called');
        
        $result = add_submenu_page(
            'orbital-editor-suite',
            'Typography Presets (Vue.js)',
            'Typography Presets (Vue.js)',
            'manage_options',
            'orbital-typography-vue-new',
            array($this, 'render_admin_page')
        );
        
        // Debug: Log the result of add_submenu_page
        error_log('Vue.js Typography Presets Admin: add_submenu_page result: ' . var_export($result, true));
        
        // Debug: Add another admin notice to confirm menu was added
        add_action('admin_notices', function() use ($result) {
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p><strong>MENU DEBUG:</strong> Vue.js Typography Presets menu method called! Result: ' . var_export($result, true) . '</p>';
            echo '</div>';
        });
    }

    /**
     * Enqueue scripts and styles.
     */
    public function enqueue_scripts($hook) {
        if (strpos($hook, 'orbital-typography-vue-new') === false) {
            return;
        }

        // Enqueue Vue.js
        wp_enqueue_script('vue-js', 'https://unpkg.com/vue@3/dist/vue.global.js', array(), '3.0.0', true);
        
        // Enqueue our Vue app
        wp_enqueue_script(
            'orbital-typography-presets-vue-app',
            ORBITAL_EDITOR_SUITE_URL . 'assets/js/typography-presets-vue-app.js',
            array('vue-js'),
            '1.0.0',
            true
        );

        // Localize script with WordPress data
        wp_localize_script('orbital-typography-presets-vue-app', 'orbitalTypographyVue', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('orbital_typography_vue_nonce'),
            'settings' => $this->module->get_settings(),
            'presets' => $this->module->get_presets(),
            'groups' => $this->get_groups_with_titles(),
            'strings' => array(
                'loading' => __('Loading...', 'orbital-editor-suite'),
                'saving' => __('Saving...', 'orbital-editor-suite'),
                'saved' => __('Settings saved successfully!', 'orbital-editor-suite'),
                'error' => __('Error saving settings', 'orbital-editor-suite'),
                'confirmDelete' => __('Are you sure you want to delete this preset?', 'orbital-editor-suite'),
                'presetDeleted' => __('Preset deleted successfully', 'orbital-editor-suite'),
                'presetSaved' => __('Preset saved successfully', 'orbital-editor-suite')
            )
        ));

        // Enqueue styles
        wp_enqueue_style(
            'orbital-typography-presets-vue-styles',
            ORBITAL_EDITOR_SUITE_URL . 'assets/css/typography-presets-vue-styles.css',
            array(),
            '1.0.0'
        );
    }

    /**
     * Register AJAX handlers.
     */
    private function register_ajax_handlers() {
        add_action('wp_ajax_orbital_typography_vue_save_settings', array($this, 'handle_save_settings'));
        add_action('wp_ajax_orbital_typography_vue_save_preset', array($this, 'handle_save_preset'));
        add_action('wp_ajax_orbital_typography_vue_delete_preset', array($this, 'handle_delete_preset'));
        add_action('wp_ajax_orbital_typography_vue_get_presets', array($this, 'handle_get_presets'));
        add_action('wp_ajax_orbital_typography_vue_generate_css', array($this, 'handle_generate_css'));
    }

    /**
     * Render the admin page.
     */
    public function render_admin_page() {
        ?>
        <div class="wrap">
            <div id="orbital-typography-vue-app">
                <!-- Loading state -->
                <div v-if="loading" class="typography-loading">
                    <div class="spinner is-active"></div>
                    <p>{{ strings.loading }}</p>
                </div>

                <!-- Main app content -->
                <div v-else class="typography-admin-container">
                    <!-- Header -->
                    <div class="typography-header">
                        <h1>Typography Presets</h1>
                        <div class="header-actions">
                            <button @click="generateCSS" class="button button-secondary">
                                Generate CSS
                            </button>
                            <button @click="saveSettings" :disabled="saving" class="button button-primary">
                                {{ saving ? strings.saving : 'Save Settings' }}
                            </button>
                        </div>
                    </div>

                    <!-- Navigation Tabs -->
                    <div class="typography-tabs">
                        <button 
                            v-for="tab in tabs" 
                            :key="tab.id"
                            @click="activeTab = tab.id"
                            :class="['typography-tab', { active: activeTab === tab.id }]"
                        >
                            <span class="dashicons" :class="tab.icon"></span>
                            {{ tab.title }}
                        </button>
                    </div>

                    <!-- Tab Content -->
                    <div class="typography-tab-content">
                        <!-- Settings Tab -->
                        <div v-if="activeTab === 'settings'" class="typography-section">
                            <h2>Module Settings</h2>
                            <p>Configure how the Typography Presets module behaves.</p>
                            
                            <div class="settings-grid">
                                <div class="setting-field">
                                    <label class="setting-label">Preset Generation Method</label>
                                    <select v-model="settings.preset_generation_method" @change="onSettingsChange">
                                        <option value="admin">Admin Interface (User-friendly)</option>
                                        <option value="theme_json">theme.json (Developer/Advanced)</option>
                                    </select>
                                    <p class="setting-description">Choose how presets are defined and managed.</p>
                                </div>

                                <div class="setting-field">
                                    <label class="setting-checkbox">
                                        <input 
                                            type="checkbox" 
                                            v-model="settings.replace_core_controls"
                                            @change="onSettingsChange"
                                        >
                                        Replace Core Typography Controls
                                    </label>
                                    <p class="setting-description">Remove WordPress core typography controls and replace with preset system.</p>
                                </div>

                                <div class="setting-field">
                                    <label class="setting-checkbox">
                                        <input 
                                            type="checkbox" 
                                            v-model="settings.show_groups"
                                            @change="onSettingsChange"
                                        >
                                        Show Groups in Dropdown
                                    </label>
                                    <p class="setting-description">Organize presets into groups in the block editor dropdown.</p>
                                </div>

                                <div class="setting-field">
                                    <label class="setting-checkbox">
                                        <input 
                                            type="checkbox" 
                                            v-model="settings.output_preset_css"
                                            @change="onSettingsChange"
                                        >
                                        Output Preset CSS
                                    </label>
                                    <p class="setting-description">Automatically generate and include CSS for all presets.</p>
                                </div>

                                <div class="setting-field">
                                    <label class="setting-label">Allowed Blocks</label>
                                    <div class="allowed-blocks-grid">
                                        <label v-for="block in availableBlocks" :key="block.value" class="block-checkbox">
                                            <input 
                                                type="checkbox" 
                                                :value="block.value"
                                                v-model="settings.allowed_blocks"
                                                @change="onSettingsChange"
                                            >
                                            {{ block.label }}
                                        </label>
                                    </div>
                                    <p class="setting-description">Select which blocks should have typography preset controls.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Preset Management Tab -->
                        <div v-if="activeTab === 'presets'" class="typography-section">
                            <h2>Preset Management</h2>
                            <p>Create and manage your typography presets.</p>
                            
                            <!-- Create New Preset -->
                            <div class="preset-form-card">
                                <h3>
                                    <span class="dashicons dashicons-plus-alt"></span>
                                    Create New Preset
                                </h3>
                                <form @submit.prevent="savePreset" class="preset-form">
                                    <div class="form-row">
                                        <div class="form-field">
                                            <label>Preset ID</label>
                                            <input type="text" v-model="newPreset.id" placeholder="e.g., custom-heading" required>
                                        </div>
                                        <div class="form-field">
                                            <label>Display Name</label>
                                            <input type="text" v-model="newPreset.label" placeholder="e.g., Custom Heading" required>
                                        </div>
                                    </div>
                                    
                                    <div class="form-row">
                                        <div class="form-field">
                                            <label>Description</label>
                                            <textarea v-model="newPreset.description" rows="2" placeholder="Brief description..."></textarea>
                                        </div>
                                        <div class="form-field">
                                            <label>Group</label>
                                            <select v-model="newPreset.group">
                                                <option value="headings">Headings</option>
                                                <option value="body">Body Text</option>
                                                <option value="utility">Utility</option>
                                                <option value="custom">Custom</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="typography-properties">
                                        <h4>Typography Properties</h4>
                                        <div class="properties-grid">
                                            <div class="property-field">
                                                <label>Font Size</label>
                                                <input type="text" v-model="newPreset.properties['font-size']" placeholder="1rem">
                                            </div>
                                            <div class="property-field">
                                                <label>Line Height</label>
                                                <input type="text" v-model="newPreset.properties['line-height']" placeholder="1.5">
                                            </div>
                                            <div class="property-field">
                                                <label>Font Weight</label>
                                                <select v-model="newPreset.properties['font-weight']">
                                                    <option value="">Default</option>
                                                    <option value="300">Light (300)</option>
                                                    <option value="400">Normal (400)</option>
                                                    <option value="500">Medium (500)</option>
                                                    <option value="600">Semi Bold (600)</option>
                                                    <option value="700">Bold (700)</option>
                                                </select>
                                            </div>
                                            <div class="property-field">
                                                <label>Letter Spacing</label>
                                                <input type="text" v-model="newPreset.properties['letter-spacing']" placeholder="0">
                                            </div>
                                            <div class="property-field">
                                                <label>Text Transform</label>
                                                <select v-model="newPreset.properties['text-transform']">
                                                    <option value="">None</option>
                                                    <option value="uppercase">Uppercase</option>
                                                    <option value="lowercase">Lowercase</option>
                                                    <option value="capitalize">Capitalize</option>
                                                </select>
                                            </div>
                                            <div class="property-field">
                                                <label>Margin Bottom</label>
                                                <input type="text" v-model="newPreset.properties['margin-bottom']" placeholder="1rem">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <button type="submit" class="button button-primary">
                                        Create Preset
                                    </button>
                                </form>
                            </div>
                            
                            <!-- Existing Presets -->
                            <div class="existing-presets">
                                <h3>
                                    <span class="dashicons dashicons-list-view"></span>
                                    Existing Presets
                                </h3>
                                <div class="presets-grid">
                                    <div v-for="(preset, presetId) in presets" :key="presetId" class="preset-card">
                                        <h4>
                                            {{ preset.label }}
                                            <span v-if="preset.is_default" class="preset-badge">Default</span>
                                            <span v-if="preset.is_theme_json" class="preset-badge theme-json">Theme.json</span>
                                        </h4>
                                        
                                        <p v-if="preset.description" class="preset-description">
                                            {{ preset.description }}
                                        </p>
                                        
                                        <div class="preset-sample" :style="getPresetSampleStyle(preset)">
                                            Sample text with this preset
                                        </div>
                                        
                                        <div class="preset-properties">
                                            <template v-for="(value, property) in preset.properties" :key="property">
                                                <span v-if="Object.keys(preset.properties).indexOf(property) < 3" class="property-tag">
                                                    {{ property }}: {{ value }}
                                                </span>
                                            </template>
                                        </div>
                                        
                                        <div v-if="!preset.is_default" class="preset-actions">
                                            <button @click="deletePreset(presetId)" class="button button-small delete-preset">
                                                Delete
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- CSS Output Tab -->
                        <div v-if="activeTab === 'css'" class="typography-section">
                            <h2>Generated CSS</h2>
                            <p>View and copy the CSS generated for your presets.</p>
                            
                            <div class="css-output-section">
                                <textarea v-model="generatedCSS" readonly class="css-textarea"></textarea>
                                <button @click="copyCSS" class="button button-secondary">
                                    Copy CSS
                                </button>
                            </div>
                        </div>

                        <!-- Theme.json Instructions Tab -->
                        <div v-if="activeTab === 'instructions'" class="typography-section">
                            <h2>theme.json Instructions</h2>
                            <p>How to configure presets using theme.json (Advanced users only).</p>
                            
                            <div class="theme-json-instructions">
                                <div class="instruction-warning">
                                    <strong>⚠️ Advanced Users Only</strong><br>
                                    This method requires coding experience and direct theme file editing.
                                </div>
                                
                                <h3>How to Configure Presets in theme.json</h3>
                                <p>Add the following structure to your theme's <code>theme.json</code> file:</p>
                                
                                <div class="code-examples">
                                    <div class="code-example">
                                        <h4>With Groups (Organized Presets)</h4>
                                        <pre class="code-block">{{ groupedThemeJsonExample }}</pre>
                                        <button @click="copyThemeJson('grouped')" class="button button-secondary">
                                            Copy Grouped Example
                                        </button>
                                    </div>
                                    
                                    <div class="code-example">
                                        <h4>Without Groups (Flat Structure)</h4>
                                        <pre class="code-block">{{ flatThemeJsonExample }}</pre>
                                        <button @click="copyThemeJson('flat')" class="button button-secondary">
                                            Copy Flat Example
                                        </button>
                                    </div>
                                </div>
                                
                                <h3>Important Notes</h3>
                                <ul class="instruction-notes">
                                    <li>The structure must be: <code>settings</code> → <code>custom</code> → <code>orbital</code> → <code>plugins</code> → <code>oes</code> → <code>Typography_Presets</code></li>
                                    <li>Settings in theme.json will override admin interface settings</li>
                                    <li>Preset IDs should use kebab-case (e.g., "termina-16-400")</li>
                                    <li>CSS properties can use camelCase or kebab-case</li>
                                    <li>Changes require clearing any caching plugins</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Status Messages -->
                    <div v-if="message" :class="['typography-message', messageType]">
                        {{ message }}
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Get groups with titles.
     */
    private function get_groups_with_titles() {
        $groups = array();
        $presets = $this->module->get_presets();
        
        foreach ($presets as $preset) {
            if (isset($preset['group'])) {
                $groups[$preset['group']] = isset($preset['group_title']) ? $preset['group_title'] : ucfirst($preset['group']);
            }
        }
        
        return $groups;
    }

    /**
     * Handle save settings AJAX request.
     */
    public function handle_save_settings() {
        check_ajax_referer('orbital_typography_vue_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        $settings = isset($_POST['settings']) ? json_decode(stripslashes($_POST['settings']), true) : array();
        
        // Get current options
        $options = get_option('orbital_editor_suite_options', array());
        
        // Update module settings
        $options['modules']['typography-presets'] = $settings;
        
        // Save options
        update_option('orbital_editor_suite_options', $options);
        
        // Refresh module settings
        $this->module->refresh_settings();
        
        wp_send_json_success(array(
            'message' => 'Settings saved successfully!',
            'settings' => $settings
        ));
    }

    /**
     * Handle save preset AJAX request.
     */
    public function handle_save_preset() {
        check_ajax_referer('orbital_typography_vue_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        $preset_id = sanitize_key($_POST['preset_id']);
        $preset_data = isset($_POST['preset_data']) ? json_decode(stripslashes($_POST['preset_data']), true) : array();
        
        // Sanitize preset data
        $sanitized_preset = array(
            'label' => sanitize_text_field($preset_data['label']),
            'description' => sanitize_textarea_field($preset_data['description']),
            'group' => sanitize_text_field($preset_data['group']),
            'properties' => array()
        );
        
        if (isset($preset_data['properties']) && is_array($preset_data['properties'])) {
            foreach ($preset_data['properties'] as $property => $value) {
                if (!empty($value)) {
                    $sanitized_preset['properties'][sanitize_key($property)] = sanitize_text_field($value);
                }
            }
        }
        
        if ($this->module->save_preset($preset_id, $sanitized_preset)) {
            wp_send_json_success(array(
                'message' => 'Preset saved successfully!',
                'presets' => $this->module->get_presets()
            ));
        } else {
            wp_send_json_error('Failed to save preset');
        }
    }

    /**
     * Handle delete preset AJAX request.
     */
    public function handle_delete_preset() {
        check_ajax_referer('orbital_typography_vue_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        $preset_id = sanitize_key($_POST['preset_id']);
        
        if ($this->module->delete_preset($preset_id)) {
            wp_send_json_success(array(
                'message' => 'Preset deleted successfully!',
                'presets' => $this->module->get_presets()
            ));
        } else {
            wp_send_json_error('Failed to delete preset or preset is default');
        }
    }

    /**
     * Handle get presets AJAX request.
     */
    public function handle_get_presets() {
        check_ajax_referer('orbital_typography_vue_nonce', 'nonce');
        
        wp_send_json_success($this->module->get_presets());
    }

    /**
     * Handle generate CSS AJAX request.
     */
    public function handle_generate_css() {
        check_ajax_referer('orbital_typography_vue_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        $css = $this->module->generate_css();
        
        wp_send_json_success(array(
            'css' => $css
        ));
    }
}