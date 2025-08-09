/**
 * Dimensions Control
 *
 * Single ToolsPanel with ToolsPanelItems for each breakpoint.
 * Each ToolsPanelItem contains gap, padding, and margin controls for that breakpoint.
 *
 * Structure:
 * ToolsPanel - Dimensions
 *   └── ToolsPanelItem - Base breakpoint (shown by default)
 *       └── gap, padding, margin controls
 *   └── ToolsPanelItem - SM breakpoint
 *       └── gap, padding, margin controls
 *   └── ToolsPanelItem - MD breakpoint
 *       └── gap, padding, margin controls
 *   etc.
 *
 * @file blocks/utils/tabbed-dimensions-control.tsx
 * @since 1.0.0
 */

import { __ } from '@wordpress/i18n';
import { RangeControl, Button } from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';
import {
    __experimentalToolsPanel as ToolsPanel,
    __experimentalToolsPanelItem as ToolsPanelItem,
} from '@wordpress/components';

import type { ResponsiveValue, Breakpoint } from './responsive-controls';
import { getResponsiveClasses } from './responsive-controls';
import { getBlockDimensionsConfig, getBreakpointOptions } from './dimensions-config';

export interface DimensionsControlProps {
    /** Current gap responsive values */
    gap?: ResponsiveValue<string>;
    /** Current padding responsive values */
    padding?: ResponsiveValue<string>;
    /** Current margin responsive values */
    margin?: ResponsiveValue<string>;
    /** Callback when gap values change */
    onGapChange?: (gap: ResponsiveValue<string>) => void;
    /** Callback when padding values change */
    onPaddingChange?: (padding: ResponsiveValue<string>) => void;
    /** Callback when margin values change */
    onMarginChange?: (margin: ResponsiveValue<string>) => void;
    /** Block name for configuration lookup */
    blockName: string;
}

/**
 * Helper to get spacing index by slug
 */
function getSpacingIndexByValue(spacingSizes: any[], value: string): number {
    if (!spacingSizes || !Array.isArray(spacingSizes) || !value) return -1;
    return spacingSizes.findIndex((size: any) => size.slug === value);
}

/**
 * Get display name for spacing value
 */
function getSpacingDisplayName(spacingSizes: any[], value: string): string {
    if (!value) return 'Default';
    if (value === '0') return 'None';

    const index = getSpacingIndexByValue(spacingSizes, value);
    if (index >= 0 && spacingSizes[index]) {
        return spacingSizes[index].name;
    }

    return value;
}

/**
 * Create a custom box control for padding/margin using our spacing presets
 */
function createBoxControl(
    spacingSizes: any[],
    dimensionType: 'padding' | 'margin',
    value: any,
    onChange: (value: any) => void
) {
    // Handle legacy string format (convert to new format)
    if (typeof value === 'string') {
        value = {
            type: 'all',
            value: value
        };
    }
    
    // Get current mode from stored data
    const currentMode = value?.type || 'all';

    const toggleMode = () => {
        const currentValue = value?.value || value?.x || value?.y || value?.top || undefined;

        if (currentMode === 'all') {
            // Switch to split (x/y) mode
            onChange({
                type: 'split',
                x: currentValue, // horizontal (left/right)
                y: currentValue  // vertical (top/bottom)
            });
        } else if (currentMode === 'split') {
            // Switch to sides mode
            onChange({
                type: 'sides',
                top: value?.y || currentValue,
                right: value?.x || currentValue,
                bottom: value?.y || currentValue,
                left: value?.x || currentValue
            });
        } else {
            // Switch back to all mode
            const sides = [value?.top, value?.right, value?.bottom, value?.left];
            const definedSides = sides.filter(s => s !== undefined);
            const uniqueSides = Array.from(new Set(definedSides));

            onChange({
                type: 'all',
                value: uniqueSides[0] || undefined
            });
        }
    };

    // Get current values - handle all 3 modes
    const getCurrentValues = () => {
        if (!value) return { all: undefined, x: undefined, y: undefined, top: undefined, right: undefined, bottom: undefined, left: undefined };

        if (value.type === 'all') {
            return {
                all: value.value,
                x: undefined,
                y: undefined,
                top: undefined,
                right: undefined,
                bottom: undefined,
                left: undefined
            };
        } else if (value.type === 'split') {
            return {
                all: undefined,
                x: value.x,
                y: value.y,
                top: undefined,
                right: undefined,
                bottom: undefined,
                left: undefined
            };
        } else if (value.type === 'sides') {
            return {
                all: undefined,
                x: undefined,
                y: undefined,
                top: value.top,
                right: value.right,
                bottom: value.bottom,
                left: value.left
            };
        }

        // Legacy format support (backwards compatibility)
        if (typeof value === 'string') {
            return {
                all: value,
                x: undefined,
                y: undefined,
                top: undefined,
                right: undefined,
                bottom: undefined,
                left: undefined
            };
        }

        return { all: undefined, x: undefined, y: undefined, top: undefined, right: undefined, bottom: undefined, left: undefined };
    };

    const currentValues = getCurrentValues();

    // Handle all sides value change
    const handleAllChange = (newValue: string | undefined) => {
        onChange({
            type: 'all',
            value: newValue
        });
    };

    // Handle x/y split value change
    const handleSplitChange = (axis: 'x' | 'y', newValue: string | undefined) => {
        onChange({
            type: 'split',
            x: axis === 'x' ? newValue : value?.x,
            y: axis === 'y' ? newValue : value?.y
        });
    };

    // Handle individual side value change
    const handleSideChange = (side: 'top' | 'right' | 'bottom' | 'left', newValue: string | undefined) => {
        const currentSides = {
            top: value?.top,
            right: value?.right,
            bottom: value?.bottom,
            left: value?.left
        };

        onChange({
            type: 'sides',
            ...currentSides,
            [side]: newValue
        });
    };

    // Icons for different modes
    const icons = {
        // All sides icon
        all: (
            <svg width="16" height="16" viewBox="0 0 640 640" fill="none">
                <path fill="#32A3E2" d="M344 320c0 13.3-10.7 24-24 24s-24-10.7-24-24 10.7-24 24-24 24 10.7 24 24Z"/>
                <path fill="#1D303A" d="M480 160v320H160V160h320ZM160 96c-35.3 0-64 28.7-64 64v320c0 35.3 28.7 64 64 64h320c35.3 0 64-28.7 64-64V160c0-35.3-28.7-64-64-64H160Z"/>
            </svg>
        ),
        // Side icons for individual sides
        sides: {
            top: (
                <svg width="16" height="16" viewBox="0 0 640 640" fill="none">
                    <path fill="#32A3E2" d="M96 256c0 17.7 14.3 32 32 32s32-14.3 32-32-14.3-32-32-32-32 14.3-32 32Zm0 128c0 17.7 14.3 32 32 32s32-14.3 32-32-14.3-32-32-32-32 14.3-32 32Zm0 128c0 17.7 14.3 32 32 32s32-14.3 32-32-14.3-32-32-32-32 14.3-32 32Zm128 0c0 17.7 14.3 32 32 32s32-14.3 32-32-14.3-32-32-32-32 14.3-32 32Zm128 0c0 17.7 14.3 32 32 32s32-14.3 32-32-14.3-32-32-32-32 14.3-32 32Zm128-256c0 17.7 14.3 32 32 32s32-14.3 32-32-14.3-32-32-32-32 14.3-32 32Zm0 128c0 17.7 14.3 32 32 32s32-14.3 32-32-14.3-32-32-32-32 14.3-32 32Zm0 128c0 17.7 14.3 32 32 32s32-14.3 32-32-14.3-32-32-32-32 14.3-32 32Z"/>
                    <path fill="#1D303A" d="M96 128c0-17.7 14.3-32 32-32h384c17.7 0 32 14.3 32 32s-14.3 32-32 32H128c-17.7 0-32-14.3-32-32Z"/>
                </svg>
            ),
            right: (
                <svg width="16" height="16" viewBox="0 0 640 640" fill="none">
                    <path fill="#32A3E2" d="M96 128c0 17.7 14.3 32 32 32s32-14.3 32-32-14.3-32-32-32-32 14.3-32 32Zm0 128c0 17.7 14.3 32 32 32s32-14.3 32-32-14.3-32-32-32-32 14.3-32 32Zm0 128c0 17.7 14.3 32 32 32s32-14.3 32-32-14.3-32-32-32-32 14.3-32 32Zm0 128c0 17.7 14.3 32 32 32s32-14.3 32-32-14.3-32-32-32-32 14.3-32 32Zm128-384c0 17.7 14.3 32 32 32s32-14.3 32-32-14.3-32-32-32-32 14.3-32 32Zm0 384c0 17.7 14.3 32 32 32s32-14.3 32-32-14.3-32-32-32-32 14.3-32 32Zm128-384c0 17.7 14.3 32 32 32s32-14.3 32-32-14.3-32-32-32-32 14.3-32 32Zm0 384c0 17.7 14.3 32 32 32s32-14.3 32-32-14.3-32-32-32-32 14.3-32 32Z"/>
                    <path fill="#1D303A" d="M512 96c17.7 0 32 14.3 32 32v384c0 17.7-14.3 32-32 32s-32-14.3-32-32V128c0-17.7 14.3-32 32-32Z"/>
                </svg>
            ),
            bottom: (
                <svg width="16" height="16" viewBox="0 0 640 640" fill="none">
                    <path fill="#32A3E2" d="M160 128c0 17.7-14.3 32-32 32s-32-14.3-32-32 14.3-32 32-32 32 14.3 32 32Zm0 128c0 17.7-14.3 32-32 32s-32-14.3-32-32 14.3-32 32-32 32 14.3 32 32Zm0 128c0 17.7-14.3 32-32 32s-32-14.3-32-32 14.3-32 32-32 32 14.3 32 32Zm128-256c0 17.7-14.3 32-32 32s-32-14.3-32-32 14.3-32 32-32 32 14.3 32 32Zm128 0c0 17.7-14.3 32-32 32s-32-14.3-32-32 14.3-32 32-32 32 14.3 32 32Zm128 0c0 17.7-14.3 32-32 32s-32-14.3-32-32 14.3-32 32-32 32 14.3 32 32Zm0 128c0 17.7-14.3 32-32 32s-32-14.3-32-32 14.3-32 32-32 32 14.3 32 32Zm0 128c0 17.7-14.3 32-32 32s-32-14.3-32-32 14.3-32 32-32 32 14.3 32 32Z"/>
                    <path fill="#1D303A" d="M544 512c0 17.7-14.3 32-32 32H128c-17.7 0-32-14.3-32-32s14.3-32 32-32h384c17.7 0 32 14.3 32 32Z"/>
                </svg>
            ),
            left: (
                <svg width="16" height="16" viewBox="0 0 640 640" fill="none">
                    <path fill="#32A3E2" d="M224 128c0 17.7 14.3 32 32 32s32-14.3 32-32-14.3-32-32-32-32 14.3-32 32Zm0 384c0 17.7 14.3 32 32 32s32-14.3 32-32-14.3-32-32-32-32 14.3-32 32Zm128-384c0 17.7 14.3 32 32 32s32-14.3 32-32-14.3-32-32-32-32 14.3-32 32Zm0 384c0 17.7 14.3 32 32 32s32-14.3 32-32-14.3-32-32-32-32 14.3-32 32Zm128-384c0 17.7 14.3 32 32 32s32-14.3 32-32-14.3-32-32-32-32 14.3-32 32Zm0 128c0 17.7 14.3 32 32 32s32-14.3 32-32-14.3-32-32-32-32 14.3-32 32Zm0 128c0 17.7 14.3 32 32 32s32-14.3 32-32-14.3-32-32-32-32 14.3-32 32Zm0 128c0 17.7 14.3 32 32 32s32-14.3 32-32-14.3-32-32-32-32 14.3-32 32Z"/>
                    <path fill="#1D303A" d="M128 544c-17.7 0-32-14.3-32-32V128c0-17.7 14.3-32 32-32s32 14.3 32 32v384c0 17.7-14.3 32-32 32Z"/>
                </svg>
            )
        },
        // Split icons for X/Y axes
        split: {
            x: (
                <svg width="16" height="16" viewBox="0 0 640 640" fill="none">
                    <path fill="#32A3E2" d="M224 128c0 17.7 14.3 32 32 32s32-14.3 32-32-14.3-32-32-32-32 14.3-32 32Zm0 384c0 17.7 14.3 32 32 32s32-14.3 32-32-14.3-32-32-32-32 14.3-32 32Zm128-384c0 17.7 14.3 32 32 32s32-14.3 32-32-14.3-32-32-32-32 14.3-32 32Zm0 384c0 17.7 14.3 32 32 32s32-14.3 32-32-14.3-32-32-32-32 14.3-32 32Z"/>
                    <path fill="#1D303A" d="M128 544c-17.7 0-32-14.3-32-32V128c0-17.7 14.3-32 32-32s32 14.3 32 32v384c0 17.7-14.3 32-32 32ZM512 96c17.7 0 32 14.3 32 32v384c0 17.7-14.3 32-32 32s-32-14.3-32-32V128c0-17.7 14.3-32 32-32Z"/>
                </svg>
            ),
            y: (
                <svg width="16" height="16" viewBox="0 0 640 640" fill="none">
                    <path fill="#32A3E2" d="M160 256c0 17.7-14.3 32-32 32s-32-14.3-32-32 14.3-32 32-32 32 14.3 32 32Zm0 128c0 17.7-14.3 32-32 32s-32-14.3-32-32 14.3-32 32-32 32 14.3 32 32Zm384-128c0 17.7-14.3 32-32 32s-32-14.3-32-32 14.3-32 32-32 32 14.3 32 32Zm0 128c0 17.7-14.3 32-32 32s-32-14.3-32-32 14.3-32 32-32 32 14.3 32 32Z"/>
                    <path fill="#1D303A" d="M544 512c0 17.7-14.3 32-32 32H128c-17.7 0-32-14.3-32-32s14.3-32 32-32h384c17.7 0 32 14.3 32 32ZM96 128c0-17.7 14.3-32 32-32h384c17.7 0 32 14.3 32 32s-14.3 32-32 32H128c-17.7 0-32-14.3-32-32Z"/>
                </svg>
            )
        }
    };

    // Toggle icon for button
    const toggleIcon = (
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" width="16" height="16">
            <path fill="#1d303a" d="M64 224c0 17.7 14.3 32 32 32h293.5c-3.5-10-5.5-20.8-5.5-32s1.9-22 5.5-32H96c-17.7 0-32 14.3-32 32zm186.5 160c3.5 10 5.5 20.8 5.5 32s-1.9 22-5.5 32H544c17.7 0 32-14.3 32-32s-14.3-32-32-32H250.5z"/>
            <path fill="#32a3e2" d="M480 256c-17.7 0-32-14.3-32-32s14.3-32 32-32 32 14.3 32 32-14.3 32-32 32zm0-128c-53 0-96 43-96 96s43 96 96 96 96-43 96-96-43-96-96-96zM160 448c-17.7 0-32-14.3-32-32s14.3-32 32-32 32 14.3 32 32-14.3 32-32 32zm0-128c-53 0-96 43-96 96s43 96 96 96 96-43 96-96-43-96-96-96z"/>
        </svg>
    );

    return (
        <div style={{ marginBottom: '8px' }}>
            {/* Header with label and toggle button */}
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
                    {dimensionType === 'padding' ? 'Padding' : 'Margin'}
                </label>
                <Button
                    size="small"
                    variant="tertiary"
                    onClick={toggleMode}
                    style={{ minWidth: 'auto', padding: '6px', background: 'transparent' }}
                >
                    {toggleIcon}
                </Button>
            </div>

            {currentMode === 'all' ? (
                // All sides control
                <div style={{
                    display: 'flex',
                    alignItems: 'center',
                    gap: '8px'
                }}>
                    <div style={{
                        width: '20px',
                        height: '20px',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                        color: '#757575',
                        flexShrink: 0
                    }}>
                        {icons.all}
                    </div>
                    <div style={{ flex: 1 }}>
                        {createSpacingControl(
                            spacingSizes,
                            dimensionType as any,
                            currentValues.all,
                            handleAllChange,
                            true
                        )}
                    </div>
                </div>
            ) : currentMode === 'split' ? (
                // X/Y split controls
                <div style={{ display: 'grid', gap: '12px' }}>
                    {(['x', 'y'] as const).map((axis) => (
                        <div key={axis} style={{
                            display: 'flex',
                            alignItems: 'center',
                            gap: '8px'
                        }}>
                            <div style={{
                                width: '20px',
                                height: '20px',
                                display: 'flex',
                                alignItems: 'center',
                                justifyContent: 'center',
                                color: '#757575',
                                flexShrink: 0
                            }}>
                                {icons.split[axis]}
                            </div>
                            <div style={{ flex: 1 }}>
                                {createSpacingControl(
                                    spacingSizes,
                                    dimensionType as any,
                                    currentValues[axis],
                                    (newValue) => handleSplitChange(axis, newValue),
                                    true
                                )}
                            </div>
                        </div>
                    ))}
                </div>
            ) : (
                // Individual sides controls
                <div style={{ display: 'grid', gap: '12px' }}>
                    {(['top', 'right', 'bottom', 'left'] as const).map((side) => (
                        <div key={side} style={{
                            display: 'flex',
                            alignItems: 'center',
                            gap: '8px'
                        }}>
                            <div style={{
                                width: '20px',
                                height: '20px',
                                display: 'flex',
                                alignItems: 'center',
                                justifyContent: 'center',
                                color: '#757575',
                                flexShrink: 0
                            }}>
                                {icons.sides[side]}
                            </div>
                            <div style={{ flex: 1 }}>
                                {createSpacingControl(
                                    spacingSizes,
                                    dimensionType as any,
                                    currentValues[side],
                                    (newValue) => handleSideChange(side, newValue),
                                    true
                                )}
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}

/**
 * Create a spacing control for gap (simple slider)
 */
function createSpacingControl(
    spacingSizes: any[],
    dimensionType: 'gap' | 'padding' | 'margin',
    value: string | undefined,
    onChange: (value: string | undefined) => void,
    hideLabel: boolean = false
) {
    const currentIndex = getSpacingIndexByValue(spacingSizes, value || '');
    const maxIndex = spacingSizes.length - 1;

    // Convert to slider index (0 = default, 1 = none, 2+ = spacing sizes)
    let sliderValue = 0;
    if (value === undefined) {
        sliderValue = 0; // Default
    } else if (value === '0') {
        sliderValue = 1; // None
    } else if (currentIndex >= 0) {
        sliderValue = currentIndex + 2; // Spacing size
    } else {
        sliderValue = 0; // Fallback to default
    }

    const updateValue = (index: number | undefined) => {
        if (index === undefined || index === 0) {
            onChange(undefined); // Default
        } else if (index === 1) {
            onChange('0'); // None
        } else if (index !== null && index !== undefined) {
            const spacingIndex = index - 2;
            const spacing = spacingSizes[spacingIndex];
            if (spacing) {
                onChange(spacing.slug);
            } else {
                onChange(undefined);
            }
        }
    };

    const dimensionLabels = {
        gap: 'Gap',
        padding: 'Padding',
        margin: 'Margin'
    };

    // Gap icon for consistency with padding controls
    const gapIcon = dimensionType === 'gap' ? (
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 640 640" width="16" height="16">
            <path fill="#32A3E2" d="M32 192v256c0 17.7 14.3 32 32 32s32-14.3 32-32V192c0-17.7-14.3-32-32-32s-32 14.3-32 32Zm512 0v256c0 17.7 14.3 32 32 32s32-14.3 32-32V192c0-17.7-14.3-32-32-32s-32 14.3-32 32Z"/>
            <path fill="#1D303A" d="m422.6 406.6 64-64c12.5-12.5 12.5-32.8 0-45.3l-64-64c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3l9.4 9.4H253.2l9.4-9.4c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0l-64 64c-6 6-9.4 14.1-9.4 22.6 0 8.5 3.4 16.6 9.4 22.6l64 64c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3l-9.4-9.4h133.5l-9.4 9.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0v.1Z"/>
        </svg>
    ) : null;

    return (
        <div style={{ marginBottom: hideLabel ? '0' : '8px' }}>
            {!hideLabel && (
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
                        {dimensionLabels[dimensionType]}
                    </label>
                    <span style={{
                        fontSize: '13px',
                        fontWeight: '500',
                        color: '#757575'
                    }}>
                        {getSpacingDisplayName(spacingSizes, value || '')}
                    </span>
                </div>
            )}
            
            {/* Gap control with icon layout matching padding controls */}
            {dimensionType === 'gap' ? (
                <div style={{
                    display: 'flex',
                    alignItems: 'center',
                    gap: '8px'
                }}>
                    <div style={{
                        width: '20px',
                        height: '20px',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                        color: '#757575',
                        flexShrink: 0
                    }}>
                        {gapIcon}
                    </div>
                    <div style={{ flex: 1 }}>
                        <RangeControl
                            value={sliderValue}
                            onChange={updateValue}
                            min={0}
                            max={maxIndex + 2}
                            step={1}
                            marks={true}
                            withInputField={false}
                            renderTooltipContent={(index) => {
                                if (!index || index === 0) return 'Default';
                                if (index === 1) return 'None';
                                const spacingIndex = index - 2;
                                if (spacingIndex >= 0 && spacingIndex < spacingSizes.length) {
                                    const spacing = spacingSizes[spacingIndex];
                                    return spacing ? spacing.name : 'None';
                                }
                                return 'None';
                            }}
                            __next40pxDefaultSize={true}
                            __nextHasNoMarginBottom={true}
                        />
                    </div>
                </div>
            ) : (
                <RangeControl
                    value={sliderValue}
                    onChange={updateValue}
                    min={0}
                    max={maxIndex + 2}
                    step={1}
                    marks={true}
                    withInputField={false}
                    renderTooltipContent={(index) => {
                        if (!index || index === 0) return 'Default';
                        if (index === 1) return 'None';
                        const spacingIndex = index - 2;
                        if (spacingIndex >= 0 && spacingIndex < spacingSizes.length) {
                            const spacing = spacingSizes[spacingIndex];
                            return spacing ? spacing.name : 'None';
                        }
                        return 'None';
                    }}
                    __next40pxDefaultSize={true}
                    __nextHasNoMarginBottom={true}
                />
            )}
        </div>
    );
}

/**
 * Generate CSS classes for gap values
 */
export function getGapClasses(gap: ResponsiveValue<string>): string {
    return getResponsiveClasses(gap, 'gap', (value: string) => {
        if (value === '0') return '0';
        return value;
    });
}

/**
 * Generate CSS classes for padding values (BoxControl format)
 */
export function getPaddingClasses(padding: ResponsiveValue<any>): string {
    // Convert box control values to classes
    const classes: string[] = [];

    Object.entries(padding || {}).forEach(([breakpoint, boxValue]) => {
        if (!boxValue || typeof boxValue !== 'object') return;

        const prefix = breakpoint === 'base' ? '' : `${breakpoint}:`;

        // Handle all sides the same (when using single value)
        if (typeof boxValue === 'string') {
            if (boxValue === '0') {
                classes.push(`${prefix}p-0`);
            } else {
                classes.push(`${prefix}p-${boxValue}`);
            }
        }
        // Handle individual sides
        else if (typeof boxValue === 'object') {
            if (boxValue.top) classes.push(`${prefix}pt-${boxValue.top === '0' ? '0' : boxValue.top}`);
            if (boxValue.right) classes.push(`${prefix}pr-${boxValue.right === '0' ? '0' : boxValue.right}`);
            if (boxValue.bottom) classes.push(`${prefix}pb-${boxValue.bottom === '0' ? '0' : boxValue.bottom}`);
            if (boxValue.left) classes.push(`${prefix}pl-${boxValue.left === '0' ? '0' : boxValue.left}`);
        }
    });

    return classes.join(' ');
}

/**
 * Generate CSS classes for margin values (BoxControl format)
 */
export function getMarginClasses(margin: ResponsiveValue<any>): string {
    // Convert box control values to classes
    const classes: string[] = [];

    Object.entries(margin || {}).forEach(([breakpoint, boxValue]) => {
        if (!boxValue || typeof boxValue !== 'object') return;

        const prefix = breakpoint === 'base' ? '' : `${breakpoint}:`;

        // Handle all sides the same (when using single value)
        if (typeof boxValue === 'string') {
            if (boxValue === '0') {
                classes.push(`${prefix}m-0`);
            } else {
                classes.push(`${prefix}m-${boxValue}`);
            }
        }
        // Handle individual sides
        else if (typeof boxValue === 'object') {
            if (boxValue.top) classes.push(`${prefix}mt-${boxValue.top === '0' ? '0' : boxValue.top}`);
            if (boxValue.right) classes.push(`${prefix}mr-${boxValue.right === '0' ? '0' : boxValue.right}`);
            if (boxValue.bottom) classes.push(`${prefix}mb-${boxValue.bottom === '0' ? '0' : boxValue.bottom}`);
            if (boxValue.left) classes.push(`${prefix}ml-${boxValue.left === '0' ? '0' : boxValue.left}`);
        }
    });

    return classes.join(' ');
}

/**
 * Dimensions Control Component
 */
export default function DimensionsControl({
    gap,
    padding,
    margin,
    onGapChange,
    onPaddingChange,
    onMarginChange,
    blockName
}: DimensionsControlProps) {
    // Get configuration from our dimensions config system
    const config = getBlockDimensionsConfig(blockName);
    const spacingSizes = (config as any).spacings;
    const { dimensions } = config as any;
    const breakpoints = getBreakpointOptions(blockName);

    // Don't render until we have spacing sizes and breakpoints
    if (!spacingSizes || !Array.isArray(spacingSizes) || !breakpoints) {
        return <div>Loading dimensions settings...</div>;
    }

    // All available breakpoints (base + configured breakpoints)
    const allBreakpoints: (Breakpoint | null)[] = [
        null, // base
        ...breakpoints
    ];

    // Helper to update a specific dimension's responsive value
    const updateDimensionValue = (
        dimensionType: 'gap' | 'padding' | 'margin',
        breakpointSlug: string,
        value: string | undefined
    ) => {
        const callbacks = {
            gap: onGapChange,
            padding: onPaddingChange,
            margin: onMarginChange
        };

        const currentValues = {
            gap: gap || {},
            padding: padding || {},
            margin: margin || {}
        };

        const callback = callbacks[dimensionType];
        if (!callback) return;

        const currentValue = currentValues[dimensionType];
        const newValue = { ...currentValue, [breakpointSlug]: value };

        // Clean up undefined values
        Object.keys(newValue).forEach(key => {
            if (newValue[key as keyof ResponsiveValue] === undefined) {
                delete newValue[key as keyof ResponsiveValue];
            }
        });

        callback(newValue);
    };

    // Reset all dimensions for all breakpoints
    const resetAllDimensions = () => {
        if (dimensions.gap && onGapChange) {
            onGapChange({});
        }
        if (dimensions.padding && onPaddingChange) {
            onPaddingChange({});
        }
        if (dimensions.margin && onMarginChange) {
            onMarginChange({});
        }
    };


    // Breakpoint icons for tabs
    const breakpointIcons = {
        base: (
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 640 640" width="16" height="16">
                <path fill="#1D303A" d="M32 339.2c0 42.4 34.4 76.8 76.8 76.8H304v-96H160V128h288v48h64v-48c0-35.3-28.7-64-64-64H160c-35.3 0-64 28.7-64 64v192H51.2c-10.6 0-19.2 8.6-19.2 19.2Z"/>
                <path fill="#32A3E2" d="M416 224c-35.3 0-64 28.7-64 64v224c0 35.3 28.7 64 64 64h96c35.3 0 64-28.7 64-64V288c0-35.3-28.7-64-64-64h-96Zm24 240h48c13.3 0 24 10.7 24 24s-10.7 24-24 24h-48c-13.3 0-24-10.7-24-24s10.7-24 24-24Z"/>
            </svg>
        ),
        sm: (
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 640 640" width="16" height="16">
                <path fill="#1D303A" d="M96 128c0-35.3 28.7-64 64-64h320c35.3 0 64 28.7 64 64v384c0 35.3-28.7 64-64 64H160c-35.3 0-64-28.7-64-64V128Zm64 24v256c0 13.3 10.7 24 24 24h272c13.3 0 24-10.7 24-24V152c0-13.3-10.7-24-24-24H184c-13.3 0-24 10.7-24 24Zm96 352c0 13.3 10.7 24 24 24h80c13.3 0 24-10.7 24-24s-10.7-24-24-24h-80c-13.3 0-24 10.7-24 24Z"/>
                <path fill="#32A3E2" d="M160 152c0-13.3 10.7-24 24-24h272c13.3 0 24 10.7 24 24v256c0 13.3-10.7 24-24 24H184c-13.3 0-24-10.7-24-24V152Z"/>
            </svg>
        ),
        md: (
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 640 640" width="16" height="16">
                <path fill="#1D303A" d="M0 467.2C0 509.6 34.4 544 76.8 544h486.4c42.4 0 76.8-34.4 76.8-76.8 0-10.6-8.6-19.2-19.2-19.2H19.2C8.6 448 0 456.6 0 467.2ZM64 160v240h64V160h384v240h64V160c0-35.3-28.7-64-64-64H128c-35.3 0-64 28.7-64 64Z"/>
                <path fill="#32A3E2" d="M128 160h384v240H128V160Z"/>
            </svg>
        ),
        lg: (
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 640 640" width="16" height="16">
                <path fill="#1D303A" d="M32 160v224c0 35.3 28.7 64 64 64h272v-64H96V160h272v-16c0-17.5 4.7-33.9 12.8-48H96c-35.3 0-64 28.7-64 64Zm96 360c0 13.3 10.7 24 24 24h228.8c-8.2-14.1-12.8-30.5-12.8-48H152c-13.3 0-24 10.7-24 24Zm288-376v352c0 26.5 21.5 48 48 48h96c26.5 0 48-21.5 48-48V144c0-26.5-21.5-48-48-48h-96c-26.5 0-48 21.5-48 48Zm48 40c0-13.3 10.7-24 24-24h48c13.3 0 24 10.7 24 24s-10.7 24-24 24h-48c-13.3 0-24-10.7-24-24Zm0 96c0-13.3 10.7-24 24-24h48c13.3 0 24 10.7 24 24s-10.7 24-24 24h-48c-13.3 0-24-10.7-24-24Zm80 120c0 17.7-14.3 32-32 32s-32-14.3-32-32 14.3-32 32-32 32 14.3 32 32Z"/>
                <path fill="#32A3E2" d="M368 160H96v224h272V160Zm144 272c17.7 0 32-14.3 32-32s-14.3-32-32-32-32 14.3-32 32 14.3 32 32 32Z"/>
            </svg>
        ),
        xl: (
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 640 640" width="16" height="16">
                <path fill="#1D303A" d="M32 160c0-35.3 28.7-64 64-64h448c35.3 0 64 28.7 64 64v240c0 35.3-28.7 64-64 64H96c-35.3 0-64-28.7-64-64V160Zm64 0v240h448V160H96Zm64 384c0-17.7 14.3-32 32-32h256c17.7 0 32 14.3 32 32s-14.3 32-32 32H192c-17.7 0-32-14.3-32-32Z"/>
                <path fill="#32A3E2" d="M96 160h448v240H96V160Z"/>
            </svg>
        )
    };


    return (
        <ToolsPanel
            label={__('Dimensions', 'orbitools')}
            resetAll={resetAllDimensions}
            panelId="main-dimensions-panel"
        >
            {allBreakpoints.map((breakpoint, index) => {
                const breakpointSlug = breakpoint?.slug || 'base';
                const icon = breakpointIcons[breakpointSlug as keyof typeof breakpointIcons] || breakpointIcons.base;
                const label = breakpoint ? breakpoint.name : __('Base', 'orbitools');
                
                return (
                    <ToolsPanelItem
                        key={breakpointSlug}
                        hasValue={() => {
                            // Has value if any dimension is set for this breakpoint
                            return (gap?.[breakpointSlug as keyof ResponsiveValue] !== undefined) ||
                                   (padding?.[breakpointSlug as keyof ResponsiveValue] !== undefined) ||
                                   (margin?.[breakpointSlug as keyof ResponsiveValue] !== undefined);
                        }}
                        label={label}
                        onDeselect={() => {
                            // Reset all dimensions for this breakpoint
                            if (dimensions.gap && onGapChange) {
                                updateDimensionValue('gap', breakpointSlug, undefined);
                            }
                            if (dimensions.padding && onPaddingChange) {
                                updateDimensionValue('padding', breakpointSlug, undefined);
                            }
                            if (dimensions.margin && onMarginChange) {
                                updateDimensionValue('margin', breakpointSlug, undefined);
                            }
                        }}
                        isShownByDefault={index === 0} // Only show base by default
                        panelId="main-dimensions-panel"
                    >
                        {/* Nested ToolsPanel for this breakpoint's dimensions */}
                        <div style={{ 
                            marginLeft: 'calc(16px * -1)',
                            marginRight: 'calc(16px * -1)',
                            maxWidth: 'none'
                        }}>
                            {/* Only show icon/label header for non-base breakpoints */}
                            {breakpointSlug !== 'base' && (
                                <div style={{ 
                                    display: 'flex', 
                                    alignItems: 'center', 
                                    gap: '8px', 
                                    paddingBottom: '8px',
                                    borderBottom: '1px solid #e0e0e0',
                                    marginLeft: '16px',
                                    marginRight: '16px'
                                }}>
                                    <div style={{ width: '16px', height: '16px', flexShrink: 0 }}>
                                        {icon}
                                    </div>
                                    <span style={{ 
                                        fontSize: '12px', 
                                        fontWeight: '500', 
                                        color: '#1e1e1e' 
                                    }}>
                                        {label}
                                    </span>
                                </div>
                            )}
                            <ToolsPanel
                                label={__('Controls', 'orbitools')}
                                resetAll={() => {
                                // Reset all dimensions for this breakpoint
                                if (dimensions.gap && onGapChange) {
                                    updateDimensionValue('gap', breakpointSlug, undefined);
                                }
                                if (dimensions.padding && onPaddingChange) {
                                    updateDimensionValue('padding', breakpointSlug, undefined);
                                }
                                if (dimensions.margin && onMarginChange) {
                                    updateDimensionValue('margin', breakpointSlug, undefined);
                                }
                            }}
                            panelId={`${breakpointSlug}-dimensions-panel`}
                        >
                            {/* Gap Control */}
                            {dimensions.gap && onGapChange && (
                                <ToolsPanelItem
                                    hasValue={() => gap?.[breakpointSlug as keyof ResponsiveValue] !== undefined}
                                    label={__('Gap', 'orbitools')}
                                    onDeselect={() => updateDimensionValue('gap', breakpointSlug, undefined)}
                                    isShownByDefault={breakpointSlug === 'base'}
                                    panelId={`${breakpointSlug}-dimensions-panel`}
                                >
                                    {createSpacingControl(
                                        spacingSizes,
                                        'gap',
                                        gap?.[breakpointSlug as keyof ResponsiveValue],
                                        (value) => updateDimensionValue('gap', breakpointSlug, value)
                                    )}
                                </ToolsPanelItem>
                            )}

                            {/* Padding Control */}
                            {dimensions.padding && onPaddingChange && (
                                <ToolsPanelItem
                                    hasValue={() => padding?.[breakpointSlug as keyof ResponsiveValue] !== undefined}
                                    label={__('Padding', 'orbitools')}
                                    onDeselect={() => updateDimensionValue('padding', breakpointSlug, undefined)}
                                    isShownByDefault={false}
                                    panelId={`${breakpointSlug}-dimensions-panel`}
                                >
                                    {createBoxControl(
                                        spacingSizes,
                                        'padding',
                                        padding?.[breakpointSlug as keyof ResponsiveValue] || {},
                                        (value) => updateDimensionValue('padding', breakpointSlug, value)
                                    )}
                                </ToolsPanelItem>
                            )}

                            {/* Margin Control */}
                            {dimensions.margin && onMarginChange && (
                                <ToolsPanelItem
                                    hasValue={() => margin?.[breakpointSlug as keyof ResponsiveValue] !== undefined}
                                    label={__('Margin', 'orbitools')}
                                    onDeselect={() => updateDimensionValue('margin', breakpointSlug, undefined)}
                                    isShownByDefault={false}
                                    panelId={`${breakpointSlug}-dimensions-panel`}
                                >
                                    {createBoxControl(
                                        spacingSizes,
                                        'margin',
                                        margin?.[breakpointSlug as keyof ResponsiveValue] || {},
                                        (value) => updateDimensionValue('margin', breakpointSlug, value)
                                    )}
                                </ToolsPanelItem>
                            )}
                        </ToolsPanel>
                        </div>
                    </ToolsPanelItem>
                );
            })}
        </ToolsPanel>
    );
}
