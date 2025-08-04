/**
 * Admin Menu Dividers JavaScript
 * 
 * Handles admin functionality for adding dividers to menus
 */

document.addEventListener('DOMContentLoaded', function() {
    const addDividerBtn = document.getElementById('add-divider-btn');
    const dividerNonce = document.getElementById('divider-nonce');
    const menuIdField = document.getElementById('divider-menu-id');

    if (addDividerBtn && dividerNonce && menuIdField) {
        addDividerBtn.addEventListener('click', function() {
            const menuId = menuIdField.value;
            const nonce = dividerNonce.value;

            if (!menuId || menuId == '0') {
                alert('Please select a menu first.');
                return;
            }

            // Disable button during request
            addDividerBtn.disabled = true;
            addDividerBtn.textContent = 'Adding...';

            // Prepare form data
            const formData = new FormData();
            formData.append('action', 'add_menu_divider');
            formData.append('menu_id', menuId);
            formData.append('nonce', nonce);

            // Send AJAX request
            fetch(ajaxurl, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload the page to show the new divider
                    window.location.reload();
                } else {
                    alert('Error: ' + (data.data || 'Failed to add divider'));
                    // Re-enable button
                    addDividerBtn.disabled = false;
                    addDividerBtn.textContent = 'Add Divider';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while adding the divider.');
                // Re-enable button
                addDividerBtn.disabled = false;
                addDividerBtn.textContent = 'Add Divider';
            });
        });
    }
});