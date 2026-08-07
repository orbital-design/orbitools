/**
 * Spacer Height Control
 *
 * Device-aware height control. The viewport being edited is driven by
 * the editor's native screen-size preview toggle via the shared
 * ResponsiveControl framework — Desktop→base, Tablet→tablet,
 * Mobile→mobile. No bespoke breakpoint tab bar.
 *
 * @file blocks/spacer/height-control.tsx
 * @since 1.0.0
 */

import { RangeControl } from '@wordpress/components';
import { useSettings } from '@wordpress/block-editor';
import { getSpacingDisplayName } from '../utils/control-helpers';
import { ResponsiveControl, ResponsiveDots } from '../../core/utils/responsive-control';

export interface ResponsiveValue<T = string> {
    base?: T;
    tablet?: T;
    mobile?: T;
    [key: string]: T | undefined;
}

export interface SpacerHeightControlProps {
    height: ResponsiveValue<string>;
    onHeightChange: (height: ResponsiveValue<string>) => void;
    blockName: string;
}

/**
 * Generate CSS classes for height values. `base` → bare class; other
 * slugs → `slug:orb-spacer--value` responsive overrides.
 */
export function getHeightClasses(height: ResponsiveValue<string>): string {
    const classes: string[] = [];

    Object.entries(height).forEach(([breakpoint, value]) => {
        if (value === undefined || value === null || value === '') return;

        const className = breakpoint === 'base'
            ? `orb-spacer--${value}`
            : `${breakpoint}:orb-spacer--${value}`;

        classes.push(className);
    });

    return classes.join(' ');
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

    if (!spacingSizes || !Array.isArray(spacingSizes)) {
        return null;
    }

    // Add fill option after the theme spacing sizes.
    const allOptions = [...spacingSizes, { slug: 'fill', name: 'Fill' }];

    const getValue = (slug: string): string | undefined => height?.[slug] || undefined;

    const setValue = (slug: string, value: string | undefined) => {
        const updated = { ...height };
        if (value !== undefined) {
            updated[slug] = value;
        } else {
            delete updated[slug];
        }
        onHeightChange(updated);
    };

    return (
        <ResponsiveControl
            blockName={blockName}
            wrap={false}
            render={({ slug }) => {
                const currentValue = getValue(slug);
                const currentIndex = currentValue ? allOptions.findIndex((o) => o.slug === currentValue) : -1;
                // Slider index 0 is "Default" (no value → inherits the
                // larger viewport); the spacing sizes follow at 1..N, so
                // dragging fully left clears the current viewport.
                const sliderValue = currentIndex >= 0 ? currentIndex + 1 : 0;

                const updateHeight = (index: number | undefined) => {
                    if (index === undefined || index <= 0) {
                        setValue(slug, undefined);
                    } else if (index - 1 < allOptions.length) {
                        setValue(slug, allOptions[index - 1].slug);
                    }
                };

                return (
                    <div style={{ padding: '16px', borderBottom: '1px solid #e0e0e0' }}>
                        <div style={{
                            display: 'flex',
                            justifyContent: 'space-between',
                            alignItems: 'center',
                        }}>
                            <span style={{ display: 'inline-flex', alignItems: 'center', gap: '6px' }}>
                                <label style={{
                                    fontSize: '11px',
                                    fontWeight: 500,
                                    color: '#757575',
                                    margin: 0
                                }}>
                                    Height
                                </label>
                                <ResponsiveDots value={height} />
                            </span>
                            <span style={{
                                fontSize: '11px',
                                fontWeight: 400,
                                color: '#949494'
                            }}>
                                {getSpacingDisplayName(spacingSizes, currentValue || '')}
                            </span>
                        </div>
                        <RangeControl
                            value={sliderValue}
                            onChange={updateHeight}
                            min={0}
                            max={allOptions.length}
                            step={1}
                            marks={true}
                            withInputField={false}
                            renderTooltipContent={(index) => {
                                if (index === undefined || index === null || typeof index !== 'number') return '';
                                if (index <= 0) return 'Default';
                                const opt = allOptions[index - 1];
                                return opt ? opt.name : 'Default';
                            }}
                            __next40pxDefaultSize={true}
                            __nextHasNoMarginBottom={true}
                        />
                    </div>
                );
            }}
        />
    );
}
