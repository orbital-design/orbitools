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
} from '@wordpress/components';

import type { LayoutAttributes } from '../types';
import { flexDirectionIcons, justifyContentIcons, alignItemsIcons } from '../utils/flex-icons';

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
                icon: flexDirectionIcons['row'],
                title: 'Horizontal',
                onClick: () => updateAttribute('flexDirection', 'row'),
                isActive: flexDirection === 'row'
            },
            {
                icon: flexDirectionIcons['column'],
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
                    icon: alignItemsIcons.column['flex-start'],
                    title: 'Start',
                    onClick: () => updateAttribute('alignItems', 'flex-start'),
                    isActive: alignItems === 'flex-start'
                },
                {
                    icon: alignItemsIcons.column['center'],
                    title: 'Center',
                    onClick: () => updateAttribute('alignItems', 'center'),
                    isActive: alignItems === 'center'
                },
                {
                    icon: alignItemsIcons.column['flex-end'],
                    title: 'End',
                    onClick: () => updateAttribute('alignItems', 'flex-end'),
                    isActive: alignItems === 'flex-end'
                },
                {
                    icon: alignItemsIcons.column['stretch'],
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
                    icon: justifyContentIcons.row['flex-start'],
                    title: 'Start',
                    onClick: () => updateAttribute('justifyContent', 'flex-start'),
                    isActive: justifyContent === 'flex-start'
                },
                {
                    icon: justifyContentIcons.row['center'],
                    title: 'Center',
                    onClick: () => updateAttribute('justifyContent', 'center'),
                    isActive: justifyContent === 'center'
                },
                {
                    icon: justifyContentIcons.row['flex-end'],
                    title: 'End',
                    onClick: () => updateAttribute('justifyContent', 'flex-end'),
                    isActive: justifyContent === 'flex-end'
                },
                {
                    icon: justifyContentIcons.row['space-between'],
                    title: 'Space Between',
                    onClick: () => updateAttribute('justifyContent', 'space-between'),
                    isActive: justifyContent === 'space-between'
                },
                {
                    icon: justifyContentIcons.row['space-around'],
                    title: 'Space Around',
                    onClick: () => updateAttribute('justifyContent', 'space-around'),
                    isActive: justifyContent === 'space-around'
                },
                {
                    icon: justifyContentIcons.row['space-evenly'],
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
                    icon: justifyContentIcons.column['flex-start'],
                    title: 'Top',
                    onClick: () => updateAttribute('justifyContent', 'flex-start'),
                    isActive: justifyContent === 'flex-start'
                },
                {
                    icon: justifyContentIcons.column['center'],
                    title: 'Middle',
                    onClick: () => updateAttribute('justifyContent', 'center'),
                    isActive: justifyContent === 'center'
                },
                {
                    icon: justifyContentIcons.column['flex-end'],
                    title: 'Bottom',
                    onClick: () => updateAttribute('justifyContent', 'flex-end'),
                    isActive: justifyContent === 'flex-end'
                },
                {
                    icon: justifyContentIcons.column['space-between'],
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
                    icon: alignItemsIcons.row['flex-start'],
                    title: 'Top',
                    onClick: () => updateAttribute('alignItems', 'flex-start'),
                    isActive: alignItems === 'flex-start'
                },
                {
                    icon: alignItemsIcons.row['center'],
                    title: 'Middle',
                    onClick: () => updateAttribute('alignItems', 'center'),
                    isActive: alignItems === 'center'
                },
                {
                    icon: alignItemsIcons.row['flex-end'],
                    title: 'Bottom',
                    onClick: () => updateAttribute('alignItems', 'flex-end'),
                    isActive: alignItems === 'flex-end'
                },
                {
                    icon: alignItemsIcons.row['stretch'],
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
            <BlockControls>
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
                        'Column Width',
                        createToggleGroup(
                            itemWidth,
                            (value) => updateAttribute('itemWidth', value),
                            ITEM_WIDTH_OPTIONS,
                            'Column Width'
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