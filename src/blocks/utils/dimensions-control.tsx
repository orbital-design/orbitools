/**
 * Unified Responsive Dimensions Control
 * 
 * Handles gap, padding, and margin controls in a single component
 * with consistent spacing values and breakpoint behavior.
 * 
 * @file blocks/utils/dimensions-control.tsx
 * @since 1.0.0
 */

import { __ } from '@wordpress/i18n';
import { RangeControl } from '@wordpress/components';

import ResponsiveToolsPanel, { 
    type ResponsiveControlConfig,
    type ResponsiveValue,
    getResponsiveClasses,
    type ControlRenderer
} from './responsive-controls';

import { 
    getBlockDimensionsConfig
} from './dimensions-config';

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
        const slug = value.match(/var\\(--wp--preset--spacing--([^)]+)\\)/)?.[1];
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
 * Create a control renderer for a specific dimension type
 */
function createDimensionControlRenderer(
    spacingSizes: any[], 
    dimensionType: 'gap' | 'padding' | 'margin'
): ControlRenderer<string> {
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
        
        const updateValue = (index: number | undefined) => {
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

        const dimensionLabels = {
            gap: 'Gap',
            padding: 'Padding', 
            margin: 'Margin'
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
                        {dimensionLabels[dimensionType]}
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
    };
}

/**
 * Generate CSS classes for gap values
 */
export function getGapClasses(gap: ResponsiveValue<string>): string {
    return getResponsiveClasses(gap, 'gap', (value: string) => {
        // Handle special values
        if (value === '0') return '0';
        
        // Handle legacy CSS variable format
        if (value.startsWith('var(--wp--preset--spacing--')) {
            const slug = value.match(/var\\(--wp--preset--spacing--([^)]+)\\)/)?.[1];
            return slug || '0';
        }
        
        // Value is already a slug (new format)
        return value;
    });
}

/**
 * Generate CSS classes for padding values
 */
export function getPaddingClasses(padding: ResponsiveValue<string>): string {
    return getResponsiveClasses(padding, 'p', (value: string) => {
        if (value === '0') return '0';
        
        if (value.startsWith('var(--wp--preset--spacing--')) {
            const slug = value.match(/var\\(--wp--preset--spacing--([^)]+)\\)/)?.[1];
            return slug || '0';
        }
        
        return value;
    });
}

/**
 * Generate CSS classes for margin values
 */
export function getMarginClasses(margin: ResponsiveValue<string>): string {
    return getResponsiveClasses(margin, 'm', (value: string) => {
        if (value === '0') return '0';
        
        if (value.startsWith('var(--wp--preset--spacing--')) {
            const slug = value.match(/var\\(--wp--preset--spacing--([^)]+)\\)/)?.[1];
            return slug || '0';
        }
        
        return value;
    });
}

/**
 * Unified Responsive Dimensions Control Component
 */
export default function DimensionsControl({ 
    gap,
    padding,
    margin,
    onGapChange,
    onPaddingChange,
    onMarginChange,
    label = __('Dimensions', 'orbitools'),
    panelId = 'dimensions-panel',
    blockName
}: DimensionsControlProps) {
    // Get configuration from our dimensions config system
    const config = getBlockDimensionsConfig(blockName);
    const spacingSizes = config.spacings;
    const { dimensions } = config;
    
    // Don't render until we have spacing sizes
    if (!spacingSizes || !Array.isArray(spacingSizes)) {
        return <div>Loading spacing settings...</div>;
    }

    // Build control configurations based on what's enabled
    const controls: ResponsiveControlConfig<string>[] = [];
    
    // Add gap control if enabled
    if (dimensions.gap && onGapChange) {
        controls.push({
            id: 'gap',
            label: __('Gap', 'orbitools'),
            isShownByDefault: true,
            hasValue: (responsiveValue: ResponsiveValue<string>) => {
                return Object.values(responsiveValue).some(value => value !== undefined);
            },
            onDeselect: () => ({}),
            renderControl: createDimensionControlRenderer(spacingSizes, 'gap')
        });
    }

    // Add padding control if enabled
    if (dimensions.padding && onPaddingChange) {
        controls.push({
            id: 'padding',
            label: __('Padding', 'orbitools'),
            isShownByDefault: false,
            hasValue: (responsiveValue: ResponsiveValue<string>) => {
                return Object.values(responsiveValue).some(value => value !== undefined);
            },
            onDeselect: () => ({}),
            renderControl: createDimensionControlRenderer(spacingSizes, 'padding')
        });
    }

    // Add margin control if enabled
    if (dimensions.margin && onMarginChange) {
        controls.push({
            id: 'margin',
            label: __('Margin', 'orbitools'),
            isShownByDefault: false,
            hasValue: (responsiveValue: ResponsiveValue<string>) => {
                return Object.values(responsiveValue).some(value => value !== undefined);
            },
            onDeselect: () => ({}),
            renderControl: createDimensionControlRenderer(spacingSizes, 'margin')
        });
    }

    const handleValuesChange = (values: Record<string, ResponsiveValue<string>>) => {
        if (values.gap && onGapChange) {
            onGapChange(values.gap);
        }
        if (values.padding && onPaddingChange) {
            onPaddingChange(values.padding);
        }
        if (values.margin && onMarginChange) {
            onMarginChange(values.margin);
        }
    };

    const resetAll = () => {
        if (onGapChange) onGapChange({});
        if (onPaddingChange) onPaddingChange({});
        if (onMarginChange) onMarginChange({});
    };

    return (
        <ResponsiveToolsPanel
            label={label}
            panelId={panelId}
            controls={controls}
            values={{ 
                gap: gap || {}, 
                padding: padding || {}, 
                margin: margin || {} 
            }}
            onValuesChange={handleValuesChange}
            resetAll={resetAll}
            blockName={blockName}
        />
    );
}