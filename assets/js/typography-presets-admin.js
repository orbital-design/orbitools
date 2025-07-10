/**
 * Typography Presets Admin JavaScript
 * 
 * Handles admin interface interactions for managing typography presets
 */

(function($) {
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
        $('#orbital-new-preset-form').on('submit', handleNewPresetSubmit);
        
        // Handle preset deletion
        $(document).on('click', '.orbital-delete-preset', handlePresetDelete);
        
        // Handle preset property changes for live preview
        $(document).on('input change', '.orbital-preset-form input, .orbital-preset-form select, .orbital-preset-form textarea', 
            debounce(updateNewPresetPreview, 300));
        
        // Auto-generate preset ID from label
        $('#preset-label').on('input', function() {
            const label = $(this).val();
            const id = generatePresetId(label);
            $('#preset-id').val(id);
        });
    }

    /**
     * Handle new preset form submission
     */
    function handleNewPresetSubmit(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $button = $form.find('button[type="submit"]');
        const originalText = $button.html();
        
        // Disable submit button
        $button.prop('disabled', true).html('<span class="dashicons dashicons-update-alt"></span> Saving...');
        
        // Collect form data
        const formData = {
            action: 'orbital_save_typography_preset',
            nonce: nonce,
            id: $('#preset-id').val(),
            label: $('#preset-label').val(),
            description: $('#preset-description').val(),
            category: $('#preset-category').val(),
            properties: {}
        };
        
        // Collect properties
        $form.find('[name^="properties["]').each(function() {
            const name = $(this).attr('name').match(/properties\[([^\]]+)\]/)[1];
            const value = $(this).val().trim();
            if (value) {
                formData.properties[name] = value;
            }
        });
        
        // Validate
        if (!formData.id || !formData.label) {
            showNotice('Please fill in required fields.', 'error');
            $button.prop('disabled', false).html(originalText);
            return;
        }
        
        // Send AJAX request
        $.post(ajaxUrl, formData)
            .done(function(response) {
                if (response.success) {
                    showNotice('Preset saved successfully!', 'success');
                    
                    // Reset form
                    $form[0].reset();
                    
                    // Refresh page to show new preset
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    showNotice(response.data || 'Failed to save preset.', 'error');
                }
            })
            .fail(function() {
                showNotice('Network error. Please try again.', 'error');
            })
            .always(function() {
                $button.prop('disabled', false).html(originalText);
            });
    }

    /**
     * Handle preset deletion
     */
    function handlePresetDelete(e) {
        e.preventDefault();
        
        const presetId = $(this).data('preset-id');
        const $card = $(this).closest('.orbital-preset-card');
        
        if (!confirm(strings.confirmDelete || 'Are you sure you want to delete this preset?')) {
            return;
        }
        
        // Send AJAX request
        $.post(ajaxUrl, {
            action: 'orbital_delete_typography_preset',
            nonce: nonce,
            id: presetId
        })
        .done(function(response) {
            if (response.success) {
                $card.fadeOut(300, function() {
                    $(this).remove();
                });
                showNotice('Preset deleted successfully!', 'success');
            } else {
                showNotice(response.data || 'Failed to delete preset.', 'error');
            }
        })
        .fail(function() {
            showNotice('Network error. Please try again.', 'error');
        });
    }

    /**
     * Update preset previews with current CSS
     */
    function updatePresetPreviews() {
        $('.orbital-preset-sample').each(function() {
            const $sample = $(this);
            const classes = $sample.attr('class').split(' ');
            const presetClass = classes.find(cls => cls.startsWith('orbital-preset-'));
            
            if (presetClass) {
                // Apply inline styles for preview (since CSS might not be loaded)
                applyPresetStyles($sample, presetClass);
            }
        });
    }

    /**
     * Apply preset styles to preview element
     */
    function applyPresetStyles($element, presetClass) {
        // This would need to reference the actual preset data
        // For now, we'll let the CSS handle it
        $element.addClass(presetClass);
    }

    /**
     * Update new preset preview
     */
    function updateNewPresetPreview() {
        const properties = {};
        
        $('#orbital-new-preset-form [name^="properties["]').each(function() {
            const name = $(this).attr('name').match(/properties\[([^\]]+)\]/)[1];
            const value = $(this).val().trim();
            if (value) {
                properties[name.replace('_', '-')] = value;
            }
        });
        
        // Create or update preview
        let $preview = $('#orbital-new-preset-preview');
        if ($preview.length === 0) {
            $preview = $('<div id="orbital-new-preset-preview" class="orbital-preset-preview">' +
                '<div class="orbital-preset-sample">Sample text with your custom preset</div>' +
                '</div>');
            $('#orbital-new-preset-form .orbital-preset-properties').after($preview);
        }
        
        // Apply styles to preview
        $preview.find('.orbital-preset-sample').css(properties);
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
        const $notice = $('<div class="notice notice-' + type + ' is-dismissible">' +
            '<p>' + message + '</p>' +
            '<button type="button" class="notice-dismiss">' +
            '<span class="screen-reader-text">Dismiss this notice.</span>' +
            '</button>' +
            '</div>');
        
        $('.orbital-typography-presets-section').prepend($notice);
        
        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            $notice.fadeOut();
        }, 5000);
        
        // Handle dismiss button
        $notice.find('.notice-dismiss').on('click', function() {
            $notice.fadeOut();
        });
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
        const $textarea = $('.orbital-css-output');
        $textarea.select();
        document.execCommand('copy');
        showNotice('CSS copied to clipboard!', 'success');
    }

    // Initialize when DOM is ready
    $(document).ready(init);

    // Export functions for global access
    window.orbitalTypographyPresetsAdmin = {
        copyCSS: copyCSS,
        showNotice: showNotice
    };

})(jQuery);