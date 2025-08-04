/**
 * Collection Block Row Controls
 * 
 * Flex layout controls for row layout type including:
 * - Item width and column system
 * - Flex direction and alignment  
 * - Column count
 * - Mobile stacking
 * - Quick toolbar controls
 * 
 * @file blocks/collection/row-controls.tsx
 * @since 1.0.0
 */

import { Fragment } from '@wordpress/element';
import { InspectorControls, BlockControls, useSetting } from '@wordpress/block-editor';
import {
    __experimentalToolsPanel as ToolsPanel,
    __experimentalToolsPanelItem as ToolsPanelItem,
    __experimentalToggleGroupControl as ToggleGroupControl,
    __experimentalToggleGroupControlOption as ToggleGroupControlOption,
    ToolbarGroup,
    ToolbarDropdownMenu,
    ToggleControl,
    RangeControl,
    SVG,
    Path,
} from '@wordpress/components';

import type { LayoutAttributes } from '../types';

interface RowControlsProps {
    attributes: LayoutAttributes;
    setAttributes: (attributes: Partial<LayoutAttributes>) => void;
}

/**
 * Default values for row controls
 */
const ROW_DEFAULTS = {
    columnCount: 2,
    flexDirection: 'row',
    alignItems: 'stretch',
    justifyContent: 'flex-start',
    stackOnMobile: true,
    itemWidth: 'equal',
    columnSystem: 12,
} as const;

/**
 * Item width options for flexible row layout
 */
const ITEM_WIDTH_OPTIONS = [
    { value: 'fit', label: 'Fit' },
    { value: 'equal', label: 'Grow' },
    { value: 'custom', label: 'Custom' },
] as const;

/**
 * Column system options for custom width layout
 */
const COLUMN_SYSTEM_OPTIONS = [
    { value: 5, label: '5 Column Grid' },
    { value: 12, label: '12 Column Grid' },
] as const;

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
            panelId="collection-row-panel"
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
 * Row Controls Component
 */
export default function RowControls({ attributes, setAttributes }: RowControlsProps) {
    const { 
        itemWidth = ROW_DEFAULTS.itemWidth, 
        columnSystem = ROW_DEFAULTS.columnSystem,
        columnCount = ROW_DEFAULTS.columnCount,
        flexDirection = ROW_DEFAULTS.flexDirection,
        alignItems = ROW_DEFAULTS.alignItems,
        justifyContent = ROW_DEFAULTS.justifyContent,
        stackOnMobile = ROW_DEFAULTS.stackOnMobile,
        gapSize,
    } = attributes;

    /**
     * Helper to update a single attribute
     */
    const updateAttribute = (key: keyof LayoutAttributes, value: any) => {
        setAttributes({ [key]: value });
    };

    /**
     * Reset all row attributes to defaults
     */
    const resetAllRowAttributes = () => {
        setAttributes({
            itemWidth: ROW_DEFAULTS.itemWidth,
            columnSystem: ROW_DEFAULTS.columnSystem,
            columnCount: ROW_DEFAULTS.columnCount,
            flexDirection: ROW_DEFAULTS.flexDirection,
            alignItems: ROW_DEFAULTS.alignItems,
            justifyContent: ROW_DEFAULTS.justifyContent,
            stackOnMobile: ROW_DEFAULTS.stackOnMobile,
        });
    };

    /**
     * Check if an attribute has a non-default value
     */
    const hasNonDefaultValue = (key: keyof LayoutAttributes, defaultValue: any) => {
        return attributes[key] !== undefined && attributes[key] !== defaultValue;
    };

    /**
     * Toolbar alignment controls for quick access
     */
    const renderToolbarControls = () => {
        const isColumn = flexDirection?.startsWith('column');
        const alignmentControls = [];

        // Direction Control
        const directionControls = [
            {
                icon: (
                    <SVG width="25" height="25" viewBox="0 0 25 25" fill="none">
                        <Path className="line" fill="#1D303A" d="M13 15.5c0 .2761-.2239.5-.5.5h-1c-.2761 0-.5-.2239-.5-.5v-6c0-.27614.2239-.5.5-.5h1c.2761 0 .5.22386.5.5v6Z" />
                        <Path className="box" fill="#32A3E2" d="M10 15.5c0 .2761-.22386.5-.5.5h-6c-.27614 0-.5-.2239-.5-.5v-6c0-.27614.22386-.5.5-.5h6c.27614 0 .5.22386.5.5v6Z" />
                        <Path className="box" fill="#32A3E2" d="M18 15.5c0 .2761-.2239.5-.5.5h-3c-.2761 0-.5-.2239-.5-.5v-6c0-.27614.2239-.5.5-.5h3c.2761 0 .5.22386.5.5v6Z" />
                        <rect className="line" width="19" height="1" fill="#1D303A" rx=".5" transform="matrix(1 0 0 -1 3 7)" />
                        <rect className="line" width="19" height="1" fill="#1D303A" rx=".5" transform="matrix(1 0 0 -1 3 19)" />
                        <Path className="arrow" stroke="#1D303A" strokeLinecap="round" strokeLinejoin="round" strokeWidth=".75" d="m19.4 15.65 2.25-3.15-2.25-3.15" />
                    </SVG>
                ),
                title: 'Horizontal',
                onClick: () => updateAttribute('flexDirection', 'row'),
                isActive: flexDirection === 'row'
            },
            {
                icon: (
                    <SVG width="25" height="25" viewBox="0 0 25 25" fill="none">
                        <Path className="line" fill="#1D303A" d="M16 12.5c0 .2761-.2239.5-.5.5h-6c-.27614 0-.5-.2239-.5-.5v-1c0-.2761.22386-.5.5-.5h6c.2761 0 .5.2239.5.5v1Z" />
                        <Path className="box" fill="#32A3E2" d="M16 9.5c0 .27614-.2239.5-.5.5h-6c-.27614 0-.5-.22386-.5-.5v-6c0-.27614.22386-.5.5-.5h6c.2761 0 .5.22386.5.5v6Z" />
                        <Path className="box" fill="#32A3E2" d="M16 17.5c0 .2761-.2239.5-.5.5h-6c-.27614 0-.5-.2239-.5-.5v-3c0-.2761.22386-.5.5-.5h6c.2761 0 .5.2239.5.5v3Z" />
                        <rect className="line" width="1" height="19" fill="#1D303A" rx=".5" transform="matrix(1 0 0 -1 6 22)" />
                        <rect className="line" width="1" height="19" fill="#1D303A" rx=".5" transform="matrix(1 0 0 -1 18 22)" />
                        <Path className="arrow" stroke="#1D303A" strokeLinecap="round" strokeLinejoin="round" strokeWidth=".75" d="m15.65 19.4-3.15 2.25-3.14999-2.25" />
                    </SVG>
                ),
                title: 'Vertical',
                onClick: () => updateAttribute('flexDirection', 'column'),
                isActive: flexDirection === 'column'
            }
        ];

        alignmentControls.push(
            <ToolbarDropdownMenu
                key="direction"
                controls={directionControls}
                icon={directionControls.find(c => c.isActive)?.icon || directionControls[0].icon}
                label="Direction"
            />
        );

        return (
            <BlockControls group="block">
                <ToolbarGroup>
                    {alignmentControls}
                </ToolbarGroup>
            </BlockControls>
        );
    };

    /**
     * Inspector panel controls for row-specific settings
     */
    const renderInspectorControls = () => {
        // Get spacing sizes from theme.json for the spacing control
        const spacingSizes = useSetting('spacing.spacingSizes');
        const currentGapSize = gapSize;
        const currentIndex = getSpacingIndexByValue(spacingSizes, currentGapSize || '');
        const maxIndex = spacingSizes ? spacingSizes.length - 1 : 0;
        
        // Get current spacing name for display
        const currentSpacingName = !currentGapSize 
            ? 'Default'
            : currentGapSize === '0' 
                ? 'None' 
                : (spacingSizes && currentIndex >= 0 ? spacingSizes[currentIndex].name : currentGapSize);

        return (
            <InspectorControls group="settings">
                {/* Layout ToolsPanel */}
                <ToolsPanel
                    label="Layout"
                    resetAll={() => {
                        updateAttribute('columnCount', ROW_DEFAULTS.columnCount);
                        updateAttribute('gapSize', undefined);
                        updateAttribute('stackOnMobile', ROW_DEFAULTS.stackOnMobile);
                    }}
                    panelId="collection-row-layout-panel"
                >
                    {/* Column Count Control */}
                    {createToolsPanelItem(
                        'columnCount',
                        () => hasNonDefaultValue('columnCount', ROW_DEFAULTS.columnCount),
                        () => updateAttribute('columnCount', ROW_DEFAULTS.columnCount),
                        'Columns',
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
                                    Columns
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
                                max={10}
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

                    {/* Spacing Control */}
                    {spacingSizes && createToolsPanelItem(
                        'gapSize',
                        () => hasNonDefaultValue('gapSize', undefined),
                        () => updateAttribute('gapSize', undefined),
                        'Spacing',
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
                                    Spacing
                                </label>
                                <span style={{
                                    fontSize: '13px',
                                    fontWeight: '500',
                                    color: '#757575'
                                }}>
                                    {currentSpacingName}
                                </span>
                            </div>
                            <RangeControl
                                value={currentIndex === -1 ? (currentGapSize === '0' ? 1 : 0) : currentIndex + 2} // 0=Default, 1=None(0), 2+=Spacing
                                onChange={(index) => {
                                    if (index === 0) {
                                        // Index 0 = "Default" - clear the gapSize
                                        updateAttribute('gapSize', undefined);
                                    } else if (index === 1) {
                                        // Index 1 = "None" - set gap to 0
                                        updateAttribute('gapSize', '0');
                                    } else {
                                        // Index 2+ = Spacing values - shift back by 2 to get actual spacing index
                                        const newValue = getSpacingValueByIndex(spacingSizes, index - 2);
                                        updateAttribute('gapSize', newValue || undefined);
                                    }
                                }}
                                min={0}
                                max={maxIndex + 2} // Add 2 for "Default" and "None" options
                                step={1}
                                marks={true}
                                withInputField={false}
                                renderTooltipContent={(index) => {
                                    if (index === 0) return 'Default';
                                    if (index === 1) return 'None';
                                    const spacing = spacingSizes && spacingSizes[index - 2];
                                    return spacing ? spacing.name : 'None';
                                }}
                                __next40pxDefaultSize={true}
                                __nextHasNoMarginBottom={true}
                            />
                        </div>,
                        true
                    )}

                    {/* Stack on Mobile Control */}
                    {createToolsPanelItem(
                        'stackOnMobile',
                        () => hasNonDefaultValue('stackOnMobile', ROW_DEFAULTS.stackOnMobile),
                        () => updateAttribute('stackOnMobile', ROW_DEFAULTS.stackOnMobile),
                        'Stack',
                        <ToggleControl
                            label="Stack on Mobile"
                            help="Stack columns on mobile devices"
                            checked={stackOnMobile}
                            onChange={(value) => updateAttribute('stackOnMobile', value)}
                            __nextHasNoMarginBottom={true}
                        />,
                        true
                    )}
                </ToolsPanel>

                {/* Entries ToolsPanel */}
                <ToolsPanel
                    label="Entries"
                    resetAll={() => {
                        updateAttribute('itemWidth', ROW_DEFAULTS.itemWidth);
                        updateAttribute('columnSystem', ROW_DEFAULTS.columnSystem);
                    }}
                    panelId="collection-row-content-panel"
                >
                    {/* Entry Width Control */}
                    {createToolsPanelItem(
                        'itemWidth',
                        () => hasNonDefaultValue('itemWidth', ROW_DEFAULTS.itemWidth),
                        () => updateAttribute('itemWidth', ROW_DEFAULTS.itemWidth),
                        'Entry Width',
                        createToggleGroup(
                            itemWidth,
                            (value) => updateAttribute('itemWidth', value),
                            ITEM_WIDTH_OPTIONS,
                            'Entry Width'
                        ),
                        true
                    )}

                    {/* Column System Control - only show for custom width */}
                    {itemWidth === 'custom' && createToolsPanelItem(
                        'columnSystem',
                        () => hasNonDefaultValue('columnSystem', ROW_DEFAULTS.columnSystem),
                        () => updateAttribute('columnSystem', ROW_DEFAULTS.columnSystem),
                        'Column System',
                        <div style={{ marginTop: '16px' }}>
                            {createToggleGroup(
                                columnSystem,
                                (value) => updateAttribute('columnSystem', value),
                                COLUMN_SYSTEM_OPTIONS,
                                'Grid System'
                            )}
                        </div>,
                        true
                    )}
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