/**
 * Grid Block Controls
 *
 * Container settings for the Grid block: the grid type (fixed columns vs
 * auto-fit), the column system (5 / 12) or minimum column width, and mobile
 * stacking. Rendered as a single "Grid" ToolsPanel in the Settings tab.
 *
 * @file blocks/grid/controls.tsx
 * @since 1.0.0
 */

import { Fragment } from '@wordpress/element';
import { createToolsPanelItem, createToggleGroup } from '../utils/control-helpers';
import { InspectorControls } from '@wordpress/block-editor';
import {
    __experimentalToolsPanel as ToolsPanel,
    __experimentalUnitControl as UnitControl,
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
    gridType: 'fixed',
    columnSystem: 12,
    minColWidth: '250px',
    stackOnMobile: true,
} as const;

/**
 * Grid type options — fixed N columns, or auto-fit tracks that wrap by width.
 */
const GRID_TYPE_OPTIONS = [
    { value: 'fixed', label: 'Fixed columns' },
    { value: 'auto', label: 'Auto-fit' },
] as const;

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
        gridType = GRID_DEFAULTS.gridType,
        columnSystem = GRID_DEFAULTS.columnSystem,
        minColWidth = GRID_DEFAULTS.minColWidth,
        stackOnMobile = GRID_DEFAULTS.stackOnMobile,
    } = attributes;

    const isAuto = gridType === 'auto';

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
                        updateAttribute('gridType', GRID_DEFAULTS.gridType);
                        updateAttribute('columnSystem', GRID_DEFAULTS.columnSystem);
                        updateAttribute('minColWidth', GRID_DEFAULTS.minColWidth);
                        updateAttribute('stackOnMobile', GRID_DEFAULTS.stackOnMobile);
                    }}
                    panelId="grid-panel"
                >
                    {/* Grid Type Control */}
                    {createToolsPanelItem(
                        'gridType',
                        () => hasNonDefaultValue('gridType', GRID_DEFAULTS.gridType),
                        () => updateAttribute('gridType', GRID_DEFAULTS.gridType),
                        'Grid type',
                        createToggleGroup(
                            gridType,
                            (value) => updateAttribute('gridType', value),
                            GRID_TYPE_OPTIONS,
                            'Grid type'
                        ),
                        true
                    )}

                    {/* Fixed mode: column system (5 / 12) */}
                    {!isAuto && createToolsPanelItem(
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

                    {/* Auto-fit mode: minimum column width (columns wrap below it) */}
                    {isAuto && createToolsPanelItem(
                        'minColWidth',
                        () => hasNonDefaultValue('minColWidth', GRID_DEFAULTS.minColWidth),
                        () => updateAttribute('minColWidth', GRID_DEFAULTS.minColWidth),
                        'Min column width',
                        <UnitControl
                            label="Min column width"
                            help="Columns are as wide as possible but never narrower than this; they wrap automatically."
                            value={minColWidth}
                            onChange={(value) => updateAttribute('minColWidth', value || GRID_DEFAULTS.minColWidth)}
                            units={[
                                { value: 'px', label: 'px' },
                                { value: 'rem', label: 'rem' },
                                { value: 'em', label: 'em' },
                            ]}
                            __next40pxDefaultSize={true}
                        />,
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
