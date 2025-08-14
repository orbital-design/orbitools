import { registerBlockType } from '@wordpress/blocks';
import type { BlockConfiguration } from '@wordpress/blocks';
import { SVG, Path } from '@wordpress/components';

import Edit from './edit';
import Save from './save';
import metadata from './block.json';

import './index.scss';

const QueryLoopIcon = () => (
    <SVG xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
        <Path d="M18 12h-2.5l1.5-1.5L15.5 9L12 12.5L8.5 9L7 10.5L8.5 12H6v1.5h2.5L7 15l1.5 1.5L12 13l3.5 3.5L17 15l-1.5-1.5H18V12z"/>
        <Path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"/>
    </SVG>
);

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

const blockConfig: BlockConfiguration<QueryLoopAttributes> = {
    ...metadata,
    icon: QueryLoopIcon,
    edit: Edit,
    save: Save,
};

registerBlockType(metadata.name as string, blockConfig);
