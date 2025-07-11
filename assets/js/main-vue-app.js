/**
 * Main Vue.js Application for Orbital Editor Suite
 * 
 * Provides the main dashboard and settings interface
 * with a modern, reactive user experience.
 */

document.addEventListener('DOMContentLoaded', function() {
    const { createApp } = Vue;

    createApp({
        data() {
            return {
                loading: true,
                saving: false,
                message: '',
                messageType: 'success',
                activeTab: 'dashboard',
                
                // Main data
                settings: {
                    enable_debug: false,
                    enabled_modules: []
                },
                
                availableModules: {},
                pluginInfo: {},
                systemInfo: {},
                
                strings: {},
                
                // Tab configuration
                tabs: [
                    { id: 'dashboard', title: 'Dashboard', icon: 'dashicons-dashboard' },
                    { id: 'modules', title: 'Modules', icon: 'dashicons-admin-plugins' },
                    { id: 'settings', title: 'Settings', icon: 'dashicons-admin-settings' },
                    { id: 'system', title: 'System Info', icon: 'dashicons-info' }
                ]
            };
        },
        
        computed: {
            enabledModulesCount() {
                return this.settings.enabled_modules ? this.settings.enabled_modules.length : 0;
            }
        },
        
        mounted() {
            this.loadInitialData();
        },
        
        methods: {
            /**
             * Load initial data from WordPress
             */
            loadInitialData() {
                if (typeof orbitalMainVue !== 'undefined') {
                    // Extract settings from the options object
                    this.settings = (orbitalMainVue.options && orbitalMainVue.options.settings) || {};
                    this.availableModules = orbitalMainVue.available_modules || {};
                    this.pluginInfo = orbitalMainVue.plugin_info || {};
                    this.systemInfo = orbitalMainVue.system_info || {};
                    this.strings = orbitalMainVue.strings || {};
                    
                    // Ensure enabled_modules is an array
                    if (!Array.isArray(this.settings.enabled_modules)) {
                        this.settings.enabled_modules = [];
                    }
                }
                
                this.loading = false;
            },
            
            /**
             * Handle settings change
             */
            onSettingsChange() {
                // Settings changed, could auto-save or mark as dirty
                console.log('Settings changed:', this.settings);
            },
            
            /**
             * Save settings via AJAX
             */
            async saveSettings() {
                this.saving = true;
                this.clearMessage();
                
                try {
                    const formData = new FormData();
                    formData.append('action', 'orbital_main_vue_save_settings');
                    formData.append('nonce', orbitalMainVue.nonce);
                    formData.append('settings', JSON.stringify(this.settings));
                    
                    const response = await fetch(orbitalMainVue.ajax_url, {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        this.showMessage(data.data.message, 'success');
                        this.settings = data.data.settings;
                    } else {
                        this.showMessage(data.data || this.strings.error, 'error');
                    }
                } catch (error) {
                    console.error('Save error:', error);
                    this.showMessage(this.strings.error, 'error');
                } finally {
                    this.saving = false;
                }
            },
            
            /**
             * Reset all settings
             */
            async resetSettings() {
                if (!confirm(this.strings.confirmReset)) {
                    return;
                }
                
                this.saving = true;
                this.clearMessage();
                
                try {
                    const formData = new FormData();
                    formData.append('action', 'orbital_main_vue_reset_settings');
                    formData.append('nonce', orbitalMainVue.nonce);
                    
                    const response = await fetch(orbitalMainVue.ajax_url, {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        this.showMessage(data.data.message, 'success');
                        this.settings = data.data.options.settings;
                        
                        // Reload page to reflect changes
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        this.showMessage(data.data || this.strings.error, 'error');
                    }
                } catch (error) {
                    console.error('Reset error:', error);
                    this.showMessage(this.strings.error, 'error');
                } finally {
                    this.saving = false;
                }
            },
            
            /**
             * Toggle module enabled/disabled
             */
            async toggleModule(moduleId) {
                const currentlyEnabled = this.isModuleEnabled(moduleId);
                const newState = !currentlyEnabled;
                
                try {
                    const formData = new FormData();
                    formData.append('action', 'orbital_main_vue_toggle_module');
                    formData.append('nonce', orbitalMainVue.nonce);
                    formData.append('module_id', moduleId);
                    formData.append('enabled', newState ? '1' : '0');
                    
                    const response = await fetch(orbitalMainVue.ajax_url, {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        this.showMessage(data.data.message, 'success');
                        this.settings.enabled_modules = data.data.enabled_modules;
                    } else {
                        this.showMessage(data.data || this.strings.error, 'error');
                    }
                } catch (error) {
                    console.error('Toggle error:', error);
                    this.showMessage(this.strings.error, 'error');
                }
            },
            
            /**
             * Check if module is enabled
             */
            isModuleEnabled(moduleId) {
                return this.settings.enabled_modules && this.settings.enabled_modules.includes(moduleId);
            },
            
            /**
             * Show message
             */
            showMessage(message, type = 'success') {
                this.message = message;
                this.messageType = type;
                
                // Auto-hide after 5 seconds
                setTimeout(() => {
                    this.clearMessage();
                }, 5000);
            },
            
            /**
             * Clear message
             */
            clearMessage() {
                this.message = '';
            }
        }
    }).mount('#orbital-main-vue-app');
});