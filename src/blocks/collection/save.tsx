import React from 'react';
import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';
import type { BlockSaveProps } from '@wordpress/blocks';
import type { LayoutAttributes } from '../types';
import { generateFlexAttributes } from '../utils/flex-attributes';
import { buildCollectionClasses, filterWordPressClasses, combineClasses } from '../utils/class-builders';


const Save: React.FC<BlockSaveProps<LayoutAttributes>> = ({ attributes }) => {
    const { layoutType, itemWidth, columnSystem, align, restrictContentWidth, gapSize } = attributes;
    
    const blockProps = useBlockProps.save();
    
    // Check if we need content constraint wrapper for full-width blocks
    const needsWrapper = align === 'full' && restrictContentWidth;
    
    // Remove WordPress default class while preserving other classes (alignment, colors, etc.)
    const filteredClasses = filterWordPressClasses(blockProps.className, ['wp-block-orb-collection']);
    
    // Generate semantic data attributes for CSS targeting
    const flexAttributes = generateFlexAttributes(attributes, blockProps);
    
    // Build semantic class names using utility functions
    const collectionClasses = buildCollectionClasses(layoutType, itemWidth, columnSystem);
    const combinedClasses = combineClasses(collectionClasses, filteredClasses);

    // Add CSS variable for custom gap spacing
    const gapStyle = gapSize ? { '--orb-gap-size': gapSize } : {};

    if (needsWrapper) {
        // Full-width with content constraint: wrapper gets filtered blockProps, inner div gets our classes
        const wrapperProps = {
            ...blockProps,
            className: filteredClasses
        };
        
        return (
            <div {...wrapperProps}>
                <div className={collectionClasses} {...flexAttributes} style={gapStyle}>
                    <InnerBlocks.Content />
                </div>
            </div>
        );
    }

    // Normal output: single div with all props and classes
    const finalProps = {
        ...blockProps,
        ...flexAttributes,
        className: combinedClasses,
        style: { ...blockProps.style, ...gapStyle }
    };

    return (
        <div {...finalProps}>
            <InnerBlocks.Content />
        </div>
    );
};

export default Save;