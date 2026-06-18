/**
 * Grid Cell Span Controls
 *
 * Device-aware column- and row-span controls. The viewport being edited is
 * driven by the editor's native screen-size preview toggle via the shared
 * ResponsiveControl framework — Desktop→base, Tablet→tablet, Mobile→mobile.
 * No bespoke breakpoint tab bar; the ResponsiveDots indicator shows which
 * viewports carry an override.
 *
 * When the active device is Mobile AND the parent Grid stacks on mobile, both
 * controls lock — column span shows "Full" and row span "Auto" — mirroring the
 * CSS, which collapses the grid to one column and resets spans on mobile.
 *
 * @file blocks/grid-cell/span-control.tsx
 * @since 1.0.0
 */

import { RangeControl } from '@wordpress/components';
import { ResponsiveControl, ResponsiveDots } from '../../core/utils/responsive-control';

export interface ResponsiveValue<T = number> {
    base?: T;
    tablet?: T;
    mobile?: T;
    [key: string]: T | undefined;
}

/** Sensible upper bound for how many rows a single cell may span. */
export const ROW_SPAN_MAX = 6;

/**
 * Generic responsive class generator. `base` → bare modifier class; other
 * slugs → `slug:orb-grid-cell--{modifier}-{value}`. Only slugs that carry a
 * positive value emit a class.
 */
function getAxisClasses(values: ResponsiveValue<number>, modifier: string): string {
    if (!values) return '';

    const classes: string[] = [];
    Object.entries(values).forEach(([breakpoint, value]) => {
        if (value === undefined || value === null || value <= 0) return;
        classes.push(
            breakpoint === 'base'
                ? `orb-grid-cell--${modifier}-${value}`
                : `${breakpoint}:orb-grid-cell--${modifier}-${value}`
        );
    });
    return classes.join(' ');
}

/** Column-span classes (orb-grid-cell--span-{n}). */
export function getSpanClasses(span: ResponsiveValue<number>): string {
    return getAxisClasses(span, 'span');
}

/** Row-span classes (orb-grid-cell--row-span-{n}). */
export function getRowSpanClasses(rowSpan: ResponsiveValue<number>): string {
    return getAxisClasses(rowSpan, 'row-span');
}

/** Column-start classes (orb-grid-cell--col-start-{n}). */
export function getColStartClasses(colStart: ResponsiveValue<number>): string {
    return getAxisClasses(colStart, 'col-start');
}

interface AxisControlProps {
    values: ResponsiveValue<number>;
    onChange: (values: ResponsiveValue<number>) => void;
    blockName: string;
    label: string;
    max: number;
    stackOnMobile: boolean;
    /** Lock (disable) on Mobile when the parent grid stacks. */
    lockOnStackedMobile?: boolean;
    /** RangeControl value to show while locked. */
    lockedValue: number;
    /** Read-out label while locked. */
    lockedLabel: string;
    /** Format a set value for the read-out + tooltip. */
    format: (value: number) => string;
}

/**
 * One responsive integer axis (column span or row span).
 */
function AxisControl({
    values,
    onChange,
    blockName,
    label,
    max,
    stackOnMobile,
    lockOnStackedMobile,
    lockedValue,
    lockedLabel,
    format,
}: AxisControlProps) {
    const getValue = (slug: string): number | undefined => values?.[slug];

    const setValue = (slug: string, value: number | undefined) => {
        const updated = { ...values };
        if (value !== undefined) {
            updated[slug] = value;
        } else {
            delete updated[slug];
        }
        onChange(updated);
    };

    return (
        <ResponsiveControl
            blockName={blockName}
            wrap={false}
            render={({ slug }) => {
                const locked = !!lockOnStackedMobile && slug === 'mobile' && stackOnMobile;
                const current = getValue(slug);
                const display = locked
                    ? lockedLabel
                    : current !== undefined
                        ? format(current)
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
                                    {label}
                                </label>
                                <ResponsiveDots value={values} />
                            </span>
                            <span style={{
                                fontSize: '11px',
                                fontWeight: 400,
                                color: '#949494',
                            }}>
                                {display}
                            </span>
                        </div>
                        <RangeControl
                            value={locked ? lockedValue : (current ?? 0)}
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
                            disabled={locked}
                            renderTooltipContent={(value) => {
                                if (value === undefined || value === null || typeof value !== 'number' || value <= 0) {
                                    return 'Auto';
                                }
                                return format(value);
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

export interface CellSpanControlsProps {
    span: ResponsiveValue<number>;
    rowSpan: ResponsiveValue<number>;
    colStart: ResponsiveValue<number>;
    onSpanChange: (span: ResponsiveValue<number>) => void;
    onRowSpanChange: (rowSpan: ResponsiveValue<number>) => void;
    onColStartChange: (colStart: ResponsiveValue<number>) => void;
    columnSystem: number;
    stackOnMobile: boolean;
    blockName: string;
}

/**
 * Column span / column start / row span controls for a grid cell.
 */
export default function CellSpanControls({
    span,
    rowSpan,
    colStart,
    onSpanChange,
    onRowSpanChange,
    onColStartChange,
    columnSystem,
    stackOnMobile,
    blockName,
}: CellSpanControlsProps) {
    const colMax = columnSystem || 12;

    return (
        <>
            <AxisControl
                values={span}
                onChange={onSpanChange}
                blockName={blockName}
                label="Column span"
                max={colMax}
                stackOnMobile={stackOnMobile}
                lockOnStackedMobile={true}
                lockedValue={colMax}
                lockedLabel="Full"
                format={(v) => `${v} / ${colMax}`}
            />
            <AxisControl
                values={colStart}
                onChange={onColStartChange}
                blockName={blockName}
                label="Column start"
                max={colMax}
                stackOnMobile={stackOnMobile}
                lockOnStackedMobile={true}
                lockedValue={0}
                lockedLabel="Auto"
                format={(v) => `Column ${v}`}
            />
            <AxisControl
                values={rowSpan}
                onChange={onRowSpanChange}
                blockName={blockName}
                label="Row span"
                max={ROW_SPAN_MAX}
                stackOnMobile={stackOnMobile}
                lockOnStackedMobile={true}
                lockedValue={0}
                lockedLabel="Auto"
                format={(v) => `${v} row${v > 1 ? 's' : ''}`}
            />
        </>
    );
}
