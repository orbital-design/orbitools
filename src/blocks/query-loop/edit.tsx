import React from 'react';
import { useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import type { BlockEditProps } from '@wordpress/blocks';
import QueryLoopControls from './controls';

interface QueryLoopAttributes {
    queryType: string;
    postTypes: string[];
    postStatus: string[];
    orderby: string;
    order: string;
    postsPerPage: number;
    noPaging: boolean;
    paged: boolean;
    offset: number;
    searchKeyword: string;
    metaQuery: Array<{
        key: string;
        value: string;
        compare: string;
    }>;
    metaQueryRelation: string;
    taxQuery: Array<{
        taxonomy: string;
        terms: string[];
        operator: string;
    }>;
    taxQueryRelation: string;
    includePosts: string[];
    excludePosts: string[];
    parentPostsOnly: boolean;
    childrenOfPosts: string[];
    layout: string;
    gridColumns: string;
}

const Edit: React.FC<BlockEditProps<QueryLoopAttributes>> = ({
    attributes,
    setAttributes
}) => {
    const blockProps = useBlockProps({
        style: {
            padding: '20px',
            border: '2px dashed #ccc',
            borderRadius: '4px',
            textAlign: 'center' as const,
            color: '#666'
        }
    });

    return (
        <>
            <QueryLoopControls
                attributes={attributes}
                setAttributes={setAttributes}
            />

            <div {...blockProps}>
                <p>{__('Query Content', 'orbitools')}</p>
            </div>
        </>
    );
};

export default Edit;
