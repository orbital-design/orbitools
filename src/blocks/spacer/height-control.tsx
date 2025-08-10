/**
 * Spacer Height Control
 *
 * Uses ResponsiveToolsPanel pattern like spacings control but for height with fill option
 *
 * @file blocks/spacer/height-control.tsx
 * @since 1.0.0
 */

import { __ } from '@wordpress/i18n';
import { RangeControl } from '@wordpress/components';
import { useSettings } from '@wordpress/block-editor';

import ResponsiveControls, {
    type ResponsiveValue,
    type ResponsiveControlConfig,
    type ControlRenderer,
    getResponsiveClasses
} from '../../core/utils/responsive-controls';


export interface SpacerHeightControlProps {
    /** Current height responsive values */
    height: ResponsiveValue<string>;
    /** Callback when height values change */
    onHeightChange: (height: ResponsiveValue<string>) => void;
    /** Block name for getting breakpoints */
    blockName: string;
}

export type { ResponsiveValue };

/**
 * Get display name for spacing value
 */
function getSpacingDisplayName(spacingSizes: any[], value: string): string {
    if (!value) return 'Default';
    if (value === '0') return 'None';
    if (value === 'fill') return 'Fill';

    const spacing = spacingSizes.find((size: any) => size.slug === value);
    return spacing ? spacing.name : value;
}

/**
 * Height Control Renderer Factory
 */
function createHeightControlRenderer(spacingSizes: any[]): ControlRenderer<string> {
    return ({ value, onChange, breakpoint }) => {
        if (!spacingSizes || !Array.isArray(spacingSizes)) {
            return <div>Loading spacing sizes...</div>;
        }

        // Add fill option to spacing sizes
        const allOptions = [...spacingSizes, { slug: 'fill', name: 'Fill' }];

        const currentIndex = value ? allOptions.findIndex(option => option.slug === value) : -1;
        const sliderValue = currentIndex >= 0 ? currentIndex : 0;

        const updateHeight = (index: number | undefined) => {
            if (index === undefined || index < 0) {
                onChange(undefined);
            } else if (index < allOptions.length) {
                onChange(allOptions[index].slug);
            }
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
                        {getSpacingDisplayName(spacingSizes, value || '')}
                    </span>
                </div>
                <RangeControl
                    value={sliderValue}
                    onChange={updateHeight}
                    min={0}
                    max={allOptions.length - 1}
                    step={1}
                    marks={true}
                    withInputField={false}
                    renderTooltipContent={(index) => {
                        if (index === undefined || index === null || typeof index !== 'number') return '';
                        if (index < 0 || index >= allOptions.length) return 'Default';
                        return allOptions[index].name;
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
        return value; // Just use the slug as-is
    });
}

/**
 * Spacer Height Control Component
 */
export default function SpacerHeightControl({
    height,
    onHeightChange,
    blockName
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
        <ResponsiveControls
            label={__('Height', 'orbitools')}
            controls={[heightControlConfig]}
            values={{ height }}
            onValuesChange={handleValuesChange}
            resetAll={resetAll}
            panelId="spacer-height-panel"
            blockName={blockName}
        />
    );
}
