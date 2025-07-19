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
    const OrbitoolsAdminKit = {

        /**
         * Initialize the framework
         */
        init: function() {
            this.bindEvents();
            this.initTabs();
            this.initConditionalFields();
        },


        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Sub-tab switching (main tab switching is handled in the global event listener)
            this.addEventListener('.adminkit-content__sub-link', 'click', this.handleSubTabSwitch.bind(this));
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
            const tabContents = document.querySelectorAll('.adminkit-content__page');
            let activeSection = null;

            tabContents.forEach(function(content) {
                const tabKey = content.getAttribute('data-page');
                if (tabKey === activeTab) {
                    content.style.display = 'block';
                    // Initialize sub-tabs for the active tab and get the active section
                    activeSection = this.initSubTabsForTab(content);
                } else {
                    content.style.display = 'none';
                }
            }.bind(this));

            // Update breadcrumbs for initial page load with the actual active section
            if (activeTab) {
                this.updateBreadcrumbs(activeTab, activeSection);
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
         *
         * @param {HTMLElement} tabContent - The tab content container
         * @returns {string|null} The active section key, or null if no sections
         */
        initSubTabsForTab: function(tabContent) {
            const subTabLinks = tabContent.querySelectorAll('.adminkit-content__sub-link');
            const sectionContents = tabContent.querySelectorAll('.adminkit-content__sub-content');

            if (subTabLinks.length === 0) return null; // No sub-tabs in this tab

            // Check if there's a section specified in URL for deep linking
            const urlParams = new URLSearchParams(window.location.search);
            const urlSection = urlParams.get('section');

            // Find which sub-tab should be active based on URL or default to first
            let activeSubTab = null;
            let activeSectionKey = null;

            if (urlSection) {
                // Try to find the sub-tab with the URL section (deep linking)
                activeSubTab = tabContent.querySelector('.adminkit-content__sub-link[data-section="' + urlSection + '"]');
                if (activeSubTab) {
                    activeSectionKey = urlSection;
                }
            }

            // Fallback: If no URL section or section not found, use first sub-tab
            if (!activeSubTab && subTabLinks.length > 0) {
                activeSubTab = subTabLinks[0];
                activeSectionKey = activeSubTab.getAttribute('data-section');
            }

            if (!activeSubTab) return null;

            // Update sub-tab active states
            subTabLinks.forEach(subTabLink => subTabLink.classList.remove('adminkit-content__sub-link--active'));
            activeSubTab.classList.add('adminkit-content__sub-link--active');

            // Update section content visibility
            sectionContents.forEach(content => content.style.display = 'none');

            const activeContent = tabContent.querySelector('.adminkit-content__sub-content[data-section="' + activeSectionKey + '"]');
            if (activeContent) {
                activeContent.style.display = 'block';
            }

            // Return the active section key so the caller can update breadcrumbs
            return activeSectionKey;
        },

        /**
         * Handle sub-tab switching
         */
        handleSubTabSwitch: function(e, link) {
            e.preventDefault();

            const sectionKey = link.getAttribute('data-section');

            // Find the current active tab to scope the sub-tab switching
            const currentTab = this.getActiveTab();
            const activeTabContent = document.querySelector('.adminkit-content__page[data-page="' + currentTab + '"]');
            if (!activeTabContent) return;

            // Update active states for sub-tabs within the active tab only
            const subTabLinks = activeTabContent.querySelectorAll('.adminkit-content__sub-link');
            subTabLinks.forEach(subTabLink => subTabLink.classList.remove('adminkit-content__sub-link--active'));
            link.classList.add('adminkit-content__sub-link--active');

            // Switch section content within the active tab only
            const sectionContents = activeTabContent.querySelectorAll('.adminkit-content__sub-content');
            sectionContents.forEach(content => content.style.display = 'none');

            const activeContent = activeTabContent.querySelector('.adminkit-content__sub-content[data-section="' + sectionKey + '"]');
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

            // Update breadcrumbs with current tab and new section
            this.updateBreadcrumbs(currentTab, sectionKey);

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
                    if (element.checked) {
                        // Handle multiple checkboxes with same name
                        if (formData[fieldName]) {
                            // If field already exists, add to array
                            if (Array.isArray(formData[fieldName])) {
                                formData[fieldName].push(element.value);
                            } else {
                                // Convert single value to array and add new value
                                formData[fieldName] = [formData[fieldName], element.value];
                            }
                        } else {
                            // Check if there are other checkboxes with the same name (multi-checkbox field)
                            const otherCheckboxes = form.querySelectorAll('input[type="checkbox"][name="' + name + '"]');
                            if (otherCheckboxes.length > 1) {
                                // Multi-checkbox field - always use array
                                formData[fieldName] = [element.value];
                            } else {
                                // Single checkbox field - use single value
                                formData[fieldName] = element.value;
                            }
                        }
                    } else {
                        // For unchecked checkboxes, set appropriate empty value if not already set
                        if (!formData.hasOwnProperty(fieldName)) {
                            const otherCheckboxes = form.querySelectorAll('input[type="checkbox"][name="' + name + '"]');
                            if (otherCheckboxes.length > 1) {
                                // Multi-checkbox field - use empty array
                                formData[fieldName] = [];
                            } else {
                                // Single checkbox field - use empty string
                                formData[fieldName] = '';
                            }
                        }
                    }
                } else if (element.type === 'radio') {
                    if (element.checked) {
                        formData[fieldName] = element.value;
                    }
                } else if (element.type === 'select-multiple') {
                    // Handle multi-select
                    const selectedValues = Array.from(element.selectedOptions).map(option => option.value);
                    formData[fieldName] = selectedValues;
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
         * Update breadcrumbs dynamically
         *
         * Updates the breadcrumb navigation to reflect current tab and section.
         * This provides real-time feedback as users navigate through the interface.
         *
         * @param {string} tabKey - Current active tab key
         * @param {string|null} sectionKey - Current active section key (null to clear section)
         */
        updateBreadcrumbs: function(tabKey, sectionKey) {
            const breadcrumbList = document.querySelector('.adminkit-toolbar__breadcrumb-list');
            if (!breadcrumbList) return;

            // Get tab and section data from DOM
            const tabData = this.getTabData();
            const sectionData = this.getSectionData(tabKey);

            // Clear existing breadcrumbs except the first one (page title)
            const items = breadcrumbList.querySelectorAll('.adminkit-toolbar__breadcrumb-item');
            for (let i = 1; i < items.length; i++) {
                items[i].remove();
            }

            // Add current tab breadcrumb
            if (tabKey && tabData[tabKey]) {
                const tabItem = this.createBreadcrumbItem(tabData[tabKey], true);
                breadcrumbList.appendChild(tabItem);

                // Add current section breadcrumb if we have one
                if (sectionKey && sectionData[sectionKey]) {
                    const sectionItem = this.createBreadcrumbItem(sectionData[sectionKey], true);
                    breadcrumbList.appendChild(sectionItem);
                }
            }
        },

        /**
         * Create a breadcrumb item element
         *
         * @param {string} text - Text to display in breadcrumb
         * @param {boolean} isCurrent - Whether this is the current/active item
         * @returns {HTMLElement} Breadcrumb item element
         */
        createBreadcrumbItem: function(text, isCurrent) {
            const item = document.createElement('li');
            item.className = 'adminkit-toolbar__breadcrumb-item';

            // Add separator
            const separator = document.createElement('span');
            separator.className = 'adminkit-toolbar__breadcrumb-separator';
            separator.setAttribute('aria-hidden', 'true');
            separator.textContent = '›';
            item.appendChild(separator);

            // Add text
            const textSpan = document.createElement('span');
            textSpan.className = 'adminkit-toolbar__breadcrumb-text' + (isCurrent ? ' adminkit-toolbar__breadcrumb-text--current' : '');
            textSpan.textContent = text;
            item.appendChild(textSpan);

            return item;
        },

        /**
         * Get tab data from DOM
         *
         * @returns {Object} Object mapping tab keys to tab labels
         */
        getTabData: function() {
            const tabData = {};
            const tabLinks = document.querySelectorAll('.adminkit-nav__item');

            tabLinks.forEach(function(link) {
                const tabKey = link.getAttribute('data-page');
                const tabLabel = link.textContent.trim();
                if (tabKey && tabLabel) {
                    tabData[tabKey] = tabLabel;
                }
            });

            return tabData;
        },

        /**
         * Get section data for a specific tab from DOM
         *
         * @param {string} tabKey - Tab key to get sections for
         * @returns {Object} Object mapping section keys to section labels
         */
        getSectionData: function(tabKey) {
            const sectionData = {};

            // Find the active tab content
            const tabContent = document.querySelector('.adminkit-content__page[data-page="' + tabKey + '"]');
            if (!tabContent) return sectionData;

            // Get section links within this tab
            const sectionLinks = tabContent.querySelectorAll('.adminkit-content__sub-link');

            sectionLinks.forEach(function(link) {
                const sectionKey = link.getAttribute('data-section');
                const sectionLabel = link.textContent.trim();
                if (sectionKey && sectionLabel) {
                    sectionData[sectionKey] = sectionLabel;
                }
            });

            return sectionData;
        },

        /**
         * Get currently active tab
         */
        getActiveTab: function() {
            const urlParams = new URLSearchParams(window.location.search);
            const urlTab = urlParams.get('tab');

            if (urlTab) return urlTab;

            const activeLink = document.querySelector('.adminkit-nav__item--active');
            return activeLink ? activeLink.getAttribute('data-page') : '';
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
        },

        /**
         * Initialize conditional fields functionality
         */
        initConditionalFields: function() {
            const conditionalFields = document.querySelectorAll('.field--conditional[data-show-if]');
            
            if (conditionalFields.length === 0) return;

            // Set up event listeners for all form fields
            document.addEventListener('change', this.handleFieldChange.bind(this));
            document.addEventListener('input', this.handleFieldChange.bind(this));

            // Initial evaluation of all conditional fields
            this.evaluateAllConditionalFields();
        },

        /**
         * Handle field change events for conditional logic
         */
        handleFieldChange: function(e) {
            const field = e.target;
            
            // Only process fields with settings[] names
            if (!field.name || !field.name.startsWith('settings[')) {
                return;
            }

            // Re-evaluate all conditional fields when any field changes
            this.evaluateAllConditionalFields();
        },

        /**
         * Evaluate all conditional fields
         */
        evaluateAllConditionalFields: function() {
            const conditionalFields = document.querySelectorAll('.field--conditional[data-show-if]');
            
            conditionalFields.forEach(field => {
                this.evaluateConditionalField(field);
            });
        },

        /**
         * Evaluate a single conditional field
         */
        evaluateConditionalField: function(fieldElement) {
            try {
                const conditionsData = JSON.parse(fieldElement.getAttribute('data-show-if'));
                const shouldShow = this.evaluateConditions(conditionsData);
                
                if (shouldShow) {
                    this.showField(fieldElement);
                } else {
                    this.hideField(fieldElement);
                }
            } catch (error) {
                console.warn('Invalid conditional field data:', error);
                // Show field by default if conditions are invalid
                this.showField(fieldElement);
            }
        },

        /**
         * Evaluate conditions object
         */
        evaluateConditions: function(conditions) {
            // Handle single condition vs array of conditions
            let conditionsArray;
            let relation = 'AND';

            if (Array.isArray(conditions)) {
                conditionsArray = conditions;
            } else if (conditions.field) {
                // Single condition object
                conditionsArray = [conditions];
            } else if (conditions.relation) {
                // Multiple conditions with relation
                relation = conditions.relation;
                conditionsArray = Object.keys(conditions)
                    .filter(key => key !== 'relation')
                    .map(key => conditions[key])
                    .filter(condition => condition && condition.field);
            } else {
                return true; // Invalid format, show by default
            }

            const results = conditionsArray.map(condition => this.evaluateSingleCondition(condition));

            // Apply relation logic
            if (relation === 'OR') {
                return results.includes(true);
            }

            // Default to AND
            return !results.includes(false);
        },

        /**
         * Evaluate a single condition
         */
        evaluateSingleCondition: function(condition) {
            const fieldId = condition.field;
            const operator = condition.operator || '===';
            const compareValue = condition.value;

            const currentValue = this.getSetting(fieldId);

            switch (operator) {
                case '===':
                    return currentValue === compareValue;

                case '==':
                    return currentValue == compareValue;

                case '!==':
                    return currentValue !== compareValue;

                case '!=':
                    return currentValue != compareValue;

                case '>':
                    return parseFloat(currentValue) > parseFloat(compareValue);

                case '<':
                    return parseFloat(currentValue) < parseFloat(compareValue);

                case '>=':
                    return parseFloat(currentValue) >= parseFloat(compareValue);

                case '<=':
                    return parseFloat(currentValue) <= parseFloat(compareValue);

                case 'contains':
                    return typeof currentValue === 'string' && currentValue.indexOf(compareValue) !== -1;

                case 'in':
                    return Array.isArray(compareValue) && compareValue.includes(currentValue);

                case 'not_in':
                    return Array.isArray(compareValue) && !compareValue.includes(currentValue);

                case 'empty':
                    return !currentValue || currentValue === '' || currentValue === '0';

                case 'not_empty':
                    return !!currentValue && currentValue !== '' && currentValue !== '0';

                default:
                    return true;
            }
        },

        /**
         * Show conditional field
         */
        showField: function(fieldElement) {
            fieldElement.style.display = '';
            fieldElement.classList.remove('field--hidden');
            
            // Re-enable form elements
            const inputs = fieldElement.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                input.disabled = false;
            });
        },

        /**
         * Hide conditional field
         */
        hideField: function(fieldElement) {
            fieldElement.style.display = 'none';
            fieldElement.classList.add('field--hidden');
            
            // Disable form elements to prevent submission
            const inputs = fieldElement.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                input.disabled = true;
            });
        }
    };

    /**
     * Initialize when DOM is ready
     */
    function initFramework() {
        // Only initialize on admin framework pages
        if (document.querySelector('.adminkit')) {
            OrbitoolsAdminKit.init();
        }

        // Simple tab switching for adminkit navigation
        document.addEventListener('click', function(e) {
            if (e.target.matches('.adminkit-nav__item') || e.target.closest('.adminkit-nav__item')) {
                const link = e.target.matches('.adminkit-nav__item') ? e.target : e.target.closest('.adminkit-nav__item');
                const tabKey = link.getAttribute('data-page');

                // Only handle elements with data-page attribute (AdminKit tabs)
                if (tabKey) {
                    e.preventDefault();

                    // Update active states
                    document.querySelectorAll('.adminkit-nav__item').forEach(tabLink => {
                        tabLink.classList.remove('adminkit-nav__item--active');
                    });
                    link.classList.add('adminkit-nav__item--active');

                    // Switch tab content
                    document.querySelectorAll('.adminkit-content__page').forEach(content => {
                        content.style.display = 'none';
                    });

                    const activeContent = document.querySelector('.adminkit-content__page[data-page="' + tabKey + '"]');
                    if (activeContent) {
                        activeContent.style.display = 'block';
                        // Initialize sub-tabs for the newly active tab
                        const activeSection = OrbitoolsAdminKit.initSubTabsForTab(activeContent);

                        // Update breadcrumbs with the active section
                        OrbitoolsAdminKit.updateBreadcrumbs(tabKey, activeSection);
                    } else {
                        // No sub-tabs, just update breadcrumbs with tab
                        OrbitoolsAdminKit.updateBreadcrumbs(tabKey);
                    }

                    // Update URL
                    if (history.pushState) {
                        const url = new URL(window.location);
                        url.searchParams.set('tab', tabKey);
                        history.pushState(null, '', url.toString());
                    }
                }
            }
        });

        // Direct form handler as backup
        const form = document.getElementById('adminkit-settings-form');
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();

                // Check if orbitoolsAdminKit exists
                if (typeof orbitoolsAdminKit === 'undefined') {
                    alert('AdminKit not loaded properly');
                    return;
                }

                // Create FormData from form
                const formData = new FormData(form);


                // Send AJAX request
                fetch(orbitoolsAdminKit.ajaxurl, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Create and show success notice
                        const notice = document.createElement('div');
                        notice.className = 'notice notice-success is-dismissible';
                        notice.innerHTML = '<p>' + (data.data.message || 'Settings saved successfully') + '</p>';

                        // Insert notice at top of content
                        const content = document.querySelector('.adminkit-content, .orbi-admin, #wpbody-content');
                        if (content) {
                            content.insertBefore(notice, content.firstChild);
                        }

                        // Auto-dismiss after 5 seconds
                        setTimeout(() => notice.remove(), 5000);
                    } else {
                        // Show error notice instead of alert
                        const notice = document.createElement('div');
                        notice.className = 'notice notice-error is-dismissible';
                        notice.innerHTML = '<p>Error: ' + (data.data || 'Unknown error') + '</p>';

                        const content = document.querySelector('.adminkit-content, .orbi-admin, #wpbody-content');
                        if (content) {
                            content.insertBefore(notice, content.firstChild);
                        }

                        setTimeout(() => notice.remove(), 8000);
                    }
                })
                .catch(error => {
                    // Show network error notice instead of alert
                    const notice = document.createElement('div');
                    notice.className = 'notice notice-error is-dismissible';
                    notice.innerHTML = '<p>Network error: ' + error.message + '</p>';

                    const content = document.querySelector('.adminkit-content, .orbi-admin, #wpbody-content');
                    if (content) {
                        content.insertBefore(notice, content.firstChild);
                    }

                    setTimeout(() => notice.remove(), 8000);
                });
            });
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
    window.OrbitoolsAdminKit = OrbitoolsAdminKit;

})();
