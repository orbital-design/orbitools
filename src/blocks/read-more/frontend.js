/**
 * Read More Block Frontend JavaScript
 *
 * Handles toggle functionality with smooth animations for read more content.
 * Uses proper accessibility attributes and CSS transitions for smooth expand/collapse.
 *
 * @file blocks/read-more/frontend.js
 * @since 1.0.0
 */

(function() {
    'use strict';

    // Wait for DOM to be fully loaded before initializing
    document.addEventListener('DOMContentLoaded', function() {
        // Find all read more toggle buttons on the page
        // Uses the BEM class for targeting
        const toggles = document.querySelectorAll('.orb-read-more__toggle');

        // Initialize each toggle button
        toggles.forEach(function(toggle) {
            toggle.addEventListener('click', function() {
                // Check current state from accessibility attribute
                const expanded = this.getAttribute('aria-expanded') === 'true';

                // Get the ID of the content area this button controls
                const contentId = this.getAttribute('aria-controls');
                const content = document.getElementById(contentId);

                // Get button text states and icon type from data attributes
                const openText = this.getAttribute('data-open-text') || 'Read More';
                const closeText = this.getAttribute('data-close-text') || 'Read Less';
                const iconType = this.getAttribute('data-icon-type') || 'chevron';

                // Only proceed if we found the matching content area
                if (content) {
                    if (expanded) {
                        // Currently open - close it
                        // Update accessibility attributes for screen readers
                        this.setAttribute('aria-expanded', 'false');
                        content.setAttribute('aria-hidden', 'true');

                        // Update button text and icon state, remove visual state class
                        updateButtonContent(this, openText, iconType, false);
                        this.classList.remove('orb-read-more__toggle--is-open');

                        // Animate the closing with smooth transition
                        slideUp(content);
                    } else {
                        // Currently closed - open it
                        // Update accessibility attributes for screen readers
                        this.setAttribute('aria-expanded', 'true');
                        content.setAttribute('aria-hidden', 'false');

                        // Update button text and icon state, add visual state class
                        updateButtonContent(this, closeText, iconType, true);
                        this.classList.add('orb-read-more__toggle--is-open');

                        // Animate the opening with smooth transition
                        slideDown(content);
                    }
                }
            });
        });

        /**
         * Update button content with text and appropriate icon
         *
         * @param {HTMLElement} button The button element
         * @param {string} text The text to display
         * @param {string} iconType The type of icon (none, chevron, arrow, plus)
         * @param {boolean} isOpen Whether the content is expanded
         */
        function updateButtonContent(button, text, iconType, isOpen) {
            // Find the text span within the button
            const textSpan = button.querySelector('.orb-read-more__text');

            if (textSpan) {
                // Update only the text content, preserving the icon
                textSpan.textContent = text;
            }

            // Update icon state for animation (but don't recreate it)
            const iconSpan = button.querySelector('.orb-read-more__icon');
            if (iconSpan && iconType !== 'none') {
                // Toggle rotation class for animation
                if (isOpen) {
                    iconSpan.classList.add('orb-read-more__icon--is-rotated');
                } else {
                    iconSpan.classList.remove('orb-read-more__icon--is-rotated');
                }
            }
        }

        /**
         * Slide down animation (expand/show content)
         *
         * Creates smooth expansion by transitioning from 0 to natural height.
         * Also handles padding/margins to prevent content jumping.
         *
         * @param {HTMLElement} element The content container to expand
         */
        function slideDown(element) {
            // Make visible and clear any existing styles
            element.style.display = 'block';
            element.style.removeProperty('height');
            element.style.removeProperty('overflow');
            element.style.removeProperty('transition');

            // Measure the natural height
            const height = element.scrollHeight;

            // Start from collapsed state
            element.style.height = '0px';
            element.style.overflow = 'hidden';
            element.style.transition = 'height 0.3s ease-out';

            // Force reflow then animate to full height
            element.offsetHeight;
            element.style.height = height + 'px';

            // Clean up after animation
            setTimeout(function() {
                element.style.removeProperty('height');
                element.style.removeProperty('overflow');
                element.style.removeProperty('transition');
            }, 300);
        }

        /**
         * Slide up animation (collapse/hide content)
         *
         * Creates smooth collapse by transitioning from natural height to 0.
         * Sets display:none at the end to fully hide from screen readers.
         *
         * @param {HTMLElement} element The content container to collapse
         */
        function slideUp(element) {
            // Get current height as starting point
            const height = element.scrollHeight;

            // Set up initial state for animation
            element.style.height = height + 'px';
            element.style.overflow = 'hidden';
            element.style.transition = 'height 0.3s ease-out';

            // Force reflow before animating
            element.offsetHeight;

            // Animate to collapsed state
            element.style.height = '0px';

            // After animation completes, fully hide and clean up
            setTimeout(function() {
                element.style.display = 'none';
                element.style.removeProperty('height');
                element.style.removeProperty('overflow');
                element.style.removeProperty('transition');
            }, 300);
        }
    });
})();
