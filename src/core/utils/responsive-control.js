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
var Button = wp.components.Button;
var Notice = wp.components.Notice;
var Tooltip = wp.components.Tooltip;
var icons = wp.icons || {};

/** Editor device type → our breakpoint slug. */
var DEVICE_TO_SLUG = { Desktop: 'base', Tablet: 'tablet', Mobile: 'mobile' };
var SLUG_TO_DEVICE = { base: 'Desktop', tablet: 'Tablet', mobile: 'Mobile' };
var DEVICE_ORDER = ['Desktop', 'Tablet', 'Mobile'];
var DEVICE_ICON = { Desktop: icons.desktop, Tablet: icons.tablet, Mobile: icons.mobile };

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
 * Which devices are offered, given the block's configured breakpoints.
 * Desktop is always present; Tablet / Mobile only when a breakpoint
 * with that slug exists in the config.
 */
function availableDevices(blockName) {
    var breakpoints = getBreakpointOptions(blockName) || [];
    var slugs = {};
    breakpoints.forEach(function (b) { slugs[b.slug] = b; });
    var devices = ['Desktop'];
    if (slugs.tablet) devices.push('Tablet');
    if (slugs.mobile) devices.push('Mobile');
    return { devices: devices, bySlug: slugs };
}

/**
 * The framework component.
 *
 * Props:
 *   - title       {string}   PanelBody title.
 *   - blockName   {string}   for breakpoint config lookup.
 *   - initialOpen {boolean}  PanelBody initialOpen (default false).
 *   - wrap        {boolean}  wrap in a PanelBody (default true). Pass
 *                            false to embed inside an existing panel.
 *   - render      {Function} ({ device, slug, breakpoint }) => element.
 */
export function ResponsiveControl(props) {
    var dt = useDeviceType();
    var device = dt.device;
    var slug = deviceToSlug(device);

    var info = availableDevices(props.blockName);
    var breakpoint = info.bySlug[slug] || null;

    // If the active preview is a device this block doesn't define a
    // breakpoint for, fall back to editing the Desktop/base value so
    // the control is never dead.
    var effectiveSlug = info.devices.indexOf(device) === -1 ? 'base' : slug;
    var effectiveBreakpoint = info.bySlug[effectiveSlug] || null;

    // Device switcher — mirrors (and drives) the editor toolbar's
    // preview toggle so the inspector and canvas stay in sync.
    var switcher = info.devices.length > 1
        ? createElement('div', { className: 'orbitools-responsive__devices' },
            info.devices.map(function (d) {
                return createElement(Tooltip, { key: d, text: d },
                    createElement(Button, {
                        size: 'small',
                        icon: DEVICE_ICON[d],
                        isPressed: device === d,
                        onClick: function () { dt.setDevice(d); },
                        'aria-label': d
                    })
                );
            })
        )
        : null;

    var hint = effectiveSlug !== 'base' && effectiveBreakpoint
        ? createElement(Notice, { status: 'info', isDismissible: false, className: 'orbitools-responsive__hint' },
            'Editing ' + (SLUG_TO_DEVICE[effectiveSlug] || effectiveSlug) +
            ' — applies at ' + effectiveBreakpoint.value + ' and below. Falls back to the Desktop value when unset.'
        )
        : null;

    var body = createElement(wp.element.Fragment, {},
        switcher,
        hint,
        createElement('div', { className: 'orbitools-responsive__control' },
            props.render({ device: device, slug: effectiveSlug, breakpoint: effectiveBreakpoint })
        )
    );

    if (props.wrap === false) {
        return body;
    }

    return createElement(PanelBody, {
        title: props.title,
        initialOpen: !!props.initialOpen
    }, body);
}

export { DEVICE_ORDER, DEVICE_TO_SLUG, SLUG_TO_DEVICE };
