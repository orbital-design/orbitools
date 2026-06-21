/**
 * Grid Cell Alignment Toolbar
 *
 * Block-toolbar alignment controls for a grid cell, reusing the same icon set
 * and dropdown pattern as the Row Layout block:
 *
 * - Vertical (align-self)   — how the cell sits in its track, top/middle/
 *   bottom/stretch. Auto (inherit the grid) by clicking the active option off.
 * - Horizontal (justify-self) — left/center/right/stretch.
 * - Content (justify-content) — where the cell's own content sits vertically
 *   (the cell is a flex column), top/middle/bottom/space-between. Pins e.g. a
 *   button to the bottom of a card.
 *
 * Not responsive (a single value each).
 *
 * @file blocks/grid-cell/align-controls.tsx
 * @since 1.0.0
 */

import { BlockControls } from '@wordpress/block-editor';
import { ToolbarGroup, ToolbarDropdownMenu } from '@wordpress/components';
import { alignItemsIcons, justifyContentIcons } from '../utils/flex-icons';

// Stored value → flex CSS key used by the icon maps.
const SELF_TO_FLEX: Record<string, string> = {
    start: 'flex-start',
    center: 'center',
    end: 'flex-end',
    stretch: 'stretch',
};
const CONTENT_TO_FLEX: Record<string, string> = {
    top: 'flex-start',
    middle: 'center',
    bottom: 'flex-end',
    between: 'space-between',
};

/** Cell alignment classes ('' for inherit/default values). */
export function getCellAlignClasses(alignSelf: string, justifySelf: string, contentAlign: string): string {
    const classes: string[] = [];
    if (alignSelf && alignSelf !== 'auto') {
        classes.push(`orb-grid-cell--align-self-${alignSelf}`);
    }
    if (justifySelf && justifySelf !== 'auto') {
        classes.push(`orb-grid-cell--justify-self-${justifySelf}`);
    }
    if (contentAlign && contentAlign !== 'top') {
        classes.push(`orb-grid-cell--content-${contentAlign}`);
    }
    return classes.join(' ');
}

interface DropdownControl {
    icon: JSX.Element;
    title: string;
    isActive: boolean;
    onClick: () => void;
}

/**
 * Build a dropdown's control list. Clicking the active option returns it to the
 * default (so self-alignment can fall back to "inherit the grid").
 */
function buildControls(
    options: Array<{ value: string; title: string; icon: JSX.Element }>,
    current: string,
    defaultValue: string,
    onChange: (value: string) => void
): DropdownControl[] {
    return options.map((o) => ({
        icon: o.icon,
        title: o.title,
        isActive: current === o.value,
        onClick: () => onChange(current === o.value ? defaultValue : o.value),
    }));
}

export interface CellAlignControlsProps {
    alignSelf: string;
    justifySelf: string;
    contentAlign: string;
    onAlignSelfChange: (value: string) => void;
    onJustifySelfChange: (value: string) => void;
    onContentAlignChange: (value: string) => void;
}

export default function CellAlignControls({
    alignSelf = 'auto',
    justifySelf = 'auto',
    contentAlign = 'top',
    onAlignSelfChange,
    onJustifySelfChange,
    onContentAlignChange,
}: CellAlignControlsProps) {
    // Vertical alignment of the cell in its track (align-self) — the vertical
    // (row cross-axis) icons.
    const verticalControls = buildControls(
        [
            { value: 'start', title: 'Top', icon: alignItemsIcons.row[SELF_TO_FLEX.start] },
            { value: 'center', title: 'Middle', icon: alignItemsIcons.row[SELF_TO_FLEX.center] },
            { value: 'end', title: 'Bottom', icon: alignItemsIcons.row[SELF_TO_FLEX.end] },
            { value: 'stretch', title: 'Stretch', icon: alignItemsIcons.row[SELF_TO_FLEX.stretch] },
        ],
        alignSelf,
        'auto',
        onAlignSelfChange
    );

    // Horizontal alignment of the cell in its track (justify-self) — the
    // horizontal (column cross-axis) icons.
    const horizontalControls = buildControls(
        [
            { value: 'start', title: 'Left', icon: alignItemsIcons.column[SELF_TO_FLEX.start] },
            { value: 'center', title: 'Center', icon: alignItemsIcons.column[SELF_TO_FLEX.center] },
            { value: 'end', title: 'Right', icon: alignItemsIcons.column[SELF_TO_FLEX.end] },
            { value: 'stretch', title: 'Stretch', icon: alignItemsIcons.column[SELF_TO_FLEX.stretch] },
        ],
        justifySelf,
        'auto',
        onJustifySelfChange
    );

    // Vertical content distribution inside the cell (justify-content of the
    // cell's flex column) — the vertical (column main-axis) icons.
    const contentControls = buildControls(
        [
            { value: 'top', title: 'Top', icon: justifyContentIcons.column[CONTENT_TO_FLEX.top] },
            { value: 'middle', title: 'Middle', icon: justifyContentIcons.column[CONTENT_TO_FLEX.middle] },
            { value: 'bottom', title: 'Bottom', icon: justifyContentIcons.column[CONTENT_TO_FLEX.bottom] },
            { value: 'between', title: 'Space between', icon: justifyContentIcons.column[CONTENT_TO_FLEX.between] },
        ],
        contentAlign,
        'top',
        onContentAlignChange
    );

    return (
        <BlockControls group="block">
            <ToolbarGroup>
                <ToolbarDropdownMenu
                    label="Cell vertical alignment"
                    icon={verticalControls.find((c) => c.isActive)?.icon || alignItemsIcons.row.stretch}
                    controls={verticalControls}
                />
                <ToolbarDropdownMenu
                    label="Cell horizontal alignment"
                    icon={horizontalControls.find((c) => c.isActive)?.icon || alignItemsIcons.column.stretch}
                    controls={horizontalControls}
                />
                <ToolbarDropdownMenu
                    label="Content position"
                    icon={contentControls.find((c) => c.isActive)?.icon || justifyContentIcons.column['flex-start']}
                    controls={contentControls}
                />
            </ToolbarGroup>
        </BlockControls>
    );
}
