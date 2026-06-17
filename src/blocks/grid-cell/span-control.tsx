/**
 * Grid Cell Span Control
 *
 * Device-aware column-span control. The viewport being edited is driven by
 * the editor's native screen-size preview toggle via the shared
 * ResponsiveControl framework — Desktop→base, Tablet→tablet, Mobile→mobile.
 * No bespoke breakpoint tab bar; the ResponsiveDots indicator shows which
 * viewports carry an override.
 *
 * When the active device is Mobile AND the parent Grid is set to stack on
 * mobile, the span control is disabled and shows "Full" — this mirrors the
 * CSS, which collapses the grid to one column on mobile, so the two can't
 * disagree.
 *
 * @file blocks/grid-cell/span-control.tsx
 * @since 1.0.0
 */

import { RangeControl } from '@wordpress/components';
import {
    ResponsiveControl,
    ResponsiveDots,
    useDeviceType,
} from '../../core/utils/responsive-control';

export interface ResponsiveValue<T = number> {
    base?: T;
    tablet?: T;
    mobile?: T;
    [key: string]: T | undefined;
}

export interface SpanControlProps {
    span: ResponsiveValue<number>;
    onSpanChange: (span: ResponsiveValue<number>) => void;
    columnSystem: number;
    stackOnMobile: boolean;
    blockName: string;
}

/**
 * Generate CSS classes for span values. `base` → bare class; other slugs →
 * `slug:orb-grid-cell--span-value` responsive overrides. Only slugs that
 * carry a value emit a class; a cell with no span class falls back to one
 * grid track.
 */
export function getSpanClasses(span: ResponsiveValue<number>): string {
    const classes: string[] = [];

    if (!span) {
        return '';
    }

    Object.entries(span).forEach(([breakpoint, value]) => {
        if (value === undefined || value === null) return;

        const className = breakpoint === 'base'
            ? `orb-grid-cell--span-${value}`
            : `${breakpoint}:orb-grid-cell--span-${value}`;

        classes.push(className);
    });

    return classes.join(' ');
}

/**
 * Grid Cell Span Control Component
 */
export default function SpanControl({
    span,
    onSpanChange,
    columnSystem,
    stackOnMobile,
    blockName,
}: SpanControlProps) {
    const { device } = useDeviceType();
    const max = columnSystem || 12;

    const getValue = (slug: string): number | undefined => span?.[slug];

    const setValue = (slug: string, value: number | undefined) => {
        const updated = { ...span };
        if (value !== undefined) {
            updated[slug] = value;
        } else {
            delete updated[slug];
        }
        onSpanChange(updated);
    };

    return (
        <ResponsiveControl
            blockName={blockName}
            wrap={false}
            render={({ slug }) => {
                // When stacking is on, the mobile viewport is forced full-width
                // by CSS — reflect that by locking the control to "Full".
                const lockedFull = slug === 'mobile' && stackOnMobile;
                const currentValue = getValue(slug);
                const displayValue = lockedFull
                    ? 'Full'
                    : currentValue !== undefined
                        ? `${currentValue} / ${max}`
                        : 'Auto';

                return (
                    <div style={{ padding: '16px', borderBottom: '1px solid #e0e0e0' }}>
                        <div style={{
                            display: 'flex',
                            justifyContent: 'space-between',
                            alignItems: 'center',
                        }}>
                            <span className="orbitools-responsive-label" style={{
                                display: 'inline-flex',
                                alignItems: 'center',
                                gap: '6px',
                            }}>
                                <label style={{
                                    fontSize: '11px',
                                    fontWeight: 500,
                                    color: '#757575',
                                    margin: 0,
                                }}>
                                    Cell
                                </label>
                                <ResponsiveDots value={span} />
                            </span>
                            <span style={{
                                fontSize: '11px',
                                fontWeight: 400,
                                color: '#949494',
                            }}>
                                {displayValue}
                            </span>
                        </div>
                        <RangeControl
                            value={lockedFull ? max : (currentValue ?? 0)}
                            onChange={(value) => {
                                if (value === undefined || value <= 0) {
                                    setValue(slug, undefined);
                                } else {
                                    setValue(slug, value);
                                }
                            }}
                            min={0}
                            max={max}
                            step={1}
                            marks={true}
                            withInputField={false}
                            disabled={lockedFull}
                            renderTooltipContent={(value) => {
                                if (value === undefined || value === null || typeof value !== 'number') return '';
                                if (value <= 0) return 'Auto';
                                return `${value} / ${max}`;
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
