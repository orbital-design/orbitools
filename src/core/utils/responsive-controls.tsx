/**
 * Responsive Controls Base Component
 *
 * Provides a reusable system for creating responsive controls that work across different breakpoints.
 * Uses ToolsPanel with breakpoint-specific controls that can be toggled on/off.
 *
 * @file blocks/utils/responsive-controls.tsx
 * @since 1.0.0
 */

import { __ } from '@wordpress/i18n';
import {
    __experimentalDivider as Divider,
    __experimentalToolsPanel as ToolsPanel,
    __experimentalToolsPanelItem as ToolsPanelItem,
    __experimentalVStack as VStack,
    __experimentalView as View,
    Icon,
    SVG,
    Path
} from '@wordpress/components';

import { getBreakpointOptions } from './breakpoints';

// Re-export Breakpoint type from spacings-config
export interface Breakpoint {
    name: string;
    slug: string;
    value: string;
}

/**
 * Responsive attribute structure
 * Each breakpoint can have its own value, falls back to base value
 */
export interface ResponsiveValue<T = string> {
    base?: T;
    sm?: T;
    md?: T;
    lg?: T;
    xl?: T;
}

/**
 * Control renderer function type
 */
export type ControlRenderer<T = any> = (params: {
    value: T | undefined;
    onChange: (value: T | undefined) => void;
    breakpoint: Breakpoint | null; // null for base
    isDefault: boolean;
}) => React.ReactNode;

export interface ResponsiveControlConfig<T = any> {
    /** Unique identifier for this control */
    id: string;
    /** Display label */
    label: string;
    /** Whether this control is shown by default */
    isShownByDefault?: boolean;
    /** Function to check if control has a value */
    hasValue: (responsiveValue: ResponsiveValue<T>) => boolean;
    /** Function to reset control value */
    onDeselect: (responsiveValue: ResponsiveValue<T>) => ResponsiveValue<T>;
    /** Function to render the control for each breakpoint */
    renderControl: ControlRenderer<T>;
}

export interface ResponsiveControlsProps {
    /** Panel label */
    label: string;
    /** Control configurations */
    controls: ResponsiveControlConfig[];
    /** Current responsive values */
    values: Record<string, ResponsiveValue>;
    /** Callback when values change */
    onValuesChange: (values: Record<string, ResponsiveValue>) => void;
    /** Reset all values */
    resetAll: () => void;
    /** Panel ID for internal use */
    panelId: string;
    /** Block name for configuration lookup */
    blockName: string;
}

/**
 * Breakpoint icon components
 */
const BreakpointIconComponents = {
    base: ({ size = 20 }) => (
        <SVG width={size} height={size} viewBox="0 0 640 640" fill="none">
            <Path fill="#1D303A" d="M32 339.2c0 42.4 34.4 76.8 76.8 76.8H304v-96H160V128h288v48h64v-48c0-35.3-28.7-64-64-64H160c-35.3 0-64 28.7-64 64v192H51.2c-10.6 0-19.2 8.6-19.2 19.2Z"/>
            <Path fill="#32A3E2" d="M416 224c-35.3 0-64 28.7-64 64v224c0 35.3 28.7 64 64 64h96c35.3 0 64-28.7 64-64V288c0-35.3-28.7-64-64-64h-96Zm24 240h48c13.3 0 24 10.7 24 24s-10.7 24-24 24h-48c-13.3 0-24-10.7-24-24s10.7-24 24-24Z"/>
        </SVG>
    ),
    sm: ({ size = 20 }) => (
        <SVG width={size} height={size} viewBox="0 0 640 640" fill="none">
            <Path fill="#1D303A" d="M96 128c0-35.3 28.7-64 64-64h320c35.3 0 64 28.7 64 64v384c0 35.3-28.7 64-64 64H160c-35.3 0-64-28.7-64-64V128Zm64 24v256c0 13.3 10.7 24 24 24h272c13.3 0 24-10.7 24-24V152c0-13.3-10.7-24-24-24H184c-13.3 0-24 10.7-24 24Zm96 352c0 13.3 10.7 24 24 24h80c13.3 0 24-10.7 24-24s-10.7-24-24-24h-80c-13.3 0-24 10.7-24 24Z"/>
            <Path fill="#32A3E2" d="M160 152c0-13.3 10.7-24 24-24h272c13.3 0 24 10.7 24 24v256c0 13.3-10.7 24-24 24H184c-13.3 0-24-10.7-24-24V152Z"/>
        </SVG>
    ),
    md: ({ size = 20 }) => (
        <SVG width={size} height={size} viewBox="0 0 640 640" fill="none">
            <Path fill="#1D303A" d="M0 467.2C0 509.6 34.4 544 76.8 544h486.4c42.4 0 76.8-34.4 76.8-76.8 0-10.6-8.6-19.2-19.2-19.2H19.2C8.6 448 0 456.6 0 467.2ZM64 160v240h64V160h384v240h64V160c0-35.3-28.7-64-64-64H128c-35.3 0-64 28.7-64 64Z"/>
            <Path fill="#32A3E2" d="M128 160h384v240H128V160Z"/>
        </SVG>
    ),
    lg: ({ size = 20 }) => (
        <SVG width={size} height={size} viewBox="0 0 640 640" fill="none">
            <Path fill="#1D303A" d="M32 160v224c0 35.3 28.7 64 64 64h272v-64H96V160h272v-16c0-17.5 4.7-33.9 12.8-48H96c-35.3 0-64 28.7-64 64Zm96 360c0 13.3 10.7 24 24 24h228.8c-8.2-14.1-12.8-30.5-12.8-48H152c-13.3 0-24 10.7-24 24Zm288-376v352c0 26.5 21.5 48 48 48h96c26.5 0 48-21.5 48-48V144c0-26.5-21.5-48-48-48h-96c-26.5 0-48 21.5-48 48Zm48 40c0-13.3 10.7-24 24-24h48c13.3 0 24 10.7 24 24s-10.7 24-24 24h-48c-13.3 0-24-10.7-24-24Zm0 96c0-13.3 10.7-24 24-24h48c13.3 0 24 10.7 24 24s-10.7 24-24 24h-48c-13.3 0-24-10.7-24-24Zm80 120c0 17.7-14.3 32-32 32s-32-14.3-32-32 14.3-32 32-32 32 14.3 32 32Z"/>
            <Path fill="#32A3E2" d="M368 160H96v224h272V160Zm144 272c17.7 0 32-14.3 32-32s-14.3-32-32-32-32 14.3-32 32 14.3 32 32 32Z"/>
        </SVG>
    ),
    xl: ({ size = 20 }) => (
        <SVG width={size} height={size} viewBox="0 0 640 640" fill="none">
            <Path fill="#1D303A" d="M32 160c0-35.3 28.7-64 64-64h448c35.3 0 64 28.7 64 64v240c0 35.3-28.7 64-64 64H96c-35.3 0-64-28.7-64-64V160Zm64 0v240h448V160H96Zm64 384c0-17.7 14.3-32 32-32h256c17.7 0 32 14.3 32 32s-14.3 32-32 32H192c-17.7 0-32-14.3-32-32Z"/>
            <Path fill="#32A3E2" d="M96 160h448v240H96V160Z"/>
        </SVG>
    )
};

/**
 * Get breakpoint label with size info
 */
function getBreakpointLabel(breakpoint: Breakpoint | null): string {
    if (!breakpoint) return __('Base (All Screens)', 'orbitools');
    return breakpoint.name;
}

/**
 * Get responsive CSS class for a control value
 */
export function getResponsiveClasses<T>(
    responsiveValue: ResponsiveValue<T>,
    classPrefix: string,
    formatValue?: (value: T) => string
): string {
    const classes: string[] = [];

    Object.entries(responsiveValue).forEach(([breakpoint, value]) => {
        if (value === undefined || value === null || value === '') return;

        const formattedValue = formatValue ? formatValue(value as T) : String(value);
        const className = breakpoint === 'base'
            ? `${classPrefix}-${formattedValue}`
            : `${breakpoint}:${classPrefix}-${formattedValue}`;

        classes.push(className);
    });

    return classes.join(' ');
}

/**
 * Responsive Controls Component - just renders breakpoint items without wrapper
 */
export default function ResponsiveControls({
    label,
    controls,
    values,
    onValuesChange,
    resetAll,
    panelId,
    blockName
}: ResponsiveControlsProps) {
    // Get breakpoints from standalone utility
    const breakpoints = getBreakpointOptions(blockName);

    // All available breakpoints (base + configured breakpoints)
    // If no breakpoints are configured, only show base
    const allBreakpoints: (Breakpoint | null)[] = [
        null, // base - always available
        ...breakpoints
    ];


    // If no breakpoints are configured, don't render responsive controls
    if (breakpoints.length === 0) {
        return (
            <ToolsPanel
                label={label}
                resetAll={resetAll}
                panelId={panelId}
            >
                <div style={{ padding: '16px', fontStyle: 'italic', color: '#757575' }}>
                    No responsive breakpoints configured for this block.
                </div>
            </ToolsPanel>
        );
    }

    /**
     * Update a specific control's responsive value
     */
    const updateControlValue = <T,>(controlId: string, breakpoint: string, value: T | undefined) => {
        const currentValue = values[controlId] || {};
        const newValue = { ...currentValue, [breakpoint]: value };

        // Clean up undefined values
        Object.keys(newValue).forEach(key => {
            if (newValue[key as keyof ResponsiveValue] === undefined) {
                delete newValue[key as keyof ResponsiveValue];
            }
        });

        onValuesChange({
            ...values,
            [controlId]: newValue
        });
    };

    return (
        <ToolsPanel
            label={label}
            resetAll={resetAll}
            panelId={panelId}
        >
            {/* Render one ToolsPanelItem per breakpoint, containing all controls for that breakpoint */}
            {allBreakpoints.map((breakpoint, index) => {
                const bpSlug = breakpoint?.slug || 'base';
                const breakpointLabel = breakpoint ? getBreakpointLabel(breakpoint) : 'Base';

                // Check if any control has a value for this breakpoint
                const hasValueForBreakpoint = () => {
                    return controls.some(control => {
                        const responsiveValue = values[control.id] || {};
                        return responsiveValue[bpSlug as keyof ResponsiveValue] !== undefined;
                    });
                };

                // Reset all controls for this breakpoint
                const onDeselectBreakpoint = () => {
                    const newValues = { ...values };
                    controls.forEach(control => {
                        const responsiveValue = { ...newValues[control.id] };
                        delete responsiveValue[bpSlug as keyof ResponsiveValue];
                        newValues[control.id] = responsiveValue;
                    });
                    onValuesChange(newValues);
                };

                return (
                    <ToolsPanelItem
                        key={bpSlug}
                        hasValue={hasValueForBreakpoint}
                        onDeselect={onDeselectBreakpoint}
                        label={breakpointLabel}
                        isShownByDefault={index === 0} // Only base is shown by default
                        panelId={panelId}
                    >
                        <VStack spacing="12px">
                            {/* Breakpoint header for non-base breakpoints */}
                            {breakpoint && (
                                <View>
                                    <div style={{
                                        display: 'flex',
                                        alignItems: 'center',
                                        gap: '8px'
                                    }}>
                                        <div style={{ width: '20px', height: '20px', flexShrink: 0, display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                                            <Icon
                                                icon={BreakpointIconComponents[breakpoint.slug as keyof typeof BreakpointIconComponents] || BreakpointIconComponents.base}
                                                size={16}
                                            />
                                        </div>
                                        <span style={{ fontWeight: '500' }}>{breakpointLabel}</span>
                                    </div>
                                </View>
                            )}

                            {/* Render controls */}
                            {controls.map(control => {
                                const responsiveValue = values[control.id] || {};
                                const currentValue = responsiveValue[bpSlug as keyof ResponsiveValue];

                                return (
                                    <View key={`${control.id}-${bpSlug}`}>
                                        {control.renderControl({
                                            value: currentValue,
                                            onChange: (value) => updateControlValue(control.id, bpSlug, value),
                                            breakpoint,
                                            isDefault: bpSlug === 'base' && !!control.isShownByDefault
                                        })}
                                    </View>
                                );
                            })}
                        </VStack>
                    </ToolsPanelItem>
                );
            })}
        </ToolsPanel>
    );
}
