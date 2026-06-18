/**
 * Grid Block Edit Component
 *
 * Server-rendered container. The editor previews the CSS grid (column count
 * driven by --orb-grid-cols and the stacked data attribute) and hosts the
 * grid-cell inner blocks. The actual wrapper markup/classes are composed in
 * PHP (Grid::render_callback).
 *
 * @file blocks/grid/edit.tsx
 * @since 1.0.0
 */

import React from 'react';
import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';
import type { BlockEditProps } from '@wordpress/blocks';

import GridControls from './controls';
import { needsConstraint, constrainAttr, resolveContentWidth } from '../../core/utils/content-width.js';

export interface GridAttributes {
    gridType: 'fixed' | 'auto';
    columnSystem: number;
    minColWidth: string;
    rowHeight: 'auto' | 'equal' | 'min';
    minRowHeight: string;
    alignItems: 'stretch' | 'start' | 'center' | 'end';
    justifyItems: 'stretch' | 'start' | 'center' | 'end';
    stackOnMobile: boolean;
    align?: string;
}

const ALLOWED_BLOCKS = ['orb/grid-cell'];

const TEMPLATE: Array<[string]> = [
    ['orb/grid-cell'],
    ['orb/grid-cell'],
];

const Edit: React.FC<BlockEditProps<GridAttributes>> = ({
    attributes,
    setAttributes,
}) => {
    const {
        gridType = 'fixed',
        columnSystem = 12,
        minColWidth = '250px',
        rowHeight = 'auto',
        minRowHeight = '200px',
        alignItems = 'stretch',
        justifyItems = 'stretch',
        stackOnMobile = true,
    } = attributes;

    const isAuto = gridType === 'auto';

    // Full-width grids can constrain their cells to the site content / wide
    // width while the background bleeds full-width. Width comes from the global
    // Content Width control; the block wrapper stays full-bleed and an inner
    // .orb-grid carries the grid + the data-constrain value.
    const needsWrapper = needsConstraint(attributes);
    const constrainVal = constrainAttr(resolveContentWidth(attributes));

    const gridStyle: Record<string, string | number> = {
        // Preview the column track count in the editor; PHP sets the
        // same custom property on the frontend wrapper.
        ['--orb-grid-cols']: columnSystem,
    };
    if (isAuto) {
        // Min track width for the auto-fit template.
        gridStyle['--orb-grid-min'] = minColWidth || '250px';
    }
    if (rowHeight === 'min') {
        gridStyle['--orb-grid-row-min'] = minRowHeight || '200px';
    }
    const dataStacked = stackOnMobile ? 'true' : undefined;
    // Drives the auto-fit grid-template in CSS; only set in auto mode.
    const dataGridMode = isAuto ? 'auto' : undefined;
    // Drives grid-auto-rows (equal / min); auto = unset.
    const dataRowHeight = rowHeight !== 'auto' ? rowHeight : undefined;
    // Cell alignment within tracks; stretch (default) = unset.
    const dataAlignItems = alignItems !== 'stretch' ? alignItems : undefined;
    const dataJustifyItems = justifyItems !== 'stretch' ? justifyItems : undefined;

    // The editor auto-stamps the block's default class (orb-grid) onto the
    // block wrapper. In constrained mode that wrapper is the full-bleed outer
    // div, not the grid — so flag it (orb-grid--bleed) to neutralise the grid
    // layout there in editor.scss. The frontend wrapper never gets the class,
    // so this is purely an editor-preview concern.
    const blockProps = useBlockProps({
        className: needsWrapper ? 'orb-grid--bleed' : 'orb-grid',
        'data-stacked': needsWrapper ? undefined : dataStacked,
        'data-grid-mode': needsWrapper ? undefined : dataGridMode,
        'data-row-height': needsWrapper ? undefined : dataRowHeight,
        'data-align-items': needsWrapper ? undefined : dataAlignItems,
        'data-justify-items': needsWrapper ? undefined : dataJustifyItems,
        style: needsWrapper ? undefined : gridStyle,
    });

    const innerBlocks = (
        <InnerBlocks
            allowedBlocks={ALLOWED_BLOCKS}
            template={TEMPLATE}
            orientation="horizontal"
        />
    );

    return (
        <>
            <GridControls
                attributes={attributes}
                setAttributes={setAttributes}
            />

            <div {...blockProps}>
                {needsWrapper ? (
                    <div
                        className="orb-grid"
                        data-constrain={constrainVal}
                        data-stacked={dataStacked}
                        data-grid-mode={dataGridMode}
                        data-row-height={dataRowHeight}
                        data-align-items={dataAlignItems}
                        data-justify-items={dataJustifyItems}
                        style={gridStyle}
                    >
                        {innerBlocks}
                    </div>
                ) : (
                    innerBlocks
                )}
            </div>
        </>
    );
};

export default Edit;
