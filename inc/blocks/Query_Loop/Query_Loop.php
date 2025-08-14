<?php

namespace Orbitools\Blocks\Query_Loop;

use Orbitools\Core\Abstracts\Module_Base;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Query Loop Block
 *
 * Registers and manages the Query Loop block for creating and running WP_Queries
 */
class Query_Loop extends Module_Base
{
    /**
     * Module version
     */
    protected const VERSION = '1.0.0';

    /**
     * Get the module's unique slug identifier
     */
    public function get_slug(): string
    {
        return 'query-loop-block';
    }

    /**
     * Get the module's display name
     */
    public function get_name(): string
    {
        return \__('Query Loop Block', 'orbitools');
    }

    /**
     * Get the module's description
     */
    public function get_description(): string
    {
        return \__('Flexible query builder for dynamically displaying posts using a rnage of parameters.', 'orbitools');
    }

    /**
     * Get the module's version
     */
    public function get_version(): string
    {
        return self::VERSION;
    }

    /**
     * Check if the module is currently enabled
     */
    public function is_enabled(): bool
    {
        return true;
    }

    /**
     * Initialize the Query Loop block
     */
    public function init(): void
    {
        // Prevent multiple registrations
        static $registered = false;
        if ($registered) {
            return;
        }
        $registered = true;


        // Register immediately if init has already fired, otherwise hook into it
        if (\did_action('init')) {
            $this->register_block();
        } else {
            \add_action('init', [$this, 'register_block']);
        }
    }

    /**
     * Register the Query Loop block
     */
    public function register_block(): void
    {
        $block_dir = ORBITOOLS_DIR . 'build/blocks/query-loop/';

        if (file_exists($block_dir . 'block.json')) {
            \register_block_type($block_dir, [
                'render_callback' => [$this, 'render_callback']
            ]);
        }
    }

    /**
     * Render callback for Query Loop block
     *
     * @param array    $attributes Block attributes
     * @param string   $content    Block inner content
     * @param \WP_Block $block      Block instance
     * @return string  Rendered HTML
     */
    public function render_callback(array $attributes, string $content, \WP_Block $block): string
    {
        // Get wrapper attributes
        $wrapper_attributes = \get_block_wrapper_attributes([
            'class' => 'orb-query-loop'
        ]);

        // For now, just output placeholder content
        return sprintf(
            '<div %s><p>%s</p></div>',
            $wrapper_attributes,
            \esc_html__('Query Content', 'orbitools')
        );
    }


    /**
     * Get default settings
     */
    public function get_default_settings(): array
    {
        return [];
    }

    /**
     * Get available sort fields for the query
     * 
     * @return array Array of sort field options
     */
    public function get_sort_fields(): array
    {
        $default_fields = [
            'title' => __('Title', 'orbitools'),
            'date' => __('Date', 'orbitools'),
            'modified' => __('Modified Date', 'orbitools'),
            'menu_order' => __('Menu Order', 'orbitools'),
            'author' => __('Author', 'orbitools'),
            'name' => __('Slug', 'orbitools'),
            'comment_count' => __('Comment Count', 'orbitools'),
            'relevance' => __('Relevance', 'orbitools'),
            'rand' => __('Random', 'orbitools')
        ];

        /**
         * Filter the available sort fields for query loop blocks
         * 
         * @param array $fields Array of sort field options
         * @param array $context Context information (post_types, etc.)
         */
        return apply_filters('orbitools_query_loop_sort_fields', $default_fields, []);
    }

    /**
     * Get available sort orders for the query
     * 
     * @return array Array of sort order options
     */
    public function get_sort_orders(): array
    {
        $default_orders = [
            'alphabetical-asc' => __('Alphabetical (A-Z)', 'orbitools'),
            'alphabetical-desc' => __('Alphabetical (Z-A)', 'orbitools'),
            'date-newest' => __('Date (Newest First)', 'orbitools'),
            'date-oldest' => __('Date (Oldest First)', 'orbitools'),
            'relevance' => __('Relevance (Search)', 'orbitools'),
            'menu-order' => __('Menu Order', 'orbitools'),
            'random' => __('Random', 'orbitools')
        ];

        /**
         * Filter the available sort orders for query loop blocks
         * 
         * @param array $orders Array of sort order options
         * @param array $context Context information (post_types, etc.)
         */
        return apply_filters('orbitools_query_loop_sort_orders', $default_orders, []);
    }

    /**
     * Get available filter taxonomies for the query
     * 
     * @param array $post_types Selected post types
     * @return array Array of taxonomy options
     */
    public function get_filter_taxonomies(array $post_types = []): array
    {
        $taxonomies = [];
        
        if (empty($post_types)) {
            // Get all public taxonomies
            $all_taxonomies = get_taxonomies(['public' => true], 'objects');
            foreach ($all_taxonomies as $taxonomy) {
                $taxonomies[$taxonomy->name] = $taxonomy->label;
            }
        } else {
            // Get taxonomies for specific post types
            foreach ($post_types as $post_type) {
                $post_type_taxonomies = get_object_taxonomies($post_type, 'objects');
                foreach ($post_type_taxonomies as $taxonomy) {
                    if ($taxonomy->public) {
                        $taxonomies[$taxonomy->name] = $taxonomy->label;
                    }
                }
            }
        }

        /**
         * Filter the available filter taxonomies for query loop blocks
         * 
         * @param array $taxonomies Array of taxonomy options
         * @param array $post_types Selected post types
         * @param array $context Additional context information
         */
        return apply_filters('orbitools_query_loop_filter_taxonomies', $taxonomies, $post_types, []);
    }

    /**
     * Get available archive filters for the query
     * 
     * @return array Array of archive filter options
     */
    public function get_archive_filters(): array
    {
        $default_filters = [
            'category' => __('Category Archives', 'orbitools'),
            'tag' => __('Tag Archives', 'orbitools'),
            'author' => __('Author Archives', 'orbitools'),
            'date' => __('Date Archives', 'orbitools'),
            'custom' => __('Custom Archives', 'orbitools')
        ];

        /**
         * Filter the available archive filters for query loop blocks
         * 
         * @param array $filters Array of archive filter options
         * @param array $context Context information (post_types, etc.)
         */
        return apply_filters('orbitools_query_loop_archive_filters', $default_filters, []);
    }

    /**
     * Process sort parameters and convert to WP_Query args
     * 
     * @param array $sort_by Array of sort fields
     * @param string $sort_order Sort order preference
     * @return array WP_Query compatible sort arguments
     */
    public function process_sort_parameters(array $sort_by, string $sort_order): array
    {
        $query_args = [];

        // Handle sort order first
        switch ($sort_order) {
            case 'alphabetical-asc':
                $query_args['orderby'] = 'title';
                $query_args['order'] = 'ASC';
                break;
            case 'alphabetical-desc':
                $query_args['orderby'] = 'title';
                $query_args['order'] = 'DESC';
                break;
            case 'date-newest':
                $query_args['orderby'] = 'date';
                $query_args['order'] = 'DESC';
                break;
            case 'date-oldest':
                $query_args['orderby'] = 'date';
                $query_args['order'] = 'ASC';
                break;
            case 'relevance':
                $query_args['orderby'] = 'relevance';
                $query_args['order'] = 'DESC';
                break;
            case 'menu-order':
                $query_args['orderby'] = 'menu_order';
                $query_args['order'] = 'ASC';
                break;
            case 'random':
                $query_args['orderby'] = 'rand';
                break;
        }

        // Handle multiple sort fields
        if (!empty($sort_by) && count($sort_by) > 1) {
            $orderby_array = [];
            foreach ($sort_by as $field) {
                $orderby_array[$field] = $query_args['order'] ?? 'DESC';
            }
            $query_args['orderby'] = $orderby_array;
        } elseif (!empty($sort_by)) {
            $query_args['orderby'] = $sort_by[0];
        }

        /**
         * Filter the processed sort parameters
         * 
         * @param array $query_args WP_Query compatible arguments
         * @param array $sort_by Original sort fields
         * @param string $sort_order Original sort order
         */
        return apply_filters('orbitools_query_loop_sort_args', $query_args, $sort_by, $sort_order);
    }

    /**
     * Process date filter parameters and convert to WP_Query date_query args
     * 
     * @param string $date_filter_type Type of date filter
     * @param string $date_filter_year Selected year (if applicable)
     * @param string $date_filter_month Selected month (if applicable)
     * @param array $date_filter_range Date range object with start/end dates
     * @return array WP_Query compatible date_query arguments
     */
    public function process_date_filter_parameters(string $date_filter_type, string $date_filter_year = '', string $date_filter_month = '', array $date_filter_range = []): array
    {
        $date_query = [];

        switch ($date_filter_type) {
            case 'year':
                if (!empty($date_filter_year)) {
                    $date_query = [
                        [
                            'year' => (int) $date_filter_year
                        ]
                    ];
                }
                break;

            case 'month':
                if (!empty($date_filter_year) && !empty($date_filter_month)) {
                    $date_query = [
                        [
                            'year' => (int) $date_filter_year,
                            'month' => (int) $date_filter_month
                        ]
                    ];
                }
                break;

            case 'range':
                if (!empty($date_filter_range['start']) || !empty($date_filter_range['end'])) {
                    $range_query = [];
                    
                    if (!empty($date_filter_range['start'])) {
                        $range_query['after'] = $date_filter_range['start'];
                    }
                    
                    if (!empty($date_filter_range['end'])) {
                        $range_query['before'] = $date_filter_range['end'];
                    }
                    
                    $range_query['inclusive'] = true;
                    $date_query = [$range_query];
                }
                break;

            case 'last_30_days':
                $date_query = [
                    [
                        'after' => '30 days ago'
                    ]
                ];
                break;

            case 'last_3_months':
                $date_query = [
                    [
                        'after' => '3 months ago'
                    ]
                ];
                break;

            case 'last_6_months':
                $date_query = [
                    [
                        'after' => '6 months ago'
                    ]
                ];
                break;

            case 'last_year':
                $date_query = [
                    [
                        'after' => '1 year ago'
                    ]
                ];
                break;
        }

        /**
         * Filter the processed date filter parameters
         * 
         * @param array $date_query WP_Query compatible date_query arguments
         * @param string $date_filter_type Original date filter type
         * @param string $date_filter_year Original year filter
         * @param string $date_filter_month Original month filter
         * @param array $date_filter_range Original date range filter
         */
        return apply_filters('orbitools_query_loop_date_filter_args', $date_query, $date_filter_type, $date_filter_year, $date_filter_month, $date_filter_range);
    }

    /**
     * Get available date filter types
     * 
     * @return array Array of date filter type options
     */
    public function get_date_filter_types(): array
    {
        $default_types = [
            'none' => __('No Date Filter', 'orbitools'),
            'year' => __('Specific Year', 'orbitools'),
            'month' => __('Specific Month', 'orbitools'),
            'range' => __('Date Range', 'orbitools'),
            'last_30_days' => __('Last 30 Days', 'orbitools'),
            'last_3_months' => __('Last 3 Months', 'orbitools'),
            'last_6_months' => __('Last 6 Months', 'orbitools'),
            'last_year' => __('Last Year', 'orbitools')
        ];

        /**
         * Filter the available date filter types for query loop blocks
         * 
         * @param array $types Array of date filter type options
         * @param array $context Context information (post_types, etc.)
         */
        return apply_filters('orbitools_query_loop_date_filter_types', $default_types, []);
    }
}
