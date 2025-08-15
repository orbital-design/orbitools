/**
 * Query Loop Block Edit Component
 * 
 * This component provides the editor interface for the Query Loop block.
 * The block uses a nested attribute structure matching block.json.
 * 
 * @file blocks/query-loop/edit.tsx
 * @since 1.0.0
 */

import React from 'react';
import { useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import type { BlockEditProps } from '@wordpress/blocks';
import QueryLoopControls from './controls';
import type { QueryLoopAttributes } from './types';
import { getQueryType } from './types';

/**
 * Query Loop Block Edit Component
 * 
 * Renders the editor interface for the Query Loop block.
 * Displays controls in the sidebar and a placeholder in the editor.
 * 
 * @param props Block edit props containing attributes and setAttributes
 * @returns JSX element with controls and editor placeholder
 */
const Edit: React.FC<BlockEditProps<QueryLoopAttributes>> = ({
    attributes,
    setAttributes
}) => {
    // Get query type for conditional messaging
    const queryType = getQueryType(attributes);
    
    // Apply block props with conditional styling based on query type
    const blockProps = useBlockProps({
        className: 'orb-query-loop-editor',
        style: {
            padding: '20px',
            border: '2px dashed #ccc',
            borderRadius: '4px',
            textAlign: 'center' as const,
            color: '#666',
            backgroundColor: queryType === 'custom' ? '#f8f9fa' : '#ffffff'
        }
    });

    // Generate helpful placeholder text based on query configuration
    const getPlaceholderText = (): string => {
        if (queryType === 'inherit') {
            return __('Query Loop: Inheriting posts from current page context', 'orbitools');
        }
        
        const hasArgs = attributes.queryParameters?.args && 
            Object.keys(attributes.queryParameters.args).length > 0;
        
        if (!hasArgs) {
            return __('Query Loop: Configure query parameters to display posts', 'orbitools');
        }
        
        return __('Query Loop: Custom query configured - posts will display on frontend', 'orbitools');
    };

    return (
        <>
            {/* Sidebar Controls */}
            <QueryLoopControls
                attributes={attributes}
                setAttributes={setAttributes}
            />

            {/* Editor Placeholder */}
            <div {...blockProps}>
                <div className="orb-query-loop-editor__icon">
                    📋
                </div>
                <p className="orb-query-loop-editor__text">
                    {getPlaceholderText()}
                </p>
                <small className="orb-query-loop-editor__help">
                    {__('Configure query settings in the sidebar to control which posts are displayed.', 'orbitools')}
                </small>
            </div>
        </>
    );
};

export default Edit;
