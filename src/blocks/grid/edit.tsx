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

export interface GridAttributes {
    columnSystem: number;
    stackOnMobile: boolean;
    restrictContentWidth: boolean;
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
        columnSystem = 12,
        stackOnMobile = true,
        restrictContentWidth = false,
        align,
    } = attributes;

    // Full-width grids can constrain their cells to the site content width
    // while the background bleeds full-width. Mirrors Row Layout: the block
    // wrapper stays full-bleed and an inner .orb-grid carries the grid +
    // constraint. The grid's own styling/attrs go on whichever div is the
    // grid container.
    const needsWrapper = align === 'full' && restrictContentWidth;

    const gridStyle = {
        // Preview the column track count in the editor; PHP sets the
        // same custom property on the frontend wrapper.
        ['--orb-grid-cols' as any]: columnSystem,
    };
    const dataStacked = stackOnMobile ? 'true' : undefined;

    // The editor auto-stamps the block's default class (orb-grid) onto the
    // block wrapper. In constrained mode that wrapper is the full-bleed outer
    // div, not the grid — so flag it (orb-grid--bleed) to neutralise the grid
    // layout there in editor.scss. The frontend wrapper never gets the class,
    // so this is purely an editor-preview concern.
    const blockProps = useBlockProps({
        className: needsWrapper ? 'orb-grid--bleed' : 'orb-grid',
        'data-stacked': needsWrapper ? undefined : dataStacked,
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
                        data-constrain="true"
                        data-stacked={dataStacked}
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
