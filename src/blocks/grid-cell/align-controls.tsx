/**
 * Grid Cell Self-Alignment Controls
 *
 * Per-cell overrides for how this cell sits within its grid track — align-self
 * (vertical) and justify-self (horizontal). "Auto" inherits the grid's own
 * Cell alignment. Not responsive (a single value each).
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

/** Class for a self-alignment value ('' when auto / inherit). */
export function getSelfAlignClasses(alignSelf: string, justifySelf: string): string {
    const classes: string[] = [];
    if (alignSelf && alignSelf !== 'auto') {
        classes.push(`orb-grid-cell--align-self-${alignSelf}`);
    }
    if (justifySelf && justifySelf !== 'auto') {
        classes.push(`orb-grid-cell--justify-self-${justifySelf}`);
    }
    return classes.join(' ');
}

export interface CellAlignControlsProps {
    alignSelf: string;
    justifySelf: string;
    onAlignSelfChange: (value: string) => void;
    onJustifySelfChange: (value: string) => void;
}

export default function CellAlignControls({
    alignSelf = 'auto',
    justifySelf = 'auto',
    onAlignSelfChange,
    onJustifySelfChange,
}: CellAlignControlsProps) {
    const panelId = 'grid-cell-align-panel';

    return (
        <InspectorControls group="settings">
            <ToolsPanel
                label="Self alignment"
                resetAll={() => {
                    onAlignSelfChange('auto');
                    onJustifySelfChange('auto');
                }}
                panelId={panelId}
            >
                <ToolsPanelItem
                    hasValue={() => alignSelf !== 'auto'}
                    label="Vertical"
                    onDeselect={() => onAlignSelfChange('auto')}
                    isShownByDefault={true}
                    panelId={panelId}
                >
                    <SelectControl
                        label="Vertical"
                        value={alignSelf}
                        options={ALIGN_SELF_OPTIONS}
                        onChange={(value) => onAlignSelfChange(value)}
                        __next40pxDefaultSize={true}
                        __nextHasNoMarginBottom={true}
                    />
                </ToolsPanelItem>

                <ToolsPanelItem
                    hasValue={() => justifySelf !== 'auto'}
                    label="Horizontal"
                    onDeselect={() => onJustifySelfChange('auto')}
                    isShownByDefault={true}
                    panelId={panelId}
                >
                    <SelectControl
                        label="Horizontal"
                        value={justifySelf}
                        options={JUSTIFY_SELF_OPTIONS}
                        onChange={(value) => onJustifySelfChange(value)}
                        __next40pxDefaultSize={true}
                        __nextHasNoMarginBottom={true}
                    />
                </ToolsPanelItem>
            </ToolsPanel>
        </InspectorControls>
    );
}
