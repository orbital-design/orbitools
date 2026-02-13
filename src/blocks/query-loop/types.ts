/**
 * Query Loop Block Type Definitions
 * 
 * Shared TypeScript interfaces and types for the Query Loop block.
 * This ensures consistency between edit.tsx, controls.tsx, and other components.
 * 
 * @file blocks/query-loop/types.ts
 * @since 1.0.0
 */

/**
 * Query Loop Block Attributes Interface
 * 
 * Defines the structure of attributes for the Query Loop block.
 * This interface matches the structure defined in block.json and 
 * handles optional values properly for WordPress block attributes.
 * 
 * All nested properties are optional because WordPress may not
 * initialize them until the user interacts with controls.
 */
export interface QueryLoopAttributes {
    queryId?: string;
    queryParameters?: {
        type?: string;
        args?: {
            postTypes?: string[];
            postStatus?: string[];
            orderby?: string;
            order?: string;
            postsPerPage?: number;
            noPaging?: boolean;
            paged?: boolean;
            paginationType?: string;
            offset?: number;
            searchKeyword?: string;
            specificPost?: number;
            includePosts?: string[];
            excludePosts?: string[];
            parentPostsOnly?: boolean;
            childrenOfPosts?: string[];
            meta_query?: {
                relation?: string;
                queries?: Array<{
                    key: string;
                    value: string;
                    compare: string;
                }>;
            };
            tax_query?: {
                relation?: string;
                queries?: Array<{
                    taxonomy: string;
                    terms: string[];
                    operator: string;
                }>;
            };
        };
        display?: {
            layout?: {
                type?: string;
                gridColumns?: string;
            };
            template?: string;
            messageTemplate?: string;
            sorting?: {
                enableSortControls?: boolean;
                availableSortOptions?: string[];
            };
            filtering?: {
                enableTaxonomyFilters?: boolean;
                enableDateFilter?: boolean;
                enableAuthorFilter?: boolean;
                taxonomyFilterType?: string;
            };
        };
    };
}

/**
 * Props interface for QueryLoopControls component
 */
export interface QueryLoopControlsProps {
    attributes: QueryLoopAttributes;
    setAttributes: (attributes: Partial<QueryLoopAttributes>) => void;
}

/**
 * Default values for query controls
 * 
 * These defaults match the structure in block.json and provide
 * fallback values when attributes are undefined.
 */
export const QUERY_DEFAULTS = {
    queryType: 'custom',
    postTypes: [] as string[],
    postStatus: [] as string[],
    orderby: '',
    order: '',
    postsPerPage: undefined as number | undefined,
    offset: 0,
    noPaging: false,
    paged: false,
    paginationType: 'pages',
    searchKeyword: '',
    metaQuery: [] as Array<{ key: string; value: string; compare: string; }>,
    metaQueryRelation: 'AND',
    taxQuery: [] as Array<{ taxonomy: string; terms: string[]; operator: string; }>,
    taxQueryRelation: 'AND',
    includePosts: [] as string[],
    excludePosts: [] as string[],
    parentPostsOnly: false,
    childrenOfPosts: [] as string[],
    layout: 'grid',
    gridColumns: '3',
    template: 'default',
    messageTemplate: 'default',
    sortBy: [] as string[],
    sortOrder: 'date-newest',
    filterTaxonomies: [] as string[],
    filterArchives: [] as string[],
    dateFilterType: 'none',
    dateFilterYear: '',
    dateFilterMonth: '',
    dateFilterDateRange: {},
    enableTaxonomyFilters: false,
    enableDateFilter: false,
    enableAuthorFilter: false,
    taxonomyFilterType: 'dropdown'
} as const;

/**
 * Type guard to check if queryParameters exists and has the expected structure
 */
export function hasValidQueryParameters(attributes: QueryLoopAttributes): boolean {
    return !!(attributes.queryParameters && typeof attributes.queryParameters === 'object');
}

/**
 * Utility function to safely get query type with fallback
 */
export function getQueryType(attributes: QueryLoopAttributes): string {
    return attributes.queryParameters?.type || QUERY_DEFAULTS.queryType;
}

/**
 * Utility function to safely get layout type with fallback
 */
export function getLayoutType(attributes: QueryLoopAttributes): string {
    return attributes.queryParameters?.display?.layout?.type || QUERY_DEFAULTS.layout;
}

/**
 * Utility function to safely get template with fallback
 */
export function getTemplate(attributes: QueryLoopAttributes): string {
    return attributes.queryParameters?.display?.template || QUERY_DEFAULTS.template;
}

/**
 * Utility function to safely get message template with fallback
 */
export function getMessageTemplate(attributes: QueryLoopAttributes): string {
    return attributes.queryParameters?.display?.messageTemplate || QUERY_DEFAULTS.messageTemplate;
}