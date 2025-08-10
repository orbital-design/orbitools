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
import { createToolsPanelItem } from '../utils/control-helpers';
import { InspectorControls } from '@wordpress/block-editor';
import {
    __experimentalToolsPanel as ToolsPanel,
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
 * Entry Block Controls Component
 */
export default function EntryControls({ attributes, setAttributes, context }: EntryControlsProps) {
    const { width } = attributes;
    const { 
        'orb/layoutType': parentLayoutType = 'row',
        'orb/itemWidth': parentItemWidth = 'equal',
        'orb/columnSystem': parentColumnSystem = 12
    } = context;


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
    const updateColumnWidth = (sliderValue?: number) => {
        if (typeof sliderValue === 'number') {
            const columnKey = columnConfig.getValueKey(sliderValue);
            setAttributes({ width: columnKey });
        }
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


    return (
        <Fragment>
            <InspectorControls group="settings">
                <ToolsPanel
                    label="Entry Settings"
                    resetAll={() => {
                        resetWidth();
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
                                        renderTooltipContent={(value) => columnConfig.getTooltipLabel(value || 0)}
                                        __next40pxDefaultSize={true}
                                        __nextHasNoMarginBottom={true}
                                    />
                                </div>,
                                true
                            )}
                        </>
                    )}
                </ToolsPanel>
            </InspectorControls>
        </Fragment>
    );
}