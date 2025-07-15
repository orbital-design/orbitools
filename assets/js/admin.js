/**
 * Orbitools Admin JavaScript
 * 
 * Handles admin functionality including module state change detection
 * and page reload when modules are enabled/disabled.
 */

(function() {
    'use strict';

    /**
     * Orbitools Admin object
     */
    const OrbitoolsAdmin = {
        
        /**
         * Store initial module states for comparison
         */
        initialModuleStates: {},
        
        /**
         * Initialize admin functionality
         */
        init: function() {
            this.storeInitialModuleStates();
            this.bindEvents();
        },
        
        /**
         * Store the initial states of all module checkboxes
         */
        storeInitialModuleStates: function() {
            const moduleCheckboxes = document.querySelectorAll('input[type="checkbox"][name*="_enabled"]');
            
            moduleCheckboxes.forEach(function(checkbox) {
                const fieldName = checkbox.name.replace(/^settings\[/, '').replace(/\]$/, '');
                OrbitoolsAdmin.initialModuleStates[fieldName] = checkbox.checked;
            });
            
            console.log('Orbitools: Initial module states stored', this.initialModuleStates);
        },
        
        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Listen for the orbital admin framework's save success event
            document.addEventListener('orbital:settingsSaved', this.handleSettingsSaved.bind(this));
            
            // Also listen for direct AJAX success if the framework doesn't fire custom events
            this.interceptAjaxSuccess();
        },
        
        /**
         * Handle successful settings save
         */
        handleSettingsSaved: function(event) {
            const responseData = event.detail || {};
            
            if (responseData.modules_changed) {
                this.reloadPage('Modules have been updated. Reloading page to initialize changes...');
            }
        },
        
        /**
         * Intercept AJAX success responses to check for module changes
         */
        interceptAjaxSuccess: function() {
            // Store the original fetch function
            const originalFetch = window.fetch;
            
            // Override fetch to intercept responses
            window.fetch = function(...args) {
                return originalFetch.apply(this, args)
                    .then(function(response) {
                        // Clone the response so we can read it without consuming it
                        const responseClone = response.clone();
                        
                        // Check if this is our settings save AJAX call
                        if (args[0] === orbiAdminKit.ajaxUrl && args[1] && args[1].body) {
                            const formData = args[1].body;
                            
                            // Check if this is a settings save action
                            if (formData.get && formData.get('action') === 'orbi_admin_save_settings_orbitools') {
                                responseClone.json().then(function(data) {
                                    if (data.success) {
                                        // Check if we have detected module changes
                                        if (OrbitoolsAdmin.hasModuleChanges()) {
                                            const changes = OrbitoolsAdmin.getModuleChanges();
                                            const changeDetails = OrbitoolsAdmin.formatModuleChanges(changes);
                                            
                                            OrbitoolsAdmin.reloadPage(
                                                'Modules have been updated: ' + changeDetails + '. Reloading page to initialize changes...'
                                            );
                                        }
                                    }
                                }).catch(function(error) {
                                    console.log('Orbitools: Error parsing AJAX response', error);
                                });
                            }
                        }
                        
                        return response;
                    });
            };
        },
        
        /**
         * Compare current module states with initial states
         */
        getModuleChanges: function() {
            const changes = {};
            const moduleCheckboxes = document.querySelectorAll('input[type="checkbox"][name*="_enabled"]');
            
            moduleCheckboxes.forEach(function(checkbox) {
                const fieldName = checkbox.name.replace(/^settings\[/, '').replace(/\]$/, '');
                const currentState = checkbox.checked;
                const initialState = OrbitoolsAdmin.initialModuleStates[fieldName];
                
                if (currentState !== initialState) {
                    changes[fieldName] = {
                        from: initialState,
                        to: currentState
                    };
                }
            });
            
            return changes;
        },
        
        /**
         * Check if any modules have changed state
         */
        hasModuleChanges: function() {
            const changes = this.getModuleChanges();
            return Object.keys(changes).length > 0;
        },

        /**
         * Format module changes for display in notification
         */
        formatModuleChanges: function(changes) {
            const changeList = [];
            
            for (const moduleId in changes) {
                if (changes.hasOwnProperty(moduleId)) {
                    const change = changes[moduleId];
                    const moduleName = moduleId.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                    changeList.push(moduleName + ' ' + change.action);
                }
            }
            
            return changeList.join(', ') || 'modules updated';
        },
        
        /**
         * Reload the page with a notice
         */
        reloadPage: function(message) {
            console.log('Orbitools: ' + message);
            
            // Show a brief notice before reloading
            if (window.OrbitalAdminFramework) {
                window.OrbitalAdminFramework.showNotice(message, 'info');
            }
            
            // Add loading state to prevent further interactions
            const adminContainer = document.querySelector('.orbi-admin');
            if (adminContainer) {
                adminContainer.classList.add('orbi-admin--reloading');
            }
            
            // Reload after a short delay to allow the notice to be seen
            setTimeout(function() {
                window.location.reload();
            }, 1500);
        }
    };

    /**
     * Initialize when DOM is ready
     */
    function initOrbitoolsAdmin() {
        // Only initialize on orbitools admin pages
        if (document.querySelector('.orbi-admin') && window.orbiAdminKit && orbiAdminKit.slug === 'orbitools') {
            OrbitoolsAdmin.init();
        }
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initOrbitoolsAdmin);
    } else {
        initOrbitoolsAdmin();
    }

    // Make available globally for debugging
    window.OrbitoolsAdmin = OrbitoolsAdmin;

})();