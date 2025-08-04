/**
 * Collection Block Grid Controls
 * 
 * CSS Grid layout controls for grid layout type including:
 * - Grid template configuration
 * - Grid-specific alignment
 * - Responsive grid behaviors
 * - Grid gap and sizing
 * 
 * @file blocks/collection/grid-controls.tsx
 * @since 1.0.0
 */

import { Fragment } from '@wordpress/element';
import { InspectorControls, BlockControls } from '@wordpress/block-editor';
import {
    __experimentalToolsPanel as ToolsPanel,
    __experimentalToolsPanelItem as ToolsPanelItem,
    __experimentalToggleGroupControl as ToggleGroupControl,
    __experimentalToggleGroupControlOption as ToggleGroupControlOption,
    ToolbarGroup,
    ToolbarDropdownMenu,
    RangeControl,
    SVG,
    Path,
} from '@wordpress/components';

import type { LayoutAttributes } from '../types';

interface GridControlsProps {
    attributes: LayoutAttributes;
    setAttributes: (attributes: Partial<LayoutAttributes>) => void;
}

/**
 * Default values for grid controls
 */
const GRID_DEFAULTS = {
    columnCount: 3,
    // Grid-specific defaults will be added here as we develop grid features
} as const;

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
            panelId="collection-grid-panel"
        >
            {children}
        </ToolsPanelItem>
    );
}

/**
 * Grid Controls Component
 */
export default function GridControls({ attributes, setAttributes }: GridControlsProps) {
    const { 
        columnCount = GRID_DEFAULTS.columnCount,
    } = attributes;

    /**
     * Helper to update a single attribute
     */
    const updateAttribute = (key: keyof LayoutAttributes, value: any) => {
        setAttributes({ [key]: value });
    };

    /**
     * Reset all grid attributes to defaults
     */
    const resetAllGridAttributes = () => {
        setAttributes({
            columnCount: GRID_DEFAULTS.columnCount,
            // Additional grid resets will be added here
        });
    };

    /**
     * Check if an attribute has a non-default value
     */
    const hasNonDefaultValue = (key: keyof LayoutAttributes, defaultValue: any) => {
        return attributes[key] !== undefined && attributes[key] !== defaultValue;
    };

    /**
     * Toolbar controls for grid layout
     * Note: Grid layouts have different alignment paradigms than flex
     */
    const renderToolbarControls = () => {
        // Grid toolbar controls will be implemented here
        // These will include grid-specific alignment options like:
        // - justify-items
        // - align-items  
        // - justify-content
        // - align-content
        
        return (
            <BlockControls group="block">
                <ToolbarGroup>
                    {/* Grid toolbar controls placeholder */}
                </ToolbarGroup>
            </BlockControls>
        );
    };

    /**
     * Inspector panel controls for grid-specific settings
     */
    const renderInspectorControls = () => {
        return (
            <InspectorControls group="settings">
                <ToolsPanel
                    label="Grid Layout Settings"
                    resetAll={resetAllGridAttributes}
                    panelId="collection-grid-panel"
                >
                    <p style={{ 
                        fontSize: '13px', 
                        color: '#757575', 
                        margin: '0 0 16px 0',
                        lineHeight: '1.4'
                    }}>
                        Configure CSS Grid layout properties for precise control over item placement and sizing.
                    </p>

                    {/* Column Count Control */}
                    {createToolsPanelItem(
                        'columnCount',
                        () => hasNonDefaultValue('columnCount', GRID_DEFAULTS.columnCount),
                        () => updateAttribute('columnCount', GRID_DEFAULTS.columnCount),
                        'Grid Columns',
                        <div>
                            <div style={{
                                display: 'flex',
                                justifyContent: 'space-between',
                                alignItems: 'center',
                                marginBottom: '8px'
                            }}>
                                <label style={{
                                    fontSize: '11px',
                                    fontWeight: '500',
                                    textTransform: 'uppercase',
                                    color: '#1e1e1e',
                                    margin: 0
                                }}>
                                    Grid Columns
                                </label>
                                <span style={{
                                    fontSize: '13px',
                                    fontWeight: '500',
                                    color: '#757575'
                                }}>
                                    {columnCount} column{columnCount !== 1 ? 's' : ''}
                                </span>
                            </div>
                            <RangeControl
                                value={columnCount}
                                onChange={(value) => updateAttribute('columnCount', value)}
                                min={1}
                                max={12}
                                step={1}
                                marks={true}
                                withInputField={false}
                                renderTooltipContent={(value) => `${value} column${value !== 1 ? 's' : ''}`}
                                __next40pxDefaultSize={true}
                                __nextHasNoMarginBottom={true}
                            />
                        </div>,
                        true
                    )}

                    {/* Placeholder for additional grid controls */}
                    <div style={{
                        padding: '16px',
                        backgroundColor: '#f8f9fa',
                        borderRadius: '4px',
                        border: '1px dashed #ddd',
                        textAlign: 'center',
                        margin: '16px 0'
                    }}>
                        <p style={{
                            fontSize: '12px',
                            color: '#666',
                            margin: 0,
                            fontStyle: 'italic'
                        }}>
                            Additional grid controls will be implemented here:
                            <br />
                            • Grid template areas
                            <br />
                            • Row sizing options
                            <br />
                            • Grid alignment controls
                            <br />
                            • Responsive breakpoint settings
                        </p>
                    </div>
                </ToolsPanel>
            </InspectorControls>
        );
    };

    return (
        <Fragment>
            {renderToolbarControls()}
            {renderInspectorControls()}
        </Fragment>
    );
}