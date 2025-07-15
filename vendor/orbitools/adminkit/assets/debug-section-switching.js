/**
 * Debug script for section switching functionality
 *
 * This script can be temporarily included to debug section navigation issues.
 * Add this script to the admin page to test section switching.
 */
(function() {
    'use strict';

    // Wait for DOM and framework to be ready
    document.addEventListener('DOMContentLoaded', function() {
        console.log('🔍 Debug: Section switching debug script loaded');

        // Test section link detection
        const sectionLinks = document.querySelectorAll('.orbi-admin__subtab-link');
        console.log('🔍 Debug: Found', sectionLinks.length, 'section links');

        sectionLinks.forEach(function(link, index) {
            const sectionKey = link.getAttribute('data-section');
            console.log('🔍 Debug: Section link', index + 1, '- data-section:', sectionKey, '- text:', link.textContent.trim());
        });

        // Test active tab detection
        function debugActiveTab() {
            const urlParams = new URLSearchParams(window.location.search);
            const urlTab = urlParams.get('tab');
            const activeTabLink = document.querySelector('.orbi-admin__tab-link--active');
            const activeTabContent = document.querySelector('.orbi-admin__tab-content[style*="display: block"]');

            console.log('🔍 Debug: URL tab parameter:', urlTab);
            console.log('🔍 Debug: Active tab link:', activeTabLink ? activeTabLink.getAttribute('data-tab') : 'none');
            console.log('🔍 Debug: Active tab content (old method):', activeTabContent ? activeTabContent.getAttribute('data-tab') : 'none');

            // Test the new method
            if (window.OrbitoolsAdminKit) {
                const currentTab = window.OrbitoolsAdminKit.getActiveTab();
                const newActiveTabContent = document.querySelector('.orbi-admin__tab-content[data-tab="' + currentTab + '"]');
                console.log('🔍 Debug: Current tab (new method):', currentTab);
                console.log('🔍 Debug: Active tab content (new method):', newActiveTabContent ? newActiveTabContent.getAttribute('data-tab') : 'none');
            }
        }

        // Test section content detection
        function debugSectionContent() {
            const sectionContents = document.querySelectorAll('.orbi-admin__section-content');
            console.log('🔍 Debug: Found', sectionContents.length, 'section content containers');

            sectionContents.forEach(function(content, index) {
                const sectionKey = content.getAttribute('data-section');
                const isVisible = content.style.display !== 'none';
                console.log('🔍 Debug: Section content', index + 1, '- data-section:', sectionKey, '- visible:', isVisible);
            });
        }

        // Run initial debug
        setTimeout(function() {
            console.log('🔍 Debug: Running initial diagnostics...');
            debugActiveTab();
            debugSectionContent();
        }, 100);

        // Monitor section clicks
        document.addEventListener('click', function(e) {
            const sectionLink = e.target.closest('.orbi-admin__subtab-link');
            if (sectionLink) {
                console.log('🔍 Debug: Section link clicked:', sectionLink.getAttribute('data-section'));

                // Debug after click
                setTimeout(function() {
                    console.log('🔍 Debug: After section click...');
                    debugActiveTab();
                    debugSectionContent();
                }, 50);
            }
        });

        // Monitor custom events
        document.addEventListener('orbital:sectionChanged', function(e) {
            console.log('🔍 Debug: orbital:sectionChanged event fired:', e.detail);
        });

        document.addEventListener('orbital:tabChanged', function(e) {
            console.log('🔍 Debug: orbital:tabChanged event fired:', e.detail);
        });
    });
})();
