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
    
})();