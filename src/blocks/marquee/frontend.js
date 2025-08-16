/**
 * Marquee Block Frontend JavaScript
 *
 * Handles marquee animation functionality including content duplication for seamless
 * scrolling, dynamic speed calculation, and proper initialization for accessibility.
 *
 * @file blocks/marquee/frontend.js
 * @since 1.0.0
 */

(function() {
    'use strict';

    /**
     * Initialize marquee functionality when DOM is ready
     */
    document.addEventListener('DOMContentLoaded', function() {
        initializeMarquees();
        
        // Set up mutation observer after DOM is ready
        setupMutationObserver();
    });

    /**
     * Set up MutationObserver to watch for dynamically added marquees
     */
    function setupMutationObserver() {
        if (!window.MutationObserver || !document.body) {
            return;
        }

        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList') {
                    // Check if any new marquee blocks were added
                    const addedNodes = Array.from(mutation.addedNodes);
                    const hasMarqueeBlocks = addedNodes.some(node => 
                        node.nodeType === Node.ELEMENT_NODE && 
                        (node.classList?.contains('orb-marquee') || 
                         node.querySelector?.('.orb-marquee'))
                    );
                    
                    if (hasMarqueeBlocks) {
                        initializeMarquees();
                    }
                }
            });
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    /**
     * Find and initialize all marquee blocks on the page
     */
    function initializeMarquees() {
        const marquees = document.querySelectorAll('.orb-marquee');
        
        marquees.forEach(function(marquee) {
            // Skip if already initialized
            if (marquee.hasAttribute('data-marquee-initialized')) {
                return;
            }

            initializeMarquee(marquee);
            marquee.setAttribute('data-marquee-initialized', 'true');
        });
    }

    /**
     * Initialize a single marquee block
     *
     * @param {HTMLElement} marquee The marquee block element
     */
    function initializeMarquee(marquee) {
        const wrapper = marquee.querySelector('.orb-marquee__wrapper');
        const content = marquee.querySelector('.orb-marquee__content');
        
        if (!wrapper || !content) {
            console.warn('Marquee block missing required elements');
            return;
        }

        // Extract configuration from CSS custom properties and classes
        const config = getMarqueeConfig(marquee);
        
        // Set up content duplication for seamless scrolling
        setupContentDuplication(wrapper, content, config);
        
        // Set up dynamic animation properties
        setupAnimation(marquee, wrapper, config);
        
        // Set up accessibility features
        setupAccessibility(marquee, config);
        
        // Set up performance optimizations
        setupPerformanceOptimizations(wrapper);
    }

    /**
     * Extract marquee configuration from element
     *
     * @param {HTMLElement} marquee The marquee element
     * @returns {Object} Configuration object
     */
    function getMarqueeConfig(marquee) {
        const style = getComputedStyle(marquee);
        
        return {
            orientation: marquee.classList.contains('orb-marquee--y') ? 'y' : 'x',
            direction: marquee.classList.contains('orb-marquee--reverse') ? 'reverse' : 'normal',
            hoverBehavior: marquee.classList.contains('orb-marquee--hover-running') ? 'running' : 'paused',
            speed: style.getPropertyValue('--marquee-speed') || '10s',
            gap: style.getPropertyValue('--marquee-gap') || '40px',
            overlayColor: style.getPropertyValue('--marquee-overlay-color'),
            whiteSpace: style.getPropertyValue('--marquee-white-space') || 'wrap',
            autoFill: marquee.dataset.autoFill === 'true' || marquee.dataset.autoFill === '1',
            minDuplicates: parseInt(marquee.dataset.minDuplicates) || 2
        };
    }

    /**
     * Set up content duplication for seamless scrolling
     *
     * @param {HTMLElement} wrapper The wrapper element
     * @param {HTMLElement} content The content element
     * @param {Object} config Configuration object
     */
    function setupContentDuplication(wrapper, content, config) {
        // Remove any existing clones first
        const existingClones = wrapper.querySelectorAll('.orb-marquee__content--clone');
        existingClones.forEach(clone => clone.remove());

        // Skip duplication if autoFill is disabled
        if (!config.autoFill) {
            // Still create at least one clone for basic marquee effect
            const clone = content.cloneNode(true);
            clone.setAttribute('aria-hidden', 'true');
            clone.classList.add('orb-marquee__content--clone');
            wrapper.appendChild(clone);
            return;
        }

        // Calculate how many duplicates we need
        const duplicatesNeeded = calculateDuplicatesNeeded(wrapper, content, config);
        
        // Create the calculated number of clones
        for (let i = 0; i < duplicatesNeeded; i++) {
            const clone = content.cloneNode(true);
            clone.setAttribute('aria-hidden', 'true');
            clone.classList.add('orb-marquee__content--clone');
            clone.dataset.cloneIndex = i + 1; // For debugging
            wrapper.appendChild(clone);
        }

        // Add data attribute to track the number of duplicates created
        wrapper.dataset.duplicateCount = duplicatesNeeded;
    }

    /**
     * Calculate how many content duplicates are needed for seamless scrolling
     *
     * @param {HTMLElement} wrapper The wrapper element
     * @param {HTMLElement} content The content element
     * @param {Object} config Configuration object
     * @returns {number} Number of duplicates needed
     */
    function calculateDuplicatesNeeded(wrapper, content, config) {
        const wrapperRect = wrapper.getBoundingClientRect();
        const contentRect = content.getBoundingClientRect();
        
        let containerSize, contentSize, gapValue;
        
        // Parse gap value to pixels
        const gapMatch = config.gap.match(/^(\d+(?:\.\d+)?)(px|rem|em)?$/);
        if (gapMatch) {
            const value = parseFloat(gapMatch[1]);
            const unit = gapMatch[2] || 'px';
            
            if (unit === 'rem') {
                gapValue = value * parseFloat(getComputedStyle(document.documentElement).fontSize);
            } else if (unit === 'em') {
                gapValue = value * parseFloat(getComputedStyle(content).fontSize);
            } else {
                gapValue = value;
            }
        } else {
            gapValue = 40; // Default gap
        }
        
        if (config.orientation === 'x') {
            containerSize = wrapperRect.width;
            contentSize = contentRect.width + gapValue;
        } else {
            containerSize = wrapperRect.height;
            contentSize = contentRect.height + gapValue;
        }
        
        // Calculate how many copies we need to fill the container
        // We need at least enough to fill the viewport + 1 extra for smooth scrolling
        let duplicatesNeeded = Math.ceil(containerSize / contentSize) + 1;
        
        // Ensure we meet the minimum duplicates requirement
        duplicatesNeeded = Math.max(duplicatesNeeded, config.minDuplicates);
        
        // Cap at a reasonable maximum to prevent performance issues
        duplicatesNeeded = Math.min(duplicatesNeeded, 20);
        
        return duplicatesNeeded;
    }


    /**
     * Set up dynamic animation properties
     *
     * @param {HTMLElement} marquee The marquee element
     * @param {HTMLElement} wrapper The wrapper element
     * @param {Object} config Configuration object
     */
    function setupAnimation(marquee, wrapper, config) {
        // Set animation name based on orientation
        const animationName = config.orientation === 'x' ? 
            'orb-marquee-scroll-x' : 'orb-marquee-scroll-y';
        
        wrapper.style.setProperty('--marquee-animation-name', animationName);
        wrapper.style.setProperty('--marquee-animation-direction', config.direction);
        
        // Calculate optimal speed based on content size
        const calculatedSpeed = calculateOptimalSpeed(wrapper, config);
        if (calculatedSpeed) {
            wrapper.style.animationDuration = calculatedSpeed;
        }
    }

    /**
     * Calculate optimal animation speed based on content
     *
     * @param {HTMLElement} wrapper The wrapper element
     * @param {Object} config Configuration object
     * @returns {string|null} Calculated speed or null if calculation fails
     */
    function calculateOptimalSpeed(wrapper, config) {
        try {
            const content = wrapper.querySelector('.orb-marquee__content');
            if (!content) return null;

            const contentRect = content.getBoundingClientRect();
            const wrapperRect = wrapper.getBoundingClientRect();
            
            // Calculate distance content needs to travel
            const distance = config.orientation === 'x' ? 
                contentRect.width : contentRect.height;
            
            // Base speed from config (convert to seconds)
            const baseSpeedMatch = config.speed.match(/^(\d+(?:\.\d+)?)(s|ms)$/);
            if (!baseSpeedMatch) return null;
            
            const baseSpeed = parseFloat(baseSpeedMatch[1]);
            const unit = baseSpeedMatch[2];
            const baseSpeedInSeconds = unit === 'ms' ? baseSpeed / 1000 : baseSpeed;
            
            // Adjust speed based on content size (longer content = proportionally longer time)
            const containerSize = config.orientation === 'x' ? 
                wrapperRect.width : wrapperRect.height;
            
            const speedMultiplier = Math.max(1, distance / containerSize);
            const calculatedSpeed = baseSpeedInSeconds * speedMultiplier;
            
            return calculatedSpeed + 's';
        } catch (error) {
            console.warn('Error calculating marquee speed:', error);
            return null;
        }
    }

    /**
     * Set up accessibility features
     *
     * @param {HTMLElement} marquee The marquee element
     * @param {Object} config Configuration object
     */
    function setupAccessibility(marquee, config) {
        // Add ARIA attributes
        marquee.setAttribute('role', 'marquee');
        marquee.setAttribute('aria-live', 'off'); // Don't announce changes
        
        // Add pause button for accessibility
        addPauseButton(marquee);
        
        // Respect reduced motion preferences
        if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            marquee.classList.add('orb-marquee--reduced-motion');
        }
    }

    /**
     * Add accessibility pause/play button
     *
     * @param {HTMLElement} marquee The marquee element
     */
    function addPauseButton(marquee) {
        const button = document.createElement('button');
        button.className = 'orb-marquee__pause-button';
        button.setAttribute('aria-label', 'Pause marquee animation');
        button.innerHTML = '⏸️'; // Pause icon
        
        let isPaused = false;
        
        button.addEventListener('click', function() {
            const wrapper = marquee.querySelector('.orb-marquee__wrapper');
            if (!wrapper) return;
            
            if (isPaused) {
                wrapper.style.animationPlayState = 'running';
                button.innerHTML = '⏸️';
                button.setAttribute('aria-label', 'Pause marquee animation');
                isPaused = false;
            } else {
                wrapper.style.animationPlayState = 'paused';
                button.innerHTML = '▶️';
                button.setAttribute('aria-label', 'Resume marquee animation');
                isPaused = true;
            }
        });
        
        // Position the button
        button.style.cssText = `
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            border: none;
            border-radius: 4px;
            padding: 4px 8px;
            cursor: pointer;
            z-index: 10;
            font-size: 12px;
            opacity: 0;
            transition: opacity 0.3s ease;
        `;
        
        // Show button on hover or focus
        marquee.addEventListener('mouseenter', () => button.style.opacity = '1');
        marquee.addEventListener('mouseleave', () => button.style.opacity = '0');
        marquee.addEventListener('focusin', () => button.style.opacity = '1');
        marquee.addEventListener('focusout', () => button.style.opacity = '0');
        
        marquee.appendChild(button);
    }

    /**
     * Set up performance optimizations
     *
     * @param {HTMLElement} wrapper The wrapper element
     */
    function setupPerformanceOptimizations(wrapper) {
        // Use Intersection Observer to pause animations when not visible
        if (window.IntersectionObserver) {
            const observer = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        // Element is visible, ensure animation is running
                        if (!wrapper.style.animationPlayState || wrapper.style.animationPlayState === 'paused') {
                            wrapper.style.animationPlayState = 'running';
                        }
                    } else {
                        // Element is not visible, pause animation for performance
                        wrapper.style.animationPlayState = 'paused';
                    }
                });
            }, {
                rootMargin: '50px' // Start animation a bit before element comes into view
            });
            
            observer.observe(wrapper);
        }
        
        // Recalculate duplicates on resize
        let resizeTimeout;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(function() {
                // Find the original content (not a clone)
                const originalContent = wrapper.querySelector('.orb-marquee__content:not(.orb-marquee__content--clone)');
                if (originalContent) {
                    const marquee = wrapper.closest('.orb-marquee');
                    const config = getMarqueeConfig(marquee);
                    
                    // Only recalculate if autoFill is enabled
                    if (config.autoFill) {
                        // Re-setup content duplication with new dimensions
                        setupContentDuplication(wrapper, originalContent, config);
                    }
                }
            }, 250);
        });
    }

    /**
     * Public API for external control
     */
    window.OrbMarquee = {
        /**
         * Initialize or re-initialize all marquees
         */
        init: initializeMarquees,
        
        /**
         * Pause all marquee animations
         */
        pauseAll: function() {
            const wrappers = document.querySelectorAll('.orb-marquee__wrapper');
            wrappers.forEach(wrapper => {
                wrapper.style.animationPlayState = 'paused';
            });
        },
        
        /**
         * Resume all marquee animations
         */
        resumeAll: function() {
            const wrappers = document.querySelectorAll('.orb-marquee__wrapper');
            wrappers.forEach(wrapper => {
                wrapper.style.animationPlayState = 'running';
            });
        }
    };

})();