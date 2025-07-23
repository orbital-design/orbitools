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

            // Check server-side authorization before initializing anything
            if (!this.config.shouldShow) {
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
            // First check server-side authorization before using localStorage
            if (!this.config.shouldShow) {
                // Clear any cached state if user shouldn't have access
                localStorage.removeItem('orbitools-layout-guides-visible');
                return;
            }

            // Check if guides should be visible by default
            const storedState = localStorage.getItem('orbitools-layout-guides-visible');
            if (storedState !== null) {
                this.state.visible = storedState === 'true';
            }

            if (this.state.visible) {
                this.showGuides();
            } else {
                // Even if guides aren't visible, we need to set initial body classes
                // for the features that are enabled in settings
                this.setInitialFeatureClasses();
            }
            
            // Initialize rulers if enabled
            if (this.config.showRulers) {
                this.updateRulers();
            }
            
            // Initialize grid if enabled
            if (this.config.showGrids) {
                this.updateGrid();
                // Ensure CSS properties match the cached grid state
                this.updateCSSProperties();
            }
            
            // Update FAB states AFTER cached states are applied
            this.updateFABStates();
        },

        /**
         * Toggle guides visibility
         */
        toggleGuides: function() {
            // Check authorization before toggling guides
            if (!this.config.shouldShow) {
                return;
            }
            
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
            // Check authorization before showing guides
            if (!this.config.shouldShow) {
                return;
            }
            
            this.state.visible = true;
            
            this.elements.body.classList.add('has-layout-guides--visible');
            this.elements.body.classList.add('has-layout-guides--enabled');
            
            // Add feature-specific classes based on cached preferences
            if (this.config.showGrids) {
                // First remove any existing grid classes that might have been set by PHP
                this.elements.body.classList.remove('has-layout-guides--12-grid');
                this.elements.body.classList.remove('has-layout-guides--5-grid');
                
                const cachedGridType = localStorage.getItem('orbitools-layout-guides-grid-type');
                if (cachedGridType === '5-grid') {
                    this.elements.body.classList.add('has-layout-guides--5-grid');
                } else if (cachedGridType === 'none') {
                    // User previously disabled grids - don't add any grid class
                } else {
                    this.elements.body.classList.add('has-layout-guides--12-grid'); // Default to 12-grid
                }
            }
            
            if (this.config.showRulers) {
                // First remove any existing rulers class that might have been set by PHP
                this.elements.body.classList.remove('has-layout-guides--rulers');
                
                const cachedRulersState = localStorage.getItem('orbitools-layout-guides-rulers');
                if (cachedRulersState === 'true') {
                    this.elements.body.classList.add('has-layout-guides--rulers');
                }
                // If cachedRulersState is 'false' or null, rulers stay off
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
            this.elements.body.classList.remove('has-layout-guides--12-grid');
            this.elements.body.classList.remove('has-layout-guides--5-grid');
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
            // Update CSS custom properties
            this.updateCSSProperties();
            
            // Update grid
            if (this.config.showGrids) {
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
            
            // Set grid columns based on which grid is active
            let gridColumns = 12; // default
            if (this.elements.body.classList.contains('has-layout-guides--5-grid')) {
                gridColumns = 5;
            } else if (this.elements.body.classList.contains('has-layout-guides--12-grid')) {
                gridColumns = 12;
            }
            
            root.style.setProperty('--layout-guides-columns', gridColumns);
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
            
            // Check if any grid is actually active
            const has5Grid = this.elements.body.classList.contains('has-layout-guides--5-grid');
            const has12Grid = this.elements.body.classList.contains('has-layout-guides--12-grid');
            
            if (!has5Grid && !has12Grid) {
                // No grid active - remove all columns
                columns.forEach(column => column.remove());
                return;
            }
            
            // Update column count if needed
            const currentColumns = columns.length;
            let targetColumns = 12; // default
            if (has5Grid) {
                targetColumns = 5;
            } else if (has12Grid) {
                targetColumns = 12;
            }
            
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
                // Check if rulers are enabled via body class (individual toggle)
                if (!this.elements.body.classList.contains('has-layout-guides--rulers')) {
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
                case 'toggle-12-grid':
                    this.toggleGridFeature('12-grid', button);
                    break;
                case 'toggle-5-grid':
                    this.toggleGridFeature('5-grid', button);
                    break;
                case 'toggle-rulers':
                    this.toggleFeature('rulers', button);
                    break;
            }
        },

        /**
         * Toggle grid feature with exclusive selection
         */
        toggleGridFeature: function(gridType, button) {
            const className = `has-layout-guides--${gridType}`;
            const isActive = this.elements.body.classList.contains(className);
            
            if (isActive) {
                // Disable this grid
                this.elements.body.classList.remove(className);
                button.classList.remove('orbitools-layout-guides__fab-btn--active');
                
                // Cache the disabled state (no grid active)
                localStorage.setItem('orbitools-layout-guides-grid-type', 'none');
                
                // Update grid display when disabling
                this.updateGrid();
                this.updateCSSProperties();
            } else {
                // Enable this grid and disable the other
                this.elements.body.classList.remove('has-layout-guides--12-grid');
                this.elements.body.classList.remove('has-layout-guides--5-grid');
                this.elements.body.classList.add(className);
                
                // Cache the selected grid type
                localStorage.setItem('orbitools-layout-guides-grid-type', gridType);
                
                // Update button states - disable other grid buttons
                const allGridButtons = this.elements.fab.querySelectorAll('[data-action^="toggle-"][data-action$="-grid"]');
                allGridButtons.forEach(btn => {
                    btn.classList.remove('orbitools-layout-guides__fab-btn--active');
                });
                
                // Activate current button
                button.classList.add('orbitools-layout-guides__fab-btn--active');
                
                // Update grid display
                this.updateGrid();
                this.updateCSSProperties();
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
                
                // Cache the disabled state
                if (feature === 'rulers') {
                    localStorage.setItem('orbitools-layout-guides-rulers', 'false');
                }
            } else {
                this.elements.body.classList.add(className);
                button.classList.add('orbitools-layout-guides__fab-btn--active');
                
                // Cache the enabled state
                if (feature === 'rulers') {
                    localStorage.setItem('orbitools-layout-guides-rulers', 'true');
                }
                
                // Initialize feature-specific functionality when enabled
                if (feature === 'rulers' && this.config.showRulers) {
                    this.updateRulers();
                }
            }
        },

        /**
         * Update FAB button states
         */
        updateFABStates: function() {
            if (!this.elements.fab) return;

            // Update feature button states
            const features = ['12-grid', '5-grid', 'rulers'];
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
         * Set initial feature classes based on settings
         */
        setInitialFeatureClasses: function() {
            // Add enabled feature classes even when guides aren't visible
            // This ensures FAB buttons show correct state
            
            if (this.config.showGrids) {
                // First remove any existing grid classes that might have been set by PHP
                this.elements.body.classList.remove('has-layout-guides--12-grid');
                this.elements.body.classList.remove('has-layout-guides--5-grid');
                
                // Check for cached grid type preference
                const cachedGridType = localStorage.getItem('orbitools-layout-guides-grid-type');
                if (cachedGridType === '5-grid') {
                    this.elements.body.classList.add('has-layout-guides--5-grid');
                } else if (cachedGridType === 'none') {
                    // User previously disabled grids - don't add any grid class
                } else {
                    this.elements.body.classList.add('has-layout-guides--12-grid'); // Default to 12-grid
                }
            }
            
            if (this.config.showRulers) {
                // First remove any existing rulers class that might have been set by PHP
                this.elements.body.classList.remove('has-layout-guides--rulers');
                
                // Check for cached rulers state
                const cachedRulersState = localStorage.getItem('orbitools-layout-guides-rulers');
                if (cachedRulersState === 'true') {
                    this.elements.body.classList.add('has-layout-guides--rulers');
                }
                // If cachedRulersState is 'false' or null, don't add the class (rulers off)
            }
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