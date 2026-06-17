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

import SpanControl, { getSpanClasses, type ResponsiveValue } from './span-control';

export interface GridCellAttributes {
    span: ResponsiveValue<number>;
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
    const { span = {} } = attributes;
    const {
        'orb/columnSystem': columnSystem = 12,
        'orb/stackOnMobile': stackOnMobile = true,
    } = context;

    const onSpanChange = (newSpan: ResponsiveValue<number>) => {
        setAttributes({ span: newSpan });
    };

    // Build span classes for the editor preview so the cell tracks its
    // configured width inside the editor's grid.
    const spanClasses = getSpanClasses(span);
    const cellClasses = `orb-grid-cell ${spanClasses}`.trim();

    const blockProps = useBlockProps({
        className: cellClasses,
    });

    return (
        <>
            <InspectorControls>
                <SpanControl
                    span={span}
                    onSpanChange={onSpanChange}
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
