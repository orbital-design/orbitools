/**
 * Toolbar Reveal — frontend behaviour.
 *
 * Injects an invisible 6px hover strip across the top of the
 * viewport. Mousing into it adds the `--visible` class to
 * #wpadminbar so the CSS transition slides it down. Mousing off
 * the bar (with a short grace period) hides it again.
 *
 * Two extra niceties on top of that core loop:
 *   - Leaving through the very top edge of the viewport (URL bar,
 *     tab strip, browser chrome) does NOT start the hide timer.
 *     The user almost always comes straight back; yanking the bar
 *     out from under a planned click is the worst outcome.
 *   - Instead, we arm a one-shot document `mousemove` listener so
 *     that the next time the cursor lands anywhere in the page
 *     OTHER than the bar / hover zone, we hide. Re-entering the
 *     bar fires show() and disarms the watcher.
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

    function disarmRecapture() {
        document.removeEventListener('mousemove', maybeRecaptureHide);
    }

    function maybeRecaptureHide(event) {
        var target = event.target;
        // If the cursor came back to the bar or the hover zone,
        // the existing mouseenter handlers will call show() and
        // disarm this listener; nothing to do here.
        if (target === bar || bar.contains(target) || target === zone) {
            return;
        }
        // Cursor re-entered the page somewhere else — user is back
        // in normal browsing, hide the toolbar.
        disarmRecapture();
        bar.classList.remove(REVEAL_CLASS);
    }

    function show() {
        if (hideTimer !== null) {
            clearTimeout(hideTimer);
            hideTimer = null;
        }
        disarmRecapture();
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
        if (event.clientY <= 0) {
            // Cursor went off the top edge of the viewport (URL
            // bar / tab strip / browser chrome). Keep the bar
            // visible, but watch the document so the next move
            // back into page content hides it.
            document.addEventListener('mousemove', maybeRecaptureHide);
            return;
        }
        scheduleHide();
    }

    zone.addEventListener('mouseenter', show);
    bar.addEventListener('mouseenter', show);
    zone.addEventListener('mouseleave', handleMouseLeave);
    bar.addEventListener('mouseleave', handleMouseLeave);
}());
