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
import { createToolsPanelItem, createToggleGroup } from '../utils/control-helpers';
import { InspectorControls, BlockControls } from '@wordpress/block-editor';
import {
    __experimentalToolsPanel as ToolsPanel,
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
    itemWidth: 'fit',
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

        // Horizontal Alignment Controls
        if (isColumn) {
            // For column: align-items controls horizontal alignment
            const alignItemsControls = [
                {
                    icon: (
                        <SVG width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <Path d="M4 2L4 22M11 8H13C13.5523 8 14 7.55228 14 7V5C14 4.44772 13.5523 4 13 4H11C10.4477 4 10 4.44772 10 5V7C10 7.55228 10.4477 8 11 8Z" stroke="currentColor" strokeWidth="1.5"/>
                        </SVG>
                    ),
                    title: 'Start',
                    onClick: () => updateAttribute('alignItems', 'flex-start'),
                    isActive: alignItems === 'flex-start'
                },
                {
                    icon: (
                        <SVG width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <Path d="M12 2L12 22M10 8H14C14.5523 8 15 7.55228 15 7V5C15 4.44772 14.5523 4 14 4H10C9.44772 4 9 4.44772 9 5V7C9 7.55228 9.44772 8 10 8Z" stroke="currentColor" strokeWidth="1.5"/>
                        </SVG>
                    ),
                    title: 'Center',
                    onClick: () => updateAttribute('alignItems', 'center'),
                    isActive: alignItems === 'center'
                },
                {
                    icon: (
                        <SVG width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <Path d="M20 2L20 22M13 8H17C17.5523 8 18 7.55228 18 7V5C18 4.44772 17.5523 4 17 4H13C12.4477 4 12 4.44772 12 5V7C12 7.55228 12.4477 8 13 8Z" stroke="currentColor" strokeWidth="1.5"/>
                        </SVG>
                    ),
                    title: 'End',
                    onClick: () => updateAttribute('alignItems', 'flex-end'),
                    isActive: alignItems === 'flex-end'
                },
                {
                    icon: (
                        <SVG width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <Path d="M2 2L2 22M22 2L22 22M8 8H16C16.5523 8 17 7.55228 17 7V5C17 4.44772 16.5523 4 16 4H8C7.44772 4 7 4.44772 7 5V7C7 7.55228 7.44772 8 8 8Z" stroke="currentColor" strokeWidth="1.5"/>
                        </SVG>
                    ),
                    title: 'Stretch',
                    onClick: () => updateAttribute('alignItems', 'stretch'),
                    isActive: alignItems === 'stretch'
                }
            ];

            alignmentControls.push(
                <ToolbarDropdownMenu
                    key="align-items"
                    controls={alignItemsControls}
                    icon={alignItemsControls.find(c => c.isActive)?.icon || alignItemsControls[3].icon}
                    label="Horizontal Alignment"
                />
            );
        } else {
            // For row: justify-content controls horizontal alignment
            const justifyContentControls = [
                {
                    icon: (
                        <SVG width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <Path d="M4 2L4 22M11 16L11 8C11 7.44772 10.5523 7 10 7H8C7.44772 7 7 7.44772 7 8L7 16C7 16.5523 7.44772 17 8 17H10C10.5523 17 11 16.5523 11 16Z" stroke="currentColor" strokeWidth="1.5"/>
                        </SVG>
                    ),
                    title: 'Start',
                    onClick: () => updateAttribute('justifyContent', 'flex-start'),
                    isActive: justifyContent === 'flex-start'
                },
                {
                    icon: (
                        <SVG width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <Path d="M12 2L12 22M9 16L9 8C9 7.44772 8.55228 7 8 7H6C5.44772 7 5 7.44772 5 8L5 16C5 16.5523 5.44772 17 6 17H8C8.55229 17 9 16.5523 9 16Z" stroke="currentColor" strokeWidth="1.5"/>
                        </SVG>
                    ),
                    title: 'Center',
                    onClick: () => updateAttribute('justifyContent', 'center'),
                    isActive: justifyContent === 'center'
                },
                {
                    icon: (
                        <SVG width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <Path d="M20 2L20 22M13 16L13 8C13 7.44772 12.5523 7 12 7H10C9.44772 7 9 7.44772 9 8L9 16C9 16.5523 9.44772 17 10 17H12C12.5523 17 13 16.5523 13 16Z" stroke="currentColor" strokeWidth="1.5"/>
                        </SVG>
                    ),
                    title: 'End',
                    onClick: () => updateAttribute('justifyContent', 'flex-end'),
                    isActive: justifyContent === 'flex-end'
                },
                {
                    icon: (
                        <SVG width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <Path d="M4 2L4 22M20 2L20 22M11 16L11 8C11 7.44772 10.5523 7 10 7H8C7.44772 7 7 7.44772 7 8L7 16C7 16.5523 7.44772 17 8 17H10C10.5523 17 11 16.5523 11 16Z" stroke="currentColor" strokeWidth="1.5"/>
                        </SVG>
                    ),
                    title: 'Space Between',
                    onClick: () => updateAttribute('justifyContent', 'space-between'),
                    isActive: justifyContent === 'space-between'
                },
                {
                    icon: (
                        <SVG width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <Path d="M21 2V22M4 2L4 22M10 16L10 8C10 7.44772 9.55229 7 9 7H7C6.44772 7 6 7.44772 6 8L6 16C6 16.5523 6.44772 17 7 17H9C9.55229 17 10 16.5523 10 16Z" stroke="currentColor" strokeWidth="1.5"/>
                        </SVG>
                    ),
                    title: 'Space Around',
                    onClick: () => updateAttribute('justifyContent', 'space-around'),
                    isActive: justifyContent === 'space-around'
                },
                {
                    icon: (
                        <SVG width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <Path d="M2 2L2 22M22 2L22 22M11 16L11 8C11 7.44772 10.5523 7 10 7H8C7.44772 7 7 7.44772 7 8L7 16C7 16.5523 7.44772 17 8 17H10C10.5523 17 11 16.5523 11 16Z" stroke="currentColor" strokeWidth="1.5"/>
                        </SVG>
                    ),
                    title: 'Space Evenly',
                    onClick: () => updateAttribute('justifyContent', 'space-evenly'),
                    isActive: justifyContent === 'space-evenly'
                }
            ];

            alignmentControls.push(
                <ToolbarDropdownMenu
                    key="justify-content"
                    controls={justifyContentControls}
                    icon={justifyContentControls.find(c => c.isActive)?.icon || justifyContentControls[0].icon}
                    label="Horizontal Alignment"
                />
            );
        }

        // Vertical Alignment Controls
        if (isColumn) {
            // For column: justify-content controls vertical alignment
            const justifyContentControls = [
                {
                    icon: (
                        <SVG width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <Path d="M2 4L22 4M11 16L11 8C11 7.44772 10.5523 7 10 7H8C7.44772 7 7 7.44772 7 8L7 16C7 16.5523 7.44772 17 8 17H10C10.5523 17 11 16.5523 11 16Z" stroke="currentColor" strokeWidth="1.5"/>
                        </SVG>
                    ),
                    title: 'Top',
                    onClick: () => updateAttribute('justifyContent', 'flex-start'),
                    isActive: justifyContent === 'flex-start'
                },
                {
                    icon: (
                        <SVG width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <Path d="M12 2L12 22M9 16L9 8C9 7.44772 8.55228 7 8 7H6C5.44772 7 5 7.44772 5 8L5 16C5 16.5523 5.44772 17 6 17H8C8.55229 17 9 16.5523 9 16Z" stroke="currentColor" strokeWidth="1.5"/>
                        </SVG>
                    ),
                    title: 'Middle',
                    onClick: () => updateAttribute('justifyContent', 'center'),
                    isActive: justifyContent === 'center'
                },
                {
                    icon: (
                        <SVG width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <Path d="M2 20L22 20M11 16L11 8C11 7.44772 10.5523 7 10 7H8C7.44772 7 7 7.44772 7 8L7 16C7 16.5523 7.44772 17 8 17H10C10.5523 17 11 16.5523 11 16Z" stroke="currentColor" strokeWidth="1.5"/>
                        </SVG>
                    ),
                    title: 'Bottom',
                    onClick: () => updateAttribute('justifyContent', 'flex-end'),
                    isActive: justifyContent === 'flex-end'
                },
                {
                    icon: (
                        <SVG width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <Path d="M2 4L22 4M2 20L22 20M11 16L11 8C11 7.44772 10.5523 7 10 7H8C7.44772 7 7 7.44772 7 8L7 16C7 16.5523 7.44772 17 8 17H10C10.5523 17 11 16.5523 11 16Z" stroke="currentColor" strokeWidth="1.5"/>
                        </SVG>
                    ),
                    title: 'Space Between',
                    onClick: () => updateAttribute('justifyContent', 'space-between'),
                    isActive: justifyContent === 'space-between'
                }
            ];

            alignmentControls.push(
                <ToolbarDropdownMenu
                    key="justify-content-vertical"
                    controls={justifyContentControls}
                    icon={justifyContentControls.find(c => c.isActive)?.icon || justifyContentControls[0].icon}
                    label="Vertical Alignment"
                />
            );
        } else {
            // For row: align-items controls vertical alignment
            const alignItemsControls = [
                {
                    icon: (
                        <SVG width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <Path d="M2 4L22 4M11 16L11 8C11 7.44772 10.5523 7 10 7H8C7.44772 7 7 7.44772 7 8L7 16C7 16.5523 7.44772 17 8 17H10C10.5523 17 11 16.5523 11 16Z" stroke="currentColor" strokeWidth="1.5"/>
                        </SVG>
                    ),
                    title: 'Top',
                    onClick: () => updateAttribute('alignItems', 'flex-start'),
                    isActive: alignItems === 'flex-start'
                },
                {
                    icon: (
                        <SVG width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <Path d="M12 2L12 22M9 16L9 8C9 7.44772 8.55228 7 8 7H6C5.44772 7 5 7.44772 5 8L5 16C5 16.5523 5.44772 17 6 17H8C8.55229 17 9 16.5523 9 16Z" stroke="currentColor" strokeWidth="1.5"/>
                        </SVG>
                    ),
                    title: 'Middle',
                    onClick: () => updateAttribute('alignItems', 'center'),
                    isActive: alignItems === 'center'
                },
                {
                    icon: (
                        <SVG width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <Path d="M2 20L22 20M11 16L11 8C11 7.44772 10.5523 7 10 7H8C7.44772 7 7 7.44772 7 8L7 16C7 16.5523 7.44772 17 8 17H10C10.5523 17 11 16.5523 11 16Z" stroke="currentColor" strokeWidth="1.5"/>
                        </SVG>
                    ),
                    title: 'Bottom',
                    onClick: () => updateAttribute('alignItems', 'flex-end'),
                    isActive: alignItems === 'flex-end'
                },
                {
                    icon: (
                        <SVG width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <Path d="M2 2L2 22M22 2L22 22M11 16L11 8C11 7.44772 10.5523 7 10 7H8C7.44772 7 7 7.44772 7 8L7 16C7 16.5523 7.44772 17 8 17H10C10.5523 17 11 16.5523 11 16Z" stroke="currentColor" strokeWidth="1.5"/>
                        </SVG>
                    ),
                    title: 'Stretch',
                    onClick: () => updateAttribute('alignItems', 'stretch'),
                    isActive: alignItems === 'stretch'
                }
            ];

            alignmentControls.push(
                <ToolbarDropdownMenu
                    key="align-items-vertical"
                    controls={alignItemsControls}
                    icon={alignItemsControls.find(c => c.isActive)?.icon || alignItemsControls[3].icon}
                    label="Vertical Alignment"
                />
            );
        }

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

        return (
            <>
                <InspectorControls group="settings">
                    {/* Layout ToolsPanel */}
                    <ToolsPanel
                        label="Layout"
                        resetAll={() => {
                            updateAttribute('columnCount', ROW_DEFAULTS.columnCount);
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

            </>
        );
    };

    return (
        <Fragment>
            {renderToolbarControls()}
            {renderInspectorControls()}
        </Fragment>
    );
}