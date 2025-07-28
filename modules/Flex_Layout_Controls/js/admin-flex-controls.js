/**
 * Flex Layout Controls Admin JavaScript
 *
 * Handles cache clearing functionality for the Flex Layout Controls module.
 *
 * @package    Orbitools
 * @subpackage Modules/Flex_Layout_Controls
 */

(function() {
    'use strict';
    
    // Wait for DOM to be ready
    document.addEventListener('DOMContentLoaded', function() {
        initClearCacheButton();
    });
    
    /**
     * Initialize clear cache button functionality
     */
    function initClearCacheButton() {
        const clearCacheBtn = document.getElementById('orbitools-clear-flex-cache');
        const resultDiv = document.getElementById('orbitools-clear-flex-cache-result');
        
        if (!clearCacheBtn) return;
        
        clearCacheBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Show loading state
            clearCacheBtn.disabled = true;
            clearCacheBtn.textContent = 'Clearing...';
            resultDiv.innerHTML = '';
            
            // Prepare form data
            const formData = new FormData();
            formData.append('action', 'orbitools_clear_flex_cache');
            formData.append('nonce', clearCacheBtn.getAttribute('data-nonce') || '');
            
            // Send AJAX request
            fetch(ajaxurl, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    resultDiv.innerHTML = '<div style="color: #46b450; font-weight: 500;">✓ ' + data.data.message + '</div>';
                } else {
                    resultDiv.innerHTML = '<div style="color: #dc3232; font-weight: 500;">✗ ' + (data.data?.message || 'Failed to clear cache') + '</div>';
                }
                
                // Clear the message after 5 seconds
                setTimeout(() => {
                    resultDiv.innerHTML = '';
                }, 5000);
            })
            .catch(error => {
                resultDiv.innerHTML = '<div style="color: #dc3232; font-weight: 500;">✗ Error: ' + error.message + '</div>';
                console.error('Error clearing flex cache:', error);
                
                // Clear the message after 5 seconds
                setTimeout(() => {
                    resultDiv.innerHTML = '';
                }, 5000);
            })
            .finally(() => {
                // Reset button state
                clearCacheBtn.disabled = false;
                clearCacheBtn.textContent = 'Clear Flex CSS Cache';
            });
        });
    }
    
})();