/**
 * Query Loop Block Controls
 *
 * Controls for the Query Loop block with ToolsPanel architecture:
 * - Query Type selection (inherit vs custom)
 * - Post type and status filters
 * - Ordering and pagination controls
 * - Advanced meta and taxonomy queries
 *
 * @file blocks/query-loop/controls.tsx
 * @since 1.0.0
 */

import { Fragment } from '@wordpress/element';
import { createToolsPanelItem, createToggleGroup } from '../utils/control-helpers';
import { InspectorControls } from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';
import {
    __experimentalToolsPanel as ToolsPanel,
    __experimentalToolsPanelItem as ToolsPanelItem,
    __experimentalToggleGroupControl as ToggleGroupControl,
    __experimentalToggleGroupControlOption as ToggleGroupControlOption,
    Notice,
    SelectControl,
    RangeControl,
    ToggleControl,
    TextControl,
    Button
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import FormTokenDropdown from './components/FormTokenDropdown';

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

interface QueryLoopControlsProps {
    attributes: QueryLoopAttributes;
    setAttributes: (attributes: Partial<QueryLoopAttributes>) => void;
}

/**
 * Default values for query controls
 */
const QUERY_DEFAULTS = {
    queryType: 'default',
    postTypes: [],
    postStatus: ['publish'],
    orderby: 'date',
    order: 'DESC',
    postsPerPage: 10,
    offset: 0,
    noPaging: false,
    paged: false,
    searchKeyword: '',
    metaQuery: [],
    metaQueryRelation: 'AND',
    taxQuery: [],
    taxQueryRelation: 'AND',
    includePosts: [],
    excludePosts: [],
    parentPostsOnly: false,
    childrenOfPosts: [],
    layout: 'grid',
    gridColumns: '3'
} as const;

/**
 * Query Loop Block Controls Component
 */
export default function QueryLoopControls({ attributes, setAttributes }: QueryLoopControlsProps) {
    const {
        queryType = QUERY_DEFAULTS.queryType,
        postTypes = QUERY_DEFAULTS.postTypes,
        postStatus = QUERY_DEFAULTS.postStatus,
        orderby = QUERY_DEFAULTS.orderby,
        order = QUERY_DEFAULTS.order,
        postsPerPage = QUERY_DEFAULTS.postsPerPage,
        offset = QUERY_DEFAULTS.offset,
        noPaging = QUERY_DEFAULTS.noPaging,
        paged = QUERY_DEFAULTS.paged,
        searchKeyword = QUERY_DEFAULTS.searchKeyword,
        metaQuery = QUERY_DEFAULTS.metaQuery,
        metaQueryRelation = QUERY_DEFAULTS.metaQueryRelation,
        taxQuery = QUERY_DEFAULTS.taxQuery,
        taxQueryRelation = QUERY_DEFAULTS.taxQueryRelation,
        includePosts = QUERY_DEFAULTS.includePosts,
        excludePosts = QUERY_DEFAULTS.excludePosts,
        parentPostsOnly = QUERY_DEFAULTS.parentPostsOnly,
        childrenOfPosts = QUERY_DEFAULTS.childrenOfPosts,
        layout = QUERY_DEFAULTS.layout,
        gridColumns = QUERY_DEFAULTS.gridColumns
    } = attributes;

    // Get available post types and taxonomies
    const { availablePostTypes, availableTaxonomies, currentTemplate } = useSelect((select) => {
        const coreSelect = select('core');
        const editorSelect = select('core/editor');

        return {
            availablePostTypes: coreSelect.getPostTypes({ per_page: -1 }) || [],
            availableTaxonomies: coreSelect.getTaxonomies({ per_page: -1 }) || [],
            currentTemplate: editorSelect.getEditedPostAttribute?.('template') || null
        };
    }, []);

    /**
     * Helper to update a single attribute
     */
    const updateAttribute = (key: keyof QueryLoopAttributes, value: any) => {
        setAttributes({ [key]: value });
    };

    /**
     * Reset query attributes to defaults
     */
    const resetQueryAttributes = () => {
        setAttributes({
            queryType: QUERY_DEFAULTS.queryType,
            postTypes: QUERY_DEFAULTS.postTypes,
            postStatus: QUERY_DEFAULTS.postStatus,
            orderby: QUERY_DEFAULTS.orderby,
            order: QUERY_DEFAULTS.order,
            postsPerPage: QUERY_DEFAULTS.postsPerPage,
            offset: QUERY_DEFAULTS.offset,
            noPaging: QUERY_DEFAULTS.noPaging,
            paged: QUERY_DEFAULTS.paged,
            searchKeyword: QUERY_DEFAULTS.searchKeyword,
            metaQuery: QUERY_DEFAULTS.metaQuery,
            metaQueryRelation: QUERY_DEFAULTS.metaQueryRelation,
            taxQuery: QUERY_DEFAULTS.taxQuery,
            taxQueryRelation: QUERY_DEFAULTS.taxQueryRelation,
            includePosts: QUERY_DEFAULTS.includePosts,
            excludePosts: QUERY_DEFAULTS.excludePosts,
            parentPostsOnly: QUERY_DEFAULTS.parentPostsOnly,
            childrenOfPosts: QUERY_DEFAULTS.childrenOfPosts
        });
    };

    /**
     * Check if an attribute has a non-default value
     */
    const hasNonDefaultValue = (key: keyof QueryLoopAttributes, defaultValue: any) => {
        const currentValue = attributes[key];
        if (Array.isArray(defaultValue)) {
            return JSON.stringify(currentValue) !== JSON.stringify(defaultValue);
        }
        return currentValue !== undefined && currentValue !== defaultValue;
    };

    // Prepare post type options - only public/viewable ones, excluding default 'post' type
    const postTypeOptions = availablePostTypes
        .filter(pt => {
            // Must be viewable/public
            const isPublic = pt.viewable || (pt.visibility && pt.visibility.publicly_queryable);
            // Exclude the default 'post' type as it's handled by inherit query
            const isNotDefaultPost = pt.name !== 'post';
            return isPublic && isNotDefaultPost;
        })
        .map(pt => pt.name);

    // Prepare post status options
    const postStatusOptions = ['publish', 'draft', 'future', 'private', 'trash', 'any'];

    // Prepare taxonomy options
    const taxonomyOptions = availableTaxonomies
        .filter(tax => tax.visibility && tax.visibility.publicly_queryable)
        .map(tax => tax.slug);

    // Check if we're on a singular template where inherit doesn't make sense
    const isSingularTemplate = currentTemplate && (
        currentTemplate.includes('single-') ||
        currentTemplate.includes('page-') ||
        currentTemplate === 'single' ||
        currentTemplate === 'page'
    );

    return (
        <Fragment>
            {/* Query Type Control - Outside of ToolsPanel */}
            <InspectorControls group="settings">
                <div style={{ padding: '16px', borderBottom: '1px solid #ddd' }}>
                    <ToggleGroupControl
                        label={__('Query Type', 'orbitools')}
                        value={queryType}
                        onChange={(value) => updateAttribute('queryType', value)}
                        isBlock
                        __nextHasNoMarginBottom={true}
                    >
                        <ToggleGroupControlOption
                            value="default"
                            label={__('Inherit', 'orbitools')}
                        />
                        <ToggleGroupControlOption
                            value="custom"
                            label={__('Custom', 'orbitools')}
                        />
                    </ToggleGroupControl>
                    
                    <p style={{ 
                        margin: '12px 0 0 0', 
                        fontSize: '13px', 
                        color: '#757575',
                        lineHeight: '1.4'
                    }}>
                        {queryType === 'default' 
                            ? __('Shows posts automatically based on the current page (like blog posts on the blog page). Note: This won\'t work on individual pages or single posts.', 'orbitools')
                            : __('Build a custom query with specific parameters to control which posts are displayed.', 'orbitools')
                        }
                    </p>
                </div>
            </InspectorControls>

            {/* Query Builder Panel - Only for custom queries */}
            {queryType === 'custom' && (
                <InspectorControls group="settings">
                    <ToolsPanel
                        label={__('Query Builder', 'orbitools')}
                        resetAll={() => {
                            updateAttribute('postTypes', QUERY_DEFAULTS.postTypes);
                            updateAttribute('postStatus', QUERY_DEFAULTS.postStatus);
                            updateAttribute('orderby', QUERY_DEFAULTS.orderby);
                            updateAttribute('order', QUERY_DEFAULTS.order);
                            updateAttribute('postsPerPage', QUERY_DEFAULTS.postsPerPage);
                            updateAttribute('offset', QUERY_DEFAULTS.offset);
                            updateAttribute('noPaging', QUERY_DEFAULTS.noPaging);
                            updateAttribute('paged', QUERY_DEFAULTS.paged);
                            updateAttribute('includePosts', QUERY_DEFAULTS.includePosts);
                            updateAttribute('excludePosts', QUERY_DEFAULTS.excludePosts);
                            updateAttribute('parentPostsOnly', QUERY_DEFAULTS.parentPostsOnly);
                            updateAttribute('childrenOfPosts', QUERY_DEFAULTS.childrenOfPosts);
                        }}
                        panelId="query-loop-panel"
                    >

                        {/* Post Type & Status */}
                        <ToolsPanelItem
                            hasValue={() =>
                                hasNonDefaultValue('postTypes', QUERY_DEFAULTS.postTypes) ||
                                hasNonDefaultValue('postStatus', QUERY_DEFAULTS.postStatus)
                            }
                            label={__('Post Type & Status', 'orbitools')}
                            onDeselect={() => {
                                updateAttribute('postTypes', QUERY_DEFAULTS.postTypes);
                                updateAttribute('postStatus', QUERY_DEFAULTS.postStatus);
                            }}
                            panelId="query-loop-panel"
                        >
                            <FormTokenDropdown
                                label={__('Post Types', 'orbitools')}
                                help={__('Select which post types to query', 'orbitools')}
                                value={postTypes}
                                suggestions={postTypeOptions}
                                onChange={(tokens) => updateAttribute('postTypes', tokens)}
                                placeholder={__('Select post types...', 'orbitools')}
                            />
                            <FormTokenDropdown
                                label={__('Post Status', 'orbitools')}
                                help={__('Filter by post status', 'orbitools')}
                                value={postStatus}
                                suggestions={postStatusOptions}
                                onChange={(tokens) => updateAttribute('postStatus', tokens)}
                                placeholder={__('Select status...', 'orbitools')}
                            />
                        </ToolsPanelItem>

                        {/* Ordering */}
                        <ToolsPanelItem
                            hasValue={() =>
                                hasNonDefaultValue('orderby', QUERY_DEFAULTS.orderby) ||
                                hasNonDefaultValue('order', QUERY_DEFAULTS.order)
                            }
                            label={__('Ordering', 'orbitools')}
                            onDeselect={() => {
                                updateAttribute('orderby', QUERY_DEFAULTS.orderby);
                                updateAttribute('order', QUERY_DEFAULTS.order);
                            }}
                            panelId="query-loop-panel"
                        >
                            <SelectControl
                                label={__('Order By', 'orbitools')}
                                value={orderby}
                                options={[
                                    { label: __('Date', 'orbitools'), value: 'date' },
                                    { label: __('Title', 'orbitools'), value: 'title' },
                                    { label: __('Menu Order', 'orbitools'), value: 'menu_order' },
                                    { label: __('Random', 'orbitools'), value: 'rand' },
                                    { label: __('Modified', 'orbitools'), value: 'modified' },
                                    { label: __('Author', 'orbitools'), value: 'author' },
                                    { label: __('Comment Count', 'orbitools'), value: 'comment_count' }
                                ]}
                                onChange={(value) => updateAttribute('orderby', value)}
                                __nextHasNoMarginBottom={true}
                            />
                            <SelectControl
                                label={__('Order', 'orbitools')}
                                value={order}
                                options={[
                                    { label: __('Descending', 'orbitools'), value: 'DESC' },
                                    { label: __('Ascending', 'orbitools'), value: 'ASC' }
                                ]}
                                onChange={(value) => updateAttribute('order', value)}
                                __nextHasNoMarginBottom={true}
                            />
                        </ToolsPanelItem>

                        {/* Pagination */}
                        <ToolsPanelItem
                            hasValue={() =>
                                hasNonDefaultValue('postsPerPage', QUERY_DEFAULTS.postsPerPage) ||
                                hasNonDefaultValue('noPaging', QUERY_DEFAULTS.noPaging) ||
                                hasNonDefaultValue('paged', QUERY_DEFAULTS.paged) ||
                                hasNonDefaultValue('offset', QUERY_DEFAULTS.offset)
                            }
                            label={__('Pagination', 'orbitools')}
                            onDeselect={() => {
                                updateAttribute('postsPerPage', QUERY_DEFAULTS.postsPerPage);
                                updateAttribute('noPaging', QUERY_DEFAULTS.noPaging);
                                updateAttribute('paged', QUERY_DEFAULTS.paged);
                                updateAttribute('offset', QUERY_DEFAULTS.offset);
                            }}
                            panelId="query-loop-panel"
                        >
                            <ToggleControl
                                label={__('Display all posts', 'orbitools')}
                                help={__('Show all posts without pagination', 'orbitools')}
                                checked={noPaging}
                                onChange={(value) => updateAttribute('noPaging', value)}
                                __nextHasNoMarginBottom={true}
                            />
                            {!noPaging && (
                                <>
                                    <RangeControl
                                        label={__('Posts Per Page', 'orbitools')}
                                        help={__('Number of posts to display (-1 for all)', 'orbitools')}
                                        value={postsPerPage}
                                        onChange={(value) => updateAttribute('postsPerPage', value)}
                                        min={-1}
                                        max={100}
                                        step={1}
                                        __nextHasNoMarginBottom={true}
                                    />
                                    <ToggleControl
                                        label={__('Enable pagination', 'orbitools')}
                                        help={__('Add pagination/load more support', 'orbitools')}
                                        checked={paged}
                                        onChange={(value) => updateAttribute('paged', value)}
                                        __nextHasNoMarginBottom={true}
                                    />
                                    <RangeControl
                                        label={__('Offset', 'orbitools')}
                                        help={__('Number of posts to skip', 'orbitools')}
                                        value={offset}
                                        onChange={(value) => updateAttribute('offset', value)}
                                        min={0}
                                        max={50}
                                        step={1}
                                        __nextHasNoMarginBottom={true}
                                    />
                                </>
                            )}
                        </ToolsPanelItem>

                        {/* Post & Page Filters */}
                        <ToolsPanelItem
                            hasValue={() =>
                                hasNonDefaultValue('includePosts', QUERY_DEFAULTS.includePosts) ||
                                hasNonDefaultValue('excludePosts', QUERY_DEFAULTS.excludePosts) ||
                                hasNonDefaultValue('parentPostsOnly', QUERY_DEFAULTS.parentPostsOnly) ||
                                hasNonDefaultValue('childrenOfPosts', QUERY_DEFAULTS.childrenOfPosts)
                            }
                            label={__('Post & Page Filters', 'orbitools')}
                            onDeselect={() => {
                                updateAttribute('includePosts', QUERY_DEFAULTS.includePosts);
                                updateAttribute('excludePosts', QUERY_DEFAULTS.excludePosts);
                                updateAttribute('parentPostsOnly', QUERY_DEFAULTS.parentPostsOnly);
                                updateAttribute('childrenOfPosts', QUERY_DEFAULTS.childrenOfPosts);
                            }}
                            panelId="query-loop-panel"
                        >
                            <TextControl
                                label={__('Include Posts (IDs)', 'orbitools')}
                                help={__('Comma-separated post IDs to include', 'orbitools')}
                                value={includePosts.join(',')}
                                onChange={(value) => {
                                    const ids = value.split(',').map(id => id.trim()).filter(id => id);
                                    updateAttribute('includePosts', ids);
                                }}
                                placeholder={__('e.g. 1,2,3', 'orbitools')}
                                __nextHasNoMarginBottom={true}
                            />
                            <TextControl
                                label={__('Exclude Posts (IDs)', 'orbitools')}
                                help={__('Comma-separated post IDs to exclude', 'orbitools')}
                                value={excludePosts.join(',')}
                                onChange={(value) => {
                                    const ids = value.split(',').map(id => id.trim()).filter(id => id);
                                    updateAttribute('excludePosts', ids);
                                }}
                                placeholder={__('e.g. 4,5,6', 'orbitools')}
                                __nextHasNoMarginBottom={true}
                            />
                            <ToggleControl
                                label={__('Parent posts only', 'orbitools')}
                                help={__('Only show top-level posts', 'orbitools')}
                                checked={parentPostsOnly}
                                onChange={(value) => updateAttribute('parentPostsOnly', value)}
                                __nextHasNoMarginBottom={true}
                            />
                            <TextControl
                                label={__('Children of Posts (IDs)', 'orbitools')}
                                help={__('Comma-separated post IDs to show children of', 'orbitools')}
                                value={childrenOfPosts.join(',')}
                                onChange={(value) => {
                                    const ids = value.split(',').map(id => id.trim()).filter(id => id);
                                    updateAttribute('childrenOfPosts', ids);
                                }}
                                placeholder={__('e.g. 7,8,9', 'orbitools')}
                                __nextHasNoMarginBottom={true}
                            />
                        </ToolsPanelItem>
                    </ToolsPanel>
                </InspectorControls>
            )}

            {/* Query Filters - Separate panel for advanced filtering */}
            {queryType === 'custom' && (
                <InspectorControls group="settings">
                    <ToolsPanel
                        label={__('Query Filters', 'orbitools')}
                        resetAll={() => {
                            updateAttribute('searchKeyword', QUERY_DEFAULTS.searchKeyword);
                            updateAttribute('metaQuery', QUERY_DEFAULTS.metaQuery);
                            updateAttribute('metaQueryRelation', QUERY_DEFAULTS.metaQueryRelation);
                            updateAttribute('taxQuery', QUERY_DEFAULTS.taxQuery);
                            updateAttribute('taxQueryRelation', QUERY_DEFAULTS.taxQueryRelation);
                        }}
                        panelId="query-filters-panel"
                    >
                        {/* Search */}
                        <ToolsPanelItem
                            hasValue={() => hasNonDefaultValue('searchKeyword', QUERY_DEFAULTS.searchKeyword)}
                            label={__('Search', 'orbitools')}
                            onDeselect={() => updateAttribute('searchKeyword', QUERY_DEFAULTS.searchKeyword)}
                            panelId="query-filters-panel"
                        >
                            <TextControl
                                label={__('Search Keyword', 'orbitools')}
                                help={__('Filter posts by keyword search', 'orbitools')}
                                value={searchKeyword}
                                onChange={(value) => updateAttribute('searchKeyword', value)}
                                placeholder={__('Enter search term...', 'orbitools')}
                                __nextHasNoMarginBottom={true}
                            />
                        </ToolsPanelItem>

                        {/* Meta Query (Advanced) */}
                        <ToolsPanelItem
                            hasValue={() => hasNonDefaultValue('metaQuery', QUERY_DEFAULTS.metaQuery)}
                            label={__('Meta Query (Advanced)', 'orbitools')}
                            onDeselect={() => updateAttribute('metaQuery', QUERY_DEFAULTS.metaQuery)}
                            panelId="query-filters-panel"
                        >
                            {metaQuery.map((rule, index) => (
                                <div key={index} style={{
                                    padding: '12px',
                                    marginBottom: '12px',
                                    border: '1px solid #ddd',
                                    borderRadius: '4px'
                                }}>
                                    <div style={{
                                        display: 'flex',
                                        justifyContent: 'space-between',
                                        marginBottom: '12px'
                                    }}>
                                        <strong>{__('Meta Rule #', 'orbitools')}{index + 1}</strong>
                                        <Button
                                            isDestructive
                                            isSmall
                                            onClick={() => {
                                                const newMetaQuery = [...metaQuery];
                                                newMetaQuery.splice(index, 1);
                                                updateAttribute('metaQuery', newMetaQuery);
                                            }}
                                        >
                                            {__('Remove', 'orbitools')}
                                        </Button>
                                    </div>
                                    <TextControl
                                        label={__('Key', 'orbitools')}
                                        value={rule.key || ''}
                                        onChange={(value) => {
                                            const newMetaQuery = [...metaQuery];
                                            newMetaQuery[index] = { ...rule, key: value };
                                            updateAttribute('metaQuery', newMetaQuery);
                                        }}
                                        __nextHasNoMarginBottom={true}
                                    />
                                    <TextControl
                                        label={__('Value', 'orbitools')}
                                        value={rule.value || ''}
                                        onChange={(value) => {
                                            const newMetaQuery = [...metaQuery];
                                            newMetaQuery[index] = { ...rule, value: value };
                                            updateAttribute('metaQuery', newMetaQuery);
                                        }}
                                        __nextHasNoMarginBottom={true}
                                    />
                                    <SelectControl
                                        label={__('Compare', 'orbitools')}
                                        value={rule.compare || '='}
                                        options={[
                                            { label: '=', value: '=' },
                                            { label: '!=', value: '!=' },
                                            { label: '>', value: '>' },
                                            { label: '>=', value: '>=' },
                                            { label: '<', value: '<' },
                                            { label: '<=', value: '<=' },
                                            { label: 'LIKE', value: 'LIKE' },
                                            { label: 'NOT LIKE', value: 'NOT LIKE' },
                                            { label: 'EXISTS', value: 'EXISTS' },
                                            { label: 'NOT EXISTS', value: 'NOT EXISTS' }
                                        ]}
                                        onChange={(value) => {
                                            const newMetaQuery = [...metaQuery];
                                            newMetaQuery[index] = { ...rule, compare: value };
                                            updateAttribute('metaQuery', newMetaQuery);
                                        }}
                                        __nextHasNoMarginBottom={true}
                                    />
                                </div>
                            ))}

                            <Button
                                variant="secondary"
                                onClick={() => {
                                    const newMetaQuery = [...metaQuery];
                                    newMetaQuery.push({ key: '', value: '', compare: '=' });
                                    updateAttribute('metaQuery', newMetaQuery);
                                }}
                                style={{ marginBottom: metaQuery.length > 1 ? '16px' : 0 }}
                            >
                                {__('Add Meta Query Rule', 'orbitools')}
                            </Button>

                            {metaQuery.length > 1 && (
                                <SelectControl
                                    label={__('Relation', 'orbitools')}
                                    value={metaQueryRelation}
                                    options={[
                                        { label: 'AND', value: 'AND' },
                                        { label: 'OR', value: 'OR' }
                                    ]}
                                    onChange={(value) => updateAttribute('metaQueryRelation', value)}
                                    __nextHasNoMarginBottom={true}
                                />
                            )}
                        </ToolsPanelItem>

                        {/* Tax Query (Advanced) */}
                        <ToolsPanelItem
                            hasValue={() => hasNonDefaultValue('taxQuery', QUERY_DEFAULTS.taxQuery)}
                            label={__('Tax Query (Advanced)', 'orbitools')}
                            onDeselect={() => updateAttribute('taxQuery', QUERY_DEFAULTS.taxQuery)}
                            panelId="query-filters-panel"
                        >
                            {taxQuery.map((rule, index) => (
                                <div key={index} style={{
                                    padding: '12px',
                                    marginBottom: '12px',
                                    border: '1px solid #ddd',
                                    borderRadius: '4px'
                                }}>
                                    <div style={{
                                        display: 'flex',
                                        justifyContent: 'space-between',
                                        marginBottom: '12px'
                                    }}>
                                        <strong>{__('Tax Rule #', 'orbitools')}{index + 1}</strong>
                                        <Button
                                            isDestructive
                                            isSmall
                                            onClick={() => {
                                                const newTaxQuery = [...taxQuery];
                                                newTaxQuery.splice(index, 1);
                                                updateAttribute('taxQuery', newTaxQuery);
                                            }}
                                        >
                                            {__('Remove', 'orbitools')}
                                        </Button>
                                    </div>
                                    <SelectControl
                                        label={__('Taxonomy', 'orbitools')}
                                        value={rule.taxonomy || ''}
                                        options={[
                                            { label: __('-- Select Taxonomy --', 'orbitools'), value: '' },
                                            ...taxonomyOptions.map(tax => ({ label: tax, value: tax }))
                                        ]}
                                        onChange={(value) => {
                                            const newTaxQuery = [...taxQuery];
                                            newTaxQuery[index] = { ...rule, taxonomy: value, terms: [] };
                                            updateAttribute('taxQuery', newTaxQuery);
                                        }}
                                        __nextHasNoMarginBottom={true}
                                    />
                                    {rule.taxonomy && (
                                        <TextControl
                                            label={__('Terms', 'orbitools')}
                                            help={__('Comma-separated term IDs or slugs', 'orbitools')}
                                            value={rule.terms ? rule.terms.join(',') : ''}
                                            onChange={(value) => {
                                                const terms = value.split(',').map(term => term.trim()).filter(term => term);
                                                const newTaxQuery = [...taxQuery];
                                                newTaxQuery[index] = { ...rule, terms: terms };
                                                updateAttribute('taxQuery', newTaxQuery);
                                            }}
                                            placeholder={__('e.g. 1,2,3 or slug1,slug2', 'orbitools')}
                                            __nextHasNoMarginBottom={true}
                                        />
                                    )}
                                    {rule.taxonomy && (
                                        <SelectControl
                                            label={__('Operator', 'orbitools')}
                                            value={rule.operator || 'IN'}
                                            options={[
                                                { label: 'IN', value: 'IN' },
                                                { label: 'NOT IN', value: 'NOT IN' },
                                                { label: 'AND', value: 'AND' },
                                                { label: 'EXISTS', value: 'EXISTS' },
                                                { label: 'NOT EXISTS', value: 'NOT EXISTS' }
                                            ]}
                                            onChange={(value) => {
                                                const newTaxQuery = [...taxQuery];
                                                newTaxQuery[index] = { ...rule, operator: value };
                                                updateAttribute('taxQuery', newTaxQuery);
                                            }}
                                            __nextHasNoMarginBottom={true}
                                        />
                                    )}
                                </div>
                            ))}

                            <Button
                                variant="secondary"
                                onClick={() => {
                                    const newTaxQuery = [...taxQuery];
                                    newTaxQuery.push({ taxonomy: '', terms: [], operator: 'IN' });
                                    updateAttribute('taxQuery', newTaxQuery);
                                }}
                                style={{ marginBottom: taxQuery.length > 1 ? '16px' : 0 }}
                            >
                                {__('Add Tax Query Rule', 'orbitools')}
                            </Button>

                            {taxQuery.length > 1 && (
                                <SelectControl
                                    label={__('Relation', 'orbitools')}
                                    value={taxQueryRelation}
                                    options={[
                                        { label: 'AND', value: 'AND' },
                                        { label: 'OR', value: 'OR' }
                                    ]}
                                    onChange={(value) => updateAttribute('taxQueryRelation', value)}
                                    __nextHasNoMarginBottom={true}
                                />
                            )}
                        </ToolsPanelItem>
                    </ToolsPanel>
                </InspectorControls>
            )}

            {/* Results Layout - Separate panel */}
            <InspectorControls group="settings">
                <ToolsPanel
                    label={__('Results Layout', 'orbitools')}
                    resetAll={() => {
                        updateAttribute('layout', QUERY_DEFAULTS.layout);
                        updateAttribute('gridColumns', QUERY_DEFAULTS.gridColumns);
                    }}
                    panelId="query-layout-panel"
                >
                    <ToolsPanelItem
                        hasValue={() => hasNonDefaultValue('layout', QUERY_DEFAULTS.layout)}
                        isShownByDefault={false}
                        label={__('Display Type', 'orbitools')}
                        onDeselect={() => updateAttribute('layout', QUERY_DEFAULTS.layout)}
                        panelId="query-layout-panel"
                    >
                        <ToggleGroupControl
                            label={__('Display Type', 'orbitools')}
                            value={layout}
                            onChange={(value) => updateAttribute('layout', value)}
                            isBlock
                            __nextHasNoMarginBottom={true}
                        >
                            <ToggleGroupControlOption
                                value="grid"
                                label={__('Grid', 'orbitools')}
                            />
                            <ToggleGroupControlOption
                                value="list"
                                label={__('List', 'orbitools')}
                            />
                        </ToggleGroupControl>
                    </ToolsPanelItem>

                    {layout === 'grid' && (
                        <ToolsPanelItem
                            hasValue={() => hasNonDefaultValue('gridColumns', QUERY_DEFAULTS.gridColumns)}
                            isShownByDefault={
                                hasNonDefaultValue('layout', QUERY_DEFAULTS.layout) && layout === 'grid'
                            }
                            label={__('Grid Columns', 'orbitools')}
                            onDeselect={() => updateAttribute('gridColumns', QUERY_DEFAULTS.gridColumns)}
                            panelId="query-layout-panel"
                        >
                            <ToggleGroupControl
                                label={__('Columns', 'orbitools')}
                                value={gridColumns}
                                onChange={(value) => updateAttribute('gridColumns', value)}
                                isBlock
                                __nextHasNoMarginBottom={true}
                            >
                                <ToggleGroupControlOption
                                    value="2"
                                    label="2"
                                />
                                <ToggleGroupControlOption
                                    value="3"
                                    label="3"
                                />
                                <ToggleGroupControlOption
                                    value="4"
                                    label="4"
                                />
                                <ToggleGroupControlOption
                                    value="5"
                                    label="5"
                                />
                            </ToggleGroupControl>
                        </ToolsPanelItem>
                    )}
                </ToolsPanel>
            </InspectorControls>
        </Fragment>
    );
}
