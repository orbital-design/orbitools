/**
 * Row Layout Block Controls
 *
 * The row layout controls (RowControls). Content-width constraint for
 * full-aligned blocks is now provided globally by the Content Width control
 * (orbitools.contentWidth support), so there's no bespoke toggle here.
 *
 * @file blocks/collection/controls.tsx
 * @since 1.0.0
 */

import type { LayoutAttributes } from '../types';
import RowControls from './row-controls';

interface CollectionControlsProps {
    attributes: LayoutAttributes;
    setAttributes: (attributes: Partial<LayoutAttributes>) => void;
}

/**
 * Collection Block Controls Component
 */
export default function CollectionControls({ attributes, setAttributes }: CollectionControlsProps) {
    return (
        <RowControls
            attributes={attributes}
            setAttributes={setAttributes}
        />
    );
}
