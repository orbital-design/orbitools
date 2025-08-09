import React from 'react';
import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';
import type { BlockSaveProps } from '@wordpress/blocks';
import type { LayoutItemAttributes } from '../types';
import { buildEntryClasses, filterWordPressClasses, combineClasses } from '../utils/class-builders';
import { getGapClasses, getPaddingClasses } from '../utils/tabbed-dimensions-control';

const Save: React.FC<BlockSaveProps<LayoutItemAttributes>> = ({ attributes }) => {
    const { width, parentItemWidth, orbGap, orbPadding } = attributes;
    
    const blockProps = useBlockProps.save();
    
    // Remove WordPress default class while preserving other classes (alignment, colors, etc.)
    const filteredClasses = filterWordPressClasses(blockProps.className, ['wp-block-orb-entry']);
    
    /**
     * Conditionally include width classes based on parent layout settings
     * 
     * Width classes are only output when the parent Collection block is set to
     * custom layout mode. This prevents unnecessary classes in other layout modes.
     */
    const shouldOutputWidthClass = parentItemWidth === 'custom' && Boolean(width);
    
    // Build semantic class names using utility functions
    const entryClasses = buildEntryClasses(width, shouldOutputWidthClass);
    
    // Generate responsive dimension classes
    const gapClasses = getGapClasses(orbGap || {});
    const paddingClasses = getPaddingClasses(orbPadding || {});
    const combinedClasses = combineClasses(entryClasses, gapClasses, paddingClasses, filteredClasses);

    const finalProps = {
        ...blockProps,
        className: combinedClasses,
        style: blockProps.style
    };

    return (
        <div {...finalProps}>
            <InnerBlocks.Content />
        </div>
    );
};

export default Save;