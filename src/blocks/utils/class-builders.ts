/**
 * Utilities for building CSS class names consistently
 * 
 * Centralizes the logic for generating block class names to ensure
 * consistency between edit and save components and improve maintainability.
 * 
 * @file blocks/utils/class-builders.ts
 * @since 1.0.0
 */

import type { LayoutAttributes } from '../types';

/**
 * Collection layout limits and constants
 */
export const COLLECTION_LIMITS = {
    MIN_COLUMNS: 1,
    MAX_COLUMNS: 10,
} as const;

/**
 * Build Collection block class names based on layout configuration
 * 
 * Generates minimal semantic class names for the Collection block.
 * All layout configuration (itemWidth, columnSystem, etc.) is handled 
 * via data attributes, not classes.
 * 
 * @param layoutType - The layout type ('row' or 'grid')
 * @param itemWidth - Item width mode (unused, kept for API consistency)
 * @param columnSystem - Grid system (unused, kept for API consistency)
 * @param baseClass - Base class name (default: 'orb-collection')
 * @returns Complete class string for the Collection block
 */
export function buildCollectionClasses(
    layoutType: string,
    itemWidth?: string,
    columnSystem?: number,
    baseClass = 'orb-collection'
): string {
    const classes = [baseClass];
    
    // Only add layout type - all other configuration is in data attributes
    classes.push(`${baseClass}--${layoutType}`);
    
    
    return classes.join(' ');
}

/**
 * Build Entry block class names based on width configuration
 * 
 * Generates appropriate class names for Entry blocks, conditionally
 * including width classes based on parent layout settings.
 * 
 * @param width - Width class (e.g., 'w-4')
 * @param shouldIncludeWidth - Whether to include width class
 * @param baseClass - Base class name (default: 'orb-entry')
 * @returns Complete class string for the Entry block
 */
export function buildEntryClasses(
    width?: string,
    shouldIncludeWidth = true,
    baseClass = 'orb-entry'
): string {
    const classes = [baseClass];
    
    // Only add width class if enabled and width is provided
    if (shouldIncludeWidth && width) {
        classes.push(`${baseClass}--${width}`);
    }
    
    return classes.join(' ');
}

/**
 * Filter WordPress default block classes while preserving other classes
 * 
 * Removes specific WordPress-generated classes while keeping user-applied
 * classes like alignment, colors, and spacing intact.
 * 
 * @param className - Original className string from blockProps
 * @param classesToFilter - Array of class names to remove
 * @returns Filtered class string
 */
export function filterWordPressClasses(
    className?: string,
    classesToFilter: string[] = []
): string {
    if (!className) return '';
    
    return className
        .split(' ')
        .filter(cls => cls && !classesToFilter.includes(cls))
        .join(' ');
}

/**
 * Combine multiple class strings safely
 * 
 * Merges class strings, removes duplicates, and cleans up whitespace.
 * Handles empty/undefined values gracefully.
 * 
 * @param classes - Array of class strings to combine
 * @returns Clean, combined class string
 */
export function combineClasses(...classes: (string | undefined)[]): string {
    return classes
        .filter(Boolean) // Remove falsy values
        .join(' ')
        .replace(/\s+/g, ' ') // Normalize whitespace
        .trim();
}