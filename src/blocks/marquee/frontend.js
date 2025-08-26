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
    });


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

        if ( !content) {
            console.warn('Marquee block missing required elements');
            return;
        }

        // Extract configuration from CSS custom properties and classes
        const config = getMarqueeConfig(marquee);

        const contentRect = content.getBoundingClientRect()
        const contentSize = {
            width: contentRect.width,
            height: contentRect.height
        }
        // Set up content duplication for seamless scrolling
        setupContentDuplication(wrapper, content, config);

        // Set up dynamic animation properties
        setupAnimation(wrapper, contentSize, config);


        // Calculate speed in pixels per frame based on duration
        const isHorizontal = config.orientation === 'x';

        // Parse speed from config (e.g., "10s" for 10 seconds)
        let durationInSeconds = 20; // Default 20 seconds

        if (config.speed) {
            // Extract numeric value from "10s" format
            const speedMatch = config.speed.match(/^(\d+(?:\.\d+)?)s$/);
            if (speedMatch) {
                durationInSeconds = parseFloat(speedMatch[1]);
            }
        }

        // The speed should be for one content item to travel its own dimension
        // This makes the speed consistent regardless of viewport size
        const travelDistance = isHorizontal ? contentSize.width : contentSize.height;
        const pixelsPerSecond = travelDistance / durationInSeconds;
        const pixelsPerFrame = pixelsPerSecond / 60; // 60 FPS

        // Store config on wrapper for use in rotateMarquee
        wrapper.marqueeConfig = {
            speed: pixelsPerFrame,
            duration: durationInSeconds,
            contentWidth: contentSize.width,
            contentHeight: contentSize.height,
            orientation: config.orientation || 'x',
            direction: config.direction || 'normal'
        };

        // Start the rotation animation
        rotateMarquee(wrapper);

        // Add hover pause functionality if configured
        if (config.hover === 'paused') {
            wrapper.isPausing = false;
            wrapper.isResuming = false;
            wrapper.currentSpeed = 1; // Speed multiplier (1 = normal, 0 = stopped)
            
            marquee.addEventListener('mouseenter', () => {
                wrapper.isPausing = true;
                wrapper.isResuming = false;
            });

            marquee.addEventListener('mouseleave', () => {
                wrapper.isPausing = false;
                wrapper.isResuming = true;
            });
        }
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
            orientation: marquee.dataset.orientation,
            direction: marquee.dataset.direction,
            hover: marquee.dataset.hover,
            speed: marquee.dataset.speed,
            overlayColor: style.getPropertyValue('--marquee-overlay-color')
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

        let containerSize, contentSize;

        if (config.orientation === 'x') {
            containerSize = wrapperRect.width;
            contentSize = contentRect.width;
        } else {
            containerSize = wrapperRect.height;
            contentSize = contentRect.height;
        }

        // Calculate how many copies we need to fill the container
        // We need at least enough to fill the viewport + 1 extra for smooth scrolling
        let duplicatesNeeded = Math.ceil(containerSize / contentSize) + 1;

        // Cap at a reasonable maximum to prevent performance issues
        duplicatesNeeded = Math.min(duplicatesNeeded, 20);

        return duplicatesNeeded;
    }


    /**
     * Set up dynamic animation properties
     *
     * @param {HTMLElement} wrapper The wrapper element
     * @param {Object} contentSize The sizes of content element
     * @param {Object} config Configuration object
     */
    function setupAnimation(wrapper, contentSize, config) {

        wrapper.style.position = 'relative';
        wrapper.style.overflow = 'hidden';

        const isHorizontal = config.orientation === 'x';

        if (isHorizontal) {
            // For horizontal scrolling, set the height
            wrapper.style.height = contentSize.height + "px";
        } else {
            // For vertical scrolling, set both width and height
            wrapper.style.width = contentSize.width + "px";
            // Height should be based on viewport or a reasonable default
            const wrapperRect = wrapper.getBoundingClientRect();
            if (!wrapper.style.height || wrapperRect.height === 0) {
                // Set a default height if not already set
                wrapper.style.height = "400px"; // Default height for vertical marquee
            }
        }

        // Set up items array for the wrapper (treating wrapper as the container)
        wrapper.items = Array.from(wrapper.children);

        // Position each content block based on orientation and direction
        wrapper.items.forEach((item) => {
            // Set static properties once
            item.style.position = 'absolute';
            item.style.width = contentSize.width + "px";
            item.style.height = contentSize.height + "px";

            if (isHorizontal) {
                // Set vertical position once
                item.style.top = "0";
                
                // Only set the animated property (left)
                if (config.direction === 'reverse') {
                    // For reverse: align items to the right edge of container
                    const wrapperWidth = wrapper.getBoundingClientRect().width;
                    const totalContentWidth = wrapper.items.length * contentSize.width;
                    const startOffset = wrapperWidth - totalContentWidth;
                    item.style.left = (startOffset + contentSize.width * i) + "px";
                } else {
                    // For normal: align items to the left edge (start from 0)
                    item.style.left = contentSize.width * i + "px";
                }
            } else {
                // Set horizontal position once
                item.style.left = "0";
                
                // Only set the animated property (top)
                if (config.direction === 'reverse') {
                    // For reverse vertical: align items to the bottom edge of container
                    const wrapperHeight = wrapper.getBoundingClientRect().height;
                    const totalContentHeight = wrapper.items.length * contentSize.height;
                    const startOffset = wrapperHeight - totalContentHeight;
                    item.style.top = (startOffset + contentSize.height * i) + "px";
                } else {
                    // For normal: align items to the top edge (start from 0)
                    item.style.top = (contentSize.height * i) + "px";
                }
            }
        });

        // Fade in items smoothly after positioning
        setTimeout(() => {

            // Remove transition override after fade completes
            setTimeout(() => {
                wrapper.items.forEach(item => {
                    item.style.transition = '';
                });
            }, 500);
        }, 50);
    }

    function rotateMarquee(wrapper) {
        if (!wrapper || !wrapper.items || wrapper.items.length === 0) return;

        const config = wrapper.marqueeConfig || {
            speed: 1,
            contentWidth: 200,
            contentHeight: 100,
            orientation: 'x',
            direction: 'normal'
        };

        const isHorizontal = config.orientation === 'x';
        const isReverse = config.direction === 'reverse';

        // Initialize transform positions if not set
        if (!wrapper.itemPositions) {
            wrapper.itemPositions = new Map();
            wrapper.items.forEach((item) => {
                // Get initial position from style.left/top
                const initialPos = isHorizontal 
                    ? parseFloat(item.style.left) || 0
                    : parseFloat(item.style.top) || 0;
                wrapper.itemPositions.set(item, initialPos);
            });
        }
        
        // Handle smooth speed transitions for pause/resume
        if (wrapper.isPausing && wrapper.currentSpeed > 0) {
            // Gradually slow down
            wrapper.currentSpeed = Math.max(0, wrapper.currentSpeed - 0.05);
        } else if (wrapper.isResuming && wrapper.currentSpeed < 1) {
            // Gradually speed up
            wrapper.currentSpeed = Math.min(1, wrapper.currentSpeed + 0.02);
        }
        
        // Calculate actual speed with easing
        const actualSpeed = config.speed * (wrapper.currentSpeed !== undefined ? wrapper.currentSpeed : 1);

        // Move all items using transforms (more performant than left/top)
        for (let i = 0; i < wrapper.items.length; i++) {
            const item = wrapper.items[i];
            let currentPos = wrapper.itemPositions.get(item) || 0;
            
            // Update position with eased speed
            currentPos += isReverse ? actualSpeed : -actualSpeed;
            wrapper.itemPositions.set(item, currentPos);
            
            // Apply transform
            if (isHorizontal) {
                item.style.transform = `translateX(${currentPos - (parseFloat(item.style.left) || 0)}px)`;
            } else {
                item.style.transform = `translateY(${currentPos - (parseFloat(item.style.top) || 0)}px)`;
            }
        }

        // Check ALL items for repositioning (not just the first one)
        const itemsToReposition = [];
        const wrapperRect = wrapper.getBoundingClientRect();
        
        wrapper.items.forEach((item, index) => {
            const itemPos = wrapper.itemPositions.get(item) || 0;
            let needsReposition = false;
            
            if (isHorizontal) {
                if (isReverse) {
                    // For reverse, check if item has moved past right edge
                    needsReposition = itemPos > wrapperRect.width;
                } else {
                    // For normal, check if item has moved past left edge
                    needsReposition = itemPos + config.contentWidth < 0;
                }
            } else {
                if (isReverse) {
                    // For reverse, check if item has moved past bottom edge
                    needsReposition = itemPos > wrapperRect.height;
                } else {
                    // For normal, check if item has moved past top edge
                    needsReposition = itemPos + config.contentHeight < 0;
                }
            }
            
            if (needsReposition) {
                itemsToReposition.push({ item, index });
            }
        });
        
        // Reposition all items that need it
        itemsToReposition.forEach(({ item }) => {
            // Remove item from its current position in the array
            const itemIndex = wrapper.items.indexOf(item);
            if (itemIndex > -1) {
                wrapper.items.splice(itemIndex, 1);
            }

            // Find the edge position based on orientation and direction
            if (isHorizontal) {
                if (isReverse) {
                    // Find leftmost position for reverse horizontal
                    let leftmostPosition = Infinity;
                    wrapper.items.forEach(otherItem => {
                        const itemPos = wrapper.itemPositions.get(otherItem) || 0;
                        if (itemPos < leftmostPosition) {
                            leftmostPosition = itemPos;
                        }
                    });
                    const newPos = leftmostPosition - config.contentWidth;
                    wrapper.itemPositions.set(item, newPos);
                    item.style.transform = `translateX(${newPos - (parseFloat(item.style.left) || 0)}px)`;
                } else {
                    // Find rightmost position for normal horizontal
                    let rightmostPosition = -Infinity;
                    wrapper.items.forEach(otherItem => {
                        const itemPos = wrapper.itemPositions.get(otherItem) || 0;
                        const itemRight = itemPos + config.contentWidth;
                        if (itemRight > rightmostPosition) {
                            rightmostPosition = itemRight;
                        }
                    });
                    const newPos = rightmostPosition;
                    wrapper.itemPositions.set(item, newPos);
                    item.style.transform = `translateX(${newPos - (parseFloat(item.style.left) || 0)}px)`;
                }
            } else {
                if (isReverse) {
                    // Find topmost position for reverse vertical
                    let topmostPosition = Infinity;
                    wrapper.items.forEach(otherItem => {
                        const itemPos = wrapper.itemPositions.get(otherItem) || 0;
                        if (itemPos < topmostPosition) {
                            topmostPosition = itemPos;
                        }
                    });
                    const newPos = topmostPosition - config.contentHeight;
                    wrapper.itemPositions.set(item, newPos);
                    item.style.transform = `translateY(${newPos - (parseFloat(item.style.top) || 0)}px)`;
                } else {
                    // Find bottommost position for normal vertical
                    let bottommostPosition = -Infinity;
                    wrapper.items.forEach(otherItem => {
                        const itemPos = wrapper.itemPositions.get(otherItem) || 0;
                        const itemBottom = itemPos + config.contentHeight;
                        if (itemBottom > bottommostPosition) {
                            bottommostPosition = itemBottom;
                        }
                    });
                    const newPos = bottommostPosition;
                    wrapper.itemPositions.set(item, newPos);
                    item.style.transform = `translateY(${newPos - (parseFloat(item.style.top) || 0)}px)`;
                }
            }

            // Add it back to the end of the array
            wrapper.items.push(item);
        });

        // Continue the animation
        wrapper.animationID = requestAnimationFrame(() => rotateMarquee(wrapper));
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
