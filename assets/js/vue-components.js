/**
 * Shared Vue.js Components for Orbital Editor Suite
 * 
 * Reusable components and utilities for all Vue.js admin interfaces
 */

// Global mixins and utilities
window.OrbitalVueUtils = {
    /**
     * Common data properties for all admin interfaces
     */
    commonData: {
        loading: false,
        saving: false,
        message: '',
        messageType: 'success'
    },
    
    /**
     * Common methods for all admin interfaces
     */
    commonMethods: {
        /**
         * Show message
         */
        showMessage(message, type = 'success') {
            this.message = message;
            this.messageType = type;
            
            // Auto-hide after 5 seconds for non-error messages
            if (type !== 'error') {
                setTimeout(() => {
                    this.clearMessage();
                }, 5000);
            }
        },
        
        /**
         * Clear message
         */
        clearMessage() {
            this.message = '';
        },
        
        /**
         * Format date for display
         */
        formatDate(dateString) {
            if (!dateString || dateString === 'Never') return dateString;
            
            try {
                const date = new Date(dateString);
                return date.toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            } catch (error) {
                return dateString;
            }
        },
        
        /**
         * Debounce function calls
         */
        debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },
        
        /**
         * Deep clone object
         */
        deepClone(obj) {
            return JSON.parse(JSON.stringify(obj));
        },
        
        /**
         * Check if object is empty
         */
        isEmpty(obj) {
            return Object.keys(obj).length === 0;
        },
        
        /**
         * Sanitize text input
         */
        sanitizeText(text) {
            if (typeof text !== 'string') return text;
            return text.replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '');
        }
    }
};

/**
 * Global Vue Components
 */
if (typeof Vue !== 'undefined') {
    const { createApp } = Vue;
    
    // Loading Spinner Component
    window.OrbitalLoadingSpinner = {
        template: `
            <div class="orbital-loading-spinner">
                <div class="spinner-wrapper">
                    <div class="spinner is-active"></div>
                    <p v-if="message">{{ message }}</p>
                </div>
            </div>
        `,
        props: {
            message: {
                type: String,
                default: 'Loading...'
            }
        }
    };
    
    // Status Message Component
    window.OrbitalStatusMessage = {
        template: `
            <div v-if="message" :class="['orbital-status-message', type]" @click="$emit('close')">
                <span class="message-text">{{ message }}</span>
                <button v-if="dismissible" class="message-close" @click="$emit('close')" type="button">
                    <span class="dashicons dashicons-no-alt"></span>
                </button>
            </div>
        `,
        props: {
            message: {
                type: String,
                required: true
            },
            type: {
                type: String,
                default: 'success',
                validator: value => ['success', 'error', 'warning', 'info'].includes(value)
            },
            dismissible: {
                type: Boolean,
                default: true
            }
        },
        emits: ['close']
    };
    
    // Form Field Component
    window.OrbitalFormField = {
        template: `
            <div class="orbital-form-field">
                <label v-if="label" :for="fieldId" class="field-label">
                    {{ label }}
                    <span v-if="required" class="required">*</span>
                </label>
                <div class="field-input">
                    <slot></slot>
                </div>
                <p v-if="description" class="field-description">{{ description }}</p>
                <p v-if="error" class="field-error">{{ error }}</p>
            </div>
        `,
        props: {
            label: String,
            description: String,
            error: String,
            required: Boolean,
            fieldId: String
        }
    };
    
    // Toggle Switch Component
    window.OrbitalToggleSwitch = {
        template: `
            <label class="orbital-toggle-switch">
                <input 
                    type="checkbox" 
                    :checked="modelValue"
                    @change="$emit('update:modelValue', $event.target.checked)"
                    :disabled="disabled"
                >
                <span class="toggle-slider"></span>
                <span v-if="label" class="toggle-label">{{ label }}</span>
            </label>
        `,
        props: {
            modelValue: Boolean,
            label: String,
            disabled: Boolean
        },
        emits: ['update:modelValue']
    };
    
    // Card Component
    window.OrbitalCard = {
        template: `
            <div :class="['orbital-card', { 'card-elevated': elevated, 'card-bordered': bordered }]">
                <div v-if="title || $slots.header" class="card-header">
                    <h3 v-if="title" class="card-title">
                        <span v-if="icon" class="dashicons" :class="icon"></span>
                        {{ title }}
                    </h3>
                    <slot name="header"></slot>
                </div>
                <div class="card-content">
                    <slot></slot>
                </div>
                <div v-if="$slots.footer" class="card-footer">
                    <slot name="footer"></slot>
                </div>
            </div>
        `,
        props: {
            title: String,
            icon: String,
            elevated: Boolean,
            bordered: {
                type: Boolean,
                default: true
            }
        }
    };
    
    // Button Group Component
    window.OrbitalButtonGroup = {
        template: `
            <div class="orbital-button-group">
                <slot></slot>
            </div>
        `
    };
    
    // Tabs Component
    window.OrbitalTabs = {
        template: `
            <div class="orbital-tabs-container">
                <div class="orbital-tabs-nav">
                    <button 
                        v-for="tab in tabs" 
                        :key="tab.id"
                        @click="setActiveTab(tab.id)"
                        :class="['orbital-tab-button', { active: activeTab === tab.id }]"
                        :disabled="tab.disabled"
                    >
                        <span v-if="tab.icon" class="dashicons" :class="tab.icon"></span>
                        {{ tab.title }}
                        <span v-if="tab.badge" class="tab-badge">{{ tab.badge }}</span>
                    </button>
                </div>
                <div class="orbital-tabs-content">
                    <slot></slot>
                </div>
            </div>
        `,
        props: {
            tabs: {
                type: Array,
                required: true
            },
            modelValue: String
        },
        emits: ['update:modelValue', 'tab-change'],
        data() {
            return {
                activeTab: this.modelValue || (this.tabs[0] && this.tabs[0].id)
            };
        },
        watch: {
            modelValue(newValue) {
                this.activeTab = newValue;
            },
            activeTab(newValue) {
                this.$emit('update:modelValue', newValue);
                this.$emit('tab-change', newValue);
            }
        },
        methods: {
            setActiveTab(tabId) {
                const tab = this.tabs.find(t => t.id === tabId);
                if (tab && !tab.disabled) {
                    this.activeTab = tabId;
                }
            }
        }
    };
    
    // Tab Panel Component
    window.OrbitalTabPanel = {
        template: `
            <div v-show="isActive" class="orbital-tab-panel">
                <slot></slot>
            </div>
        `,
        props: {
            tabId: {
                type: String,
                required: true
            }
        },
        inject: ['activeTab'],
        computed: {
            isActive() {
                return this.activeTab === this.tabId;
            }
        }
    };
}

/**
 * CSS for shared components (to be included in main styles)
 */
window.OrbitalComponentsCSS = `
/* Loading Spinner */
.orbital-loading-spinner {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 200px;
}

.spinner-wrapper {
    text-align: center;
}

.spinner-wrapper p {
    margin-top: 15px;
    color: #666;
}

/* Status Message */
.orbital-status-message {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    border-radius: 6px;
    margin: 15px 0;
    font-weight: 500;
    animation: slideInDown 0.3s ease;
}

.orbital-status-message.success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.orbital-status-message.error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.orbital-status-message.warning {
    background: #fff3cd;
    color: #856404;
    border: 1px solid #ffeaa7;
}

.orbital-status-message.info {
    background: #d1ecf1;
    color: #0c5460;
    border: 1px solid #bee5eb;
}

.message-close {
    background: none;
    border: none;
    cursor: pointer;
    color: inherit;
    opacity: 0.7;
    margin-left: 10px;
}

.message-close:hover {
    opacity: 1;
}

/* Form Field */
.orbital-form-field {
    margin-bottom: 20px;
}

.field-label {
    display: block;
    font-weight: 600;
    color: #333;
    margin-bottom: 6px;
    font-size: 14px;
}

.field-label .required {
    color: #dc3545;
}

.field-description {
    margin: 6px 0 0 0;
    color: #666;
    font-size: 13px;
    line-height: 1.4;
}

.field-error {
    margin: 6px 0 0 0;
    color: #dc3545;
    font-size: 13px;
    font-weight: 500;
}

/* Toggle Switch */
.orbital-toggle-switch {
    display: flex;
    align-items: center;
    cursor: pointer;
    user-select: none;
}

.orbital-toggle-switch input {
    position: absolute;
    opacity: 0;
    cursor: pointer;
    height: 0;
    width: 0;
}

.toggle-slider {
    position: relative;
    display: inline-block;
    width: 44px;
    height: 24px;
    background-color: #ccc;
    border-radius: 24px;
    transition: 0.4s;
    margin-right: 8px;
}

.toggle-slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    border-radius: 50%;
    transition: 0.4s;
}

.orbital-toggle-switch input:checked + .toggle-slider {
    background-color: #007cba;
}

.orbital-toggle-switch input:checked + .toggle-slider:before {
    transform: translateX(20px);
}

.orbital-toggle-switch input:disabled + .toggle-slider {
    opacity: 0.5;
    cursor: not-allowed;
}

.toggle-label {
    font-size: 14px;
    color: #333;
}

/* Card */
.orbital-card {
    background: white;
    border-radius: 8px;
    overflow: hidden;
}

.orbital-card.card-bordered {
    border: 1px solid #e0e0e0;
}

.orbital-card.card-elevated {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.card-header {
    padding: 20px 24px 0 24px;
    border-bottom: 1px solid #f0f0f0;
    margin-bottom: 20px;
}

.card-title {
    margin: 0 0 20px 0;
    font-size: 18px;
    font-weight: 600;
    color: #333;
    display: flex;
    align-items: center;
    gap: 8px;
}

.card-content {
    padding: 0 24px 24px 24px;
}

.card-footer {
    padding: 0 24px 24px 24px;
    border-top: 1px solid #f0f0f0;
    margin-top: 20px;
    padding-top: 20px;
}

/* Button Group */
.orbital-button-group {
    display: flex;
    gap: 8px;
    align-items: center;
}

/* Tabs */
.orbital-tabs-container {
    border-radius: 8px;
    overflow: hidden;
}

.orbital-tabs-nav {
    display: flex;
    background: #f8f9fa;
    border-bottom: 1px solid #e0e0e0;
}

.orbital-tab-button {
    flex: 1;
    padding: 12px 16px;
    border: none;
    background: transparent;
    color: #666;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    border-bottom: 2px solid transparent;
}

.orbital-tab-button:hover:not(:disabled) {
    background: #e9ecef;
    color: #495057;
}

.orbital-tab-button.active {
    background: white;
    color: #007cba;
    border-bottom-color: #007cba;
}

.orbital-tab-button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.tab-badge {
    background: #007cba;
    color: white;
    font-size: 11px;
    padding: 2px 6px;
    border-radius: 10px;
    margin-left: 4px;
}

.orbital-tabs-content {
    background: white;
    min-height: 200px;
}

.orbital-tab-panel {
    padding: 24px;
}

@keyframes slideInDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
`;

// Auto-inject CSS if in admin
if (typeof window.wp !== 'undefined' && window.wp.hooks) {
    document.addEventListener('DOMContentLoaded', function() {
        const style = document.createElement('style');
        style.textContent = window.OrbitalComponentsCSS;
        document.head.appendChild(style);
    });
}