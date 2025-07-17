/**
 * Layout Guides JavaScript
 *
 * Handles the interactive functionality for the Layout Guides module.
 * NOTE: No jQuery - using vanilla JavaScript only
 *
 * @package    Orbitools
 * @subpackage Modules/Layout_Guides
 * @since      1.0.0
 */

(function() {
    'use strict';


    // Layout Guides Controller
    const LayoutGuides = {
        
        // Configuration
        config: window.orbitoolsLayoutGuides || {},
        
        // Elements
        elements: {
            container: null,
            body: null,
            fab: null,
            fabToggle: null,
            fabPanel: null
        },
        
        // State
        state: {
            visible: false,
            initialized: false,
            fabOpen: false
        },

        /**
         * Initialize the layout guides
         */
        init: function() {
            if (this.state.initialized) {
                return;
            }

            this.state.initialized = true;
            
            
            this.cacheElements();
            this.bindEvents();
            this.setupKeyboardShortcuts();
            this.setupFAB();
            this.initializeState();
        },

        /**
         * Cache DOM elements
         */
        cacheElements: function() {
            this.elements.container = document.getElementById('orbitools-layout-guides');
            this.elements.body = document.body;
            this.elements.fab = document.getElementById('orbitools-layout-guides-fab');
            this.elements.fabToggle = this.elements.fab ? this.elements.fab.querySelector('.orbitools-layout-guides__fab-toggle') : null;
            this.elements.fabPanel = this.elements.fab ? this.elements.fab.querySelector('.orbitools-layout-guides__fab-panel') : null;
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Window resize handler
            window.addEventListener('resize', this.debounce(this.handleResize.bind(this), 100));

            // Update guides when settings change
            document.addEventListener('orbitools:settings:changed', this.updateGuides.bind(this));
        },

        /**
         * Setup keyboard shortcuts
         */
        setupKeyboardShortcuts: function() {
            // Use fallback if config not ready yet
            const toggleKey = this.config.toggleKey || 'ctrl+shift+g';
            
            const shortcut = this.parseShortcut(toggleKey);
            
            document.addEventListener('keydown', (e) => {
                if (this.matchesShortcut(e, shortcut)) {
                    e.preventDefault();
                    this.toggleGuides();
                }
            });
        },

        /**
         * Parse keyboard shortcut string
         */
        parseShortcut: function(shortcut) {
            const parts = shortcut.toLowerCase().split('+');
            return {
                ctrl: parts.includes('ctrl'),
                alt: parts.includes('alt'),
                shift: parts.includes('shift'),
                key: parts[parts.length - 1]
            };
        },

        /**
         * Check if event matches shortcut
         */
        matchesShortcut: function(event, shortcut) {
            return event.ctrlKey === shortcut.ctrl &&
                   event.altKey === shortcut.alt &&
                   event.shiftKey === shortcut.shift &&
                   event.key.toLowerCase() === shortcut.key;
        },

        /**
         * Initialize state
         */
        initializeState: function() {
            // Check if guides should be visible by default
            const storedState = localStorage.getItem('orbitools-layout-guides-visible');
            if (storedState !== null) {
                this.state.visible = storedState === 'true';
            }

            if (this.state.visible) {
                this.showGuides();
            }
        },

        /**
         * Toggle guides visibility
         */
        toggleGuides: function() {
            if (this.state.visible) {
                this.hideGuides();
            } else {
                this.showGuides();
            }
        },

        /**
         * Show guides
         */
        showGuides: function() {
            this.state.visible = true;
            
            this.elements.body.classList.add('has-layout-guides--visible');
            this.elements.body.classList.add('has-layout-guides--enabled');
            
            // Add feature-specific classes
            if (this.config.showGrid) {
                this.elements.body.classList.add('has-layout-guides--grid');
            }
            if (this.config.showRulers) {
                this.elements.body.classList.add('has-layout-guides--rulers');
            }
            
            // Store state
            localStorage.setItem('orbitools-layout-guides-visible', 'true');
            
            // Update guides
            this.updateGuides();
            
            // Update FAB states
            this.updateFABStates();
        },

        /**
         * Hide guides
         */
        hideGuides: function() {
            this.state.visible = false;
            
            this.elements.body.classList.remove('has-layout-guides--visible');
            this.elements.body.classList.remove('has-layout-guides--enabled');
            this.elements.body.classList.remove('has-layout-guides--grid');
            this.elements.body.classList.remove('has-layout-guides--rulers');
            
            // Store state
            localStorage.setItem('orbitools-layout-guides-visible', 'false');
            
            // Update FAB states
            this.updateFABStates();
        },

        /**
         * Update guides based on current settings
         */
        updateGuides: function() {
            if (!this.state.visible) {
                return;
            }

            // Update CSS custom properties
            this.updateCSSProperties();
            
            // Update grid
            if (this.config.showGrid) {
                this.updateGrid();
            }
            
            
            // Update rulers
            if (this.config.showRulers) {
                this.updateRulers();
            }
        },

        /**
         * Update CSS custom properties
         */
        updateCSSProperties: function() {
            const root = document.documentElement;
            
            root.style.setProperty('--layout-guides-columns', this.config.gridColumns);
            root.style.setProperty('--layout-guides-gutter', this.config.gridGutter);
            root.style.setProperty('--layout-guides-opacity', this.config.opacity);
            root.style.setProperty('--layout-guides-color', this.config.color);
        },

        /**
         * Update grid overlay
         */
        updateGrid: function() {
            if (!this.elements.container) return;
            
            const grid = this.elements.container.querySelector('.orbitools-layout-guides__grid');
            if (!grid) return;
            
            const columns = grid.querySelectorAll('.orbitools-layout-guides__grid-column');
            
            // Update column count if needed
            const currentColumns = columns.length;
            const targetColumns = this.config.gridColumns;
            
            if (currentColumns !== targetColumns) {
                // Remove existing columns
                columns.forEach(column => column.remove());
                
                // Add new columns
                for (let i = 0; i < targetColumns; i++) {
                    const column = document.createElement('div');
                    column.className = 'orbitools-layout-guides__grid-column';
                    grid.appendChild(column);
                }
            }
        },


        /**
         * Update rulers
         */
        updateRulers: function() {
            if (!this.elements.container) return;
            
            const rulers = this.elements.container.querySelector('.orbitools-layout-guides__rulers');
            if (!rulers) return;
            
            const horizontalRuler = rulers.querySelector('.orbitools-layout-guides__ruler--horizontal');
            const verticalRuler = rulers.querySelector('.orbitools-layout-guides__ruler--vertical');
            
            if (!horizontalRuler || !verticalRuler) return;
            
            // Remove existing mousemove listener
            document.removeEventListener('mousemove', this._rulersMouseHandler);
            
            // Add new mousemove listener
            this._rulersMouseHandler = (e) => {
                if (!this.state.visible || !this.config.showRulers) {
                    return;
                }
                
                horizontalRuler.style.top = e.clientY + 'px';
                verticalRuler.style.left = e.clientX + 'px';
            };
            
            document.addEventListener('mousemove', this._rulersMouseHandler);
        },

        /**
         * Handle window resize
         */
        handleResize: function() {
            if (this.state.visible) {
                this.updateGuides();
            }
        },

        /**
         * Setup FAB functionality
         */
        setupFAB: function() {
            if (!this.elements.fab || !this.elements.fabToggle) {
                return;
            }

            // Toggle FAB panel
            this.elements.fabToggle.addEventListener('click', (e) => {
                e.preventDefault();
                this.toggleFAB();
            });

            // Handle FAB button clicks
            const fabButtons = this.elements.fab.querySelectorAll('.orbitools-layout-guides__fab-btn');
            fabButtons.forEach(button => {
                button.addEventListener('click', (e) => {
                    e.preventDefault();
                    const action = button.getAttribute('data-action');
                    this.handleFABAction(action, button);
                });
            });

            // Close FAB when clicking outside
            document.addEventListener('click', (e) => {
                if (!this.elements.fab.contains(e.target) && this.state.fabOpen) {
                    this.closeFAB();
                }
            });
        },

        /**
         * Toggle FAB panel
         */
        toggleFAB: function() {
            if (this.state.fabOpen) {
                this.closeFAB();
            } else {
                this.openFAB();
            }
        },

        /**
         * Open FAB panel
         */
        openFAB: function() {
            this.state.fabOpen = true;
            this.elements.fab.classList.add('orbitools-layout-guides__fab--open');
        },

        /**
         * Close FAB panel
         */
        closeFAB: function() {
            this.state.fabOpen = false;
            this.elements.fab.classList.remove('orbitools-layout-guides__fab--open');
        },

        /**
         * Handle FAB action
         */
        handleFABAction: function(action, button) {
            switch (action) {
                case 'toggle':
                    this.toggleGuides();
                    break;
                case 'toggle-grid':
                    this.toggleFeature('grid', button);
                    break;
                case 'toggle-rulers':
                    this.toggleFeature('rulers', button);
                    break;
            }
        },

        /**
         * Toggle individual feature
         */
        toggleFeature: function(feature, button) {
            const className = `has-layout-guides--${feature}`;
            const isActive = this.elements.body.classList.contains(className);
            
            if (isActive) {
                this.elements.body.classList.remove(className);
                button.classList.remove('orbitools-layout-guides__fab-btn--active');
            } else {
                this.elements.body.classList.add(className);
                button.classList.add('orbitools-layout-guides__fab-btn--active');
            }
        },

        /**
         * Update FAB button states
         */
        updateFABStates: function() {
            if (!this.elements.fab) return;

            const toggleBtn = this.elements.fab.querySelector('[data-action="toggle"]');
            if (toggleBtn) {
                if (this.state.visible) {
                    toggleBtn.classList.add('orbitools-layout-guides__fab-btn--active');
                } else {
                    toggleBtn.classList.remove('orbitools-layout-guides__fab-btn--active');
                }
            }

            // Update feature button states
            const features = ['grid', 'rulers'];
            features.forEach(feature => {
                const btn = this.elements.fab.querySelector(`[data-action="toggle-${feature}"]`);
                if (btn) {
                    const className = `has-layout-guides--${feature}`;
                    if (this.elements.body.classList.contains(className)) {
                        btn.classList.add('orbitools-layout-guides__fab-btn--active');
                    } else {
                        btn.classList.remove('orbitools-layout-guides__fab-btn--active');
                    }
                }
            });
        },

        /**
         * Debounce function
         */
        debounce: function(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
    };


    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            LayoutGuides.init();
        });
    } else {
        LayoutGuides.init();
    }

    // Expose to global scope
    window.OrbitoolsLayoutGuides = LayoutGuides;

})();