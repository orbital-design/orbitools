/**
 * Menu Groups Item Processor
 * 
 * Handles processing of menu items to add proper classes and labels
 */

document.addEventListener('DOMContentLoaded', function() {
    // Function to process group items
    function processGroupItems() {
        const itemTypes = document.querySelectorAll('.menu-item .item-type');
        
        itemTypes.forEach(function(itemType) {
            if (itemType.textContent.trim() === 'Group') {
                const menuItem = itemType.closest('.menu-item');
                if (menuItem && !menuItem.classList.contains('menu-item-group')) {
                    menuItem.classList.add('menu-item-group');
                    
                    // Change "Navigation Label" to "Group Name"
                    const labels = menuItem.querySelectorAll('label');
                    labels.forEach(function(label) {
                        if (label.textContent.includes('Navigation Label')) {
                            label.innerHTML = label.innerHTML.replace('Navigation Label', 'Group Name');
                        }
                    });
                }
            }
        });
    }
    
    // Run on page load
    processGroupItems();
    
    // Set up mutation observer with specific targeting
    if (window.MutationObserver) {
        const observer = new MutationObserver(function(mutations) {
            let shouldReprocess = false;
            
            mutations.forEach(function(mutation) {
                // Only process mutations that affect menu items
                if (mutation.type === 'childList') {
                    // Check if nodes were added to menu structure
                    if (mutation.addedNodes.length > 0) {
                        for (let node of mutation.addedNodes) {
                            if (node.nodeType === Node.ELEMENT_NODE) {
                                // Check if it's a menu item or contains menu items
                                if (node.classList.contains('menu-item') || 
                                    node.querySelector('.menu-item') ||
                                    node.classList.contains('menu-item-settings') ||
                                    node.querySelector('.item-type')) {
                                    shouldReprocess = true;
                                    break;
                                }
                            }
                        }
                    }
                } else if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                    // Only care about class changes on menu items
                    if (mutation.target.classList.contains('menu-item') ||
                        mutation.target.closest('.menu-item')) {
                        shouldReprocess = true;
                    }
                }
            });
            
            if (shouldReprocess) {
                setTimeout(processGroupItems, 100);
            }
        });
        
        // Only observe the menu editor area
        const menuEditor = document.querySelector('#menu-to-edit');
        if (menuEditor) {
            observer.observe(menuEditor, {
                childList: true,
                subtree: true,
                attributes: true,
                attributeFilter: ['class']
            });
        }
    }
    
    // Handle menu item expansion/collapse
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('item-edit') || e.target.classList.contains('item-close')) {
            setTimeout(processGroupItems, 100);
        }
    });
});