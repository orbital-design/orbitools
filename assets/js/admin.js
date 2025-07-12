/**
 * Orbital Editor Suite Admin JavaScript
 * 
 * Common functionality for all admin pages
 */

document.addEventListener('DOMContentLoaded', function() {
    'use strict';
    
    // Initialize Orbital Editor Suite admin functionality
    Orbital_Admin.init();
});

var Orbital_Admin = {
    
    init: function() {
        this.bindEvents();
        this.initTooltips();
        this.initAnimations();
        this.initTabSwitching();
        this.initNotices();
    },
    
    bindEvents: function() {
        // Toggle switch animations for orbital controls
        document.querySelectorAll('.orbital-toggle-switch input[type="checkbox"]').forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                var slider = this.parentNode.querySelector('.orbital-slider');
                if (this.checked) {
                    slider.classList.add('checked');
                } else {
                    slider.classList.remove('checked');
                }
            });
        });
        
        // Settings card hover effects
        document.querySelectorAll('.orbital-card').forEach(function(card) {
            card.addEventListener('mouseenter', function() {
                this.classList.add('hovered');
            });
            card.addEventListener('mouseleave', function() {
                this.classList.remove('hovered');
            });
        });
        
        // Copy to clipboard functionality
        document.querySelectorAll('.orbital-copy-button').forEach(function(button) {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                var targetSelector = this.getAttribute('data-copy-target');
                var target = document.querySelector(targetSelector);
                if (target) {
                    Orbital_Admin.copyToClipboard(target.textContent || target.value);
                }
            });
        });
    },
    
    initTabSwitching: function() {
        // Global tab switching functionality for non-Vue pages only
        document.querySelectorAll('.orbital-tabs:not([data-vue-controlled])').forEach(function(tabContainer) {
            var tabs = tabContainer.querySelectorAll('.orbital-tab');
            var panels = document.querySelectorAll('[role="tabpanel"]');
            
            tabs.forEach(function(tab) {
                tab.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Remove active state from all tabs
                    tabs.forEach(function(t) {
                        t.classList.remove('active');
                        t.setAttribute('aria-selected', 'false');
                    });
                    
                    // Hide all panels
                    panels.forEach(function(panel) {
                        panel.style.display = 'none';
                    });
                    
                    // Activate clicked tab
                    this.classList.add('active');
                    this.setAttribute('aria-selected', 'true');
                    
                    // Show corresponding panel
                    var targetPanel = document.getElementById(this.getAttribute('aria-controls'));
                    if (targetPanel) {
                        targetPanel.style.display = 'block';
                    }
                });
            });
            
            // Set initial active state
            if (tabs.length > 0) {
                tabs[0].click();
            }
        });
    },
    
    initTooltips: function() {
        // Add tooltips to help icons
        document.querySelectorAll('.orbital-help-text').forEach(function(help) {
            var parent = help.closest('.orbital-field');
            if (parent) {
                // Create tooltip trigger
                var tooltip = document.createElement('span');
                tooltip.className = 'orbital-tooltip-trigger dashicons dashicons-editor-help';
                var label = parent.querySelector('label');
                if (label) {
                    label.appendChild(tooltip);
                }
                
                // Tooltip behavior
                tooltip.addEventListener('mouseenter', function() {
                    var content = document.createElement('div');
                    content.className = 'orbital-tooltip';
                    content.textContent = help.textContent;
                    document.body.appendChild(content);
                    
                    var rect = this.getBoundingClientRect();
                    content.style.position = 'absolute';
                    content.style.top = (rect.top + window.scrollY - content.offsetHeight - 10) + 'px';
                    content.style.left = (rect.left + window.scrollX - (content.offsetWidth / 2) + (this.offsetWidth / 2)) + 'px';
                    content.style.zIndex = '9999';
                });
                
                tooltip.addEventListener('mouseleave', function() {
                    document.querySelectorAll('.orbital-tooltip').forEach(function(tooltip) {
                        tooltip.remove();
                    });
                });
            }
        });
    },
    
    initAnimations: function() {
        // Staggered animation for settings cards
        document.querySelectorAll('.orbital-card').forEach(function(card, index) {
            card.style.animationDelay = (index * 0.1) + 's';
        });
        
        // Smooth scrolling for internal links
        document.querySelectorAll('a[href^="#"]').forEach(function(link) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                var targetId = this.getAttribute('href').substring(1);
                var target = document.getElementById(targetId);
                if (target) {
                    var targetPosition = target.offsetTop - 100;
                    window.scrollTo({
                        top: targetPosition,
                        behavior: 'smooth'
                    });
                }
            });
        });
    },
    
    initNotices: function() {
        // Auto-dismiss notices after 5 seconds
        document.querySelectorAll('.orbital-notice.is-dismissible').forEach(function(notice) {
            setTimeout(function() {
                notice.style.opacity = '0';
                notice.style.transition = 'opacity 0.3s';
                setTimeout(function() {
                    if (notice.parentNode) {
                        notice.remove();
                    }
                }, 300);
            }, 5000);
        });
    },
    
    showNotice: function(message, type) {
        type = type || 'info';
        
        var notice = document.createElement('div');
        notice.className = 'orbital-notice orbital-notice-' + type;
        notice.innerHTML = '<p>' + message + '</p>';
        
        // Remove existing notices
        document.querySelectorAll('.orbital-notice').forEach(function(existingNotice) {
            existingNotice.remove();
        });
        
        // Add new notice
        var adminContent = document.querySelector('.orbital-notices-container');
        if (adminContent) {
            adminContent.appendChild(notice);
        }
        
        // Auto-hide after 5 seconds
        setTimeout(function() {
            notice.style.opacity = '0';
            notice.style.transition = 'opacity 0.3s';
            setTimeout(function() {
                if (notice.parentNode) {
                    notice.remove();
                }
            }, 300);
        }, 5000);
    },
    
    copyToClipboard: function(text) {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(function() {
                Orbital_Admin.showNotice('Copied to clipboard!', 'success');
            }).catch(function() {
                Orbital_Admin.fallbackCopyToClipboard(text);
            });
        } else {
            Orbital_Admin.fallbackCopyToClipboard(text);
        }
    },
    
    fallbackCopyToClipboard: function(text) {
        // Fallback for older browsers
        var textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        textArea.style.top = '-999999px';
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        
        try {
            document.execCommand('copy');
            Orbital_Admin.showNotice('Copied to clipboard!', 'success');
        } catch (err) {
            Orbital_Admin.showNotice('Copy to clipboard failed', 'error');
        }
        
        document.body.removeChild(textArea);
    }
};

// Utility functions
window.Orbital_Utils = {
    
    formatClassName: function(className) {
        return className.replace(/[^a-z0-9-]/gi, '').toLowerCase();
    },
    
    debounce: function(func, wait, immediate) {
        var timeout;
        return function() {
            var context = this, args = arguments;
            var later = function() {
                timeout = null;
                if (!immediate) func.apply(context, args);
            };
            var callNow = immediate && !timeout;
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
            if (callNow) func.apply(context, args);
        };
    },
    
    isElementInViewport: function(el) {
        var rect = el.getBoundingClientRect();
        return (
            rect.top >= 0 &&
            rect.left >= 0 &&
            rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
            rect.right <= (window.innerWidth || document.documentElement.clientWidth)
        );
    }
};