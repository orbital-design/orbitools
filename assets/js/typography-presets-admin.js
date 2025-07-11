/**
 * Typography Presets Admin JavaScript
 * 
 * Handles admin interface interactions for managing typography presets
 */

(function() {
    'use strict';

    const { ajaxUrl, nonce, strings } = orbitalTypographyPresetsAdmin;

    /**
     * Initialize admin functionality
     */
    function init() {
        bindEvents();
        updatePresetPreviews();
    }

    /**
     * Bind event handlers
     */
    function bindEvents() {
        // Handle new preset form submission
        const newPresetForm = document.getElementById('orbital-new-preset-form');
        if (newPresetForm) {
            newPresetForm.addEventListener('submit', handleNewPresetSubmit);
        }
        
        // Handle preset deletion
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('orbital-delete-preset')) {
                handlePresetDelete(e);
            }
        });
        
        // Handle preset property changes for live preview
        document.addEventListener('input', function(e) {
            if (e.target.matches('.orbital-preset-form input, .orbital-preset-form select, .orbital-preset-form textarea')) {
                debounce(updateNewPresetPreview, 300)();
            }
        });
        
        document.addEventListener('change', function(e) {
            if (e.target.matches('.orbital-preset-form input, .orbital-preset-form select, .orbital-preset-form textarea')) {
                debounce(updateNewPresetPreview, 300)();
            }
        });
        
        // Auto-generate preset ID from label
        const presetLabel = document.getElementById('preset-label');
        if (presetLabel) {
            presetLabel.addEventListener('input', function() {
                const label = this.value;
                const id = generatePresetId(label);
                const presetId = document.getElementById('preset-id');
                if (presetId) {
                    presetId.value = id;
                }
            });
        }
    }

    /**
     * Handle new preset form submission
     */
    function handleNewPresetSubmit(e) {
        e.preventDefault();
        
        const form = e.target;
        const button = form.querySelector('button[type="submit"]');
        const originalText = button.innerHTML;
        
        // Disable submit button
        button.disabled = true;
        button.innerHTML = '<span class="dashicons dashicons-update-alt"></span> Saving...';
        
        // Collect form data
        const formData = {
            action: 'orbital_save_typography_preset',
            nonce: nonce,
            id: document.getElementById('preset-id').value,
            label: document.getElementById('preset-label').value,
            description: document.getElementById('preset-description').value,
            category: document.getElementById('preset-category').value,
            properties: {}
        };
        
        // Collect properties
        form.querySelectorAll('[name^="properties["]').forEach(function(input) {
            const match = input.name.match(/properties\[([^\]]+)\]/);
            if (match) {
                const name = match[1];
                const value = input.value.trim();
                if (value) {
                    formData.properties[name] = value;
                }
            }
        });
        
        // Validate
        if (!formData.id || !formData.label) {
            showNotice('Please fill in required fields.', 'error');
            button.disabled = false;
            button.innerHTML = originalText;
            return;
        }
        
        // Send AJAX request
        const xhr = new XMLHttpRequest();
        xhr.open('POST', ajaxUrl);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        
        xhr.onload = function() {
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        showNotice('Preset saved successfully!', 'success');
                        
                        // Reset form
                        form.reset();
                        
                        // Refresh page to show new preset
                        setTimeout(() => {
                            location.reload();
                        }, 1000);
                    } else {
                        showNotice(response.data || 'Failed to save preset.', 'error');
                    }
                } catch (e) {
                    showNotice('Invalid response from server.', 'error');
                }
            } else {
                showNotice('Network error. Please try again.', 'error');
            }
            
            button.disabled = false;
            button.innerHTML = originalText;
        };
        
        xhr.onerror = function() {
            showNotice('Network error. Please try again.', 'error');
            button.disabled = false;
            button.innerHTML = originalText;
        };
        
        // Convert formData to URL-encoded string
        const params = new URLSearchParams();
        for (const key in formData) {
            if (key === 'properties') {
                for (const prop in formData.properties) {
                    params.append(`properties[${prop}]`, formData.properties[prop]);
                }
            } else {
                params.append(key, formData[key]);
            }
        }
        
        xhr.send(params.toString());
    }

    /**
     * Handle preset deletion
     */
    function handlePresetDelete(e) {
        e.preventDefault();
        
        const presetId = e.target.dataset.presetId;
        const card = e.target.closest('.orbital-preset-card');
        
        if (!confirm(strings.confirmDelete || 'Are you sure you want to delete this preset?')) {
            return;
        }
        
        // Send AJAX request
        const xhr = new XMLHttpRequest();
        xhr.open('POST', ajaxUrl);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        
        xhr.onload = function() {
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        if (card) {
                            card.style.opacity = '0';
                            card.style.transition = 'opacity 0.3s';
                            setTimeout(() => {
                                card.remove();
                            }, 300);
                        }
                        showNotice('Preset deleted successfully!', 'success');
                    } else {
                        showNotice(response.data || 'Failed to delete preset.', 'error');
                    }
                } catch (e) {
                    showNotice('Invalid response from server.', 'error');
                }
            } else {
                showNotice('Network error. Please try again.', 'error');
            }
        };
        
        xhr.onerror = function() {
            showNotice('Network error. Please try again.', 'error');
        };
        
        const params = new URLSearchParams({
            action: 'orbital_delete_typography_preset',
            nonce: nonce,
            id: presetId
        });
        
        xhr.send(params.toString());
    }

    /**
     * Update preset previews with current CSS
     */
    function updatePresetPreviews() {
        document.querySelectorAll('.orbital-preset-sample').forEach(function(sample) {
            const classes = sample.className.split(' ');
            const presetClass = classes.find(cls => cls.startsWith('orbital-preset-'));
            
            if (presetClass) {
                // Apply inline styles for preview (since CSS might not be loaded)
                applyPresetStyles(sample, presetClass);
            }
        });
    }

    /**
     * Apply preset styles to preview element
     */
    function applyPresetStyles(element, presetClass) {
        // This would need to reference the actual preset data
        // For now, we'll let the CSS handle it
        element.classList.add(presetClass);
    }

    /**
     * Update new preset preview
     */
    function updateNewPresetPreview() {
        const properties = {};
        
        document.querySelectorAll('#orbital-new-preset-form [name^="properties["]').forEach(function(input) {
            const match = input.name.match(/properties\[([^\]]+)\]/);
            if (match) {
                const name = match[1];
                const value = input.value.trim();
                if (value) {
                    properties[name.replace('_', '-')] = value;
                }
            }
        });
        
        // Create or update preview
        let preview = document.getElementById('orbital-new-preset-preview');
        if (!preview) {
            preview = document.createElement('div');
            preview.id = 'orbital-new-preset-preview';
            preview.className = 'orbital-preset-preview';
            preview.innerHTML = '<div class="orbital-preset-sample">Sample text with your custom preset</div>';
            
            const propertiesSection = document.querySelector('#orbital-new-preset-form .orbital-preset-properties');
            if (propertiesSection && propertiesSection.parentNode) {
                propertiesSection.parentNode.insertBefore(preview, propertiesSection.nextSibling);
            }
        }
        
        // Apply styles to preview
        const sample = preview.querySelector('.orbital-preset-sample');
        if (sample) {
            // Clear existing styles
            sample.style.cssText = '';
            
            // Apply new styles
            for (const prop in properties) {
                sample.style.setProperty(prop, properties[prop]);
            }
        }
    }

    /**
     * Generate preset ID from label
     */
    function generatePresetId(label) {
        return label
            .toLowerCase()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-')
            .replace(/^-|-$/g, '');
    }

    /**
     * Show admin notice
     */
    function showNotice(message, type = 'info') {
        const notice = document.createElement('div');
        notice.className = 'notice notice-' + type + ' is-dismissible';
        notice.innerHTML = '<p>' + message + '</p>' +
            '<button type="button" class="notice-dismiss">' +
            '<span class="screen-reader-text">Dismiss this notice.</span>' +
            '</button>';
        
        const section = document.querySelector('.orbital-typography-presets-section');
        if (section) {
            section.insertBefore(notice, section.firstChild);
        }
        
        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            notice.style.opacity = '0';
            notice.style.transition = 'opacity 0.3s';
            setTimeout(() => {
                if (notice.parentNode) {
                    notice.remove();
                }
            }, 300);
        }, 5000);
        
        // Handle dismiss button
        const dismissButton = notice.querySelector('.notice-dismiss');
        if (dismissButton) {
            dismissButton.addEventListener('click', function() {
                notice.style.opacity = '0';
                notice.style.transition = 'opacity 0.3s';
                setTimeout(() => {
                    if (notice.parentNode) {
                        notice.remove();
                    }
                }, 300);
            });
        }
    }

    /**
     * Debounce function
     */
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    /**
     * Copy CSS to clipboard
     */
    function copyCSS() {
        const textarea = document.querySelector('.orbital-css-output');
        if (textarea) {
            textarea.select();
            document.execCommand('copy');
            showNotice('CSS copied to clipboard!', 'success');
        }
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Export functions for global access
    window.orbitalTypographyPresetsAdmin = {
        copyCSS: copyCSS,
        showNotice: showNotice
    };

})();