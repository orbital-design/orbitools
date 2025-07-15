/**
 * Typography Presets Admin JavaScript
 *
 * Handles copy-to-clipboard functionality for preset cards.
 *
 * @package    Orbitools
 * @subpackage Modules/Typography_Presets
 */

(function() {
    'use strict';
    
    // Wait for DOM to be ready
    document.addEventListener('DOMContentLoaded', function() {
        initPresetCardCopy();
        initAccordionToggle();
    });
    
    /**
     * Initialize copy functionality for preset cards
     */
    function initPresetCardCopy() {
        const presetCards = document.querySelectorAll('.preset-card');
        
        presetCards.forEach(function(card) {
            card.addEventListener('click', function(e) {
                const copyText = card.getAttribute('data-copy-text');
                if (copyText) {
                    copyToClipboard(copyText);
                    showCopyFeedback(card);
                }
            });
        });
    }
    
    /**
     * Copy text to clipboard
     * @param {string} text - Text to copy
     */
    function copyToClipboard(text) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            // Modern approach
            navigator.clipboard.writeText(text).catch(function(err) {
                console.error('Failed to copy text: ', err);
                fallbackCopyTextToClipboard(text);
            });
        } else {
            // Fallback for older browsers
            fallbackCopyTextToClipboard(text);
        }
    }
    
    /**
     * Fallback copy method for older browsers
     * @param {string} text - Text to copy
     */
    function fallbackCopyTextToClipboard(text) {
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        textArea.style.top = '-999999px';
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        
        try {
            document.execCommand('copy');
        } catch (err) {
            console.error('Fallback: Could not copy text: ', err);
        }
        
        document.body.removeChild(textArea);
    }
    
    /**
     * Show visual feedback when text is copied
     * @param {HTMLElement} card - The preset card element
     */
    function showCopyFeedback(card) {
        // Add copied class to change pseudo-element
        card.classList.add('preset-card--copied');
        
        // Remove after 1.5 seconds
        setTimeout(function() {
            card.classList.remove('preset-card--copied');
        }, 1500);
    }
    
    /**
     * Initialize accordion toggle functionality
     */
    function initAccordionToggle() {
        const accordionToggle = document.querySelector('[data-toggle="presets-accordion"]');
        if (!accordionToggle) return;
        
        accordionToggle.addEventListener('click', function() {
            const accordion = accordionToggle.closest('.presets-accordion');
            const content = accordion.querySelector('.presets-accordion__content');
            const isExpanded = accordion.classList.contains('presets-accordion--expanded');
            
            // Toggle the accordion
            if (isExpanded) {
                // Collapse
                const height = content.scrollHeight;
                content.style.height = height + 'px';
                
                // Force a repaint before setting height to 0
                content.offsetHeight;
                
                content.style.height = '0';
                accordion.classList.remove('presets-accordion--expanded');
                accordionToggle.setAttribute('aria-expanded', 'false');
            } else {
                // Expand
                accordion.classList.add('presets-accordion--expanded');
                accordionToggle.setAttribute('aria-expanded', 'true');
                
                const height = content.scrollHeight;
                content.style.height = height + 'px';
                
                // Remove the height style after transition to allow for dynamic content
                setTimeout(function() {
                    if (accordion.classList.contains('presets-accordion--expanded')) {
                        content.style.height = 'auto';
                    }
                }, 300);
            }
            
            // Save user preference
            saveAccordionState(!isExpanded);
        });
    }
    
    /**
     * Save accordion state to user meta
     * @param {boolean} isExpanded - Whether the accordion is expanded
     */
    function saveAccordionState(isExpanded) {
        // Use WordPress AJAX
        const formData = new FormData();
        formData.append('action', 'orbitools_save_accordion_state');
        formData.append('expanded', isExpanded ? 'true' : 'false');
        formData.append('nonce', getAjaxNonce());
        
        fetch(ajaxurl, {
            method: 'POST',
            body: formData
        }).catch(function(error) {
            console.error('Error saving accordion state:', error);
        });
    }
    
    /**
     * Get AJAX nonce (from WordPress admin)
     * @returns {string} AJAX nonce
     */
    function getAjaxNonce() {
        // Try to get from WordPress admin
        if (typeof window.wpApiSettings !== 'undefined' && window.wpApiSettings.nonce) {
            return window.wpApiSettings.nonce;
        }
        
        // Fallback - generate a basic nonce-like string
        return 'orbitools_nonce_' + Date.now();
    }
    
})();