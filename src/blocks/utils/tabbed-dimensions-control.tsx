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
import { Fragment, useState } from '@wordpress/element';
import { RangeControl, Button } from '@wordpress/components';
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
 * Create a spacing control for a specific dimension and breakpoint
 */
function createSpacingControl(
    spacingSizes: any[], 
    dimensionType: 'gap' | 'padding' | 'margin',
    value: string | undefined,
    onChange: (value: string | undefined) => void
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
        } else {
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

    return (
        <div style={{ marginBottom: '16px' }}>
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
            <RangeControl
                value={sliderValue}
                onChange={updateValue}
                min={0}
                max={maxIndex + 2}
                step={1}
                marks={true}
                withInputField={false}
                renderTooltipContent={(index) => {
                    if (index === 0) return 'Default';
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
 * Generate CSS classes for padding values
 */
export function getPaddingClasses(padding: ResponsiveValue<string>): string {
    return getResponsiveClasses(padding, 'p', (value: string) => {
        if (value === '0') return '0';
        return value;
    });
}

/**
 * Generate CSS classes for margin values
 */
export function getMarginClasses(margin: ResponsiveValue<string>): string {
    return getResponsiveClasses(margin, 'm', (value: string) => {
        if (value === '0') return '0';
        return value;
    });
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
    const spacingSizes = config.spacings;
    const { dimensions } = config;
    const breakpoints = getBreakpointOptions(blockName);
    
    // Don't render until we have spacing sizes and breakpoints
    if (!spacingSizes || !Array.isArray(spacingSizes) || !breakpoints) {
        return <div>Loading dimensions settings...</div>;
    }

    // State to manage which accordions are open
    const [openAccordions, setOpenAccordions] = useState<Record<string, boolean>>({
        base: true // Base is open by default
    });

    // All available breakpoints (base + configured breakpoints)
    const allBreakpoints: (Breakpoint | null)[] = [
        null, // base
        ...breakpoints
    ];

    // Toggle accordion open/closed state
    const toggleAccordion = (breakpointSlug: string) => {
        setOpenAccordions(prev => ({
            ...prev,
            [breakpointSlug]: !prev[breakpointSlug]
        }));
    };

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

    return (
        <ToolsPanel
            label={__('Dimensions', 'orbitools')}
            resetAll={resetAllDimensions}
            panelId="dimensions-panel"
        >
            {allBreakpoints.map(breakpoint => {
                const breakpointSlug = breakpoint?.slug || 'base';
                const breakpointLabel = breakpoint ? breakpoint.name : __('Base (All Screens)', 'orbitools');
                
                // Check if this breakpoint has any values
                const hasAnyValue = () => {
                    const gapValue = gap?.[breakpointSlug as keyof ResponsiveValue];
                    const paddingValue = padding?.[breakpointSlug as keyof ResponsiveValue];
                    const marginValue = margin?.[breakpointSlug as keyof ResponsiveValue];
                    return gapValue !== undefined || paddingValue !== undefined || marginValue !== undefined;
                };

                // Reset all dimensions for this breakpoint
                const resetBreakpoint = () => {
                    if (dimensions.gap && onGapChange) {
                        updateDimensionValue('gap', breakpointSlug, undefined);
                    }
                    if (dimensions.padding && onPaddingChange) {
                        updateDimensionValue('padding', breakpointSlug, undefined);
                    }
                    if (dimensions.margin && onMarginChange) {
                        updateDimensionValue('margin', breakpointSlug, undefined);
                    }
                };

                return (
                    <ToolsPanelItem
                        key={breakpointSlug}
                        hasValue={hasAnyValue}
                        label={breakpointLabel}
                        onDeselect={resetBreakpoint}
                        isShownByDefault={breakpointSlug === 'base'}
                        panelId="dimensions-panel"
                    >
                        <div>
                            {/* Accordion Header */}
                            {breakpoint && (
                                <Button
                                    variant="tertiary"
                                    onClick={() => toggleAccordion(breakpointSlug)}
                                    style={{
                                        display: 'flex',
                                        alignItems: 'center',
                                        justifyContent: 'space-between',
                                        width: '100%',
                                        padding: '8px 0',
                                        marginBottom: '12px',
                                        border: 'none',
                                        background: 'none',
                                        cursor: 'pointer',
                                        gap: '8px'
                                    }}
                                    aria-expanded={openAccordions[breakpointSlug]}
                                >
                                    <div style={{
                                        display: 'flex',
                                        alignItems: 'center',
                                        gap: '8px'
                                    }}>
                                        <span style={{
                                            fontSize: '10px',
                                            fontWeight: '500',
                                            textTransform: 'uppercase',
                                            color: '#fff',
                                            backgroundColor: '#757575',
                                            padding: '2px 6px',
                                            borderRadius: '3px',
                                            lineHeight: '1.2'
                                        }}>
                                            {breakpoint.slug}
                                        </span>
                                        <span style={{
                                            fontSize: '12px',
                                            fontWeight: '500',
                                            color: '#1e1e1e'
                                        }}>
                                            {breakpoint.name}
                                        </span>
                                    </div>
                                    <span style={{
                                        fontSize: '12px',
                                        transform: openAccordions[breakpointSlug] ? 'rotate(180deg)' : 'rotate(0deg)',
                                        transition: 'transform 0.2s ease'
                                    }}>
                                        ▼
                                    </span>
                                </Button>
                            )}

                            {/* Accordion Content */}
                            {(!breakpoint || openAccordions[breakpointSlug]) && (
                                <div style={{
                                    paddingLeft: breakpoint ? '8px' : '0'
                                }}>
                                    {/* Gap Control */}
                                    {dimensions.gap && onGapChange && (
                                        createSpacingControl(
                                            spacingSizes,
                                            'gap',
                                            gap?.[breakpointSlug as keyof ResponsiveValue],
                                            (value) => updateDimensionValue('gap', breakpointSlug, value)
                                        )
                                    )}

                                    {/* Padding Control */}
                                    {dimensions.padding && onPaddingChange && (
                                        createSpacingControl(
                                            spacingSizes,
                                            'padding',
                                            padding?.[breakpointSlug as keyof ResponsiveValue],
                                            (value) => updateDimensionValue('padding', breakpointSlug, value)
                                        )
                                    )}

                                    {/* Margin Control */}
                                    {dimensions.margin && onMarginChange && (
                                        createSpacingControl(
                                            spacingSizes,
                                            'margin',
                                            margin?.[breakpointSlug as keyof ResponsiveValue],
                                            (value) => updateDimensionValue('margin', breakpointSlug, value)
                                        )
                                    )}
                                </div>
                            )}
                        </div>
                    </ToolsPanelItem>
                );
            })}
        </ToolsPanel>
    );
}