/**
 * Marquee Block Frontend Animation
 * 
 * Provides smooth, performant marquee animations with support for:
 * - Horizontal and vertical scrolling
 * - Normal and reverse directions  
 * - Pause on hover functionality
 * - Automatic content duplication for seamless looping
 * - Intersection Observer for performance optimization
 * 
 * @file blocks/marquee/frontend.js
 * @since 1.0.0
 */

(function() {
    'use strict';

    /**
     * Default configuration values
     */
    const DEFAULT_CONFIG = {
        speed: '10s',
        orientation: 'x',
        direction: 'normal',
        hoverState: 'paused',
        duplicatesNeeded: 1,
        easeSpeed: 0.02,
        defaultDuration: 20
    };

    /**
     * MarqueeAnimation Class
     * Handles all marquee animation logic for a single element
     */
    class MarqueeAnimation {
        /**
         * Creates a new MarqueeAnimation instance
         * 
         * @param {HTMLElement} element - The marquee element to animate
         */
        constructor(element) {
            this.element = element;
            this.wrapper = null;
            this.content = null;
            this.config = {};
            this.animationID = null;
            this.isVisible = false;
            this.isPaused = false;
            this.currentSpeed = 1;
            this.lastTime = null;
            this.itemPositions = new Map();
            this.items = [];
            this.cleanupFunctions = [];
            this.cachedDimensions = null;
            
            this.init();
        }

        /**
         * Initialize the marquee animation
         */
        init() {
            try {
                // Find required elements
                this.wrapper = this.element.querySelector('.orb-marquee__wrapper');
                this.content = this.element.querySelector('.orb-marquee__content');

                if (!this.wrapper || !this.content) {
                    console.warn('Marquee: Missing required elements', this.element);
                    return;
                }

                // Extract configuration
                this.config = this.getConfig();
                
                // Get content dimensions
                const contentRect = this.content.getBoundingClientRect();
                const contentSize = {
                    width: contentRect.width,
                    height: contentRect.height
                };

                // Set up content duplication
                this.setupContentDuplication(contentSize);

                // Calculate animation speed
                this.setupAnimationSpeed(contentSize);

                // Set up hover behavior
                if (this.config.hoverState === 'paused') {
                    this.setupHoverBehavior();
                }

                // Set up intersection observer for performance
                this.setupIntersectionObserver();

                // Set up resize handler
                this.setupResizeHandler();

                // Initialize positioning
                this.setupInitialPositioning(contentSize);

                // Mark as initialized
                this.element.dataset.marqueeInitialized = 'true';

                // Start animation
                this.start();

            } catch (error) {
                console.error('Marquee initialization failed:', error);
            }
        }

        /**
         * Extract configuration from element
         * 
         * @returns {Object} Configuration object
         */
        getConfig() {
            const dataset = this.element.dataset;
            
            return {
                orientation: dataset.orientation || DEFAULT_CONFIG.orientation,
                direction: dataset.direction || DEFAULT_CONFIG.direction,
                hoverState: dataset.hover || DEFAULT_CONFIG.hoverState,
                speed: dataset.speed || DEFAULT_CONFIG.speed
            };
        }

        /**
         * Set up content duplication for seamless scrolling
         * 
         * @param {Object} contentSize - Content dimensions
         */
        setupContentDuplication(contentSize) {
            const duplicatesNeeded = this.calculateDuplicatesNeeded(contentSize);

            for (let i = 0; i < duplicatesNeeded; i++) {
                const clone = this.content.cloneNode(true);
                clone.setAttribute('aria-hidden', 'true');
                clone.classList.add('orb-marquee__content--clone');
                
                if (this.config.direction === 'reverse') {
                    this.wrapper.insertBefore(clone, this.content);
                } else {
                    this.wrapper.appendChild(clone);
                }
            }

            this.wrapper.dataset.duplicateCount = duplicatesNeeded;
        }

        /**
         * Calculate how many duplicates are needed
         * 
         * @param {Object} contentSize - Content dimensions
         * @returns {number} Number of duplicates needed
         */
        calculateDuplicatesNeeded(contentSize) {
            const wrapperRect = this.wrapper.getBoundingClientRect();
            const wrapperStyles = getComputedStyle(this.wrapper);
            
            const paddingLeft = parseFloat(wrapperStyles.paddingLeft) || 0;
            const paddingRight = parseFloat(wrapperStyles.paddingRight) || 0;
            const paddingTop = parseFloat(wrapperStyles.paddingTop) || 0;
            const paddingBottom = parseFloat(wrapperStyles.paddingBottom) || 0;

            const isHorizontal = this.config.orientation === 'x';
            const containerSize = isHorizontal 
                ? wrapperRect.width - paddingLeft - paddingRight
                : wrapperRect.height - paddingTop - paddingBottom;
            
            const contentDimension = isHorizontal ? contentSize.width : contentSize.height;

            if (contentDimension >= containerSize) {
                return 1; // Content larger than container, only need 1 duplicate
            } else {
                return Math.ceil(containerSize / contentDimension) + 1;
            }
        }

        /**
         * Set up animation speed
         * 
         * @param {Object} contentSize - Content dimensions
         */
        setupAnimationSpeed(contentSize) {
            const isHorizontal = this.config.orientation === 'x';
            
            // Parse duration from config
            let durationInSeconds = DEFAULT_CONFIG.defaultDuration;
            if (this.config.speed) {
                const speedMatch = this.config.speed.match(/^(\d+(?:\.\d+)?)s$/);
                if (speedMatch) {
                    const parsedDuration = parseFloat(speedMatch[1]);
                    if (parsedDuration > 0) {
                        durationInSeconds = parsedDuration;
                    }
                }
            }

            // Calculate speed based on viewport
            const viewportSize = isHorizontal ? window.innerWidth : window.innerHeight;
            const pixelsPerSecond = viewportSize / durationInSeconds;

            // Store configuration
            this.marqueeConfig = {
                speed: pixelsPerSecond,
                duration: durationInSeconds,
                contentWidth: contentSize.width,
                contentHeight: contentSize.height,
                orientation: this.config.orientation,
                direction: this.config.direction
            };
        }

        /**
         * Set up hover pause behavior
         */
        setupHoverBehavior() {
            const handleMouseEnter = () => {
                this.isPaused = true;
                this.isResuming = false;
            };

            const handleMouseLeave = () => {
                this.isPaused = false;
                this.isResuming = true;
            };

            this.wrapper.addEventListener('mouseenter', handleMouseEnter);
            this.wrapper.addEventListener('mouseleave', handleMouseLeave);

            // Store cleanup functions
            this.cleanupFunctions.push(() => {
                this.wrapper.removeEventListener('mouseenter', handleMouseEnter);
                this.wrapper.removeEventListener('mouseleave', handleMouseLeave);
            });
        }

        /**
         * Set up intersection observer for performance
         */
        setupIntersectionObserver() {
            if (!('IntersectionObserver' in window)) {
                this.isVisible = true;
                return;
            }

            const observer = new IntersectionObserver(
                (entries) => {
                    entries.forEach(entry => {
                        this.isVisible = entry.isIntersecting;
                        
                        if (this.isVisible && !this.animationID) {
                            this.start();
                        } else if (!this.isVisible && this.animationID) {
                            this.pause();
                        }
                    });
                },
                { rootMargin: '50px' }
            );

            observer.observe(this.element);
            
            this.cleanupFunctions.push(() => {
                observer.disconnect();
            });
        }

        /**
         * Set up resize handler to invalidate cached dimensions
         */
        setupResizeHandler() {
            let resizeTimeout;
            
            const handleResize = () => {
                clearTimeout(resizeTimeout);
                resizeTimeout = setTimeout(() => {
                    this.cachedDimensions = null;
                }, 150);
            };

            window.addEventListener('resize', handleResize);
            
            this.cleanupFunctions.push(() => {
                window.removeEventListener('resize', handleResize);
                clearTimeout(resizeTimeout);
            });
        }

        /**
         * Set up initial positioning
         * 
         * @param {Object} contentSize - Content dimensions
         */
        setupInitialPositioning(contentSize) {
            const isHorizontal = this.config.orientation === 'x';
            
            // Get wrapper padding
            const wrapperStyles = getComputedStyle(this.wrapper);
            const paddingLeft = parseFloat(wrapperStyles.paddingLeft) || 0;
            const paddingTop = parseFloat(wrapperStyles.paddingTop) || 0;

            // Set wrapper positioning and dimensions for absolute positioning context
            this.wrapper.style.position = 'relative';
            this.wrapper.style.height = contentSize.height + 'px';
            this.wrapper.style.overflow = 'hidden';

            // Set up items array
            this.items = Array.from(this.wrapper.children);

            // Position each content block
            this.items.forEach((item, i) => {
                item.style.position = 'absolute';
                item.style.width = contentSize.width + 'px';
                item.style.height = contentSize.height + 'px';

                if (isHorizontal) {
                    item.style.top = paddingTop + 'px';
                    
                    if (this.config.direction === 'reverse') {
                        const originalIndex = this.items.length - 1;
                        const relativeIndex = originalIndex - i;
                        item.style.left = (paddingLeft + (-contentSize.width * relativeIndex)) + 'px';
                    } else {
                        item.style.left = (paddingLeft + (contentSize.width * i)) + 'px';
                    }
                } else {
                    item.style.left = paddingLeft + 'px';
                    
                    if (this.config.direction === 'reverse') {
                        const originalIndex = this.items.length - 1;
                        const relativeIndex = originalIndex - i;
                        item.style.top = (paddingTop + (-contentSize.height * relativeIndex)) + 'px';
                    } else {
                        item.style.top = (paddingTop + (contentSize.height * i)) + 'px';
                    }
                }
                
                // Initialize position tracking
                this.itemPositions.set(item, 0);
            });
        }

        /**
         * Start the animation
         */
        start() {
            if (this.animationID) return;
            
            this.animate();
        }

        /**
         * Pause the animation
         */
        pause() {
            if (this.animationID) {
                cancelAnimationFrame(this.animationID);
                this.animationID = null;
            }
        }

        /**
         * Main animation loop
         */
        animate() {
            if (!this.isVisible || !this.marqueeConfig) {
                this.animationID = null;
                return;
            }

            // Calculate time delta
            const currentTime = Date.now();
            if (!this.lastTime) {
                this.lastTime = currentTime;
            }
            const deltaTime = (currentTime - this.lastTime) / 1000;
            this.lastTime = currentTime;

            const config = this.marqueeConfig;
            const isHorizontal = config.orientation === 'x';
            const isReverse = config.direction === 'reverse';

            // Handle pause/resume with smooth speed transitions
            if (this.isPaused && this.currentSpeed > 0) {
                this.currentSpeed = Math.max(0, this.currentSpeed - DEFAULT_CONFIG.easeSpeed);
            } else if (this.isResuming && this.currentSpeed < 1) {
                this.currentSpeed = Math.min(1, this.currentSpeed + DEFAULT_CONFIG.easeSpeed);
            }

            // Calculate movement
            const baseSpeed = config.speed * deltaTime;
            const actualSpeed = baseSpeed * this.currentSpeed;

            // Move all items
            this.items.forEach(item => {
                let currentPos = this.itemPositions.get(item) || 0;
                currentPos += isReverse ? actualSpeed : -actualSpeed;
                this.itemPositions.set(item, currentPos);

                // Apply transform
                if (isHorizontal) {
                    item.style.transform = `translateX(${currentPos - (parseFloat(item.style.left) || 0)}px)`;
                } else {
                    item.style.transform = `translateY(${currentPos - (parseFloat(item.style.top) || 0)}px)`;
                }
            });

            // Check for repositioning
            this.checkRepositioning(isHorizontal, isReverse, config);

            // Continue animation
            this.animationID = requestAnimationFrame(() => this.animate());
        }

        /**
         * Check and handle item repositioning for seamless loop
         */
        checkRepositioning(isHorizontal, isReverse, config) {
            // Get cached dimensions
            if (!this.cachedDimensions) {
                const wrapperStyles = getComputedStyle(this.wrapper);
                const wrapperRect = this.wrapper.getBoundingClientRect();
                const paddingLeft = parseFloat(wrapperStyles.paddingLeft) || 0;
                const paddingRight = parseFloat(wrapperStyles.paddingRight) || 0;
                const paddingTop = parseFloat(wrapperStyles.paddingTop) || 0;
                const paddingBottom = parseFloat(wrapperStyles.paddingBottom) || 0;
                
                this.cachedDimensions = {
                    contentWidth: wrapperRect.width - paddingLeft - paddingRight,
                    contentHeight: wrapperRect.height - paddingTop - paddingBottom,
                    lastUpdate: Date.now()
                };
            }
            
            const { contentWidth, contentHeight } = this.cachedDimensions;

            // Check each item for repositioning
            this.items.forEach((item) => {
                const itemPos = this.itemPositions.get(item) || 0;
                let needsReposition = false;

                if (isHorizontal) {
                    const itemLeft = itemPos + (parseFloat(item.style.left) || 0);
                    if (isReverse) {
                        needsReposition = itemLeft > contentWidth;
                    } else {
                        needsReposition = (itemLeft + config.contentWidth) < 0;
                    }
                } else {
                    const itemTop = itemPos + (parseFloat(item.style.top) || 0);
                    if (isReverse) {
                        needsReposition = itemTop > contentHeight;
                    } else {
                        needsReposition = (itemTop + config.contentHeight) < 0;
                    }
                }

                if (needsReposition) {
                    this.repositionItem(item, isHorizontal, isReverse, config);
                }
            });
        }

        /**
         * Reposition an item to maintain seamless loop
         */
        repositionItem(item, isHorizontal, isReverse, config) {
            if (isHorizontal) {
                if (isReverse) {
                    // Find leftmost position
                    let leftmostPosition = Infinity;
                    this.items.forEach(otherItem => {
                        const pos = this.itemPositions.get(otherItem) || 0;
                        if (pos < leftmostPosition) {
                            leftmostPosition = pos;
                        }
                    });
                    this.itemPositions.set(item, leftmostPosition - config.contentWidth);
                } else {
                    // Find rightmost position
                    let rightmostPosition = -Infinity;
                    this.items.forEach(otherItem => {
                        const pos = this.itemPositions.get(otherItem) || 0;
                        const right = pos + config.contentWidth;
                        if (right > rightmostPosition) {
                            rightmostPosition = right;
                        }
                    });
                    this.itemPositions.set(item, rightmostPosition);
                }
                
                const newPos = this.itemPositions.get(item);
                item.style.transform = `translateX(${newPos - (parseFloat(item.style.left) || 0)}px)`;
                
            } else {
                if (isReverse) {
                    // Find topmost position
                    let topmostPosition = Infinity;
                    this.items.forEach(otherItem => {
                        const pos = this.itemPositions.get(otherItem) || 0;
                        if (pos < topmostPosition) {
                            topmostPosition = pos;
                        }
                    });
                    this.itemPositions.set(item, topmostPosition - config.contentHeight);
                } else {
                    // Find bottommost position
                    let bottommostPosition = -Infinity;
                    this.items.forEach(otherItem => {
                        const pos = this.itemPositions.get(otherItem) || 0;
                        const bottom = pos + config.contentHeight;
                        if (bottom > bottommostPosition) {
                            bottommostPosition = bottom;
                        }
                    });
                    this.itemPositions.set(item, bottommostPosition);
                }
                
                const newPos = this.itemPositions.get(item);
                item.style.transform = `translateY(${newPos - (parseFloat(item.style.top) || 0)}px)`;
            }

            // Move item to end of array for proper layering
            const itemIndex = this.items.indexOf(item);
            if (itemIndex > -1) {
                this.items.splice(itemIndex, 1);
                this.items.push(item);
            }
        }

        /**
         * Destroy the marquee animation and clean up
         */
        destroy() {
            // Stop animation
            this.pause();

            // Run all cleanup functions
            this.cleanupFunctions.forEach(cleanup => cleanup());
            
            // Clear references
            this.element = null;
            this.wrapper = null;
            this.content = null;
            this.items = [];
            this.itemPositions.clear();
            this.cleanupFunctions = [];
            this.cachedDimensions = null;
        }
    }

    /**
     * Marquee Manager - Handles all marquee instances on the page
     */
    class MarqueeManager {
        constructor() {
            this.instances = new Map();
            this.init();
        }

        /**
         * Initialize all marquees on the page
         */
        init() {
            // Wait for DOM ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => this.initializeAll());
            } else {
                this.initializeAll();
            }
        }

        /**
         * Initialize all marquee elements
         */
        initializeAll() {
            const marquees = document.querySelectorAll('.orb-marquee');
            marquees.forEach(marquee => this.initializeOne(marquee));
        }

        /**
         * Initialize a single marquee element
         * 
         * @param {HTMLElement} element - The marquee element
         */
        initializeOne(element) {
            // Skip if already initialized
            if (this.instances.has(element)) {
                return;
            }

            try {
                const instance = new MarqueeAnimation(element);
                this.instances.set(element, instance);
            } catch (error) {
                console.error('Failed to initialize marquee:', error, element);
            }
        }

        /**
         * Destroy a marquee instance
         * 
         * @param {HTMLElement} element - The marquee element
         */
        destroy(element) {
            const instance = this.instances.get(element);
            if (instance) {
                instance.destroy();
                this.instances.delete(element);
            }
        }

        /**
         * Destroy all marquee instances
         */
        destroyAll() {
            this.instances.forEach(instance => instance.destroy());
            this.instances.clear();
        }

        /**
         * Pause all marquee animations
         */
        pauseAll() {
            this.instances.forEach(instance => instance.pause());
        }

        /**
         * Resume all marquee animations
         */
        resumeAll() {
            this.instances.forEach(instance => instance.start());
        }
    }

    // Create global manager instance
    const marqueeManager = new MarqueeManager();

    // Public API
    window.OrbMarquee = {
        /**
         * Initialize or re-initialize all marquees
         */
        init: () => marqueeManager.initializeAll(),

        /**
         * Initialize a specific marquee element
         * 
         * @param {HTMLElement} element - The marquee element
         */
        initOne: (element) => marqueeManager.initializeOne(element),

        /**
         * Destroy a specific marquee instance
         * 
         * @param {HTMLElement} element - The marquee element
         */
        destroy: (element) => marqueeManager.destroy(element),

        /**
         * Destroy all marquee instances
         */
        destroyAll: () => marqueeManager.destroyAll(),

        /**
         * Pause all marquee animations
         */
        pauseAll: () => marqueeManager.pauseAll(),

        /**
         * Resume all marquee animations
         */
        resumeAll: () => marqueeManager.resumeAll(),

        /**
         * Get the manager instance (for advanced usage)
         */
        getManager: () => marqueeManager
    };

})();