/**
 * WP Options Kit Vue.js App
 * 
 * This is the Vue.js application that powers the WP Options Kit admin interface.
 * It demonstrates modern, reactive admin interfaces for WordPress.
 */

const { createApp } = Vue;

createApp({
    data() {
        return {
            // App state
            loading: true,
            saving: false,
            changed: false,
            activeTab: 'general',
            
            // UI data
            appTitle: 'Typography Presets Settings',
            message: '',
            messageType: 'success',
            debugMode: window.orbitalWOK?.debug || false,
            
            // Form data
            options: {
                enable_module: true,
                preset_method: 'admin',
                allowed_blocks: [],
                replace_core_controls: true,
                output_css: true,
                default_font_size: '16px'
            },
            
            // Preview
            previewText: 'This is a preview of your typography settings. Change the settings above to see the preview update in real-time.',
            
            // Static data
            tabs: [
                {
                    id: 'general',
                    title: 'General',
                    icon: 'dashicons-admin-generic'
                },
                {
                    id: 'typography',
                    title: 'Typography',
                    icon: 'dashicons-editor-textcolor'
                },
                {
                    id: 'preview',
                    title: 'Preview',
                    icon: 'dashicons-visibility'
                }
            ],
            
            availableBlocks: [
                { value: 'core/paragraph', label: 'Paragraph' },
                { value: 'core/heading', label: 'Heading' },
                { value: 'core/list', label: 'List' },
                { value: 'core/quote', label: 'Quote' },
                { value: 'core/button', label: 'Button' },
                { value: 'core/pullquote', label: 'Pullquote' }
            ]
        };
    },
    
    computed: {
        previewStyles() {
            return {
                fontSize: this.options.default_font_size || '16px',
                fontFamily: this.options.preset_method === 'theme_json' ? 'Georgia, serif' : 'system-ui, sans-serif',
                lineHeight: '1.6',
                transition: 'all 0.3s ease'
            };
        }
    },
    
    mounted() {
        this.loadOptions();
    },
    
    methods: {
        async loadOptions() {
            try {
                this.loading = true;
                
                // Load options from WordPress
                if (window.orbitalWOK && window.orbitalWOK.options) {
                    this.options = { ...this.options, ...window.orbitalWOK.options };
                }
                
                // Simulate loading delay for demo
                await new Promise(resolve => setTimeout(resolve, 500));
                
                this.loading = false;
            } catch (error) {
                console.error('Error loading options:', error);
                this.showMessage('Error loading settings', 'error');
                this.loading = false;
            }
        },
        
        async saveOptions() {
            try {
                this.saving = true;
                
                const formData = new FormData();
                formData.append('action', 'orbital_wok_save_options');
                formData.append('nonce', window.orbitalWOK.nonce);
                formData.append('options', JSON.stringify(this.options));
                
                const response = await fetch(window.orbitalWOK.ajax_url, {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    this.showMessage(result.data.message || 'Settings saved successfully!', 'success');
                    this.changed = false;
                    
                    // Update options with sanitized values
                    if (result.data.options) {
                        this.options = result.data.options;
                    }
                } else {
                    this.showMessage(result.data || 'Error saving settings', 'error');
                }
                
            } catch (error) {
                console.error('Error saving options:', error);
                this.showMessage('Error saving settings', 'error');
            } finally {
                this.saving = false;
            }
        },
        
        markChanged() {
            this.changed = true;
        },
        
        showMessage(message, type = 'success') {
            this.message = message;
            this.messageType = type;
            
            // Auto-hide message after 3 seconds
            setTimeout(() => {
                this.message = '';
            }, 3000);
        },
        
        // Tab switching with animation
        switchTab(tabId) {
            this.activeTab = tabId;
        }
    },
    
    watch: {
        // Watch for changes in options
        options: {
            handler() {
                this.markChanged();
            },
            deep: true
        }
    }
}).mount('#orbital-wok-vue-app');