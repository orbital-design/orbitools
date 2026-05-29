/**
 * Toolbar Reveal — frontend behaviour.
 *
 * Injects an invisible 6px hover strip across the top of the
 * viewport. Mousing into it adds the `--visible` class to
 * #wpadminbar so the CSS transition slides it down. Mousing off
 * the bar (with a short grace period) hides it again.
 *
 * Vanilla JS — no jQuery, no dependencies. The bar's own
 * `:focus-within` rule handles keyboard reveal, and the touch
 * fallback lives in CSS (`@media (hover: none)`).
 */
(function () {
    'use strict';

    var bar = document.getElementById('wpadminbar');
    if (!bar) {
        return;
    }

    var zone = document.createElement('div');
    zone.className = 'orbitools-toolbar-reveal__zone';
    zone.setAttribute('aria-hidden', 'true');
    document.body.appendChild(zone);

    var REVEAL_CLASS = 'orbitools-toolbar-reveal--visible';
    var HIDE_DELAY_MS = 300;
    var hideTimer = null;

    function show() {
        if (hideTimer !== null) {
            clearTimeout(hideTimer);
            hideTimer = null;
        }
        bar.classList.add(REVEAL_CLASS);
    }

    function scheduleHide() {
        if (hideTimer !== null) {
            clearTimeout(hideTimer);
        }
        hideTimer = window.setTimeout(function () {
            hideTimer = null;
            // Don't yank it out from under a hover or an open
            // submenu / focused link.
            if (bar.matches(':hover') || bar.matches(':focus-within')) {
                return;
            }
            bar.classList.remove(REVEAL_CLASS);
        }, HIDE_DELAY_MS);
    }

    function handleMouseLeave(event) {
        // If the cursor left through the *top* edge of the viewport
        // (URL bar, tab strip, browser chrome) keep the toolbar
        // visible — the user almost always comes straight back, and
        // having the bar vanish out from under a planned click is
        // worse than briefly leaving it up.
        if (event.clientY <= 0) {
            return;
        }
        scheduleHide();
    }

    zone.addEventListener('mouseenter', show);
    bar.addEventListener('mouseenter', show);
    zone.addEventListener('mouseleave', handleMouseLeave);
    bar.addEventListener('mouseleave', handleMouseLeave);
}());
