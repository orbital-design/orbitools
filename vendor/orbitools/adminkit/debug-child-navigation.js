/**
 * Debug Child Navigation
 * 
 * Add this to your browser console to debug child page navigation issues
 */

console.log('=== AdminKit Child Navigation Debug ===');

// Check if AdminKit is loaded
if (typeof window.OrbitoolsAdminKit !== 'undefined') {
    console.log('✓ AdminKit loaded');
} else {
    console.log('✗ AdminKit not loaded');
}

// Check page info
if (typeof adminkit_page_info !== 'undefined') {
    console.log('✓ Page info available:', adminkit_page_info);
} else {
    console.log('✗ Page info not available');
}

// Check orbitools admin kit data
if (typeof orbitoolsAdminKit !== 'undefined') {
    console.log('✓ OrbiTools data available:', orbitoolsAdminKit);
} else {
    console.log('✗ OrbiTools data not available');
}

// Check child page detection
if (typeof window.OrbitoolsAdminKit !== 'undefined' && window.OrbitoolsAdminKit.isChildPage) {
    const isChild = window.OrbitoolsAdminKit.isChildPage();
    console.log('Child page detection result:', isChild);
} else {
    console.log('✗ Child page detection not available');
}

// Check current URL
const urlParams = new URLSearchParams(window.location.search);
const currentPage = urlParams.get('page');
console.log('Current page parameter:', currentPage);

// Check AdminKit elements
const adminKitElements = document.querySelectorAll('.orbi-admin');
console.log('AdminKit elements found:', adminKitElements.length);

// Check tab links
const tabLinks = document.querySelectorAll('.adminkit-nav__item');
console.log('Nav items found:', tabLinks.length);

// Check tab link attributes
tabLinks.forEach((link, index) => {
    console.log(`Nav item ${index}:`, {
        text: link.textContent.trim(),
        href: link.href,
        'data-tab': link.getAttribute('data-tab'),
        classes: link.className
    });
});

// Test click simulation
function testTabClick(index) {
    const link = tabLinks[index];
    if (link) {
        console.log(`Testing click on tab ${index}:`, link.textContent.trim());
        
        // Create a test event
        const event = new MouseEvent('click', {
            bubbles: true,
            cancelable: true,
            view: window
        });
        
        // Dispatch the event
        link.dispatchEvent(event);
    } else {
        console.log(`Tab ${index} not found`);
    }
}

// Make test function available globally
window.testTabClick = testTabClick;

console.log('=== Debug Complete ===');
console.log('To test a tab click, use: testTabClick(0) for first tab, testTabClick(1) for second tab, etc.');