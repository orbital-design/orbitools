/**
 * Responsive Spacing Control
 * 
 * Reusable spacing control that uses theme.json spacing values
 * and generates CSS classes for different breakpoints.
 * 
 * @file blocks/utils/spacing-control.tsx
 * @since 1.0.0
 */

import { __ } from '@wordpress/i18n';
import { RangeControl } from '@wordpress/components';

import ResponsiveToolsPanel, { 
    type ResponsiveControlConfig,
    type ResponsiveValue,
    getResponsiveClasses,
    type ControlRenderer
} from '../../core/utils/responsive-controls';

import { 
    getBlockDimensionsConfig,
    getBreakpointOptions 
} from '../../core/utils/dimensions-config';

export interface SpacingControlProps {
    /** Current spacing responsive values */
    spacing: ResponsiveValue<string>;
    /** Callback when spacing values change */
    onSpacingChange: (spacing: ResponsiveValue<string>) => void;
    /** Label for the control panel */
    label?: string;
    /** Panel ID for internal use */
    panelId?: string;
    /** Block name for configuration lookup */
    blockName: string;
}

/**
 * Helper to get spacing index by slug or legacy CSS variable reference
 */
function getSpacingIndexByValue(spacingSizes: any[], value: string): number {
    if (!spacingSizes || !Array.isArray(spacingSizes) || !value) return -1;

    // Handle legacy CSS variable references (e.g., "var(--wp--preset--spacing--medium)")
    if (value.startsWith('var(--wp--preset--spacing--')) {
        const slug = value.match(/var\(--wp--preset--spacing--([^)]+)\)/)?.[1];
        if (slug) {
            const index = spacingSizes.findIndex((size: any) => size.slug === slug);
            return index >= 0 ? index : -1;
        }
    }

    // Handle slug directly (new format)
    const slugIndex = spacingSizes.findIndex((size: any) => size.slug === value);
    if (slugIndex >= 0) return slugIndex;

    // Fallback: try to match by raw size value
    const index = spacingSizes.findIndex((size: any) => size.size === value);
    return index >= 0 ? index : -1;
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
 * Spacing Control Renderer Factory
 * Creates a renderer with access to spacing sizes
 */
function createSpacingControlRenderer(spacingSizes: any[]): ControlRenderer<string> {
    return ({ value, onChange, breakpoint }) => {
        if (!spacingSizes || !Array.isArray(spacingSizes)) {
            return <div>Loading spacing sizes...</div>;
        }

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
        
        const updateSpacing = (index: number | undefined) => {
            if (index === undefined || index === 0) {
                onChange(undefined); // Default
            } else if (index === 1) {
                onChange('0'); // None
            } else {
                const spacingIndex = index - 2;
                const spacing = spacingSizes[spacingIndex];
                if (spacing) {
                    // Store just the slug
                    onChange(spacing.slug);
                } else {
                    onChange(undefined);
                }
            }
        };

        const getCurrentDisplayName = () => {
            return getSpacingDisplayName(spacingSizes, value || '');
        };

        return (
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
                        {getCurrentDisplayName()}
                    </span>
                </div>
                <RangeControl
                    value={sliderValue}
                    onChange={updateSpacing}
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
    };
}

/**
 * Spacing control configuration
 */
function createSpacingControlConfig(spacingSizes: any[]): ResponsiveControlConfig<string> {
    return {
        id: 'spacing',
        label: __('Spacing', 'orbitools'),
        isShownByDefault: true,
        hasValue: (responsiveValue: ResponsiveValue<string>) => {
            return Object.values(responsiveValue).some(value => value !== undefined);
        },
        onDeselect: () => {
            // Reset all breakpoint values
            return {};
        },
        renderControl: createSpacingControlRenderer(spacingSizes)
    };
}

/**
 * Generate CSS classes for spacing values
 */
export function getSpacingClasses(spacing: ResponsiveValue<string>): string {
    return getResponsiveClasses(spacing, 'gap', (value: string) => {
        // Handle special values
        if (value === '0') return '0';
        
        // Handle legacy CSS variable format
        if (value.startsWith('var(--wp--preset--spacing--')) {
            const slug = value.match(/var\(--wp--preset--spacing--([^)]+)\)/)?.[1];
            return slug || '0';
        }
        
        // Value is already a slug (new format)
        return value;
    });
}

/**
 * Responsive Spacing Control Component
 */
export default function SpacingControl({ 
    spacing, 
    onSpacingChange,
    label = __('Spacing Settings', 'orbitools'),
    panelId = 'spacing-panel',
    blockName
}: SpacingControlProps) {
    // Get configuration from our new dimensions config system
    const config = getBlockDimensionsConfig(blockName);
    const spacingSizes = config.spacings;
    
    // Debug logging in development
    if (process.env.NODE_ENV === 'development') {
        console.log('SpacingControl:', {
            blockName,
            spacingOptions: spacingSizes?.length || 0
        });
    }
    
    // Don't render until we have spacing sizes
    if (!spacingSizes || !Array.isArray(spacingSizes)) {
        return <div>Loading spacing settings...</div>;
    }

    const spacingControlConfig = createSpacingControlConfig(spacingSizes);

    const handleValuesChange = (values: Record<string, ResponsiveValue<string>>) => {
        onSpacingChange(values.spacing || {});
    };

    const resetAll = () => {
        onSpacingChange({});
    };

    return (
        <ResponsiveToolsPanel
            label={label}
            panelId={panelId}
            controls={[spacingControlConfig]}
            values={{ spacing }}
            onValuesChange={handleValuesChange}
            resetAll={resetAll}
            blockName={blockName}
        />
    );
}