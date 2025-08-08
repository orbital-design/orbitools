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
import { RangeControl, Button, TabPanel, Panel, PanelBody, BoxControl } from '@wordpress/components';
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
 * Create a box control for padding/margin using our spacing presets
 */
function createBoxControl(
    spacingSizes: any[],
    dimensionType: 'padding' | 'margin',
    value: any,
    onChange: (value: any) => void
) {
    // WordPress BoxControl expects actual CSS values, not slugs
    // We need to convert slug values to CSS values for display
    const normalizeValue = (val: any) => {
        if (!val) return val;
        // If it's a slug, find the corresponding CSS value
        if (typeof val === 'string' && !val.includes('px') && !val.includes('rem') && !val.includes('em')) {
            const spacing = spacingSizes.find((size: any) => size.slug === val);
            return spacing ? spacing.size : val;
        }
        return val;
    };

    // Convert values to CSS for BoxControl
    const normalizedValues = value ? {
        top: normalizeValue(value.top),
        right: normalizeValue(value.right),
        bottom: normalizeValue(value.bottom),
        left: normalizeValue(value.left)
    } : {};

    // Convert CSS values back to slugs on change
    const handleChange = (newValues: any) => {
        if (!newValues) {
            onChange(undefined);
            return;
        }

        const convertToSlug = (cssValue: any) => {
            if (!cssValue) return cssValue;
            // Find matching spacing preset by CSS value
            const spacing = spacingSizes.find((size: any) => size.size === cssValue);
            return spacing ? spacing.slug : cssValue;
        };

        const slugValues = {
            top: convertToSlug(newValues.top),
            right: convertToSlug(newValues.right),
            bottom: convertToSlug(newValues.bottom),
            left: convertToSlug(newValues.left)
        };

        onChange(slugValues);
    };

    // Convert spacing sizes to proper presets format
    const presets = spacingSizes.map((size: any) => ({
        name: size.name,
        slug: size.slug,
        value: size.size
    }));

    return (
        <BoxControl
            __next40pxDefaultSize
            id="base_padding"
            label={'Padding'}
            values={normalizedValues}
            onChange={handleChange}
            allowReset={false}
            presetKey={'padding'}
            inputProps={[]}
            units={[]}
            presets={spacingSizes}
        />
    );
}

/**
 * Create a spacing control for gap (simple slider)
 */
function createSpacingControl(
    spacingSizes: any[],
    dimensionType: 'gap',
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

    // Create ToolsPanel content for a specific breakpoint
    const createBreakpointToolsPanel = (breakpointSlug: string, breakpoint: Breakpoint | null) => {

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
            <div>
                {/* Breakpoint Label */}
                {breakpoint && (
                    <div style={{
                        display: 'flex',
                        alignItems: 'center',
                        marginBottom: '16px',
                        paddingBottom: '8px',
                        borderBottom: '1px solid #e0e0e0',
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
                )}

                <ToolsPanel
                    label={__('Controls', 'orbitools')}
                    resetAll={resetBreakpoint}
                    panelId={`dimensions-${breakpointSlug}-panel`}
                >
                {/* Gap Control */}
                {dimensions.gap && onGapChange && (
                    <ToolsPanelItem
                        hasValue={() => gap?.[breakpointSlug as keyof ResponsiveValue] !== undefined}
                        label={__('Gap', 'orbitools')}
                        onDeselect={() => updateDimensionValue('gap', breakpointSlug, undefined)}
                        isShownByDefault={true}
                        panelId={`dimensions-${breakpointSlug}-panel`}
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
                        panelId={`dimensions-${breakpointSlug}-panel`}
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
                        panelId={`dimensions-${breakpointSlug}-panel`}
                    >
                        {/* {createBoxControl(
                            spacingSizes,
                            'margin',
                            margin?.[breakpointSlug as keyof ResponsiveValue] || {},
                            (value) => updateDimensionValue('margin', breakpointSlug, value)
                        )} */}
                    </ToolsPanelItem>
                )}
                </ToolsPanel>
            </div>
        );
    };

    // Create tab data for TabPanel
    const tabs = allBreakpoints.map(breakpoint => {
        const breakpointSlug = breakpoint?.slug || 'base';
        const tabTitle = breakpoint ? breakpoint.slug.toUpperCase() : __('Base', 'orbitools');

        return {
            name: breakpointSlug,
            title: tabTitle,
            className: `dimensions-tab-${breakpointSlug}`
        };
    });

    return (
        <Panel>
            <PanelBody
                title={__('Dimensions', 'orbitools')}
                initialOpen={true}
            >
                <TabPanel
                    className="dimensions-tab-panel"
                    tabs={tabs}
                    initialTabName="base"
                >
                    {(tab) => {
                        const breakpoint = allBreakpoints.find(bp =>
                            (bp?.slug || 'base') === tab.name
                        );
                        return createBreakpointToolsPanel(tab.name, breakpoint || null);
                    }}
                </TabPanel>

                {/* Reset All Button */}
                <div style={{
                    marginTop: '16px',
                    paddingTop: '12px',
                    borderTop: '1px solid #e0e0e0',
                    textAlign: 'right'
                }}>
                    <Button
                        variant="secondary"
                        size="small"
                        onClick={resetAllDimensions}
                    >
                        {__('Reset All Dimensions', 'orbitools')}
                    </Button>
                </div>
            </PanelBody>
        </Panel>
    );
}
