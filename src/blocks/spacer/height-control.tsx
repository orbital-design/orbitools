/**
 * Spacer Height Control
 * 
 * Responsive height control that uses theme.json spacing values
 * and generates CSS classes for different breakpoints.
 * 
 * @file blocks/spacer/height-control.tsx
 * @since 1.0.0
 */

import { __ } from '@wordpress/i18n';
import { RangeControl } from '@wordpress/components';
import { useSettings } from '@wordpress/block-editor';

import ResponsiveToolsPanel, { 
    type ResponsiveControlConfig,
    type ResponsiveValue,
    getResponsiveClasses,
    type ControlRenderer
} from '../utils/responsive-controls';

export interface SpacerHeightControlProps {
    /** Current height responsive values */
    height: ResponsiveValue<string>;
    /** Callback when height values change */
    onHeightChange: (height: ResponsiveValue<string>) => void;
}


/**
 * Helper to get spacing index by CSS variable reference
 */
function getSpacingIndexByValue(spacingSizes: any[], value: string): number {
    if (!spacingSizes || !Array.isArray(spacingSizes) || !value) return -1;

    // Handle CSS variable references (e.g., "var(--wp--preset--spacing--medium)")
    if (value.startsWith('var(--wp--preset--spacing--')) {
        const slug = value.match(/var\(--wp--preset--spacing--([^)]+)\)/)?.[1];
        if (slug) {
            const index = spacingSizes.findIndex((size: any) => size.slug === slug);
            return index >= 0 ? index : -1;
        }
    }

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
 * Height Control Renderer Factory
 * Creates a renderer with access to spacing sizes
 */
function createHeightControlRenderer(spacingSizes: any[]): ControlRenderer<string> {
    return ({ value, onChange, breakpoint }) => {
        if (!spacingSizes || !Array.isArray(spacingSizes)) {
            return <div>Loading spacing sizes...</div>;
        }

        const currentIndex = getSpacingIndexByValue(spacingSizes, value || '');
        const maxIndex = spacingSizes.length - 1;
        const fillIndex = maxIndex + 3; // After spacing sizes
        
        // Convert to slider index (0 = default, 1 = none, 2+ = spacing sizes, final = fill)
        let sliderValue = 0;
        if (value === undefined) {
            sliderValue = 0; // Default
        } else if (value === '0') {
            sliderValue = 1; // None
        } else if (value === 'fill') {
            sliderValue = fillIndex; // Fill
        } else if (currentIndex >= 0) {
            sliderValue = currentIndex + 2; // Spacing size
        } else {
            sliderValue = 0; // Fallback to default
        }
        
        const updateHeight = (index: number | undefined) => {
            if (index === undefined || index === 0) {
                onChange(undefined); // Default
            } else if (index === 1) {
                onChange('0'); // None
            } else if (index === fillIndex) {
                onChange('fill'); // Fill
            } else {
                const spacingIndex = index - 2;
                const spacing = spacingSizes[spacingIndex];
                if (spacing) {
                    // Store CSS variable reference
                    onChange(`var(--wp--preset--spacing--${spacing.slug})`);
                } else {
                    onChange(undefined);
                }
            }
        };

        const getCurrentDisplayName = () => {
            if (value === 'fill') return 'Fill';
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
                        Height {breakpoint && `(${breakpoint.name})`}
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
                    onChange={updateHeight}
                    min={0}
                    max={fillIndex}
                    step={1}
                    marks={true}
                    withInputField={false}
                    renderTooltipContent={(index) => {
                        if (index === 0) return 'Default';
                        if (index === 1) return 'None';
                        if (index === fillIndex) return 'Fill';
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
 * Height control configuration
 */
function createHeightControlConfig(spacingSizes: any[]): ResponsiveControlConfig<string> {
    return {
        id: 'height',
        label: __('Height', 'orbitools'),
        isShownByDefault: true,
        hasValue: (responsiveValue: ResponsiveValue<string>) => {
            return Object.values(responsiveValue).some(value => value !== undefined);
        },
        onDeselect: () => {
            // Reset all breakpoint values
            return {};
        },
        renderControl: createHeightControlRenderer(spacingSizes)
    };
}

/**
 * Generate CSS classes for height values
 */
export function getHeightClasses(height: ResponsiveValue<string>): string {
    return getResponsiveClasses(height, 'h', (value: string) => {
        // Handle special values
        if (value === 'fill') return 'fill';
        if (value === '0') return '0';
        
        // Convert CSS variable to class name
        if (value.startsWith('var(--wp--preset--spacing--')) {
            const slug = value.match(/var\(--wp--preset--spacing--([^)]+)\)/)?.[1];
            return slug || '0';
        }
        
        return value;
    });
}

/**
 * Spacer Height Control Component
 */
export default function SpacerHeightControl({ 
    height, 
    onHeightChange 
}: SpacerHeightControlProps) {
    const [spacingSizes] = useSettings('spacing.spacingSizes');
    
    // Don't render until we have spacing sizes
    if (!spacingSizes || !Array.isArray(spacingSizes)) {
        return <div>Loading spacing settings...</div>;
    }

    const heightControlConfig = createHeightControlConfig(spacingSizes);

    const handleValuesChange = (values: Record<string, ResponsiveValue<string>>) => {
        onHeightChange(values.height || {});
    };

    const resetAll = () => {
        onHeightChange({});
    };

    return (
        <ResponsiveToolsPanel
            label={__('Height Settings', 'orbitools')}
            panelId="spacer-height-panel"
            controls={[heightControlConfig]}
            values={{ height }}
            onValuesChange={handleValuesChange}
            resetAll={resetAll}
        />
    );
}