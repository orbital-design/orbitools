import React, { useEffect, useRef } from 'react';
import {
    InnerBlocks,
    useBlockProps
} from '@wordpress/block-editor';
import { useSelect, useDispatch } from '@wordpress/data';
import { createBlock } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import type { BlockEditProps } from '@wordpress/blocks';
import type { LayoutAttributes } from '../types';
import CollectionControls from './controls';
import { generateFlexAttributes } from '../utils/flex-attributes';
import { buildCollectionClasses, COLLECTION_LIMITS, combineClasses } from '../utils/class-builders';
import { getGapClasses, getPaddingClasses, getMarginClasses } from '../utils/tabbed-dimensions-control';

const ALLOWED_BLOCKS = ['orb/entry'];

const TEMPLATE = [
    ['orb/entry'],
    ['orb/entry']
];

const Edit: React.FC<BlockEditProps<LayoutAttributes>> = ({
    attributes,
    setAttributes,
    clientId
}) => {
    const { layoutType, itemWidth, columnSystem, columnCount = 2 } = attributes;
    
    // Get inner blocks and dispatch functions
    const { innerBlocks } = useSelect((select) => {
        const { getBlocks } = select('core/block-editor');
        return {
            innerBlocks: getBlocks(clientId)
        };
    }, [clientId]);

    const { replaceInnerBlocks } = useDispatch('core/block-editor');

    /**
     * Sync the number of entry blocks with the columnCount setting
     */
    useEffect(() => {
        // Safety check to prevent running on initial load or invalid states
        if (!innerBlocks || columnCount < COLLECTION_LIMITS.MIN_COLUMNS || columnCount > COLLECTION_LIMITS.MAX_COLUMNS) {
            return;
        }

        const currentCount = innerBlocks.length;
        const targetCount = columnCount;

        if (currentCount !== targetCount) {
            let newBlocks = [...innerBlocks];

            if (currentCount < targetCount) {
                // Add blocks
                const blocksToAdd = targetCount - currentCount;
                for (let i = 0; i < blocksToAdd; i++) {
                    newBlocks.push(createBlock('orb/entry'));
                }
            } else {
                // Remove blocks from the end
                newBlocks = newBlocks.slice(0, targetCount);
            }

            // Use setTimeout to prevent potential race conditions
            setTimeout(() => {
                replaceInnerBlocks(clientId, newBlocks, false);
            }, 0);
        }
    }, [columnCount, innerBlocks?.length, clientId, replaceInnerBlocks]);

    // Track previous columnSystem to detect actual changes
    const prevColumnSystemRef = useRef(columnSystem);
    
    /**
     * Reset Entry block widths when column system changes
     */
    useEffect(() => {
        // Only proceed if columnSystem actually changed
        if (prevColumnSystemRef.current === columnSystem) {
            return;
        }
        
        // Update the ref for next comparison
        prevColumnSystemRef.current = columnSystem;
        
        if (!innerBlocks || innerBlocks.length === 0) {
            return;
        }

        // Only reset if we're in custom itemWidth mode
        if (itemWidth !== 'custom') {
            return;
        }

        // Reset all entry block width attributes to empty (auto)
        const updatedBlocks = innerBlocks.map(block => {
            if (block.name === 'orb/entry' && block.attributes.width) {
                return {
                    ...block,
                    attributes: {
                        ...block.attributes,
                        width: ''
                    }
                };
            }
            return block;
        });

        // Only update if there were changes
        const hasChanges = updatedBlocks.some((block, index) => 
            block.attributes.width !== innerBlocks[index].attributes.width
        );

        if (hasChanges) {
            setTimeout(() => {
                replaceInnerBlocks(clientId, updatedBlocks, false);
            }, 0);
        }
    }, [columnSystem, innerBlocks, itemWidth, clientId, replaceInnerBlocks]);

    const { align, restrictContentWidth } = attributes;
    
    // Check if we need content constraint wrapper in editor
    const needsWrapper = align === 'full' && restrictContentWidth;
    
    // Build semantic class names using utility functions
    const collectionClasses = buildCollectionClasses(layoutType, itemWidth, columnSystem);
    
    // Generate responsive dimension classes
    const { orbGap, orbPadding, orbMargin } = attributes;
    const gapClasses = getGapClasses(orbGap || {});
    const paddingClasses = getPaddingClasses(orbPadding || {});
    const marginClasses = getMarginClasses(orbMargin || {});
    const allClasses = combineClasses(collectionClasses, gapClasses, paddingClasses, marginClasses);
    
    // Generate data attributes for layout consistency with save component  
    const tempBlockProps = useBlockProps();
    const flexAttributes = generateFlexAttributes(attributes, tempBlockProps);
    
    const blockProps = useBlockProps({
        className: needsWrapper ? undefined : allClasses,
        ...(needsWrapper ? {} : flexAttributes)
    });

    return (
        <>
            <CollectionControls 
                attributes={attributes}
                setAttributes={setAttributes}
            />
            
            <div {...blockProps}>
                {needsWrapper ? (
                    <div className={allClasses} {...flexAttributes}>
                        <InnerBlocks
                            allowedBlocks={ALLOWED_BLOCKS}
                            template={TEMPLATE}
                            templateLock={layoutType === 'row' ? 'insert' : false}
                            orientation="horizontal"
                        />
                    </div>
                ) : (
                    <InnerBlocks
                        allowedBlocks={ALLOWED_BLOCKS}
                        template={TEMPLATE}
                        templateLock={layoutType === 'row' ? 'insert' : false}
                        orientation="horizontal"
                    />
                )}
            </div>
        </>
    );
};

export default Edit;