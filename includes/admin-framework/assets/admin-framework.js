/**
 * Orbital Admin Framework JavaScript
 * 
 * Pure vanilla JavaScript for handling form submission, AJAX requests,
 * and interactive functionality for admin pages built with the framework.
 */

(function() {
    'use strict';

    /**
     * Main framework object
     */
    const OrbitalAdminFramework = {
        
        /**
         * Initialize the framework
         */
        init: function() {
            this.bindEvents();
            this.initTabs();
            this.initNotices();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Form submission
            this.addEventListener('.orbi-admin__settings-form', 'submit', this.handleFormSubmit.bind(this));
            
            // Tab switching
            this.addEventListener('.orbi-admin__tab-link', 'click', this.handleTabSwitch.bind(this));
            
            // Sub-tab switching
            this.addEventListener('.orbi-admin__subtab-link', 'click', this.handleSubTabSwitch.bind(this));
            
            // Notice dismissal
            this.addEventListener('.orbi-notice__dismiss', 'click', this.dismissNotice.bind(this));
        },

        /**
         * Add event listener with delegation
         */
        addEventListener: function(selector, event, handler) {
            document.addEventListener(event, function(e) {
                const target = e.target.closest(selector);
                if (target) {
                    handler.call(this, e, target);
                }
            }.bind(this));
        },

        /**
         * Initialize tab functionality
         */
        initTabs: function() {
            // Show active tab content, hide others
            const activeTab = this.getActiveTab();
            const tabContents = document.querySelectorAll('.orbi-admin__tab-content');
            
            tabContents.forEach(function(content) {
                const tabKey = content.getAttribute('data-tab');
                if (tabKey === activeTab) {
                    content.style.display = 'block';
                    // Initialize sub-tabs for the active tab
                    this.initSubTabsForTab(content);
                } else {
                    content.style.display = 'none';
                }
            }.bind(this));
        },

        /**
         * Initialize notice system
         */
        initNotices: function() {
            // Auto-dismiss success notices after 5 seconds
            setTimeout(function() {
                const successNotices = document.querySelectorAll('.orbi-notice--success');
                successNotices.forEach(function(notice) {
                    notice.style.opacity = '0';
                    setTimeout(function() {
                        if (notice.parentNode) {
                            notice.parentNode.removeChild(notice);
                        }
                    }, 300);
                });
            }, 5000);
        },


        /**
         * Handle form submission via AJAX
         */
        handleFormSubmit: function(e, form) {
            e.preventDefault();
            
            const submitButton = form.querySelector('.button-primary');
            const originalText = submitButton.textContent;
            
            // Show loading state
            this.setLoadingState(true);
            submitButton.textContent = orbitalAdminFramework.labels.loading;
            
            // Collect form data
            const formData = new FormData();
            formData.append('action', 'orbital_admin_save_settings');
            formData.append('nonce', orbitalAdminFramework.nonce);
            formData.append('slug', orbitalAdminFramework.slug);
            
            // Serialize settings data
            const settingsData = this.serializeFormData(form);
            formData.append('settings', JSON.stringify(settingsData));
            
            // Send AJAX request
            fetch(orbitalAdminFramework.ajaxUrl, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => this.handleSaveSuccess(data))
            .catch(error => this.handleSaveError(error))
            .finally(() => {
                // Reset loading state
                this.setLoadingState(false);
                submitButton.textContent = originalText;
            });
        },

        /**
         * Handle successful save
         */
        handleSaveSuccess: function(response) {
            if (response.success) {
                this.showNotice(
                    orbitalAdminFramework.labels.save_success,
                    'success'
                );
            } else {
                this.showNotice(
                    response.data || orbitalAdminFramework.labels.save_error,
                    'error'
                );
            }
        },

        /**
         * Handle save error
         */
        handleSaveError: function(error) {
            console.error('Save error:', error);
            this.showNotice(
                orbitalAdminFramework.labels.save_error,
                'error'
            );
        },

        /**
         * Handle tab switching
         */
        handleTabSwitch: function(e, link) {
            const tabsNav = link.closest('.orbi-admin__tabs-nav');
            
            // Don't follow the link if it's just for switching tabs
            if (tabsNav) {
                e.preventDefault();
                
                const tabKey = link.getAttribute('data-tab');
                
                // Update active states
                const allTabLinks = document.querySelectorAll('.orbi-admin__tab-link');
                allTabLinks.forEach(tabLink => tabLink.classList.remove('orbi-admin__tab-link--active'));
                link.classList.add('orbi-admin__tab-link--active');
                
                // Switch tab content
                const allTabContent = document.querySelectorAll('.orbi-admin__tab-content');
                allTabContent.forEach(content => content.style.display = 'none');
                
                const activeContent = document.querySelector('.orbi-admin__tab-content[data-tab="' + tabKey + '"]');
                if (activeContent) {
                    activeContent.style.display = 'block';
                    
                    // Initialize sub-tabs for this tab
                    this.initSubTabsForTab(activeContent);
                }
                
                // Update URL without page reload for shareable links
                // Creates URLs like: ?page=your-page&tab=tab-key
                if (history.pushState) {
                    const url = new URL(window.location);
                    url.searchParams.set('tab', tabKey);
                    // Clear section when switching tabs (will default to first section)
                    url.searchParams.delete('section');
                    history.pushState(null, '', url.toString());
                }
                
                // Trigger custom event
                const event = new CustomEvent('orbital:tabChanged', { 
                    detail: { tabKey: tabKey } 
                });
                document.dispatchEvent(event);
            }
        },

        /**
         * Initialize sub-tabs for a specific tab content
         * 
         * This function handles URL-based navigation for sub-tabs (sections).
         * It supports deep linking by reading the 'section' URL parameter.
         * 
         * URL Format: ?page=your-page&tab=tab-key&section=section-key
         * Examples:
         *   - ?page=orbital-framework-demo&tab=general&section=options
         *   - ?page=orbital-framework-demo&tab=advanced&section=debug
         * 
         * Behavior:
         *   - If 'section' parameter exists and is valid: activates that section
         *   - If 'section' parameter missing/invalid: activates first section
         *   - If no sections exist: does nothing
         */
        initSubTabsForTab: function(tabContent) {
            const subTabLinks = tabContent.querySelectorAll('.orbi-admin__subtab-link');
            const sectionContents = tabContent.querySelectorAll('.orbi-admin__section-content');
            
            if (subTabLinks.length === 0) return; // No sub-tabs in this tab
            
            // Check if there's a section specified in URL for deep linking
            const urlParams = new URLSearchParams(window.location.search);
            const urlSection = urlParams.get('section');
            
            // Find which sub-tab should be active based on URL or default to first
            let activeSubTab = null;
            let activeSectionKey = null;
            
            if (urlSection) {
                // Try to find the sub-tab with the URL section (deep linking)
                activeSubTab = tabContent.querySelector('.orbi-admin__subtab-link[data-section="' + urlSection + '"]');
                if (activeSubTab) {
                    activeSectionKey = urlSection;
                }
            }
            
            // Fallback: If no URL section or section not found, use first sub-tab
            if (!activeSubTab && subTabLinks.length > 0) {
                activeSubTab = subTabLinks[0];
                activeSectionKey = activeSubTab.getAttribute('data-section');
            }
            
            if (!activeSubTab) return;
            
            // Update sub-tab active states
            subTabLinks.forEach(subTabLink => subTabLink.classList.remove('orbi-admin__subtab-link--active'));
            activeSubTab.classList.add('orbi-admin__subtab-link--active');
            
            // Update section content visibility
            sectionContents.forEach(content => content.style.display = 'none');
            
            const activeContent = tabContent.querySelector('.orbi-admin__section-content[data-section="' + activeSectionKey + '"]');
            if (activeContent) {
                activeContent.style.display = 'block';
            }
        },

        /**
         * Handle sub-tab switching
         */
        handleSubTabSwitch: function(e, link) {
            e.preventDefault();
            
            const sectionKey = link.getAttribute('data-section');
            
            // Find the current active tab to scope the sub-tab switching
            const activeTabContent = document.querySelector('.orbi-admin__tab-content[style*="display: block"]');
            if (!activeTabContent) return;
            
            // Update active states for sub-tabs within the active tab only
            const subTabLinks = activeTabContent.querySelectorAll('.orbi-admin__subtab-link');
            subTabLinks.forEach(subTabLink => subTabLink.classList.remove('orbi-admin__subtab-link--active'));
            link.classList.add('orbi-admin__subtab-link--active');
            
            // Switch section content within the active tab only
            const sectionContents = activeTabContent.querySelectorAll('.orbi-admin__section-content');
            sectionContents.forEach(content => content.style.display = 'none');
            
            const activeContent = activeTabContent.querySelector('.orbi-admin__section-content[data-section="' + sectionKey + '"]');
            if (activeContent) {
                activeContent.style.display = 'block';
            }
            
            // Update URL without page reload for shareable deep links
            // Creates URLs like: ?page=your-page&tab=tab-key&section=section-key
            if (history.pushState) {
                const url = new URL(window.location);
                url.searchParams.set('section', sectionKey);
                history.pushState(null, '', url.toString());
            }
            
            // Trigger custom event
            const event = new CustomEvent('orbital:sectionChanged', { 
                detail: { sectionKey: sectionKey } 
            });
            document.dispatchEvent(event);
        },

        /**
         * Serialize form data into object
         */
        serializeFormData: function(form) {
            const formData = {};
            const elements = form.elements;
            
            for (let i = 0; i < elements.length; i++) {
                const element = elements[i];
                const name = element.name;
                
                // Skip elements without names or non-settings fields
                if (!name || !name.startsWith('settings[')) {
                    continue;
                }
                
                // Extract field name (remove 'settings[' and ']')
                const fieldName = name.replace(/^settings\[/, '').replace(/\]$/, '');
                
                // Handle different input types
                if (element.type === 'checkbox') {
                    formData[fieldName] = element.checked ? element.value : '';
                } else if (element.type === 'radio') {
                    if (element.checked) {
                        formData[fieldName] = element.value;
                    }
                } else if (element.type !== 'submit' && element.type !== 'button') {
                    formData[fieldName] = element.value;
                }
            }
            
            return formData;
        },

        /**
         * Set loading state
         */
        setLoadingState: function(loading) {
            const framework = document.querySelector('.orbi-admin');
            
            if (!framework) return;
            
            if (loading) {
                framework.classList.add('orbi-admin--loading');
            } else {
                framework.classList.remove('orbi-admin--loading');
            }
        },

        /**
         * Show notice
         */
        showNotice: function(message, type = 'info') {
            const container = document.getElementById('orbi-notices-container');
            
            if (!container) return;
            
            // Remove existing notices
            const existingNotices = container.querySelectorAll('.orbi-notice');
            existingNotices.forEach(notice => notice.remove());
            
            // Create new notice
            const notice = document.createElement('div');
            notice.className = 'orbi-notice orbi-notice--' + type;
            notice.innerHTML = '<div class="orbi-notice__content"><p class="orbi-notice__message">' + message + '</p></div>';
            
            // Add dismiss button for non-error notices
            if (type !== 'error') {
                const dismissBtn = document.createElement('button');
                dismissBtn.type = 'button';
                dismissBtn.className = 'orbi-notice__dismiss';
                dismissBtn.innerHTML = '<span class="orbi-notice__dismiss-icon">&times;</span><span class="screen-reader-text">Dismiss this notice.</span>';
                notice.appendChild(dismissBtn);
            }
            
            // Add to container with animation
            notice.style.opacity = '0';
            container.appendChild(notice);
            
            // Fade in
            setTimeout(() => {
                notice.style.transition = 'opacity 0.3s ease';
                notice.style.opacity = '1';
            }, 10);
            
            // Scroll to notice
            this.scrollToElement(container, -50);
        },

        /**
         * Dismiss notice
         */
        dismissNotice: function(e, button) {
            e.preventDefault();
            const notice = button.closest('.orbi-notice');
            
            if (notice) {
                notice.style.transition = 'opacity 0.3s ease';
                notice.style.opacity = '0';
                setTimeout(() => {
                    if (notice.parentNode) {
                        notice.parentNode.removeChild(notice);
                    }
                }, 300);
            }
        },

        /**
         * Smooth scroll to element
         */
        scrollToElement: function(element, offset = 0) {
            if (!element) return;
            
            const elementTop = element.offsetTop + offset;
            const startingY = window.pageYOffset;
            const diff = elementTop - startingY;
            const duration = 300;
            let startTime = null;

            function step(timestamp) {
                if (!startTime) startTime = timestamp;
                
                const progress = Math.min((timestamp - startTime) / duration, 1);
                const ease = progress * (2 - progress); // easeOutQuad
                
                window.scrollTo(0, startingY + (diff * ease));
                
                if (progress < 1) {
                    requestAnimationFrame(step);
                }
            }
            
            requestAnimationFrame(step);
        },

        /**
         * Get currently active tab
         */
        getActiveTab: function() {
            const urlParams = new URLSearchParams(window.location.search);
            const urlTab = urlParams.get('tab');
            
            if (urlTab) return urlTab;
            
            const activeLink = document.querySelector('.orbi-admin__tab-link--active');
            return activeLink ? activeLink.getAttribute('data-tab') : '';
        },

        /**
         * Utility: Get setting value
         */
        getSetting: function(settingKey) {
            const field = document.querySelector('[name="settings[' + settingKey + ']"]');
            
            if (!field) return null;
            
            if (field.type === 'checkbox') {
                return field.checked ? field.value : '';
            } else {
                return field.value;
            }
        },

        /**
         * Utility: Set setting value
         */
        setSetting: function(settingKey, value) {
            const field = document.querySelector('[name="settings[' + settingKey + ']"]');
            
            if (!field) return false;
            
            if (field.type === 'checkbox') {
                field.checked = !!value;
            } else {
                field.value = value;
            }
            
            return true;
        }
    };

    /**
     * Initialize when DOM is ready
     */
    function initFramework() {
        // Only initialize on admin framework pages
        if (document.querySelector('.orbi-admin')) {
            OrbitalAdminFramework.init();
        }
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFramework);
    } else {
        initFramework();
    }

    /**
     * Make framework object globally available
     */
    window.OrbitalAdminFramework = OrbitalAdminFramework;

})();