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
import { InspectorControls, InspectorAdvancedControls } from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';
import {
    __experimentalToolsPanel as ToolsPanel,
    __experimentalToolsPanelItem as ToolsPanelItem,
    __experimentalToggleGroupControl as ToggleGroupControl,
    __experimentalToggleGroupControlOption as ToggleGroupControlOption,
    __experimentalVStack as VStack,
    Notice,
    SelectControl,
    RangeControl,
    ToggleControl,
    TextControl,
    Button,
    PanelBody
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import FormTokenDropdown from './components/FormTokenDropdown';
import QueryTemplateControl from './components/QueryTemplateControl';
import MessageTemplateControl from './components/MessageTemplateControl';
import PostSelector from './components/PostSelector';
import type { QueryLoopAttributes, QueryLoopControlsProps } from './types';
import { QUERY_DEFAULTS } from './types';

/**
 * Helper functions to work with nested attribute structure
 */


/**
 * Query Loop Block Controls Component
 */
export default function QueryLoopControls({ attributes, setAttributes }: QueryLoopControlsProps) {
    // Extract values from nested structure with fallbacks
    const params = attributes.queryParameters;
    const queryType = params?.type || QUERY_DEFAULTS.queryType;
    
    
    const postTypes = params?.args?.postTypes ?? QUERY_DEFAULTS.postTypes;
    const postStatus = params?.args?.postStatus ?? QUERY_DEFAULTS.postStatus;
    const orderby = params?.args?.orderby || QUERY_DEFAULTS.orderby;
    const order = params?.args?.order || QUERY_DEFAULTS.order;
    const postsPerPage = params?.args?.postsPerPage ?? QUERY_DEFAULTS.postsPerPage;
    const offset = params?.args?.offset || QUERY_DEFAULTS.offset;
    const noPaging = params?.args?.noPaging || QUERY_DEFAULTS.noPaging;
    const paged = params?.args?.paged || QUERY_DEFAULTS.paged;
    const searchKeyword = params?.args?.searchKeyword || QUERY_DEFAULTS.searchKeyword;
    const metaQuery = params?.args?.meta_query?.queries || QUERY_DEFAULTS.metaQuery;
    const metaQueryRelation = params?.args?.meta_query?.relation || QUERY_DEFAULTS.metaQueryRelation;
    const taxQuery = params?.args?.tax_query?.queries || QUERY_DEFAULTS.taxQuery;
    const taxQueryRelation = params?.args?.tax_query?.relation || QUERY_DEFAULTS.taxQueryRelation;
    const includePosts = params?.args?.includePosts || QUERY_DEFAULTS.includePosts;
    const excludePosts = params?.args?.excludePosts || QUERY_DEFAULTS.excludePosts;
    const parentPostsOnly = params?.args?.parentPostsOnly || QUERY_DEFAULTS.parentPostsOnly;
    const childrenOfPosts = params?.args?.childrenOfPosts || QUERY_DEFAULTS.childrenOfPosts;
    const layout = params?.display?.layout?.type || QUERY_DEFAULTS.layout;
    const gridColumns = params?.display?.layout?.gridColumns || QUERY_DEFAULTS.gridColumns;
    const template = params?.display?.template || QUERY_DEFAULTS.template;
    const messageTemplate = params?.display?.messageTemplate || QUERY_DEFAULTS.messageTemplate;
    const sortBy = params?.display?.sorting?.availableSortOptions || QUERY_DEFAULTS.sortBy;
    const enableTaxonomyFilters = params?.display?.filtering?.enableTaxonomyFilters || QUERY_DEFAULTS.enableTaxonomyFilters;
    const enableDateFilter = params?.display?.filtering?.enableDateFilter || QUERY_DEFAULTS.enableDateFilter;
    const enableAuthorFilter = params?.display?.filtering?.enableAuthorFilter || QUERY_DEFAULTS.enableAuthorFilter;
    const taxonomyFilterType = params?.display?.filtering?.taxonomyFilterType || QUERY_DEFAULTS.taxonomyFilterType;

    // Get available post types and taxonomies
    const { availablePostTypes, availableTaxonomies, currentTemplate } = useSelect((select: any) => {
        const coreSelect = select('core');
        const editorSelect = select('core/editor');

        return {
            availablePostTypes: coreSelect?.getPostTypes?.({ per_page: -1 }) || [],
            availableTaxonomies: coreSelect?.getTaxonomies?.({ per_page: -1 }) || [],
            currentTemplate: editorSelect?.getEditedPostAttribute?.('template') || null
        };
    }, []);

    /**
     * Update query type
     */
    const updateQueryType = (value: string) => {
        const currentParams = attributes.queryParameters || {};
        
        // When switching to custom, start completely fresh - don't preserve any values
        if (value === 'custom') {
            setAttributes({
                queryParameters: { type: 'custom' }
            });
            return;
        }
        
        // Only preserve non-default values from existing params
        const cleanedParams: any = { type: value };
        
        if (currentParams.args) {
            const cleanedArgs: any = {};
            Object.entries(currentParams.args).forEach(([key, val]) => {
                const defaultMap: Record<string, any> = {
                    postTypes: QUERY_DEFAULTS.postTypes,
                    postStatus: QUERY_DEFAULTS.postStatus,
                    orderby: QUERY_DEFAULTS.orderby,
                    order: QUERY_DEFAULTS.order,
                    postsPerPage: QUERY_DEFAULTS.postsPerPage,
                    offset: QUERY_DEFAULTS.offset,
                    noPaging: QUERY_DEFAULTS.noPaging,
                    paged: QUERY_DEFAULTS.paged,
                    searchKeyword: QUERY_DEFAULTS.searchKeyword,
                    specificPost: 0, // Add this missing default
                    includePosts: QUERY_DEFAULTS.includePosts,
                    excludePosts: QUERY_DEFAULTS.excludePosts,
                    parentPostsOnly: QUERY_DEFAULTS.parentPostsOnly,
                    childrenOfPosts: QUERY_DEFAULTS.childrenOfPosts,
                    meta_query: { relation: 'AND', queries: [] },
                    tax_query: { relation: 'AND', queries: [] }
                };
                
                const defaultVal = defaultMap[key];
                let isDefault = false;
                
                if (key === 'meta_query' || key === 'tax_query') {
                    // Special handling for meta_query and tax_query
                    isDefault = val && 
                        val.relation === 'AND' && 
                        Array.isArray(val.queries) && 
                        val.queries.length === 0;
                } else if (Array.isArray(defaultVal)) {
                    isDefault = JSON.stringify(val) === JSON.stringify(defaultVal);
                } else {
                    isDefault = val === defaultVal;
                }
                    
                if (!isDefault) {
                    cleanedArgs[key] = val;
                }
            });
            
            if (Object.keys(cleanedArgs).length > 0) {
                cleanedParams.args = cleanedArgs;
            }
        }
        
        if (currentParams.display) {
            const cleanedDisplay: any = {};
            Object.entries(currentParams.display).forEach(([section, sectionData]) => {
                const cleanedSection: any = {};
                Object.entries(sectionData as any).forEach(([key, val]) => {
                    const defaultMap: Record<string, Record<string, any>> = {
                        layout: { type: QUERY_DEFAULTS.layout, gridColumns: QUERY_DEFAULTS.gridColumns },
                        sorting: { enableSortControls: false, availableSortOptions: QUERY_DEFAULTS.sortBy },
                        filtering: {
                            enableTaxonomyFilters: QUERY_DEFAULTS.enableTaxonomyFilters,
                            enableDateFilter: QUERY_DEFAULTS.enableDateFilter,
                            enableAuthorFilter: QUERY_DEFAULTS.enableAuthorFilter,
                            taxonomyFilterType: QUERY_DEFAULTS.taxonomyFilterType
                        }
                    };
                    
                    const defaultVal = defaultMap[section]?.[key];
                    const isDefault = Array.isArray(defaultVal)
                        ? JSON.stringify(val) === JSON.stringify(defaultVal)
                        : val === defaultVal;
                        
                    if (!isDefault) {
                        cleanedSection[key] = val;
                    }
                });
                
                if (Object.keys(cleanedSection).length > 0) {
                    cleanedDisplay[section] = cleanedSection;
                }
            });
            
            if (Object.keys(cleanedDisplay).length > 0) {
                cleanedParams.display = cleanedDisplay;
            }
        }
        
        setAttributes({
            queryParameters: cleanedParams
        });
    };

    /**
     * Update a query argument (only if different from default)
     */
    const updateQueryArg = (key: string, value: any) => {
        const defaultMap: Record<string, any> = {
            postTypes: QUERY_DEFAULTS.postTypes,
            postStatus: QUERY_DEFAULTS.postStatus,
            orderby: QUERY_DEFAULTS.orderby,
            order: QUERY_DEFAULTS.order,
            postsPerPage: QUERY_DEFAULTS.postsPerPage,
            offset: QUERY_DEFAULTS.offset,
            noPaging: QUERY_DEFAULTS.noPaging,
            paged: QUERY_DEFAULTS.paged,
            searchKeyword: QUERY_DEFAULTS.searchKeyword,
            specificPost: 0, // Add this missing default
            includePosts: QUERY_DEFAULTS.includePosts,
            excludePosts: QUERY_DEFAULTS.excludePosts,
            parentPostsOnly: QUERY_DEFAULTS.parentPostsOnly,
            childrenOfPosts: QUERY_DEFAULTS.childrenOfPosts
        };

        const defaultValue = defaultMap[key];
        const isDefault = Array.isArray(defaultValue) 
            ? JSON.stringify(value) === JSON.stringify(defaultValue)
            : value === defaultValue;

        const currentParams = attributes.queryParameters || { type: 'inherit' };
        const currentArgs = { ...currentParams.args };

        if (!isDefault) {
            currentArgs[key] = value;
        } else {
            delete currentArgs[key];
        }

        setAttributes({
            queryParameters: {
                ...currentParams,
                args: Object.keys(currentArgs).length > 0 ? currentArgs : undefined
            }
        });
    };

    /**
     * Update meta query
     */
    const updateMetaQuery = (queries: any[], relation?: string) => {
        const currentParams = attributes.queryParameters || { type: 'inherit' };
        const currentArgs = { ...currentParams.args };

        // Only save if there are actual queries or non-default relation
        if (queries.length > 0) {
            currentArgs.meta_query = {
                relation: relation || 'AND',
                queries
            };
        } else {
            // Delete the entire meta_query if no queries
            delete currentArgs.meta_query;
        }

        setAttributes({
            queryParameters: {
                ...currentParams,
                args: Object.keys(currentArgs).length > 0 ? currentArgs : undefined
            }
        });
    };

    /**
     * Update tax query
     */
    const updateTaxQuery = (queries: any[], relation?: string) => {
        const currentParams = attributes.queryParameters || { type: 'inherit' };
        const currentArgs = { ...currentParams.args };

        // Only save if there are actual queries
        if (queries.length > 0) {
            currentArgs.tax_query = {
                relation: relation || 'AND',
                queries
            };
        } else {
            // Delete the entire tax_query if no queries
            delete currentArgs.tax_query;
        }

        setAttributes({
            queryParameters: {
                ...currentParams,
                args: Object.keys(currentArgs).length > 0 ? currentArgs : undefined
            }
        });
    };

    /**
     * Update display settings
     */
    const updateDisplay = (section: string, key: string, value: any) => {
        const currentParams = attributes.queryParameters || { type: 'inherit' };
        const currentDisplay = { ...currentParams.display };
        const currentSection = { ...currentDisplay[section] };

        const defaultMap: Record<string, Record<string, any>> = {
            layout: { type: QUERY_DEFAULTS.layout, gridColumns: QUERY_DEFAULTS.gridColumns },
            sorting: { availableSortOptions: QUERY_DEFAULTS.sortBy },
            filtering: {
                enableTaxonomyFilters: QUERY_DEFAULTS.enableTaxonomyFilters,
                enableDateFilter: QUERY_DEFAULTS.enableDateFilter,
                enableAuthorFilter: QUERY_DEFAULTS.enableAuthorFilter,
                taxonomyFilterType: QUERY_DEFAULTS.taxonomyFilterType
            }
        };

        const defaultValue = defaultMap[section]?.[key];
        const isDefault = Array.isArray(defaultValue)
            ? JSON.stringify(value) === JSON.stringify(defaultValue)
            : value === defaultValue;

        if (!isDefault) {
            currentSection[key] = value;
        } else {
            delete currentSection[key];
        }

        if (Object.keys(currentSection).length > 0) {
            currentDisplay[section] = currentSection;
        } else {
            delete currentDisplay[section];
        }

        setAttributes({
            queryParameters: {
                ...currentParams,
                display: Object.keys(currentDisplay).length > 0 ? currentDisplay : undefined
            }
        });
    };

    /**
     * Legacy updateAttribute function for backward compatibility
     */
    const updateAttribute = (key: string, value: any) => {
        switch (key) {
            case 'queryType':
                updateQueryType(value);
                break;
            case 'postTypes':
            case 'postStatus':
            case 'orderby':
            case 'order':
            case 'postsPerPage':
            case 'offset':
            case 'noPaging':
            case 'paged':
            case 'searchKeyword':
            case 'includePosts':
            case 'excludePosts':
            case 'parentPostsOnly':
            case 'childrenOfPosts':
                updateQueryArg(key, value);
                break;
            case 'metaQuery':
                updateMetaQuery(value, metaQueryRelation);
                break;
            case 'metaQueryRelation':
                updateMetaQuery(metaQuery, value);
                break;
            case 'taxQuery':
                updateTaxQuery(value, taxQueryRelation);
                break;
            case 'taxQueryRelation':
                updateTaxQuery(taxQuery, value);
                break;
            case 'layout':
            case 'gridColumns':
                updateDisplay('layout', key === 'layout' ? 'type' : key, value);
                break;
            case 'template':
                // Template is stored directly in display, not in a subsection
                const currentParams = attributes.queryParameters || { type: 'inherit' };
                const currentDisplay = { ...currentParams.display };
                
                if (value !== QUERY_DEFAULTS.template) {
                    currentDisplay.template = value;
                } else {
                    delete currentDisplay.template;
                }
                
                setAttributes({
                    queryParameters: {
                        ...currentParams,
                        display: Object.keys(currentDisplay).length > 0 ? currentDisplay : undefined
                    }
                });
                break;
            case 'messageTemplate':
                // Message template is stored directly in display, not in a subsection
                const currentParamsMsg = attributes.queryParameters || { type: 'inherit' };
                const currentDisplayMsg = { ...currentParamsMsg.display };
                
                if (value !== QUERY_DEFAULTS.messageTemplate) {
                    currentDisplayMsg.messageTemplate = value;
                } else {
                    delete currentDisplayMsg.messageTemplate;
                }
                
                setAttributes({
                    queryParameters: {
                        ...currentParamsMsg,
                        display: Object.keys(currentDisplayMsg).length > 0 ? currentDisplayMsg : undefined
                    }
                });
                break;
            case 'sortBy':
                updateDisplay('sorting', 'availableSortOptions', value);
                break;
            case 'enableTaxonomyFilters':
            case 'enableDateFilter':
            case 'enableAuthorFilter':
            case 'taxonomyFilterType':
                updateDisplay('filtering', key, value);
                break;
            default:
                console.warn('Unknown key:', key);
        }
    };

    /**
     * Reset query attributes to defaults
     */
    const resetQueryAttributes = () => {
        updateAttribute('queryType', QUERY_DEFAULTS.queryType);
        updateAttribute('postTypes', QUERY_DEFAULTS.postTypes);
        updateAttribute('postStatus', QUERY_DEFAULTS.postStatus);
        updateAttribute('orderby', QUERY_DEFAULTS.orderby);
        updateAttribute('order', QUERY_DEFAULTS.order);
        updateAttribute('postsPerPage', QUERY_DEFAULTS.postsPerPage);
        updateAttribute('offset', QUERY_DEFAULTS.offset);
        updateAttribute('noPaging', QUERY_DEFAULTS.noPaging);
        updateAttribute('paged', QUERY_DEFAULTS.paged);
        updateAttribute('searchKeyword', QUERY_DEFAULTS.searchKeyword);
        updateAttribute('metaQuery', QUERY_DEFAULTS.metaQuery);
        updateAttribute('metaQueryRelation', QUERY_DEFAULTS.metaQueryRelation);
        updateAttribute('taxQuery', QUERY_DEFAULTS.taxQuery);
        updateAttribute('taxQueryRelation', QUERY_DEFAULTS.taxQueryRelation);
        updateAttribute('includePosts', QUERY_DEFAULTS.includePosts);
        updateAttribute('excludePosts', QUERY_DEFAULTS.excludePosts);
        updateAttribute('parentPostsOnly', QUERY_DEFAULTS.parentPostsOnly);
        updateAttribute('childrenOfPosts', QUERY_DEFAULTS.childrenOfPosts);
    };

    /**
     * Check if an attribute has a non-default value
     */
    const hasNonDefaultValue = (key: string, defaultValue: any) => {
        // Get current value based on key
        let currentValue;
        const params = attributes.queryParameters;
        
        if (!params) return false;
        
        switch (key) {
            case 'queryType':
                currentValue = params.type;
                break;
            case 'postTypes':
                currentValue = params.args?.postTypes;
                break;
            case 'postStatus':
                currentValue = params.args?.postStatus;
                break;
            case 'orderby':
                currentValue = params.args?.orderby;
                break;
            case 'order':
                currentValue = params.args?.order;
                break;
            case 'postsPerPage':
                currentValue = params.args?.postsPerPage;
                break;
            case 'offset':
                currentValue = params.args?.offset;
                break;
            case 'noPaging':
                currentValue = params.args?.noPaging;
                break;
            case 'paged':
                currentValue = params.args?.paged;
                break;
            case 'searchKeyword':
                currentValue = params.args?.searchKeyword;
                break;
            case 'metaQuery':
                currentValue = params.args?.meta_query?.queries;
                break;
            case 'metaQueryRelation':
                currentValue = params.args?.meta_query?.relation;
                break;
            case 'taxQuery':
                currentValue = params.args?.tax_query?.queries;
                break;
            case 'taxQueryRelation':
                currentValue = params.args?.tax_query?.relation;
                break;
            case 'includePosts':
                currentValue = params.args?.includePosts;
                break;
            case 'excludePosts':
                currentValue = params.args?.excludePosts;
                break;
            case 'parentPostsOnly':
                currentValue = params.args?.parentPostsOnly;
                break;
            case 'childrenOfPosts':
                currentValue = params.args?.childrenOfPosts;
                break;
            case 'layout':
                currentValue = params.display?.layout?.type;
                break;
            case 'gridColumns':
                currentValue = params.display?.layout?.gridColumns;
                break;
            case 'template':
                currentValue = params.display?.template;
                break;
            case 'messageTemplate':
                currentValue = params.display?.messageTemplate;
                break;
            case 'sortBy':
                currentValue = params.display?.sorting?.availableSortOptions;
                break;
            case 'enableTaxonomyFilters':
                currentValue = params.display?.filtering?.enableTaxonomyFilters;
                break;
            case 'enableDateFilter':
                currentValue = params.display?.filtering?.enableDateFilter;
                break;
            case 'enableAuthorFilter':
                currentValue = params.display?.filtering?.enableAuthorFilter;
                break;
            case 'taxonomyFilterType':
                currentValue = params.display?.filtering?.taxonomyFilterType;
                break;
            default:
                return false;
        }
        
        if (Array.isArray(defaultValue)) {
            // If currentValue is undefined, treat it as the default
            if (currentValue === undefined) {
                return false;
            }
            return JSON.stringify(currentValue) !== JSON.stringify(defaultValue);
        }
        // If currentValue is undefined, treat it as the default
        if (currentValue === undefined) {
            return false;
        }
        return currentValue !== defaultValue;
    };


    // Prepare post type options - only public/viewable ones, excluding 'post' type
    const postTypeOptions = availablePostTypes
        .filter((pt: any) => {
            // Must be viewable/public
            const isPublic = pt.viewable || (pt.visibility && pt.visibility.publicly_queryable);
            // Exclude 'post' type completely - we don't use it
            const isNotPost = pt.slug !== 'post';
            return isPublic && isNotPost;
        })
        .map((pt: any) => {
            // Use slug for the actual post type value, not name or label  
            return pt.slug;
        });

    // Prepare post status options
    const postStatusOptions = ['publish', 'draft', 'future', 'private', 'trash', 'any'];

    // Prepare taxonomy options - all available taxonomies
    const allTaxonomyOptions = availableTaxonomies
        .filter((tax: any) => tax.visibility && tax.visibility.publicly_queryable)
        .map((tax: any) => tax.slug);

    // Prepare taxonomy options filtered by selected post types
    const getFilteredTaxonomyOptions = () => {
        const selectedPostTypes = postTypes || [];

        if (selectedPostTypes.length === 0) {
            // No post types selected, show all public taxonomies
            return allTaxonomyOptions;
        }

        // Get taxonomies that are attached to the selected post types
        const filteredTaxonomies = availableTaxonomies
            .filter((tax: any) => {
                if (!tax.visibility || !tax.visibility.publicly_queryable) {
                    return false;
                }

                // Check if this taxonomy is associated with any of the selected post types
                return selectedPostTypes.some((postType: string) => {
                    // Get object types (post types) this taxonomy supports
                    const objectTypes = tax.types || [];
                    return objectTypes.includes(postType);
                });
            })
            .map((tax: any) => tax.slug);

        return filteredTaxonomies;
    };

    const taxonomyOptions = getFilteredTaxonomyOptions();

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
                            value="inherit"
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
                        {queryType === 'inherit'
                            ? __('Shows posts automatically based on the current page (like blog posts on the blog page). Note: This won\'t work on individual pages or single posts.', 'orbitools')
                            : __('Build a custom query with specific parameters to control which posts are displayed.', 'orbitools')
                        }
                    </p>
                </div>
            </InspectorControls>

            {/* Query Builder Panel - Only for custom queries */}
            {queryType === 'custom' && (
                <InspectorControls group="settings">
                    <PanelBody
                        title={__('Query builder', 'orbitools')}
                        initialOpen={true}
                    >
                        <ToolsPanel
                            id="query-parameters-tools-panel"
                            label={__('Add parameters', 'orbitools')}
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
                            isShownByDefault={false}
                        >
                            <VStack spacing={4}>
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
                            </VStack>
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
                            isShownByDefault={false}
                        >
                            <VStack spacing={4}>
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
                                        { label: __('Comment Count', 'orbitools'), value: 'comment_count' },
                                        { label: __('Post In Order', 'orbitools'), value: 'post__in' }
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
                            </VStack>
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
                            isShownByDefault={false}
                        >
                            <VStack spacing={4}>
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
                            </VStack>
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
                            isShownByDefault={false}
                        >
                            <VStack spacing={4}>
                                <PostSelector
                                    label={__('Include Posts', 'orbitools')}
                                    help={__('Select specific posts to include in the query', 'orbitools')}
                                    value={includePosts}
                                    onChange={(ids) => updateAttribute('includePosts', ids)}
                                    placeholder={__('Search and select posts to include...', 'orbitools')}
                                    postTypes={postTypes}
                                />
                                <PostSelector
                                    label={__('Exclude Posts', 'orbitools')}
                                    help={__('Select specific posts to exclude from the query', 'orbitools')}
                                    value={excludePosts}
                                    onChange={(ids) => updateAttribute('excludePosts', ids)}
                                    placeholder={__('Search and select posts to exclude...', 'orbitools')}
                                    postTypes={postTypes}
                                />
                                <ToggleControl
                                    label={__('Parent posts only', 'orbitools')}
                                    help={__('Only show top-level posts', 'orbitools')}
                                    checked={parentPostsOnly}
                                    onChange={(value) => updateAttribute('parentPostsOnly', value)}
                                    __nextHasNoMarginBottom={true}
                                />
                                <PostSelector
                                    label={__('Children of Posts', 'orbitools')}
                                    help={__('Select posts to show children/sub-pages of', 'orbitools')}
                                    value={childrenOfPosts}
                                    onChange={(ids) => updateAttribute('childrenOfPosts', ids)}
                                    placeholder={__('Search and select parent posts...', 'orbitools')}
                                    postTypes={postTypes}
                                />
                            </VStack>
                        </ToolsPanelItem>
                        </ToolsPanel>
                    </PanelBody>
                </InspectorControls>
            )}

            {/* Query Filters - Separate panel for advanced filtering */}
            {queryType === 'custom' && (
                <InspectorControls group="settings">
                    <PanelBody
                        title={__('Query filters', 'orbitools')}
                        initialOpen={false}
                    >
                        <ToolsPanel
                            id="advanced-filters-tools-panel"
                            label={__('Add filters', 'orbitools')}
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
                            <VStack spacing={4}>
                                <TextControl
                                    label={__('Search Keyword', 'orbitools')}
                                    help={__('Filter posts by keyword search', 'orbitools')}
                                    value={searchKeyword}
                                    onChange={(value) => updateAttribute('searchKeyword', value)}
                                    placeholder={__('Enter search term...', 'orbitools')}
                                    __nextHasNoMarginBottom={true}
                                />
                            </VStack>
                        </ToolsPanelItem>

                        {/* Meta Query (Advanced) */}
                        <ToolsPanelItem
                            hasValue={() => hasNonDefaultValue('metaQuery', QUERY_DEFAULTS.metaQuery)}
                            label={__('Meta Query (Advanced)', 'orbitools')}
                            onDeselect={() => updateAttribute('metaQuery', QUERY_DEFAULTS.metaQuery)}
                            panelId="query-filters-panel"
                        >
                            <VStack spacing={4}>
                                {metaQuery.map((rule: any, index: number) => (
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
                            </VStack>
                        </ToolsPanelItem>

                        {/* Tax Query (Advanced) */}
                        <ToolsPanelItem
                            hasValue={() => hasNonDefaultValue('taxQuery', QUERY_DEFAULTS.taxQuery)}
                            label={__('Tax Query (Advanced)', 'orbitools')}
                            onDeselect={() => updateAttribute('taxQuery', QUERY_DEFAULTS.taxQuery)}
                            panelId="query-filters-panel"
                        >
                            <VStack spacing={4}>
                                {taxQuery.map((rule: any, index: number) => (
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
                                            ...taxonomyOptions.map((tax: string) => ({ label: tax, value: tax }))
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
                            </VStack>
                        </ToolsPanelItem>
                        </ToolsPanel>
                    </PanelBody>
                </InspectorControls>
            )}

            {/* Results Settings - Multiple panels */}
            <InspectorControls group="settings">
                <PanelBody
                    title={__('Query results', 'orbitools')}
                    initialOpen={false}
                >
                    <ToolsPanel
                        id="display-template-tools-panel"
                        label={__('Display & Template Options', 'orbitools')}
                        resetAll={() => {
                            updateAttribute('layout', QUERY_DEFAULTS.layout);
                            updateAttribute('gridColumns', QUERY_DEFAULTS.gridColumns);
                            updateAttribute('template', QUERY_DEFAULTS.template);
                            updateAttribute('messageTemplate', QUERY_DEFAULTS.messageTemplate);
                            updateAttribute('sortBy', QUERY_DEFAULTS.sortBy);
                            updateAttribute('sortOrder', QUERY_DEFAULTS.sortOrder);
                            updateAttribute('filterTaxonomies', QUERY_DEFAULTS.filterTaxonomies);
                            updateAttribute('filterArchives', QUERY_DEFAULTS.filterArchives);
                        }}
                        panelId="results-settings-panel"
                    >
                    {/* Layout Controls */}
                    <ToolsPanelItem
                        hasValue={() =>
                            hasNonDefaultValue('layout', QUERY_DEFAULTS.layout) ||
                            hasNonDefaultValue('gridColumns', QUERY_DEFAULTS.gridColumns) ||
                            hasNonDefaultValue('template', QUERY_DEFAULTS.template)
                        }
                        isShownByDefault={false}
                        label={__('Layout', 'orbitools')}
                        onDeselect={() => {
                            updateAttribute('layout', QUERY_DEFAULTS.layout);
                            updateAttribute('gridColumns', QUERY_DEFAULTS.gridColumns);
                            updateAttribute('template', QUERY_DEFAULTS.template);
                        }}
                        panelId="results-settings-panel"
                    >
                        <VStack spacing={4}>
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

                            {layout === 'grid' && (
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
                            )}
                        </VStack>
                    </ToolsPanelItem>

                    {/* Template Control */}
                    <ToolsPanelItem
                        hasValue={() =>
                            hasNonDefaultValue('template', QUERY_DEFAULTS.template)
                        }
                        isShownByDefault={false}
                        label={__('Template', 'orbitools')}
                        onDeselect={() => {
                            updateAttribute('template', QUERY_DEFAULTS.template);
                        }}
                        panelId="results-settings-panel"
                    >
                        <VStack spacing={4}>
                            <QueryTemplateControl
                                layout={layout}
                                template={template}
                                onChange={(value) => updateAttribute('template', value)}
                            />
                        </VStack>
                    </ToolsPanelItem>

                    {/* Message Template Control */}
                    <ToolsPanelItem
                        hasValue={() =>
                            hasNonDefaultValue('messageTemplate', QUERY_DEFAULTS.messageTemplate)
                        }
                        isShownByDefault={false}
                        label={__('Message Template', 'orbitools')}
                        onDeselect={() => {
                            updateAttribute('messageTemplate', QUERY_DEFAULTS.messageTemplate);
                        }}
                        panelId="results-settings-panel"
                    >
                        <VStack spacing={4}>
                            <MessageTemplateControl
                                messageTemplate={messageTemplate}
                                onChange={(value) => updateAttribute('messageTemplate', value)}
                            />
                        </VStack>
                    </ToolsPanelItem>

                    {/* Frontend Sorting Controls */}
                    <ToolsPanelItem
                        hasValue={() =>
                            hasNonDefaultValue('sortBy', [])
                        }
                        isShownByDefault={false}
                        label={__('Frontend Sorting Controls', 'orbitools')}
                        onDeselect={() => {
                            updateAttribute('sortBy', []);
                        }}
                        panelId="results-settings-panel"
                    >
                        <VStack spacing={4}>
                            <FormTokenDropdown
                                label={__('Available Sort Options', 'orbitools')}
                                help={__('Which sorting buttons/dropdown options to display on the frontend for users to interact with', 'orbitools')}
                                value={sortBy || []}
                                suggestions={[
                                    'title',
                                    'date',
                                    'modified',
                                    'menu_order',
                                    'author',
                                    'name',
                                    'comment_count',
                                    'relevance',
                                    'rand',
                                    'post__in'
                                ]}
                                onChange={(tokens) => updateAttribute('sortBy', tokens)}
                                placeholder={__('Select frontend sort options...', 'orbitools')}
                            />
                        </VStack>
                    </ToolsPanelItem>

                    {/* Frontend Filtering Controls */}
                    <ToolsPanelItem
                        hasValue={() =>
                            hasNonDefaultValue('enableTaxonomyFilters', false) ||
                            hasNonDefaultValue('enableDateFilter', false) ||
                            hasNonDefaultValue('enableAuthorFilter', false)
                        }
                        isShownByDefault={false}
                        label={__('Frontend Filtering Controls', 'orbitools')}
                        onDeselect={() => {
                            updateAttribute('enableTaxonomyFilters', false);
                            updateAttribute('enableDateFilter', false);
                            updateAttribute('enableAuthorFilter', false);
                            updateAttribute('taxonomyFilterType', 'dropdown');
                        }}
                        panelId="results-settings-panel"
                    >
                        <VStack spacing={4}>
                            {/* Enable Taxonomy Filters */}
                            <ToggleControl
                                label={__('Enable Taxonomy Filters', 'orbitools')}
                                help={__('Show taxonomy-based filtering options on the frontend (categories, tags, custom taxonomies)', 'orbitools')}
                                checked={enableTaxonomyFilters || false}
                                onChange={(value) => updateAttribute('enableTaxonomyFilters', value)}
                                __nextHasNoMarginBottom={true}
                            />

                            {/* Taxonomy Filter Control Type - Show when enabled */}
                            {enableTaxonomyFilters && (
                                <SelectControl
                                    label={__('Taxonomy Filter Display Type', 'orbitools')}
                                    help={__('How taxonomy filters should be displayed to users', 'orbitools')}
                                    value={taxonomyFilterType || 'dropdown'}
                                    options={[
                                        { label: __('Dropdown Select', 'orbitools'), value: 'dropdown' },
                                        { label: __('Checkboxes', 'orbitools'), value: 'checkboxes' },
                                        { label: __('Multi-Select', 'orbitools'), value: 'multiselect' }
                                    ]}
                                    onChange={(value) => updateAttribute('taxonomyFilterType', value)}
                                    __nextHasNoMarginBottom={true}
                                />
                            )}

                            {/* Enable Date Filter */}
                            <ToggleControl
                                label={__('Enable Date Filter', 'orbitools')}
                                help={__('Show date-based filtering options on the frontend (year, month, date ranges)', 'orbitools')}
                                checked={enableDateFilter || false}
                                onChange={(value) => updateAttribute('enableDateFilter', value)}
                                __nextHasNoMarginBottom={true}
                            />

                            {/* Enable Author Filter */}
                            <ToggleControl
                                label={__('Enable Author Filter', 'orbitools')}
                                help={__('Show author-based filtering options on the frontend', 'orbitools')}
                                checked={enableAuthorFilter || false}
                                onChange={(value) => updateAttribute('enableAuthorFilter', value)}
                                __nextHasNoMarginBottom={true}
                            />
                        </VStack>
                    </ToolsPanelItem>
                    </ToolsPanel>
                </PanelBody>
            </InspectorControls>

            {/* Advanced Controls - Query ID for filter targeting */}
            <InspectorAdvancedControls>
                <TextControl
                    label={__('Query ID', 'orbitools')}
                    help={__('Optional identifier for targeting this query with PHP filters (orbitools/query_loop/custom_query_args or orbitools/query_loop/inherit_query_args). Use lowercase letters, numbers, and hyphens.', 'orbitools')}
                    value={attributes.queryId || ''}
                    onChange={(value) => setAttributes({ queryId: value.toLowerCase().replace(/[^a-z0-9-]/g, '-') })}
                    placeholder={__('e.g., upcoming-events', 'orbitools')}
                />
            </InspectorAdvancedControls>
        </Fragment>
    );
}
