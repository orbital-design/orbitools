/**
 * Row Layout Block Controls
 *
 * Common controls plus the row layout controls (RowControls). The block is a
 * row layout; a dedicated grid block will be separate, so there's no grid mode
 * here.
 *
 * @file blocks/collection/controls.tsx
 * @since 1.0.0
 */

import { Fragment } from '@wordpress/element';
import { createToolsPanelItem } from '../utils/control-helpers';
import { InspectorControls } from '@wordpress/block-editor';
import {
    __experimentalToolsPanel as ToolsPanel,
    ToggleControl,
} from '@wordpress/components';

import type { LayoutAttributes } from '../types';
import RowControls from './row-controls';

interface CollectionControlsProps {
    attributes: LayoutAttributes;
    setAttributes: (attributes: Partial<LayoutAttributes>) => void;
}

/**
 * Default values for common controls
 */
const COMMON_DEFAULTS = {
    restrictContentWidth: false,
} as const;

/**
 * Collection Block Controls Component
 */
export default function CollectionControls({ attributes, setAttributes }: CollectionControlsProps) {
    const {
        restrictContentWidth = COMMON_DEFAULTS.restrictContentWidth,
        align
    } = attributes;

    /**
     * Helper to update a single attribute
     */
    const updateAttribute = (key: keyof LayoutAttributes, value: any) => {
        setAttributes({ [key]: value });
    };

    /**
     * Reset common attributes to defaults
     */
    const resetCommonAttributes = () => {
        setAttributes({
            restrictContentWidth: COMMON_DEFAULTS.restrictContentWidth,
        });
    };

    /**
     * Check if an attribute has a non-default value
     */
    const hasNonDefaultValue = (key: keyof LayoutAttributes, defaultValue: any) => {
        return attributes[key] !== undefined && attributes[key] !== defaultValue;
    };

    const renderCommonControls = () => {
        // The only common control left is Constrain Content, which applies
        // to full-width blocks only — so skip the panel entirely otherwise
        // rather than render an empty "Settings" panel.
        if (align !== 'full') {
            return null;
        }

        return (
            <InspectorControls group="settings">
                <ToolsPanel
                    label="Settings"
                    resetAll={resetCommonAttributes}
                    panelId="collection-common-panel"
                >
                    {/* Constrain Content Control - full-width blocks only */}
                    {createToolsPanelItem(
                        'restrictContentWidth',
                        () => hasNonDefaultValue('restrictContentWidth', COMMON_DEFAULTS.restrictContentWidth),
                        () => updateAttribute('restrictContentWidth', COMMON_DEFAULTS.restrictContentWidth),
                        'Constrain Content',
                        <ToggleControl
                            label="Constrain Content"
                            help="Constrain child blocks to the standard content width."
                            checked={restrictContentWidth}
                            onChange={(value) => updateAttribute('restrictContentWidth', value)}
                            __nextHasNoMarginBottom={true}
                        />,
                        true
                    )}
                </ToolsPanel>
            </InspectorControls>
        );
    };

    return (
        <Fragment>
            {renderCommonControls()}
            <RowControls
                attributes={attributes}
                setAttributes={setAttributes}
            />
        </Fragment>
    );
}
