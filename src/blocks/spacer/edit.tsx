/**
 * Spacer Block Edit Component
 * 
 * Editor component for the spacer block with responsive height controls.
 * Outputs a single empty div with height classes.
 * 
 * @file blocks/spacer/edit.tsx
 * @since 1.0.0
 */

import { Fragment } from '@wordpress/element';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import type { BlockEditProps } from '@wordpress/blocks';

import SpacerHeightControl, { getHeightClasses } from './height-control';
import type { ResponsiveValue } from '../../core/utils/responsive-controls';

export interface SpacerAttributes {
    height: ResponsiveValue<string>;
}

/**
 * Spacer Block Edit Component
 */
export default function SpacerEdit({
    attributes,
    setAttributes
}: BlockEditProps<SpacerAttributes>) {
    const { height = {} } = attributes;

    const onHeightChange = (newHeight: ResponsiveValue<string>) => {
        setAttributes({ height: newHeight });
    };

    // Generate CSS classes for the spacer
    const heightClasses = getHeightClasses(height);
    const spacerClasses = `orb-spacer ${heightClasses}`.trim();

    // Generate inline styles for editor preview
    const getPreviewHeight = (): string => {
        // Use base height, or fallback to a default
        const baseHeight = height.base;
        if (baseHeight) {
            if (baseHeight.startsWith('var(--wp--preset--spacing--')) {
                // For CSS variables, we'll show a placeholder height in the editor
                return '2rem'; // Default preview height
            }
            if (baseHeight === '0') {
                return '1px'; // Minimum visible height
            }
            return baseHeight;
        }
        return '2rem'; // Default height when no value is set
    };

    const editorStyles: React.CSSProperties = {
        height: getPreviewHeight(),
        backgroundColor: '#e0e0e0',
        border: '1px dashed #ccc',
        borderRadius: '2px',
        minHeight: '1px',
        position: 'relative',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center'
    };

    const blockProps = useBlockProps({
        className: spacerClasses,
        style: editorStyles
    });

    return (
        <Fragment>
            <InspectorControls group="settings">
                <SpacerHeightControl
                    height={height}
                    onHeightChange={onHeightChange}
                    blockName="orb/spacer"
                />
            </InspectorControls>

            {/* Editor preview of the spacer */}
            <div {...blockProps}>
                <span style={{
                    fontSize: '11px',
                    color: '#666',
                    fontStyle: 'italic',
                    textAlign: 'center',
                    padding: '4px 8px',
                    backgroundColor: 'rgba(255, 255, 255, 0.8)',
                    borderRadius: '2px',
                    userSelect: 'none'
                }}>
                    Spacer {heightClasses && `(${heightClasses})`}
                </span>
            </div>
        </Fragment>
    );
}