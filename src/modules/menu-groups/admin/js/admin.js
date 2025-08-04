/**
 * Menu Groups Admin JavaScript
 * 
 * Handles AJAX functionality for adding menu groups
 */

document.addEventListener('DOMContentLoaded', function() {
    const addGroupBtn = document.getElementById('add-group-btn');
    
    if (!addGroupBtn) {
        return;
    }
    
    addGroupBtn.addEventListener('click', function() {
        const titleInput = document.getElementById('group-title');
        const nonceInput = document.getElementById('group-nonce');
        const menuIdInput = document.getElementById('menu-id');
        
        const title = titleInput.value.trim();
        const nonce = nonceInput.value;
        let menuId = menuIdInput.value;
        
        if (!title) {
            alert('Please enter a group name');
            return;
        }
        
        // Try to get menu ID from URL if not in hidden input
        if (!menuId || menuId === '0') {
            const urlParams = new URLSearchParams(window.location.search);
            menuId = urlParams.get('menu');
        }
        
        // Try to get from the menu select dropdown
        if (!menuId || menuId === '0') {
            const menuSelect = document.querySelector('#nav-menu-theme-location-primary, #nav-menu-theme-location-secondary, select[name="menu"]');
            if (menuSelect && menuSelect.value) {
                menuId = menuSelect.value;
            }
        }
        
        if (!menuId || menuId === '0') {
            alert('No menu selected. Please select a menu first.');
            return;
        }
        
        // Disable button during request
        addGroupBtn.disabled = true;
        addGroupBtn.textContent = 'Adding...';
        
        // Prepare form data
        const formData = new FormData();
        formData.append('action', 'add_menu_group');
        formData.append('group_title', title);
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
                // Clear input and reload page
                titleInput.value = '';
                window.location.reload();
            } else {
                alert(data.data || 'Failed to add group');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        })
        .finally(() => {
            // Re-enable button
            addGroupBtn.disabled = false;
            addGroupBtn.textContent = menuGroupsAdmin.addGroupText;
        });
    });
});