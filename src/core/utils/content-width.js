/**
 * Content Width — shared resolver
 *
 * The Content Width control lets a *full-aligned* block keep its background
 * full-bleed while constraining its inner content to the site content
 * ("standard") or wide width. Storage is a single string attribute
 * `orbContentWidth` with values: 'full' (default, no constraint), 'wide',
 * 'standard'.
 *
 * This module is the single source of truth for resolving the effective width
 * from a block's attributes, including back-compat with the legacy Row/Grid
 * `restrictContentWidth` boolean (true => 'standard'). It's plain JS so both
 * the controls build (preset-env only) and the blocks build (TS) can import it
 * — same arrangement as responsive-control.js.
 *
 * @file core/utils/content-width.js
 */

export const CONTENT_WIDTH_VALUES = ['full', 'wide', 'standard'];

/**
 * Resolve a block's effective content width.
 *
 * @param {object} attributes Block attributes.
 * @return {('full'|'wide'|'standard')} The effective width.
 */
export function resolveContentWidth(attributes) {
    const w = attributes && attributes.orbContentWidth;
    if (w === 'full' || w === 'wide' || w === 'standard') {
        return w;
    }
    // Legacy Row/Grid boolean: true meant "constrain to content width".
    if (attributes && attributes.restrictContentWidth === true) {
        return 'standard';
    }
    return 'full';
}

/**
 * The data-constrain attribute value for a resolved width.
 *
 * @param {string} width Resolved width.
 * @return {('standard'|'wide'|'')} Attribute value; '' when not constrained.
 */
export function constrainAttr(width) {
    return width === 'wide' || width === 'standard' ? width : '';
}

/**
 * Does a full-aligned block need the constrain wrapper?
 *
 * @param {object} attributes Block attributes (must include `align`).
 * @return {boolean}
 */
export function needsConstraint(attributes) {
    const align = attributes && attributes.align;
    return align === 'full' && resolveContentWidth(attributes) !== 'full';
}
