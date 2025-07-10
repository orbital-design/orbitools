jQuery(document).ready(function($) {
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
        $('.tuc-toggle-switch input[type="checkbox"]').on('change', function() {
            var $slider = $(this).siblings('.tuc-slider');
            if ($(this).is(':checked')) {
                $slider.addClass('checked');
            } else {
                $slider.removeClass('checked');
            }
        });
        
        // Checkbox animations
        $('.tuc-checkbox-item input[type="checkbox"]').on('change', function() {
            var $item = $(this).closest('.tuc-checkbox-item');
            if ($(this).is(':checked')) {
                $item.addClass('checked');
            } else {
                $item.removeClass('checked');
            }
        });
        
        // Settings card hover effects
        $('.tuc-settings-card').hover(
            function() {
                $(this).addClass('hovered');
            },
            function() {
                $(this).removeClass('hovered');
            }
        );
        
        // Form validation
        $('form').on('submit', function(e) {
            if (!TUC_Admin.validateForm()) {
                e.preventDefault();
                TUC_Admin.showNotice('Please check your settings before saving.', 'error');
            } else {
                TUC_Admin.showNotice('Saving settings...', 'info');
            }
        });
        
        // Custom CSS editor enhancements
        $('#custom_css').on('input', function() {
            TUC_Admin.updatePreview();
        });
    },
    
    validateForm: function() {
        var valid = true;
        
        // Check if at least one block is selected
        if ($('input[name="tuc_options[allowed_blocks][]"]:checked').length === 0) {
            TUC_Admin.showNotice('Please select at least one block type.', 'warning');
            valid = false;
        }
        
        // Check if at least one utility category is selected
        if ($('input[name="tuc_options[utility_categories][]"]:checked').length === 0) {
            TUC_Admin.showNotice('Please select at least one utility category.', 'warning');
            valid = false;
        }
        
        return valid;
    },
    
    updatePreview: function() {
        var customCSS = $('#custom_css').val();
        
        // Remove existing custom preview styles
        $('#tuc-custom-preview-styles').remove();
        
        if (customCSS.trim()) {
            // Add custom styles to preview
            $('<style id="tuc-custom-preview-styles">' + customCSS + '</style>').appendTo('head');
        }
    },
    
    initPreview: function() {
        // Add interactive preview functionality
        var $previewItems = $('.tuc-preview-item');
        
        $previewItems.each(function() {
            var $item = $(this);
            var $text = $item.find('.tuc-preview-text');
            
            // Add click-to-edit functionality
            $text.attr('contenteditable', 'true');
            $text.on('focus', function() {
                $(this).addClass('editing');
            }).on('blur', function() {
                $(this).removeClass('editing');
            });
        });
    },
    
    initTooltips: function() {
        // Add tooltips to help icons
        $('.tuc-help-text').each(function() {
            var $help = $(this);
            var $parent = $help.closest('.tuc-field');
            
            // Create tooltip trigger
            var $tooltip = $('<span class="tuc-tooltip-trigger dashicons dashicons-editor-help"></span>');
            $parent.find('label').first().append($tooltip);
            
            // Tooltip behavior
            $tooltip.hover(
                function() {
                    var $content = $('<div class="tuc-tooltip">' + $help.text() + '</div>');
                    $('body').append($content);
                    
                    var offset = $(this).offset();
                    $content.css({
                        'position': 'absolute',
                        'top': offset.top - $content.outerHeight() - 10,
                        'left': offset.left - ($content.outerWidth() / 2) + ($(this).outerWidth() / 2),
                        'z-index': 9999
                    });
                },
                function() {
                    $('.tuc-tooltip').remove();
                }
            );
        });
    },
    
    initAnimations: function() {
        // Staggered animation for settings cards
        $('.tuc-settings-card').each(function(index) {
            $(this).css('animation-delay', (index * 0.1) + 's');
        });
        
        // Smooth scrolling for internal links
        $('a[href^="#"]').on('click', function(e) {
            e.preventDefault();
            var target = $($(this).attr('href'));
            if (target.length) {
                $('html, body').animate({
                    scrollTop: target.offset().top - 100
                }, 500);
            }
        });
    },
    
    showNotice: function(message, type) {
        type = type || 'info';
        
        var $notice = $('<div class="tuc-notice tuc-notice-' + type + '">' + message + '</div>');
        
        // Remove existing notices
        $('.tuc-notice').remove();
        
        // Add new notice
        $('.tuc-admin-content').prepend($notice);
        
        // Auto-hide after 5 seconds
        setTimeout(function() {
            $notice.fadeOut(300, function() {
                $(this).remove();
            });
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