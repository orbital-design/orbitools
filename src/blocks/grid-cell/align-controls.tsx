/**
 * Grid Cell Alignment Controls
 *
 * Per-cell alignment: how the cell sits within its grid track — align-self
 * (vertical) and justify-self (horizontal), "Auto" inheriting the grid's Cell
 * alignment — plus how the cell's own content sits vertically inside it
 * (justify-content on the cell's flex column), e.g. pin a button to the bottom
 * of a card. Not responsive (a single value each).
 *
 * @file blocks/grid-cell/align-controls.tsx
 * @since 1.0.0
 */

import { InspectorControls } from '@wordpress/block-editor';
import {
    __experimentalToolsPanel as ToolsPanel,
    __experimentalToolsPanelItem as ToolsPanelItem,
    SelectControl,
} from '@wordpress/components';

const ALIGN_SELF_OPTIONS = [
    { label: 'Auto', value: 'auto' },
    { label: 'Top', value: 'start' },
    { label: 'Middle', value: 'center' },
    { label: 'Bottom', value: 'end' },
    { label: 'Stretch', value: 'stretch' },
];

const JUSTIFY_SELF_OPTIONS = [
    { label: 'Auto', value: 'auto' },
    { label: 'Left', value: 'start' },
    { label: 'Center', value: 'center' },
    { label: 'Right', value: 'end' },
    { label: 'Stretch', value: 'stretch' },
];

const CONTENT_ALIGN_OPTIONS = [
    { label: 'Top', value: 'top' },
    { label: 'Middle', value: 'middle' },
    { label: 'Bottom', value: 'bottom' },
    { label: 'Space between', value: 'between' },
];

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
    const panelId = 'grid-cell-align-panel';

    return (
        <InspectorControls group="settings">
            <ToolsPanel
                label="Alignment"
                resetAll={() => {
                    onAlignSelfChange('auto');
                    onJustifySelfChange('auto');
                    onContentAlignChange('top');
                }}
                panelId={panelId}
            >
                <ToolsPanelItem
                    hasValue={() => alignSelf !== 'auto'}
                    label="Cell vertical"
                    onDeselect={() => onAlignSelfChange('auto')}
                    isShownByDefault={true}
                    panelId={panelId}
                >
                    <SelectControl
                        label="Cell vertical"
                        help="How this cell sits in its track (overrides the grid)."
                        value={alignSelf}
                        options={ALIGN_SELF_OPTIONS}
                        onChange={(value) => onAlignSelfChange(value)}
                        __next40pxDefaultSize={true}
                        __nextHasNoMarginBottom={true}
                    />
                </ToolsPanelItem>

                <ToolsPanelItem
                    hasValue={() => justifySelf !== 'auto'}
                    label="Cell horizontal"
                    onDeselect={() => onJustifySelfChange('auto')}
                    isShownByDefault={true}
                    panelId={panelId}
                >
                    <SelectControl
                        label="Cell horizontal"
                        value={justifySelf}
                        options={JUSTIFY_SELF_OPTIONS}
                        onChange={(value) => onJustifySelfChange(value)}
                        __next40pxDefaultSize={true}
                        __nextHasNoMarginBottom={true}
                    />
                </ToolsPanelItem>

                <ToolsPanelItem
                    hasValue={() => contentAlign !== 'top'}
                    label="Content"
                    onDeselect={() => onContentAlignChange('top')}
                    isShownByDefault={true}
                    panelId={panelId}
                >
                    <SelectControl
                        label="Content"
                        help="Vertical position of this cell's content (needs spare height)."
                        value={contentAlign}
                        options={CONTENT_ALIGN_OPTIONS}
                        onChange={(value) => onContentAlignChange(value)}
                        __next40pxDefaultSize={true}
                        __nextHasNoMarginBottom={true}
                    />
                </ToolsPanelItem>
            </ToolsPanel>
        </InspectorControls>
    );
}
