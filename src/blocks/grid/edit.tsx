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
    const { columnSystem = 12, stackOnMobile = true } = attributes;

    const blockProps = useBlockProps({
        className: 'orb-grid',
        'data-stacked': stackOnMobile ? 'true' : undefined,
        style: {
            // Preview the column track count in the editor; PHP sets the
            // same custom property on the frontend wrapper.
            ['--orb-grid-cols' as any]: columnSystem,
        },
    });

    return (
        <>
            <GridControls
                attributes={attributes}
                setAttributes={setAttributes}
            />

            <div {...blockProps}>
                <InnerBlocks
                    allowedBlocks={ALLOWED_BLOCKS}
                    template={TEMPLATE}
                    orientation="horizontal"
                />
            </div>
        </>
    );
};

export default Edit;
