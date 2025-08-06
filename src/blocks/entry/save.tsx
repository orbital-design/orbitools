import React from 'react';
import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';
import type { BlockSaveProps } from '@wordpress/blocks';
import type { LayoutItemAttributes } from '../types';
import { buildEntryClasses, filterWordPressClasses, combineClasses } from '../utils/class-builders';

const Save: React.FC<BlockSaveProps<LayoutItemAttributes>> = ({ attributes }) => {
    const { width, parentItemWidth, gapSize } = attributes;
    
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
    const combinedClasses = combineClasses(entryClasses, filteredClasses);
    
    // Generate data attribute for gap spacing (matching Collection block approach)
    const gapDataAttr: Record<string, string> = {};
    if (gapSize !== undefined && gapSize !== null) {
        gapDataAttr['data-gap'] = 'spacing';
    }
    
    // Add CSS variable for custom gap spacing
    const gapStyle = gapSize ? { '--orb-gap-size': gapSize } : {};

    const finalProps = {
        ...blockProps,
        className: combinedClasses,
        style: { ...blockProps.style, ...gapStyle },
        ...gapDataAttr
    };

    return (
        <div {...finalProps}>
            <InnerBlocks.Content />
        </div>
    );
};

export default Save;