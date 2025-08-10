/**
 * Spacer Block Save Component
 * 
 * Outputs a single empty div with responsive height CSS classes.
 * No inner content or controls, just the spacer element.
 * 
 * @file blocks/spacer/save.tsx
 * @since 1.0.0
 */

import { useBlockProps } from '@wordpress/block-editor';
import type { BlockSaveProps } from '@wordpress/blocks';

import { getHeightClasses } from './height-control';
import { filterWordPressClasses } from '../utils/class-builders';
import type { SpacerAttributes } from './edit';

/**
 * Spacer Block Save Component
 */
export default function SpacerSave({ attributes }: BlockSaveProps<SpacerAttributes>) {
    const { height = {} } = attributes;

    // Generate height CSS classes
    const heightClasses = getHeightClasses(height);
    const spacerClasses = `orb-spacer ${heightClasses}`.trim();

    // Get block props and filter out WordPress default classes
    const blockProps = useBlockProps.save({
        className: spacerClasses
    });

    // Filter out wp-block-orb-spacer from the className
    const filteredClassName = filterWordPressClasses(blockProps.className, ['wp-block-orb-spacer']);

    // Output a single empty div with height classes
    return <div {...blockProps} className={filteredClassName} />;
}
