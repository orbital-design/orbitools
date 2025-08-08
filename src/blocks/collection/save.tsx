import React from 'react';
import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';
import type { BlockSaveProps } from '@wordpress/blocks';
import type { LayoutAttributes } from '../types';
import { generateFlexAttributes } from '../utils/flex-attributes';
import { buildCollectionClasses, filterWordPressClasses, combineClasses } from '../utils/class-builders';
import { getSpacingClasses } from '../utils/spacing-control';


const Save: React.FC<BlockSaveProps<LayoutAttributes>> = ({ attributes }) => {
    const { layoutType, itemWidth, columnSystem, align, restrictContentWidth, orbGap } = attributes;
    
    const blockProps = useBlockProps.save();
    
    // Check if we need content constraint wrapper for full-width blocks
    const needsWrapper = align === 'full' && restrictContentWidth;
    
    // Remove WordPress default class while preserving other classes (alignment, colors, etc.)
    const filteredClasses = filterWordPressClasses(blockProps.className, ['wp-block-orb-collection']);
    
    // Generate semantic data attributes for CSS targeting
    const flexAttributes = generateFlexAttributes(attributes, blockProps);
    
    // Build semantic class names using utility functions
    const collectionClasses = buildCollectionClasses(layoutType, itemWidth, columnSystem);
    
    // Generate responsive spacing classes
    const spacingClasses = getSpacingClasses(orbGap || {});
    const allClasses = combineClasses(collectionClasses, spacingClasses, filteredClasses);

    if (needsWrapper) {
        // Full-width with content constraint: wrapper gets filtered blockProps, inner div gets our classes
        const wrapperProps = {
            ...blockProps,
            className: filteredClasses
        };
        
        const innerClasses = combineClasses(collectionClasses, spacingClasses);
        
        return (
            <div {...wrapperProps}>
                <div className={innerClasses} {...flexAttributes}>
                    <InnerBlocks.Content />
                </div>
            </div>
        );
    }

    // Normal output: single div with all props and classes
    const finalProps = {
        ...blockProps,
        ...flexAttributes,
        className: allClasses,
        style: blockProps.style
    };

    return (
        <div {...finalProps}>
            <InnerBlocks.Content />
        </div>
    );
};

export default Save;