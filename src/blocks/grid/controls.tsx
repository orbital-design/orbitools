/**
 * Grid Block Controls
 *
 * Container settings for the Grid block: the column system (5 / 12) and
 * mobile stacking. Rendered as a single "Grid" ToolsPanel in the Settings
 * tab, mirroring the Row Layout block's column controls.
 *
 * @file blocks/grid/controls.tsx
 * @since 1.0.0
 */

import { Fragment } from '@wordpress/element';
import { createToolsPanelItem, createToggleGroup } from '../utils/control-helpers';
import { InspectorControls } from '@wordpress/block-editor';
import {
    __experimentalToolsPanel as ToolsPanel,
    ToggleControl,
} from '@wordpress/components';

import type { GridAttributes } from './edit';

interface GridControlsProps {
    attributes: GridAttributes;
    setAttributes: (attributes: Partial<GridAttributes>) => void;
}

/**
 * Default values for grid controls
 */
const GRID_DEFAULTS = {
    columnSystem: 12,
    stackOnMobile: true,
} as const;

/**
 * Grid system options
 */
const COLUMN_SYSTEM_OPTIONS = [
    { value: 5, label: '5 Column' },
    { value: 12, label: '12 Column' },
] as const;

/**
 * Grid Controls Component
 */
export default function GridControls({ attributes, setAttributes }: GridControlsProps) {
    const {
        columnSystem = GRID_DEFAULTS.columnSystem,
        stackOnMobile = GRID_DEFAULTS.stackOnMobile,
    } = attributes;

    /**
     * Helper to update a single attribute
     */
    const updateAttribute = (key: keyof GridAttributes, value: any) => {
        setAttributes({ [key]: value });
    };

    /**
     * Check if an attribute has a non-default value
     */
    const hasNonDefaultValue = (key: keyof GridAttributes, defaultValue: any) => {
        return attributes[key] !== undefined && attributes[key] !== defaultValue;
    };

    return (
        <Fragment>
            <InspectorControls group="settings">
                <ToolsPanel
                    label="Grid"
                    resetAll={() => {
                        updateAttribute('columnSystem', GRID_DEFAULTS.columnSystem);
                        updateAttribute('stackOnMobile', GRID_DEFAULTS.stackOnMobile);
                    }}
                    panelId="grid-panel"
                >
                    {/* Grid System Control */}
                    {createToolsPanelItem(
                        'columnSystem',
                        () => hasNonDefaultValue('columnSystem', GRID_DEFAULTS.columnSystem),
                        () => updateAttribute('columnSystem', GRID_DEFAULTS.columnSystem),
                        'Grid System',
                        createToggleGroup(
                            columnSystem,
                            (value) => updateAttribute('columnSystem', value),
                            COLUMN_SYSTEM_OPTIONS,
                            'Grid System'
                        ),
                        true
                    )}

                    {/* Stack on Mobile Control */}
                    {createToolsPanelItem(
                        'stackOnMobile',
                        () => hasNonDefaultValue('stackOnMobile', GRID_DEFAULTS.stackOnMobile),
                        () => updateAttribute('stackOnMobile', GRID_DEFAULTS.stackOnMobile),
                        'Stack',
                        <ToggleControl
                            label="Stack on Mobile"
                            help="Collapse the grid to a single column on mobile devices"
                            checked={stackOnMobile}
                            onChange={(value) => updateAttribute('stackOnMobile', value)}
                            __nextHasNoMarginBottom={true}
                        />,
                        true
                    )}
                </ToolsPanel>
            </InspectorControls>
        </Fragment>
    );
}
