import React from 'react';
import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';
import type { BlockDeprecation } from '@wordpress/blocks';
import { generateFlexAttributes } from '../utils/flex-attributes';
import { buildCollectionClasses, filterWordPressClasses, combineClasses } from '../utils/class-builders';

// Deprecation for blocks with string gapSize
const v2: BlockDeprecation<any> = {
    attributes: {
        layoutType: { type: 'string', default: 'row' },
        itemWidth: { type: 'string', default: 'fit' },
        columnSystem: { type: 'number', default: 12 },
        columnCount: { type: 'number', default: 2 },
        flexDirection: { type: 'string', default: 'row' },
        flexWrap: { type: 'string', default: 'nowrap' },
        alignItems: { type: 'string', default: 'stretch' },
        justifyContent: { type: 'string', default: 'flex-start' },
        gapSize: { type: 'string' },
        restrictContentWidth: { type: 'boolean', default: false },
        stackOnMobile: { type: 'boolean', default: true },
        align: { type: 'string' }
    },

    migrate(attributes: any) {
        return { ...attributes, gapSize: {} };
    },

    save({ attributes }: any) {
        const { layoutType, itemWidth, columnSystem, align, restrictContentWidth } = attributes;
        const blockProps = useBlockProps.save();
        const needsWrapper = align === 'full' && restrictContentWidth;
        const filteredClasses = filterWordPressClasses(blockProps.className || '', ['wp-block-orb-collection']);
        // For deprecation, remove gapSize since generateFlexAttributes no longer handles it
        const { gapSize, ...attrsForFlex } = attributes;
        const flexAttributes = generateFlexAttributes(attrsForFlex as any, blockProps);
        const collectionClasses = buildCollectionClasses(layoutType, itemWidth, columnSystem);
        const combinedClasses = combineClasses(collectionClasses, filteredClasses);
        const deprecatedAttributes = { ...flexAttributes, 'data-gap': 'spacing' };

        if (needsWrapper) {
            return (
                <div {...blockProps} className={filteredClasses}>
                    <div className={collectionClasses} {...deprecatedAttributes}>
                        <InnerBlocks.Content />
                    </div>
                </div>
            );
        }

        return (
            <div {...blockProps} {...deprecatedAttributes} className={combinedClasses}>
                <InnerBlocks.Content />
            </div>
        );
    }
};

// Deprecation for blocks without gapSize (undefined)  
const v1: BlockDeprecation<any> = {
    attributes: {
        layoutType: { type: 'string', default: 'row' },
        itemWidth: { type: 'string', default: 'fit' },
        columnSystem: { type: 'number', default: 12 },
        columnCount: { type: 'number', default: 2 },
        flexDirection: { type: 'string', default: 'row' },
        flexWrap: { type: 'string', default: 'nowrap' },
        alignItems: { type: 'string', default: 'stretch' },
        justifyContent: { type: 'string', default: 'flex-start' },
        restrictContentWidth: { type: 'boolean', default: false },
        stackOnMobile: { type: 'boolean', default: true },
        align: { type: 'string' }
    },

    migrate(attributes: any) {
        return { ...attributes, gapSize: {} };
    },

    save({ attributes }: any) {
        const { layoutType, itemWidth, columnSystem, align, restrictContentWidth } = attributes;
        const blockProps = useBlockProps.save();
        const needsWrapper = align === 'full' && restrictContentWidth;
        const filteredClasses = filterWordPressClasses(blockProps.className || '', ['wp-block-orb-collection']);
        // For deprecation, remove gapSize since generateFlexAttributes no longer handles it
        const { gapSize, ...attrsForFlex } = attributes;
        const flexAttributes = generateFlexAttributes(attrsForFlex as any, blockProps);
        const collectionClasses = buildCollectionClasses(layoutType, itemWidth, columnSystem);
        const combinedClasses = combineClasses(collectionClasses, filteredClasses);
        const deprecatedAttributes = { ...flexAttributes, 'data-gap': 'spacing' };

        if (needsWrapper) {
            return (
                <div {...blockProps} className={filteredClasses}>
                    <div className={collectionClasses} {...deprecatedAttributes}>
                        <InnerBlocks.Content />
                    </div>
                </div>
            );
        }

        return (
            <div {...blockProps} {...deprecatedAttributes} className={combinedClasses}>
                <InnerBlocks.Content />
            </div>
        );
    }
};

export default [v2, v1];
