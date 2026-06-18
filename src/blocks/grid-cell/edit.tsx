/**
 * Grid Cell Block Edit Component
 *
 * Server-rendered cell. The editor previews the cell's column span (via the
 * span classes) and hosts arbitrary inner blocks. The wrapper markup/classes
 * are composed in PHP (Grid_Cell::render_callback).
 *
 * @file blocks/grid-cell/edit.tsx
 * @since 1.0.0
 */

import React from 'react';
import {
    InnerBlocks,
    useBlockProps,
    ButtonBlockAppender,
    InspectorControls,
} from '@wordpress/block-editor';
import type { BlockEditProps } from '@wordpress/blocks';

import CellSpanControls, { getSpanClasses, getRowSpanClasses, type ResponsiveValue } from './span-control';

export interface GridCellAttributes {
    span: ResponsiveValue<number>;
    rowSpan: ResponsiveValue<number>;
}

interface GridCellContext {
    'orb/columnSystem'?: number;
    'orb/stackOnMobile'?: boolean;
}

const Edit: React.FC<BlockEditProps<GridCellAttributes> & { context: GridCellContext }> = ({
    attributes,
    setAttributes,
    context,
    clientId,
}) => {
    const { span = {}, rowSpan = {} } = attributes;
    const {
        'orb/columnSystem': columnSystem = 12,
        'orb/stackOnMobile': stackOnMobile = true,
    } = context;

    const onSpanChange = (newSpan: ResponsiveValue<number>) => {
        setAttributes({ span: newSpan });
    };

    const onRowSpanChange = (newRowSpan: ResponsiveValue<number>) => {
        setAttributes({ rowSpan: newRowSpan });
    };

    // Build span classes for the editor preview so the cell tracks its
    // configured width/height inside the editor's grid.
    const cellClasses = `orb-grid-cell ${getSpanClasses(span)} ${getRowSpanClasses(rowSpan)}`
        .replace(/\s+/g, ' ')
        .trim();

    const blockProps = useBlockProps({
        className: cellClasses,
    });

    return (
        <>
            <InspectorControls>
                <CellSpanControls
                    span={span}
                    rowSpan={rowSpan}
                    onSpanChange={onSpanChange}
                    onRowSpanChange={onRowSpanChange}
                    columnSystem={columnSystem}
                    stackOnMobile={stackOnMobile}
                    blockName="orb/grid-cell"
                />
            </InspectorControls>

            <div {...blockProps}>
                <InnerBlocks
                    template={[]}
                    templateLock={false}
                    renderAppender={() => <ButtonBlockAppender rootClientId={clientId} />}
                />
            </div>
        </>
    );
};

export default Edit;
