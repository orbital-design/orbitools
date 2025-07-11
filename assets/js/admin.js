document.addEventListener('DOMContentLoaded', function() {
    'use strict';
    
    // Initialize admin functionality
    TUC_Admin.init();
    
});

var TUC_Admin = {
    
    init: function() {
        this.bindEvents();
        this.initPreview();
        this.initTooltips();
        this.initAnimations();
    },
    
    bindEvents: function() {
        // Toggle switch animations
        document.querySelectorAll('.tuc-toggle-switch input[type="checkbox"]').forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                var slider = this.parentNode.querySelector('.tuc-slider');
                if (this.checked) {
                    slider.classList.add('checked');
                } else {
                    slider.classList.remove('checked');
                }
            });
        });
        
        // Checkbox animations
        document.querySelectorAll('.tuc-checkbox-item input[type="checkbox"]').forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                var item = this.closest('.tuc-checkbox-item');
                if (this.checked) {
                    item.classList.add('checked');
                } else {
                    item.classList.remove('checked');
                }
            });
        });
        
        // Settings card hover effects
        document.querySelectorAll('.tuc-settings-card').forEach(function(card) {
            card.addEventListener('mouseenter', function() {
                this.classList.add('hovered');
            });
            card.addEventListener('mouseleave', function() {
                this.classList.remove('hovered');
            });
        });
        
        // Form validation
        document.querySelectorAll('form').forEach(function(form) {
            form.addEventListener('submit', function(e) {
                if (!TUC_Admin.validateForm()) {
                    e.preventDefault();
                    TUC_Admin.showNotice('Please check your settings before saving.', 'error');
                } else {
                    TUC_Admin.showNotice('Saving settings...', 'info');
                }
            });
        });
        
        // Custom CSS editor enhancements
        var customCssElement = document.getElementById('custom_css');
        if (customCssElement) {
            customCssElement.addEventListener('input', function() {
                TUC_Admin.updatePreview();
            });
        }
    },
    
    validateForm: function() {
        var valid = true;
        
        // Check if at least one block is selected
        if (document.querySelectorAll('input[name="tuc_options[allowed_blocks][]"]:checked').length === 0) {
            TUC_Admin.showNotice('Please select at least one block type.', 'warning');
            valid = false;
        }
        
        // Check if at least one utility category is selected
        if (document.querySelectorAll('input[name="tuc_options[utility_categories][]"]:checked').length === 0) {
            TUC_Admin.showNotice('Please select at least one utility category.', 'warning');
            valid = false;
        }
        
        return valid;
    },
    
    updatePreview: function() {
        var customCssElement = document.getElementById('custom_css');
        var customCSS = customCssElement ? customCssElement.value : '';
        
        // Remove existing custom preview styles
        var existingStyles = document.getElementById('tuc-custom-preview-styles');
        if (existingStyles) {
            existingStyles.remove();
        }
        
        if (customCSS.trim()) {
            // Add custom styles to preview
            var style = document.createElement('style');
            style.id = 'tuc-custom-preview-styles';
            style.textContent = customCSS;
            document.head.appendChild(style);
        }
    },
    
    initPreview: function() {
        // Add interactive preview functionality
        var previewItems = document.querySelectorAll('.tuc-preview-item');
        
        previewItems.forEach(function(item) {
            var text = item.querySelector('.tuc-preview-text');
            if (text) {
                // Add click-to-edit functionality
                text.setAttribute('contenteditable', 'true');
                text.addEventListener('focus', function() {
                    this.classList.add('editing');
                });
                text.addEventListener('blur', function() {
                    this.classList.remove('editing');
                });
            }
        });
    },
    
    initTooltips: function() {
        // Add tooltips to help icons
        document.querySelectorAll('.tuc-help-text').forEach(function(help) {
            var parent = help.closest('.tuc-field');
            if (parent) {
                // Create tooltip trigger
                var tooltip = document.createElement('span');
                tooltip.className = 'tuc-tooltip-trigger dashicons dashicons-editor-help';
                var label = parent.querySelector('label');
                if (label) {
                    label.appendChild(tooltip);
                }
                
                // Tooltip behavior
                tooltip.addEventListener('mouseenter', function() {
                    var content = document.createElement('div');
                    content.className = 'tuc-tooltip';
                    content.textContent = help.textContent;
                    document.body.appendChild(content);
                    
                    var rect = this.getBoundingClientRect();
                    content.style.position = 'absolute';
                    content.style.top = (rect.top + window.scrollY - content.offsetHeight - 10) + 'px';
                    content.style.left = (rect.left + window.scrollX - (content.offsetWidth / 2) + (this.offsetWidth / 2)) + 'px';
                    content.style.zIndex = '9999';
                });
                
                tooltip.addEventListener('mouseleave', function() {
                    document.querySelectorAll('.tuc-tooltip').forEach(function(tooltip) {
                        tooltip.remove();
                    });
                });
            }
        });
    },
    
    initAnimations: function() {
        // Staggered animation for settings cards
        document.querySelectorAll('.tuc-settings-card').forEach(function(card, index) {
            card.style.animationDelay = (index * 0.1) + 's';
        });
        
        // Smooth scrolling for internal links
        document.querySelectorAll('a[href^="#"]').forEach(function(link) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                var targetId = this.getAttribute('href').substring(1);
                var target = document.getElementById(targetId);
                if (target) {
                    var targetPosition = target.offsetTop - 100;
                    window.scrollTo({
                        top: targetPosition,
                        behavior: 'smooth'
                    });
                }
            });
        });
    },
    
    showNotice: function(message, type) {
        type = type || 'info';
        
        var notice = document.createElement('div');
        notice.className = 'tuc-notice tuc-notice-' + type;
        notice.textContent = message;
        
        // Remove existing notices
        document.querySelectorAll('.tuc-notice').forEach(function(existingNotice) {
            existingNotice.remove();
        });
        
        // Add new notice
        var adminContent = document.querySelector('.tuc-admin-content');
        if (adminContent) {
            adminContent.insertBefore(notice, adminContent.firstChild);
        }
        
        // Auto-hide after 5 seconds
        setTimeout(function() {
            notice.style.opacity = '0';
            notice.style.transition = 'opacity 0.3s';
            setTimeout(function() {
                if (notice.parentNode) {
                    notice.remove();
                }
            }, 300);
        }, 5000);
    }
};

// Utility functions
window.TUC_Utils = {
    
    formatClassName: function(className) {
        return className.replace(/[^a-z0-9-]/gi, '').toLowerCase();
    },
    
    generatePreviewCode: function(classes) {
        return '.' + classes.join(' .');
    },
    
    copyToClipboard: function(text) {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(function() {
                TUC_Admin.showNotice('Copied to clipboard!', 'success');
            });
        } else {
            // Fallback for older browsers
            var textArea = document.createElement('textarea');
            textArea.value = text;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            TUC_Admin.showNotice('Copied to clipboard!', 'success');
        }
    }
};