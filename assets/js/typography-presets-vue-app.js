/**
 * Typography Presets Vue.js App
 * 
 * Modern, reactive admin interface for Typography Presets module
 */

const { createApp } = Vue;

createApp({
    data() {
        return {
            // App state
            loading: true,
            saving: false,
            changed: false,
            activeTab: 'settings',
            
            // Messages
            message: '',
            messageType: 'success',
            
            // Data from WordPress
            settings: {},
            presets: {},
            groups: {},
            strings: {},
            
            // New preset form
            newPreset: {
                id: '',
                label: '',
                description: '',
                group: 'headings',
                properties: {
                    'font-size': '',
                    'line-height': '',
                    'font-weight': '',
                    'letter-spacing': '',
                    'text-transform': '',
                    'margin-bottom': ''
                }
            },
            
            // Generated CSS
            generatedCSS: '',
            
            // Static data
            
            availableBlocks: [
                { value: 'core/paragraph', label: 'Paragraph' },
                { value: 'core/heading', label: 'Heading' },
                { value: 'core/list', label: 'List' },
                { value: 'core/quote', label: 'Quote' },
                { value: 'core/button', label: 'Button' },
                { value: 'core/pullquote', label: 'Pullquote' },
                { value: 'core/group', label: 'Group' },
                { value: 'core/column', label: 'Column' }
            ],
            
            // Theme.json examples
            groupedThemeJsonExample: `{
  "settings": {
    "custom": {
      "orbital": {
        "plugins": {
          "oes": {
            "Typography_Presets": {
              "settings": {
                "replace_core_controls": true,
                "show_groups": true,
                "output_preset_css": true
              },
              "groups": {
                "headings": {
                  "title": "Headings & Standouts"
                },
                "body": {
                  "title": "Body Text"
                }
              },
              "items": {
                "termina-16-400": {
                  "label": "Termina 16 Regular",
                  "description": "Clean heading style",
                  "group": "headings",
                  "properties": {
                    "font-family": "Termina",
                    "font-weight": 400,
                    "font-size": "16px",
                    "line-height": "20px",
                    "letter-spacing": "0"
                  }
                }
              }
            }
          }
        }
      }
    }
  }
}`,
            
            flatThemeJsonExample: `{
  "settings": {
    "custom": {
      "orbital": {
        "plugins": {
          "oes": {
            "Typography_Presets": {
              "settings": {
                "replace_core_controls": true,
                "show_groups": false,
                "output_preset_css": true
              },
              "items": {
                "termina-16-400": {
                  "label": "Termina 16 Regular",
                  "description": "Clean heading style",
                  "properties": {
                    "font-family": "Termina",
                    "font-weight": 400,
                    "font-size": "16px",
                    "line-height": "20px",
                    "letter-spacing": "0"
                  }
                }
              }
            }
          }
        }
      }
    }
  }
}`
        };
    },
    
    mounted() {
        this.loadData();
        this.initTabSwitching();
    },
    
    methods: {
        loadData() {
            // Load data from WordPress localized script
            if (window.orbitalTypographyVue) {
                this.settings = { ...window.orbitalTypographyVue.settings };
                this.presets = { ...window.orbitalTypographyVue.presets };
                this.groups = { ...window.orbitalTypographyVue.groups };
                this.strings = { ...window.orbitalTypographyVue.strings };
                
                // Generate initial CSS
                this.generateCSS();
            }
            
            // Simulate loading delay
            setTimeout(() => {
                this.loading = false;
            }, 500);
        },
        
        initTabSwitching() {
            // Initialize tab switching for static PHP-rendered tabs
            const tabButtons = document.querySelectorAll('.orbital-tab');
            
            tabButtons.forEach(button => {
                button.addEventListener('click', (e) => {
                    e.preventDefault();
                    
                    // Remove active class from all tabs
                    tabButtons.forEach(tab => tab.classList.remove('active'));
                    
                    // Add active class to clicked tab
                    button.classList.add('active');
                    
                    // Update Vue activeTab data
                    this.activeTab = button.dataset.tab;
                });
            });
            
            // Set initial active tab
            if (tabButtons.length > 0) {
                tabButtons[0].classList.add('active');
            }
        },
        
        async saveSettings() {
            try {
                this.saving = true;
                
                const formData = new FormData();
                formData.append('action', 'orbital_typography_vue_save_settings');
                formData.append('nonce', window.orbitalTypographyVue.nonce);
                formData.append('settings', JSON.stringify(this.settings));
                
                const response = await fetch(window.orbitalTypographyVue.ajax_url, {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    this.showMessage(result.data.message, 'success');
                    this.changed = false;
                } else {
                    this.showMessage(result.data || this.strings.error, 'error');
                }
                
            } catch (error) {
                console.error('Error saving settings:', error);
                this.showMessage(this.strings.error, 'error');
            } finally {
                this.saving = false;
            }
        },
        
        async savePreset() {
            try {
                // Validate required fields
                if (!this.newPreset.id || !this.newPreset.label) {
                    this.showMessage('Please fill in required fields', 'error');
                    return;
                }
                
                const formData = new FormData();
                formData.append('action', 'orbital_typography_vue_save_preset');
                formData.append('nonce', window.orbitalTypographyVue.nonce);
                formData.append('preset_id', this.newPreset.id);
                formData.append('preset_data', JSON.stringify(this.newPreset));
                
                const response = await fetch(window.orbitalTypographyVue.ajax_url, {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    this.showMessage(result.data.message, 'success');
                    this.presets = result.data.presets;
                    this.resetPresetForm();
                    this.generateCSS();
                } else {
                    this.showMessage(result.data || 'Error saving preset', 'error');
                }
                
            } catch (error) {
                console.error('Error saving preset:', error);
                this.showMessage('Error saving preset', 'error');
            }
        },
        
        async deletePreset(presetId) {
            if (!confirm(this.strings.confirmDelete)) {
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('action', 'orbital_typography_vue_delete_preset');
                formData.append('nonce', window.orbitalTypographyVue.nonce);
                formData.append('preset_id', presetId);
                
                const response = await fetch(window.orbitalTypographyVue.ajax_url, {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    this.showMessage(result.data.message, 'success');
                    this.presets = result.data.presets;
                    this.generateCSS();
                } else {
                    this.showMessage(result.data || 'Error deleting preset', 'error');
                }
                
            } catch (error) {
                console.error('Error deleting preset:', error);
                this.showMessage('Error deleting preset', 'error');
            }
        },
        
        async generateCSS() {
            try {
                const formData = new FormData();
                formData.append('action', 'orbital_typography_vue_generate_css');
                formData.append('nonce', window.orbitalTypographyVue.nonce);
                
                const response = await fetch(window.orbitalTypographyVue.ajax_url, {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    this.generatedCSS = result.data.css;
                }
                
            } catch (error) {
                console.error('Error generating CSS:', error);
            }
        },
        
        copyCSS() {
            navigator.clipboard.writeText(this.generatedCSS).then(() => {
                this.showMessage('CSS copied to clipboard!', 'success');
            }).catch(() => {
                this.showMessage('Failed to copy CSS', 'error');
            });
        },
        
        copyThemeJson(type) {
            const text = type === 'grouped' ? this.groupedThemeJsonExample : this.flatThemeJsonExample;
            navigator.clipboard.writeText(text).then(() => {
                this.showMessage('Theme.json example copied to clipboard!', 'success');
            }).catch(() => {
                this.showMessage('Failed to copy example', 'error');
            });
        },
        
        resetPresetForm() {
            this.newPreset = {
                id: '',
                label: '',
                description: '',
                group: 'headings',
                properties: {
                    'font-size': '',
                    'line-height': '',
                    'font-weight': '',
                    'letter-spacing': '',
                    'text-transform': '',
                    'margin-bottom': ''
                }
            };
        },
        
        getPresetSampleStyle(preset) {
            const style = {};
            
            if (preset.properties) {
                Object.keys(preset.properties).forEach(property => {
                    // Convert kebab-case to camelCase for Vue style binding
                    const camelProperty = property.replace(/-([a-z])/g, (g) => g[1].toUpperCase());
                    style[camelProperty] = preset.properties[property];
                });
            }
            
            return style;
        },
        
        onSettingsChange() {
            this.changed = true;
        },
        
        showMessage(message, type = 'success') {
            this.message = message;
            this.messageType = type;
            
            // Auto-hide message after 4 seconds
            setTimeout(() => {
                this.message = '';
            }, 4000);
        }
    },
    
    computed: {
        presetsArray() {
            return Object.entries(this.presets).map(([id, preset]) => ({
                id,
                ...preset
            }));
        }
    },
    
    watch: {
        settings: {
            handler() {
                this.onSettingsChange();
            },
            deep: true
        }
    }
}).mount('#orbital-typography-vue-app');