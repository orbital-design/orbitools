/**
 * Entry Block Controls
 * 
 * Controls for individual Entry blocks within a Collection, using RangeControl slider
 * for intuitive column width selection with visual feedback.
 * 
 * @file blocks/entry/controls.tsx
 * @since 1.0.0
 */

import { Fragment, useMemo } from '@wordpress/element';
import { InspectorControls, useSetting } from '@wordpress/block-editor';
import {
    __experimentalToolsPanel as ToolsPanel,
    __experimentalToolsPanelItem as ToolsPanelItem,
    RangeControl,
} from '@wordpress/components';

import type { LayoutItemAttributes } from '../types';

interface EntryControlsProps {
    attributes: LayoutItemAttributes;
    setAttributes: (attributes: Partial<LayoutItemAttributes>) => void;
    context: {
        'orb/layoutType'?: string;
        'orb/itemWidth'?: string;
        'orb/columnSystem'?: number;
    };
}

/**
 * Generate column configuration based on grid system
 */
function getColumnConfig(gridSystem: number) {
    if (gridSystem === 5) {
        return {
            max: 5,
            marks: [
                { value: 0 },
                { value: 1 },
                { value: 2 },
                { value: 3 },
                { value: 4 },
                { value: 5 }
            ],
            getValueLabel: (value: number) => {
                if (value === 0) return 'Auto';
                const percentage = (value / 5 * 100).toFixed(0);
                return `${value} of 5 (${percentage}%)`;
            },
            getTooltipLabel: (value: number) => {
                if (value === 0) return 'Auto';
                return `${value}/5`;
            },
            getValueKey: (value: number) => {
                if (value === 0) return '';
                return `w-${value}`;
            },
            getKeyValue: (key: string) => {
                if (!key || key === 'auto') return 0;
                const match = key.match(/w-(\d+)/);
                return match ? parseInt(match[1]) : 0;
            }
        };
    } else {
        return {
            max: 12,
            marks: [
                { value: 0 },
                { value: 1 },
                { value: 2 },
                { value: 3 },
                { value: 4 },
                { value: 5 },
                { value: 6 },
                { value: 7 },
                { value: 8 },
                { value: 9 },
                { value: 10 },
                { value: 11 },
                { value: 12 }
            ],
            getValueLabel: (value: number) => {
                if (value === 0) return 'Auto';
                const percentage = (value / 12 * 100).toFixed(1);
                return `${value} of 12 (${percentage}%)`;
            },
            getTooltipLabel: (value: number) => {
                if (value === 0) return 'Auto';
                return `${value}/12`;
            },
            getValueKey: (value: number) => {
                if (value === 0) return '';
                return `w-${value}`;
            },
            getKeyValue: (key: string) => {
                if (!key || key === 'auto') return 0;
                const match = key.match(/w-(\d+)/);
                return match ? parseInt(match[1]) : 0;
            }
        };
    }
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
 * Helper to get spacing index by value (now supports CSS variable references)
 */
function getSpacingIndexByValue(spacingSizes: any[], value: string) {
    if (!spacingSizes || !Array.isArray(spacingSizes)) return -1;
    
    // Handle CSS variable references (e.g., "var(--wp--preset--spacing--medium)")
    if (value && value.startsWith('var(--wp--preset--spacing--')) {
        const slug = value.match(/var\(--wp--preset--spacing--([^)]+)\)/)?.[1];
        if (slug) {
            const index = spacingSizes.findIndex((size: any) => size.slug === slug);
            return index >= 0 ? index : -1;
        }
    }
    
    // Fallback: try to match by raw size value (for backward compatibility)
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
            onDeselect={onDeselect}
            label={label}
            isShownByDefault={isShownByDefault}
            panelId="entry-layout-panel"
        >
            {children}
        </ToolsPanelItem>
    );
}

/**
 * Entry Block Controls Component
 */
export default function EntryControls({ attributes, setAttributes, context }: EntryControlsProps) {
    const { width, gapSize } = attributes;
    const { 
        'orb/layoutType': parentLayoutType = 'row',
        'orb/itemWidth': parentItemWidth = 'equal',
        'orb/columnSystem': parentColumnSystem = 12
    } = context;

    // Get spacing sizes from theme.json
    const spacingSizes = useSetting('spacing.spacingSizes');

    /**
     * Determine if width controls should be shown
     * Only show for row layout with custom item width
     */
    const shouldShowWidthControls = useMemo(() => {
        return parentLayoutType === 'row' && parentItemWidth === 'custom';
    }, [parentLayoutType, parentItemWidth]);

    /**
     * Get column configuration based on parent column system
     */
    const columnConfig = useMemo(() => {
        return getColumnConfig(parentColumnSystem);
    }, [parentColumnSystem]);

    /**
     * Helper to update width attribute using slider value
     */
    const updateColumnWidth = (sliderValue: number) => {
        const columnKey = columnConfig.getValueKey(sliderValue);
        setAttributes({ width: columnKey });
    };

    /**
     * Reset width to auto (0)
     */
    const resetWidth = () => {
        setAttributes({ width: '' });
    };

    /**
     * Check if width has a non-default value
     */
    const hasCustomWidth = () => {
        return width !== '' && width !== undefined;
    };

    /**
     * Get current slider value from stored width
     */
    const getCurrentSliderValue = () => {
        return columnConfig.getKeyValue(width || '');
    };

    /**
     * Get current label for display
     */
    const getCurrentLabel = () => {
        const sliderValue = getCurrentSliderValue();
        return columnConfig.getValueLabel(sliderValue);
    };

    /**
     * Gap control functions
     */
    const currentGapSize = gapSize;
    const currentGapIndex = getSpacingIndexByValue(spacingSizes, currentGapSize || '');
    const maxGapIndex = spacingSizes ? spacingSizes.length - 1 : 0;
    
    const getCurrentGapName = () => {
        if (!currentGapSize) return 'Default';
        if (currentGapSize === '0') return 'None';
        return spacingSizes && currentGapIndex >= 0 ? spacingSizes[currentGapIndex].name : currentGapSize;
    };

    const updateGapSize = (index: number) => {
        if (index === 0) {
            setAttributes({ gapSize: undefined });
        } else if (index === 1) {
            setAttributes({ gapSize: '0' });
        } else {
            const spacingIndex = index - 2;
            const spacing = spacingSizes && spacingSizes[spacingIndex];
            if (spacing) {
                // Store the CSS variable reference instead of raw value
                setAttributes({ gapSize: `var(--wp--preset--spacing--${spacing.slug})` });
            } else {
                setAttributes({ gapSize: undefined });
            }
        }
    };

    const resetGapSize = () => {
        setAttributes({ gapSize: undefined });
    };

    const hasCustomGapSize = () => {
        return gapSize !== undefined;
    };

    return (
        <Fragment>
            <InspectorControls group="settings">
                <ToolsPanel
                    label="Entry Settings"
                    resetAll={() => {
                        resetWidth();
                        resetGapSize();
                    }}
                    panelId="entry-layout-panel"
                >
                    {/* Width Control - only show for row layout with custom item width */}
                    {shouldShowWidthControls && (
                        <>
                            <p style={{ 
                                fontSize: '13px', 
                                color: '#757575', 
                                margin: '0 0 16px 0',
                                lineHeight: '1.4'
                            }}>
                                Set the column width within the {parentColumnSystem}-column grid layout.
                            </p>

                            {createToolsPanelItem(
                                'width',
                                hasCustomWidth,
                                resetWidth,
                                'Column Width',
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
                                            Column Width
                                        </label>
                                        <span style={{
                                            fontSize: '13px',
                                            fontWeight: '500',
                                            color: '#757575'
                                        }}>
                                            {getCurrentLabel()}
                                        </span>
                                    </div>
                                    <RangeControl
                                        value={getCurrentSliderValue()}
                                        onChange={updateColumnWidth}
                                        min={0}
                                        max={columnConfig.max}
                                        step={1}
                                        marks={columnConfig.marks}
                                        withInputField={false}
                                        renderTooltipContent={(value) => columnConfig.getTooltipLabel(value)}
                                        __next40pxDefaultSize={true}
                                        __nextHasNoMarginBottom={true}
                                    />
                                </div>,
                                true
                            )}
                        </>
                    )}

                    {/* Gap Control */}
                    {spacingSizes && createToolsPanelItem(
                        'gapSize',
                        hasCustomGapSize,
                        resetGapSize,
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
                                    {getCurrentGapName()}
                                </span>
                            </div>
                            <RangeControl
                                value={currentGapIndex === -1 ? (currentGapSize === '0' ? 1 : 0) : currentGapIndex + 2}
                                onChange={updateGapSize}
                                min={0}
                                max={maxGapIndex + 2}
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
                </ToolsPanel>
            </InspectorControls>
        </Fragment>
    );
}