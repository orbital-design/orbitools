/**
 * Shared utilities for generating flex layout data attributes
 *
 * This ensures consistency between edit and save components by using
 * the same logic for generating data attributes across both contexts.
 *
 * @file blocks/utils/flex-attributes.ts
 * @since 1.0.0
 */

import type { LayoutAttributes } from '../types';

/**
 * Default flex control values - these should match block.json defaults
 * and be kept in sync with any PHP configuration
 */
export const FLEX_DEFAULTS = {
    columnCount: 2,
    flexDirection: 'row' as const,
    flexWrap: 'nowrap' as const,
    alignItems: 'stretch' as const,
    justifyContent: 'flex-start' as const,
    gapSize: undefined,
    restrictContentWidth: false,
    stackOnMobile: true,
    itemWidth: 'fit' as const,
    columnSystem: 5 as const,
} as const;

/**
 * Layout value mappings for cleaner data attribute names
 * These create semantic, CSS-friendly attribute values
 */
const VALUE_MAPPINGS = {
    alignItems: {
        'flex-start': 'start',
        'flex-end': 'end',
        'center': 'center'
    },
    justifyContent: {
        'flex-start': 'start',
        'flex-end': 'end',
        'center': 'center',
        'space-between': 'between',
        'space-around': 'around',
        'space-evenly': 'evenly'
    },
    flexWrap: {
        'nowrap': 'nowrap',
        'wrap': 'wrap',
        'wrap-reverse': 'wrap-reverse'
    },
    gridSystem: {
        '5': 'penta',
        '12': 'dodeca'
    }
} as const;

/**
 * Generate flex layout data attributes for clean, semantic HTML
 *
 * Only outputs non-default values to keep HTML minimal and clean.
 * Maps complex CSS values to simpler, more semantic attribute names.
 *
 * @param attributes - Block attributes from Collection block
 * @param blockProps - Optional WordPress block props for context detection
 * @returns Object with data-* attributes for the HTML element
 */
export function generateFlexAttributes(
    attributes: LayoutAttributes,
    blockProps?: any
): Record<string, string> {
    const dataAttrs: Record<string, string> = {};

    // Extract values with fallbacks to defaults
    const direction = attributes.flexDirection || FLEX_DEFAULTS.flexDirection;
    const flexWrap = attributes.flexWrap || FLEX_DEFAULTS.flexWrap;
    const alignItems = attributes.alignItems || FLEX_DEFAULTS.alignItems;
    const justifyContent = attributes.justifyContent || FLEX_DEFAULTS.justifyContent;
    const restrictContentWidth = attributes.restrictContentWidth || FLEX_DEFAULTS.restrictContentWidth;
    const stackOnMobile = attributes.stackOnMobile !== false; // Default true unless explicitly false
    const itemWidth = attributes.itemWidth || FLEX_DEFAULTS.itemWidth;
    const columnSystem = attributes.columnSystem || FLEX_DEFAULTS.columnSystem;

    // Flex flow: Combine direction and wrap into single data attribute
    // Only add if not default combination (row nowrap)
    const isDefaultFlow = direction === FLEX_DEFAULTS.flexDirection && flexWrap === FLEX_DEFAULTS.flexWrap;
    if (!isDefaultFlow) {
        // Create flex-flow value: "direction wrap" (e.g., "row wrap", "column nowrap")
        dataAttrs['data-flow'] = `${direction} ${flexWrap}`;
    }

    // Cross-axis alignment: Only add if not default (stretch)
    if (alignItems !== FLEX_DEFAULTS.alignItems) {
        dataAttrs['data-align'] = VALUE_MAPPINGS.alignItems[alignItems] || alignItems;
    }

    // Main-axis alignment: Only add if not default (flex-start)
    if (justifyContent !== FLEX_DEFAULTS.justifyContent) {
        dataAttrs['data-justify'] = VALUE_MAPPINGS.justifyContent[justifyContent] || justifyContent;
    }

    // Content constraint: Only for full-width blocks with constraint enabled
    const isFullWidth = blockProps?.className?.includes('alignfull') || false;
    if (restrictContentWidth && isFullWidth) {
        dataAttrs['data-constrain'] = 'true';
    }

    // Mobile stacking: Only add if enabled (which is default)
    if (stackOnMobile) {
        dataAttrs['data-stacked'] = 'true';
    }

    // Layout mode: Only add non-default item width modes
    if (itemWidth !== FLEX_DEFAULTS.itemWidth) {
        if (itemWidth === 'custom') {
            // For custom layout, use semantic grid system names
            dataAttrs['data-layout'] = VALUE_MAPPINGS.gridSystem[columnSystem.toString() as keyof typeof VALUE_MAPPINGS.gridSystem] || 'custom';
        } else {
            dataAttrs['data-layout'] = itemWidth; // 'equal'
        }
    }

    return dataAttrs;
}
