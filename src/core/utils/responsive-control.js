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
 * The framework owns device detection (version-safe across
 * core/editor ↔ core/edit-post) and reflecting the editor's active
 * preview device in the panel title. There is no in-panel switcher —
 * the editor's own screen-size preview toggle is the single control.
 * The consumer owns only the actual input and how it reads/writes its
 * own attribute slice for the active `slug`.
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
var Tooltip = wp.components.Tooltip;

/** Editor device type → our breakpoint slug. */
var DEVICE_TO_SLUG = { Desktop: 'base', Tablet: 'tablet', Mobile: 'mobile' };
var SLUG_TO_DEVICE = { base: 'Desktop', tablet: 'Tablet', mobile: 'Mobile' };
var DEVICE_ORDER = ['Desktop', 'Tablet', 'Mobile'];

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
 * Decide whether a responsive value object holds a real value for a slug.
 * Empty strings, null/undefined, and empty objects/arrays count as unset.
 */
function slugHasValue(value, slug) {
    if (!value) {
        return false;
    }
    var v = value[slug];
    if (v === undefined || v === null || v === '') {
        return false;
    }
    if (typeof v === 'object') {
        return Object.keys(v).length > 0;
    }
    return true;
}

/**
 * Responsive indicator — three non-interactive dots (Desktop / Tablet /
 * Mobile) that signal a control varies by viewport. A dot is filled when
 * that viewport has its own value, and the dot for the editor's active
 * preview device is accent-coloured. Place it next to a control's label.
 *
 * Props (one of):
 *   - value {Object}   responsive value keyed by slug; a slug counts as
 *                      "set" when slugHasValue() is true.
 *   - isSet {Function} (slug) => boolean — custom predicate (use for
 *                      controls that span several values, e.g. spacings).
 */
export function ResponsiveDots(props) {
    var dt = useDeviceType();
    var active = dt.device;
    var isSet = props.isSet || function (s) { return slugHasValue(props.value, s); };

    return createElement('span', { className: 'orbitools-responsive-dots' },
        DEVICE_ORDER.map(function (d) {
            var set = isSet(deviceToSlug(d));
            var className = 'orbitools-responsive-dots__dot'
                + (set ? ' is-set' : '')
                + (active === d ? ' is-active' : '');
            return createElement(Tooltip, { key: d, text: d },
                createElement('span', { className: className })
            );
        })
    );
}

/**
 * The framework component.
 *
 * Props:
 *   - title       {string}   PanelBody title.
 *   - blockName   {string}   for the (cosmetic) breakpoint lookup.
 *   - initialOpen {boolean}  PanelBody initialOpen (default false).
 *   - wrap        {boolean}  wrap in a PanelBody (default true). Pass
 *                            false to embed inside an existing panel.
 *   - indicator   {Element}  optional node (a <ResponsiveDots/>) rendered
 *                            inline in the panel title — for wrapped
 *                            controls that have no inline label of their
 *                            own. Ignored when wrap === false (place the
 *                            dots next to your own label instead).
 *   - render      {Function} ({ device, slug, breakpoint }) => element.
 *
 * There is no in-panel device switcher: the control reflects the editor's
 * own screen-size preview toggle (top-bar Preview ▸ Desktop/Tablet/
 * Mobile). Whichever device the editor previews is the viewport you edit
 * — Desktop→base, Tablet→tablet, Mobile→mobile. Discoverability comes
 * from the ResponsiveDots indicator instead.
 */
export function ResponsiveControl(props) {
    var dt = useDeviceType();
    var device = dt.device;
    var slug = deviceToSlug(device);
    var breakpoint = breakpointForSlug(props.blockName, slug);

    var control = props.render({ device: device, slug: slug, breakpoint: breakpoint });

    if (props.wrap === false) {
        return control;
    }

    var title = props.indicator
        ? createElement('span', { className: 'orbitools-responsive__heading' },
            createElement('span', { className: 'orbitools-responsive__heading-text' }, props.title),
            props.indicator
        )
        : props.title;

    return createElement(PanelBody, {
        title: title,
        initialOpen: !!props.initialOpen
    }, control);
}

export { DEVICE_ORDER, DEVICE_TO_SLUG, SLUG_TO_DEVICE };
