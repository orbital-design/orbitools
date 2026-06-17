/**
 * Responsive Control framework — device-aware.
 *
 * A reusable wrapper that turns any control into a per-viewport
 * (responsive) control driven by the editor's native screen-size
 * toggle. Instead of a custom breakpoint tab bar, the active editor
 * preview device decides which value you're editing:
 *
 *   Desktop → 'base'    (no media query — the default value)
 *   Tablet  → 'tablet'  (@media max-width: 781px)
 *   Mobile  → 'mobile'  (@media max-width: 479px)
 *
 * The 781 / 479 widths match WordPress's device-preview canvas, so
 * resizing the preview actually fires the generated max-width CSS and
 * the canvas reflects the real responsive output.
 *
 * Usage (any control can opt in):
 *
 *   createElement(ResponsiveControl, {
 *       title: 'Aspect Ratio',
 *       blockName: blockName,
 *       render: function (ctx) {
 *           // ctx = { device, slug, breakpoint }
 *           return createElement(SelectControl, {
 *               value: getValue(ctx.slug),
 *               onChange: function (v) { setValue(ctx.slug, v); },
 *               ...
 *           });
 *       },
 *   });
 *
 * The framework owns: device detection (version-safe across
 * core/editor ↔ core/edit-post), the device switcher chrome (which
 * also drives the editor's real preview so the inspector and toolbar
 * stay in sync), and the cascade hint. The consumer owns only the
 * actual input control and how it reads/writes its own attribute
 * slice for the active `slug`.
 *
 * Plain JS + wp.element.createElement on purpose — the controls build
 * (webpack.assets.js) transpiles JS with preset-env only, no JSX/TS.
 *
 * @file core/utils/responsive-control.js
 * @since 2.0.0
 */

import { getBreakpointOptions } from './breakpoints.js';

var createElement = wp.element.createElement;
var useSelect = wp.data.useSelect;
var useDispatch = wp.data.useDispatch;
var PanelBody = wp.components.PanelBody;
var DropdownMenu = wp.components.DropdownMenu;

/** Editor device type → our breakpoint slug. */
var DEVICE_TO_SLUG = { Desktop: 'base', Tablet: 'tablet', Mobile: 'mobile' };
var SLUG_TO_DEVICE = { base: 'Desktop', tablet: 'Tablet', mobile: 'Mobile' };
var DEVICE_ORDER = ['Desktop', 'Tablet', 'Mobile'];

/**
 * Inline device icons — built from wp.element only. We deliberately do
 * NOT use @wordpress/icons (`wp.icons`): in WP 7.0 there is no
 * registered `wp-icons` script handle, so declaring it as a dependency
 * makes WordPress silently drop the whole control script. These SVGs
 * inherit `currentColor`, so they tint with the button state.
 */
function deviceSvg(children) {
    return createElement('svg', {
        width: 20,
        height: 20,
        viewBox: '0 0 24 24',
        xmlns: 'http://www.w3.org/2000/svg',
        'aria-hidden': true,
        focusable: false
    }, children);
}

var DEVICE_ICON = {
    Desktop: deviceSvg([
        createElement('rect', { key: 'b', x: 3, y: 4, width: 18, height: 12, rx: 1.5, fill: 'none', stroke: 'currentColor', 'stroke-width': 1.6 }),
        createElement('path', { key: 's', d: 'M8.5 20h7M12 16.5v3.5', fill: 'none', stroke: 'currentColor', 'stroke-width': 1.6, 'stroke-linecap': 'round' })
    ]),
    Tablet: deviceSvg([
        createElement('rect', { key: 'b', x: 6, y: 3, width: 12, height: 18, rx: 2, fill: 'none', stroke: 'currentColor', 'stroke-width': 1.6 }),
        createElement('circle', { key: 'h', cx: 12, cy: 18, r: 0.85, fill: 'currentColor' })
    ]),
    Mobile: deviceSvg([
        createElement('rect', { key: 'b', x: 8, y: 3, width: 8, height: 18, rx: 2, fill: 'none', stroke: 'currentColor', 'stroke-width': 1.6 }),
        createElement('circle', { key: 'h', cx: 12, cy: 18, r: 0.75, fill: 'currentColor' })
    ])
};

/** Map an editor device type to our breakpoint slug. */
export function deviceToSlug(device) {
    return DEVICE_TO_SLUG[device] || 'base';
}

/**
 * Read + set the editor's preview device type, version-safe.
 * getDeviceType / setDeviceType moved from core/edit-post to
 * core/editor around WP 6.5; support both, default to Desktop.
 */
export function useDeviceType() {
    var device = useSelect(function (select) {
        var editor = select('core/editor');
        if (editor && editor.getDeviceType) {
            return editor.getDeviceType();
        }
        var editPost = select('core/edit-post');
        if (editPost && editPost.__experimentalGetPreviewDeviceType) {
            return editPost.__experimentalGetPreviewDeviceType();
        }
        return 'Desktop';
    }, []);

    var editorDispatch = useDispatch('core/editor');
    var editPostDispatch = useDispatch('core/edit-post');

    function setDevice(d) {
        if (editorDispatch && editorDispatch.setDeviceType) {
            editorDispatch.setDeviceType(d);
        } else if (editPostDispatch && editPostDispatch.__experimentalSetPreviewDeviceType) {
            editPostDispatch.__experimentalSetPreviewDeviceType(d);
        }
    }

    return { device: device || 'Desktop', setDevice: setDevice };
}

/**
 * Build the list of CSS classes for a responsive value object.
 * `base` → bare class; other slugs → `slug:prefix-value` overrides.
 *
 * @param {Object} responsiveValue e.g. { base: '16-9', tablet: '1-1' }
 * @param {string} classPrefix     e.g. 'has-aspect-ratio'
 * @param {Function} [formatValue] optional value formatter
 * @return {string} space-joined class list
 */
export function getResponsiveClasses(responsiveValue, classPrefix, formatValue) {
    var classes = [];
    if (!responsiveValue) {
        return '';
    }
    Object.keys(responsiveValue).forEach(function (slug) {
        var value = responsiveValue[slug];
        if (value === undefined || value === null || value === '') {
            return;
        }
        var formatted = formatValue ? formatValue(value) : String(value);
        classes.push(slug === 'base' ? classPrefix + '-' + formatted : slug + ':' + classPrefix + '-' + formatted);
    });
    return classes.join(' ');
}

/**
 * Look up the px breakpoint for a slug (for the cascade hint only).
 * Behaviour never depends on this — it's purely cosmetic.
 */
function breakpointForSlug(blockName, slug) {
    var breakpoints = getBreakpointOptions(blockName) || [];
    for (var i = 0; i < breakpoints.length; i++) {
        if (breakpoints[i].slug === slug) {
            return breakpoints[i];
        }
    }
    return null;
}

/**
 * The framework component.
 *
 * Props:
 *   - title       {string}   PanelBody title.
 *   - blockName   {string}   for the (cosmetic) breakpoint hint lookup.
 *   - initialOpen {boolean}  PanelBody initialOpen (default false).
 *   - wrap        {boolean}  wrap in a PanelBody (default true). Pass
 *                            false to embed inside an existing panel.
 *   - render      {Function} ({ device, slug, breakpoint }) => element.
 *
 * The three editor preview devices (Desktop / Tablet / Mobile) are
 * always offered and map straight through to base / tablet / mobile —
 * deliberately NOT gated on the block's breakpoint config, so the
 * control can never silently collapse to Desktop-only and write every
 * device's value to `base`.
 */
export function ResponsiveControl(props) {
    var dt = useDeviceType();
    var device = dt.device;
    var slug = deviceToSlug(device);
    var breakpoint = breakpointForSlug(props.blockName, slug);

    // Device switcher — a compact dropdown whose toggle is the current
    // device's icon. Docked onto the panel's title row (see CSS) so it
    // costs no extra height; selecting a device also drives the editor's
    // preview, keeping the inspector and canvas in sync.
    var switcher = createElement(DropdownMenu, {
        icon: DEVICE_ICON[device],
        label: 'Editing device: ' + device,
        popoverProps: { placement: 'bottom-end' },
        toggleProps: { size: 'small', className: 'orbitools-responsive__toggle' },
        controls: DEVICE_ORDER.map(function (d) {
            return {
                title: d,
                icon: DEVICE_ICON[d],
                isActive: device === d,
                onClick: function () { dt.setDevice(d); }
            };
        })
    });

    var control = createElement('div', { className: 'orbitools-responsive__control' },
        props.render({ device: device, slug: slug, breakpoint: breakpoint })
    );

    // No panel header to dock the dropdown onto — show it inline,
    // right-aligned above the control instead.
    if (props.wrap === false) {
        return createElement(wp.element.Fragment, {},
            createElement('div', { className: 'orbitools-responsive__devices orbitools-responsive__devices--inline' }, switcher),
            control
        );
    }

    return createElement('div', { className: 'orbitools-responsive' },
        createElement('div', { className: 'orbitools-responsive__devices' }, switcher),
        createElement(PanelBody, {
            title: props.title,
            initialOpen: !!props.initialOpen
        }, control)
    );
}

export { DEVICE_ORDER, DEVICE_TO_SLUG, SLUG_TO_DEVICE };
