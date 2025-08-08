/**
 * Collection Block Controls
 * 
 * Main controls for the Collection block that are common to all layout types:
 * - Layout type selection (row vs grid)
 * - Gap/spacing controls with theme.json integration
 * - Content width restriction for full-width blocks
 * 
 * Layout-specific controls are handled by separate components:
 * - RowControls for row layout
 * - GridControls for grid layout
 * 
 * @file blocks/collection/controls.tsx
 * @since 1.0.0
 */

import { Fragment } from '@wordpress/element';
import { InspectorControls, BlockControls, AlignmentToolbar, useSettings } from '@wordpress/block-editor';
import {
    __experimentalToolsPanel as ToolsPanel,
    __experimentalToolsPanelItem as ToolsPanelItem,
    __experimentalToggleGroupControl as ToggleGroupControl,
    __experimentalToggleGroupControlOption as ToggleGroupControlOption,
    ToggleControl,
    RangeControl,
} from '@wordpress/components';

import type { LayoutAttributes } from '../types';
import RowControls from './row-controls';
import GridControls from './grid-controls';

interface CollectionControlsProps {
    attributes: LayoutAttributes;
    setAttributes: (attributes: Partial<LayoutAttributes>) => void;
}

/**
 * Default values for common controls
 */
const COMMON_DEFAULTS = {
    layoutType: 'row',
    gapSize: undefined,
    restrictContentWidth: false,
} as const;

/**
 * Layout options for the Collection block
 */
const LAYOUT_OPTIONS = [
    { value: 'row', label: 'Row' },
    { value: 'grid', label: 'Grid' },
] as const;

/**
 * Helper to get spacing marks from theme.json
 */
function getSpacingMarks(spacingSizes: any[]) {
    const marks: { value: number; label: string }[] = [];
    
    if (spacingSizes && Array.isArray(spacingSizes)) {
        spacingSizes.forEach((size, index) => {
            marks.push({
                value: index,
                label: size.name
            });
        });
    }
    
    return marks;
}

/**
 * Helper to get spacing value by index
 */
function getSpacingValueByIndex(spacingSizes: any[], index: number) {
    if (spacingSizes && Array.isArray(spacingSizes) && spacingSizes[index]) {
        return spacingSizes[index].size;
    }
    return '';
}

/**
 * Helper to get spacing index by value
 */
function getSpacingIndexByValue(spacingSizes: any[], value: string) {
    if (!spacingSizes || !Array.isArray(spacingSizes)) return -1;
    
    const index = spacingSizes.findIndex((size: any) => size.size === value);
    return index >= 0 ? index : -1;
}

/**
 * Helper function to create a ToolsPanelItem with consistent styling
 */
function createToolsPanelItem(
    controlName: string,
    hasValue: () => boolean,
    onDeselect: () => void,
    label: string,
    children: React.ReactNode,
    isShownByDefault = false
) {
    return (
        <ToolsPanelItem
            hasValue={hasValue}
            onDeselect={onDeselect} // Use actual onDeselect function instead of no-op
            label={label}
            isShownByDefault={isShownByDefault}
            panelId="collection-common-panel"
        >
            {children}
        </ToolsPanelItem>
    );
}

/**
 * Helper function to create labeled toggle group controls
 */
function createToggleGroup(
    value: string | number,
    onChange: (value: string | number) => void,
    options: readonly { value: string | number; label: string }[],
    label?: string
) {
    const control = (
        <ToggleGroupControl
            value={value}
            onChange={onChange}
            isBlock={true}
            __next40pxDefaultSize={true}
            __nextHasNoMarginBottom={true}
        >
            {options.map(option => (
                <ToggleGroupControlOption
                    key={option.value}
                    value={option.value}
                    label={option.label}
                />
            ))}
        </ToggleGroupControl>
    );

    if (label) {
        return (
            <div>
                <label style={{
                    display: 'block',
                    marginBottom: '8px',
                    fontSize: '11px',
                    fontWeight: '500',
                    textTransform: 'uppercase',
                    color: '#1e1e1e'
                }}>
                    {label}
                </label>
                {control}
            </div>
        );
    }

    return control;
}

/**
 * Collection Block Controls Component
 */
export default function CollectionControls({ attributes, setAttributes }: CollectionControlsProps) {
    const { 
        layoutType = COMMON_DEFAULTS.layoutType,
        gapSize = COMMON_DEFAULTS.gapSize,
        restrictContentWidth = COMMON_DEFAULTS.restrictContentWidth,
        align
    } = attributes;

    // Get spacing sizes from theme.json
    const [spacingSizes] = useSettings('spacing.spacingSizes');

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
            layoutType: COMMON_DEFAULTS.layoutType,
            gapSize: COMMON_DEFAULTS.gapSize,
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
        return (
            <InspectorControls group="settings">
                <ToolsPanel
                    label="Settings"
                    resetAll={resetCommonAttributes}
                    panelId="collection-common-panel"
                >
                    {/* Layout Type Control */}
                    {createToolsPanelItem(
                        'layoutType',
                        () => hasNonDefaultValue('layoutType', COMMON_DEFAULTS.layoutType),
                        () => updateAttribute('layoutType', COMMON_DEFAULTS.layoutType),
                        'Layout Type',
                        createToggleGroup(
                            layoutType,
                            (value) => updateAttribute('layoutType', value),
                            LAYOUT_OPTIONS,
                            'Layout Type'
                        ),
                        true
                    )}

                    {/* Constrain Content Control - only for full-width blocks */}
                    {align === 'full' && createToolsPanelItem(
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
            
            {/* Layout-specific controls */}
            {layoutType === 'row' && (
                <RowControls 
                    attributes={attributes} 
                    setAttributes={setAttributes}
                />
            )}
            
            {layoutType === 'grid' && (
                <GridControls 
                    attributes={attributes} 
                    setAttributes={setAttributes}
                />
            )}
        </Fragment>
    );
}