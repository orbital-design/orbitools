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

    // Debug: Log that the script is loading
    console.log('Layout Guides JavaScript file loaded');

    // Layout Guides Controller
    const LayoutGuides = {
        
        // Configuration
        config: window.orbitoolsLayoutGuides || {},
        
        // Elements
        elements: {
            container: null,
            adminBarToggle: null,
            body: null
        },
        
        // State
        state: {
            visible: false,
            initialized: false
        },

        /**
         * Initialize the layout guides
         */
        init: function() {
            if (this.state.initialized) {
                return;
            }

            this.state.initialized = true;
            
            // Debug: log configuration
            console.log('Layout Guides Config:', this.config);
            
            this.cacheElements();
            this.bindEvents();
            this.setupKeyboardShortcuts();
            this.initializeState();
        },

        /**
         * Cache DOM elements
         */
        cacheElements: function() {
            this.elements.container = document.getElementById('orbitools-layout-guides');
            this.elements.adminBarToggle = document.getElementById('wp-admin-bar-orbitools-layout-guides-toggle');
            this.elements.body = document.body;
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Admin bar toggle
            if (this.elements.adminBarToggle) {
                this.elements.adminBarToggle.addEventListener('click', this.toggleGuides.bind(this));
            }

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
            console.log('Setting up keyboard shortcut:', toggleKey, shortcut);
            
            document.addEventListener('keydown', (e) => {
                if (this.matchesShortcut(e, shortcut)) {
                    console.log('Keyboard shortcut matched! Toggling guides...');
                    e.preventDefault();
                    this.toggleGuides();
                } else if (e.ctrlKey && e.shiftKey) {
                    // Debug: log when ctrl+shift is pressed with any key
                    console.log('Ctrl+Shift+' + e.key + ' pressed, but not matching toggle key');
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
            console.log('toggleGuides called, current state:', this.state.visible);
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
            
            if (this.elements.adminBarToggle) {
                this.elements.adminBarToggle.classList.add('active');
            }
            
            this.elements.body.classList.add('orbitools-layout-guides--visible');
            this.elements.body.classList.add('orbitools-layout-guides--enabled');
            
            // Add feature-specific classes
            if (this.config.showGrid) {
                this.elements.body.classList.add('orbitools-layout-guides--grid');
            }
            if (this.config.showBaseline) {
                this.elements.body.classList.add('orbitools-layout-guides--baseline');
            }
            if (this.config.showRulers) {
                this.elements.body.classList.add('orbitools-layout-guides--rulers');
            }
            if (this.config.showSpacing) {
                this.elements.body.classList.add('orbitools-layout-guides--spacing');
            }
            
            // Store state
            localStorage.setItem('orbitools-layout-guides-visible', 'true');
            
            // Update guides
            this.updateGuides();
        },

        /**
         * Hide guides
         */
        hideGuides: function() {
            this.state.visible = false;
            
            if (this.elements.adminBarToggle) {
                this.elements.adminBarToggle.classList.remove('active');
            }
            
            this.elements.body.classList.remove('orbitools-layout-guides--visible');
            this.elements.body.classList.remove('orbitools-layout-guides--enabled');
            this.elements.body.classList.remove('orbitools-layout-guides--grid');
            this.elements.body.classList.remove('orbitools-layout-guides--baseline');
            this.elements.body.classList.remove('orbitools-layout-guides--rulers');
            this.elements.body.classList.remove('orbitools-layout-guides--spacing');
            
            // Store state
            localStorage.setItem('orbitools-layout-guides-visible', 'false');
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
            
            // Update baseline
            if (this.config.showBaseline) {
                this.updateBaseline();
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
            root.style.setProperty('--layout-guides-gutter', this.config.gridGutter + 'px');
            root.style.setProperty('--layout-guides-baseline', this.config.baselineHeight + 'px');
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
         * Update baseline grid
         */
        updateBaseline: function() {
            // Baseline is handled via CSS custom properties
            // No additional JavaScript needed
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

    // Element spacing visualization
    const SpacingVisualizer = {
        
        init: function() {
            if (!LayoutGuides.config.showSpacing) {
                return;
            }

            this.bindEvents();
        },

        bindEvents: function() {
            document.addEventListener('mouseenter', this.showSpacing.bind(this), true);
            document.addEventListener('mouseleave', this.hideSpacing.bind(this), true);
        },

        showSpacing: function(e) {
            if (!LayoutGuides.state.visible || !LayoutGuides.config.showSpacing) {
                return;
            }

            const element = e.target;
            
            // Only process actual elements, not text nodes
            if (element.nodeType !== Node.ELEMENT_NODE) {
                return;
            }
            
            const computedStyle = window.getComputedStyle(element);
            
            // Create spacing visualization
            this.createSpacingOverlay(element, computedStyle);
        },

        hideSpacing: function(e) {
            const overlays = document.querySelectorAll('.orbitools-spacing-overlay');
            overlays.forEach(overlay => overlay.remove());
        },

        createSpacingOverlay: function(element, computedStyle) {
            const rect = element.getBoundingClientRect();
            const margin = {
                top: parseInt(computedStyle.marginTop, 10),
                right: parseInt(computedStyle.marginRight, 10),
                bottom: parseInt(computedStyle.marginBottom, 10),
                left: parseInt(computedStyle.marginLeft, 10)
            };
            const padding = {
                top: parseInt(computedStyle.paddingTop, 10),
                right: parseInt(computedStyle.paddingRight, 10),
                bottom: parseInt(computedStyle.paddingBottom, 10),
                left: parseInt(computedStyle.paddingLeft, 10)
            };

            // Create overlay elements
            const overlay = document.createElement('div');
            overlay.className = 'orbitools-spacing-overlay';
            
            // Add to body
            document.body.appendChild(overlay);
            
            // Position overlay
            Object.assign(overlay.style, {
                position: 'fixed',
                top: (rect.top - margin.top) + 'px',
                left: (rect.left - margin.left) + 'px',
                width: (rect.width + margin.left + margin.right) + 'px',
                height: (rect.height + margin.top + margin.bottom) + 'px',
                pointerEvents: 'none',
                zIndex: '10000',
                border: '1px solid ' + LayoutGuides.config.color,
                background: 'rgba(255, 0, 0, 0.1)'
            });
        }
    };

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM ready, initializing Layout Guides...');
            console.log('Available config:', window.orbitoolsLayoutGuides);
            LayoutGuides.init();
            SpacingVisualizer.init();
        });
    } else {
        console.log('DOM already ready, initializing Layout Guides...');
        console.log('Available config:', window.orbitoolsLayoutGuides);
        LayoutGuides.init();
        SpacingVisualizer.init();
    }

    // Expose to global scope
    window.OrbitoolsLayoutGuides = LayoutGuides;

})();