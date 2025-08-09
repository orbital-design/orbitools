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
    __experimentalToolsPanel as ToolsPanel,
    __experimentalToolsPanelItem as ToolsPanelItem,
    SVG,
    Path
} from '@wordpress/components';

import { getBreakpointOptions } from './dimensions-config';

// Re-export Breakpoint type from dimensions-config
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

export interface ResponsiveToolsPanelProps {
    /** Panel label */
    label: string;
    /** Panel ID for internal use */
    panelId: string;
    /** Control configurations */
    controls: ResponsiveControlConfig[];
    /** Current responsive values */
    values: Record<string, ResponsiveValue>;
    /** Callback when values change */
    onValuesChange: (values: Record<string, ResponsiveValue>) => void;
    /** Reset all values */
    resetAll: () => void;
    /** Additional breakpoints to enable by default */
    enabledBreakpoints?: string[];
    /** Block name for configuration lookup */
    blockName: string;
}

/**
 * Breakpoint icons
 */
const BreakpointIcons = {
    base: (
        <SVG width="24" height="24" viewBox="0 0 24 24">
            <Path d="M20.5 6c0-.4-.4-.8-.8-.8H4.3c-.4 0-.8.4-.8.8v12c0 .4.4.8.8.8h15.4c.4 0 .8-.4.8-.8V6zM19 7v10H5V7h14z"/>
        </SVG>
    ),
    sm: (
        <SVG width="24" height="24" viewBox="0 0 24 24">
            <Path d="M17 6H7c-.6 0-1 .4-1 1v10c0 .6.4 1 1 1h10c.6 0 1-.4 1-1V7c0-.6-.4-1-1-1zm-1 10H8V8h8v8z"/>
        </SVG>
    ),
    md: (
        <SVG width="24" height="24" viewBox="0 0 24 24">
            <Path d="M19 5H5c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 12H5V7h14v10z"/>
        </SVG>
    ),
    lg: (
        <SVG width="24" height="24" viewBox="0 0 24 24">
            <Path d="M21 3H3c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h18c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H3V5h18v14z"/>
        </SVG>
    ),
    xl: (
        <SVG width="24" height="24" viewBox="0 0 24 24">
            <Path d="M22 2H2c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h20c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 18H2V4h20v16z"/>
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
 * Responsive Tools Panel Component
 */
export default function ResponsiveToolsPanel({
    label,
    panelId,
    controls,
    values,
    onValuesChange,
    resetAll,
    blockName
}: ResponsiveToolsPanelProps) {
    // Get breakpoints from new dimensions config system
    const breakpoints = getBreakpointOptions(blockName);

    // Don't render controls until breakpoints are loaded
    if (!breakpoints) {
        return <div>Loading breakpoints...</div>;
    }

    // All available breakpoints (base + configured breakpoints)
    const allBreakpoints: (Breakpoint | null)[] = [
        null, // base
        ...breakpoints
    ];

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
            {/* Render each control for each breakpoint as individual ToolsPanelItems */}
            {allBreakpoints.map(breakpoint => {
                const bpSlug = breakpoint?.slug || 'base';

                return controls.map(control => {
                    const responsiveValue = values[control.id] || {};
                    const currentValue = responsiveValue[bpSlug as keyof ResponsiveValue];
                    const isDefault = !breakpoint && !!control.isShownByDefault;
                    
                    // Create unique label for breakpoint-specific controls
                    const controlLabel = breakpoint 
                        ? `${control.label} (${getBreakpointLabel(breakpoint)})`
                        : control.label;

                    return (
                        <ToolsPanelItem
                            key={`${control.id}-${bpSlug}`}
                            hasValue={() => currentValue !== undefined}
                            onDeselect={() => updateControlValue(control.id, bpSlug, undefined)}
                            label={controlLabel}
                            isShownByDefault={isDefault}
                            panelId={panelId}
                        >
                            {/* Breakpoint label for non-base breakpoints */}
                            {breakpoint && (
                                <div style={{
                                    display: 'flex',
                                    alignItems: 'center',
                                    gap: '8px',
                                    marginBottom: '12px',
                                    paddingBottom: '8px',
                                    borderBottom: '1px solid #f0f0f0',
                                    fontSize: '11px',
                                    fontWeight: '500',
                                    color: '#757575',
                                    textTransform: 'uppercase',
                                    letterSpacing: '0.5px'
                                }}>
                                    <span style={{ 
                                        backgroundColor: '#f0f0f0', 
                                        padding: '2px 6px', 
                                        borderRadius: '3px',
                                        fontSize: '10px',
                                        fontWeight: '600'
                                    }}>
                                        {breakpoint.slug.toUpperCase()}
                                    </span>
                                    {getBreakpointLabel(breakpoint)}
                                </div>
                            )}
                            
                            {control.renderControl({
                                value: currentValue,
                                onChange: (value) => updateControlValue(control.id, bpSlug, value),
                                breakpoint,
                                isDefault
                            })}
                        </ToolsPanelItem>
                    );
                });
            }).flat()}
        </ToolsPanel>
    );
}