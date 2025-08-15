/**
 * Group Block Edit Component
 * 
 * This component provides the editor interface for the Group block.
 * The block creates a flexible container for organizing other blocks
 * with various layout options and semantic HTML tag support.
 * 
 * @file blocks/group/edit.tsx
 * @since 1.0.0
 */

import React from 'react';
import {
    InspectorControls,
    useBlockProps,
    useInnerBlocksProps,
    InnerBlocks,
    store as blockEditorStore,
} from '@wordpress/block-editor';
import {
    __experimentalToolsPanel as ToolsPanel,
    __experimentalToolsPanelItem as ToolsPanelItem,
    SelectControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import type { BlockEditProps } from '@wordpress/blocks';
import type { GroupAttributes } from './types';
import { 
    GROUP_DEFAULTS, 
    HTML_TAG_OPTIONS, 
    TEMPLATE_LOCK_OPTIONS,
    getTagName,
    getTemplateLock
} from './types';

/**
 * Group Block Edit Component
 * 
 * Renders the editor interface for the Group block.
 * Provides controls for HTML tag selection, template locking, and layout management.
 * 
 * @param props Block edit props containing attributes and setAttributes
 * @returns JSX element with controls and editor preview
 */
const Edit: React.FC<BlockEditProps<GroupAttributes>> = ({
    attributes, 
    setAttributes, 
    clientId
}) => {
    // Extract attributes with fallbacks to defaults
    const { 
        tagName = GROUP_DEFAULTS.tagName,
        templateLock = GROUP_DEFAULTS.templateLock,
        allowedBlocks
    } = attributes;

    // Get block data
    const { hasInnerBlocks } = useSelect(
        (select) => {
            const { getBlock } = select(blockEditorStore) as any;
            const block = getBlock(clientId);
            
            return {
                hasInnerBlocks: !!(block && block.innerBlocks && block.innerBlocks.length),
            };
        },
        [clientId]
    );

    // Set up block props with dynamic tag name
    const TagName = getTagName(attributes) as keyof JSX.IntrinsicElements;
    const blockProps = useBlockProps({
        className: 'orb-group'
    });

    // Set up inner blocks props
    const innerBlocksProps = useInnerBlocksProps(
        { className: 'orb-group__inner' },
        {
            templateLock: getTemplateLock(attributes),
            allowedBlocks,
            renderAppender: InnerBlocks.ButtonBlockAppender,
        }
    );

    // Attribute update functions with type safety
    const setTagName = (newValue: string) => {
        setAttributes({ tagName: newValue });
    };

    const setTemplateLock = (newValue: string | boolean) => {
        setAttributes({ templateLock: newValue === 'false' ? false : newValue });
    };

    // Helper to check if attribute has non-default value
    const hasNonDefaultValue = (key: keyof GroupAttributes, defaultValue: any) => {
        return attributes[key] !== undefined && attributes[key] !== defaultValue;
    };

    return (
        <>
            <InspectorControls>
                <ToolsPanel
                    label={__('Group Settings', 'orbitools')}
                    resetAll={() => {
                        setAttributes({
                            tagName: GROUP_DEFAULTS.tagName,
                            templateLock: GROUP_DEFAULTS.templateLock,
                            allowedBlocks: GROUP_DEFAULTS.allowedBlocks,
                            layout: GROUP_DEFAULTS.layout
                        });
                    }}
                    panelId="group-settings-panel"
                >
                    {/* HTML Tag Control */}
                    <ToolsPanelItem
                        hasValue={() => hasNonDefaultValue('tagName', GROUP_DEFAULTS.tagName)}
                        label={__('HTML Tag', 'orbitools')}
                        onDeselect={() => setTagName(GROUP_DEFAULTS.tagName)}
                        isShownByDefault={true}
                        panelId="group-settings-panel"
                    >
                        <SelectControl
                            label={__('HTML Tag', 'orbitools')}
                            value={tagName}
                            onChange={setTagName}
                            options={HTML_TAG_OPTIONS}
                            help={__('Choose the HTML tag that best represents the semantic meaning of this group.', 'orbitools')}
                            __nextHasNoMarginBottom={true}
                        />
                    </ToolsPanelItem>

                    {/* Template Lock Control */}
                    <ToolsPanelItem
                        hasValue={() => hasNonDefaultValue('templateLock', GROUP_DEFAULTS.templateLock)}
                        label={__('Template Lock', 'orbitools')}
                        onDeselect={() => setTemplateLock(GROUP_DEFAULTS.templateLock)}
                        isShownByDefault={false}
                        panelId="group-settings-panel"
                    >
                        <SelectControl
                            label={__('Template Lock', 'orbitools')}
                            value={String(templateLock)}
                            onChange={setTemplateLock}
                            options={TEMPLATE_LOCK_OPTIONS}
                            help={__('Control how users can modify the inner blocks.', 'orbitools')}
                            __nextHasNoMarginBottom={true}
                        />
                    </ToolsPanelItem>
                </ToolsPanel>
            </InspectorControls>

            <TagName {...blockProps}>
                <div {...innerBlocksProps} />
            </TagName>
        </>
    );
};

export default Edit;