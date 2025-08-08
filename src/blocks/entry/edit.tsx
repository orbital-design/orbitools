import React from 'react';
import {
    InnerBlocks,
    useBlockProps,
    ButtonBlockAppender
} from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import type { BlockEditProps } from '@wordpress/blocks';
import type { LayoutItemAttributes } from '../types';
import EntryControls from './controls';
import { buildEntryClasses, combineClasses } from '../utils/class-builders';
import { getSpacingClasses } from '../utils/spacing-control';

const Edit: React.FC<BlockEditProps<LayoutItemAttributes>> = ({
    attributes,
    setAttributes,
    context,
    clientId
}) => {
    const { width, parentItemWidth, orbGap } = attributes;
    const {
        'orb/layoutType': layoutType,
        'orb/itemWidth': itemWidth,
        'orb/columnSystem': columnSystem
    } = context;

    /**
     * Store parent itemWidth in Entry attributes for save component access
     * 
     * Since context is not available in save components, we need to store
     * the parent's itemWidth setting in the Entry block's own attributes.
     * This allows the save component to conditionally output width classes.
     */
    React.useEffect(() => {
        if (parentItemWidth !== itemWidth) {
            setAttributes({ parentItemWidth: itemWidth });
        }
    }, [itemWidth, parentItemWidth, setAttributes]);

    // Only show width classes in editor when parent uses custom layout
    const shouldShowWidthClass = itemWidth === 'custom' && Boolean(width);
    
    // Build semantic class names using utility functions
    const entryClasses = buildEntryClasses(width, shouldShowWidthClass);
    
    // Generate responsive spacing classes
    const spacingClasses = getSpacingClasses(orbGap || {});
    const combinedClasses = combineClasses(entryClasses, spacingClasses);
    
    const blockProps = useBlockProps({
        className: combinedClasses
    });

    return (
        <>
            <EntryControls
                attributes={attributes}
                setAttributes={setAttributes}
                context={context}
            />
            
            <div {...blockProps}>
                <InnerBlocks
                    template={[]}
                    templateLock={false}
                    renderAppender={() => <ButtonBlockAppender rootClientId={clientId} />}
                />
            </div>
        </>
    );
};

export default Edit;