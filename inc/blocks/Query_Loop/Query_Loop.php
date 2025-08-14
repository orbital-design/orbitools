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
        // Extract query parameters with defaults
        $query_parameters = $attributes['queryParameters'] ?? [];
        $query_type = $query_parameters['type'] ?? 'inherit';
        
        // Build the query based on type
        if ($query_type === 'inherit') {
            $query_args = $this->build_inherit_query($block);
        } else {
            $query_args = $this->build_custom_query($query_parameters['args'] ?? []);
        }
        
        // Debug logging
        if (\defined('WP_DEBUG') && WP_DEBUG) {
            \error_log('Orbitools Query Loop - Type: ' . $query_type);
            \error_log('Orbitools Query Loop - Args: ' . print_r($query_args, true));
        }
        
        // Execute the query
        $query_result = $this->execute_query($query_args);
        
        // Generate unique query ID for this block instance
        $query_id = 'query-' . \wp_unique_id();
        
        // Render the results
        $html = $this->render_query_results($query_result, $query_parameters, $query_id);
        
        return $html;
    }


    /**
     * Build query arguments for inherit type (uses block context)
     *
     * @param \WP_Block $block Block instance with context
     * @return array WP_Query arguments
     */
    private function build_inherit_query(\WP_Block $block): array
    {
        $context = $block->context ?? [];
        
        // If no context is available, fall back to page query
        if (empty($context)) {
            return [
                'post_type' => 'page',
                'posts_per_page' => 10,
                'post_status' => 'publish',
                'no_found_rows' => false,
                'orderby' => 'date',
                'order' => 'DESC'
            ];
        }
        
        // Default inherit query - uses global query context from parent blocks
        $query_args = [
            'post_type' => $context['postType'] ?? 'page',
            'posts_per_page' => isset($context['query']['perPage']) ? $context['query']['perPage'] : 10,
            'post_status' => 'publish',
            'no_found_rows' => false, // Enable pagination
        ];

        // Apply inherited query modifications from context
        if (isset($context['query'])) {
            $inherit_query = $context['query'];
            
            // Handle inherited pagination
            if (isset($inherit_query['offset'])) {
                $query_args['offset'] = $inherit_query['offset'];
            }
            
            // Handle inherited ordering
            if (isset($inherit_query['orderBy'])) {
                $query_args['orderby'] = $inherit_query['orderBy'];
            }
            
            if (isset($inherit_query['order'])) {
                $query_args['order'] = $inherit_query['order'];
            }
            
            // Handle inherited post inclusion/exclusion
            if (!empty($inherit_query['exclude'])) {
                $query_args['post__not_in'] = $inherit_query['exclude'];
            }
            
            if (!empty($inherit_query['include'])) {
                $query_args['post__in'] = $inherit_query['include'];
            }
        }

        /**
         * Filter inherit query arguments
         * 
         * @param array $query_args WP_Query arguments
         * @param array $context Block context
         */
        return \apply_filters('orbitools/query_loop/inherit_query_args', $query_args, $context);
    }

    /**
     * Build query arguments for custom type (uses block attributes)
     *
     * @param array $args Custom query arguments from block attributes
     * @return array WP_Query arguments
     */
    private function build_custom_query(array $args): array
    {
        // If no args provided, create a basic page query
        if (empty($args)) {
            return [
                'post_type' => 'page',
                'posts_per_page' => 10,
                'post_status' => 'publish',
                'no_found_rows' => false,
                'orderby' => 'date',
                'order' => 'DESC'
            ];
        }
        
        // Start with basic query structure
        $query_args = [
            'post_status' => $args['postStatus'] ?? ['publish'],
            'no_found_rows' => false, // Enable pagination
        ];

        // Handle post types
        if (!empty($args['postTypes'])) {
            // Fix any legacy 'pages' values to 'page'
            $post_types = array_map(function($type) {
                return $type === 'pages' ? 'page' : $type;
            }, $args['postTypes']);
            $query_args['post_type'] = $post_types;
        } else {
            $query_args['post_type'] = 'page';
        }

        // Handle posts per page and pagination
        if (isset($args['noPaging']) && $args['noPaging']) {
            $query_args['nopaging'] = true;
        } else {
            $query_args['posts_per_page'] = $args['postsPerPage'] ?? 10;
        }

        // Handle offset
        if (isset($args['offset']) && $args['offset'] > 0) {
            $query_args['offset'] = $args['offset'];
        }

        // Handle ordering
        if (isset($args['orderby'])) {
            $query_args['orderby'] = $args['orderby'];
        }
        
        if (isset($args['order'])) {
            $query_args['order'] = $args['order'];
        }

        // Handle search keyword
        if (!empty($args['searchKeyword'])) {
            $query_args['s'] = \sanitize_text_field($args['searchKeyword']);
        }

        // Handle specific post
        if (isset($args['specificPost']) && $args['specificPost'] > 0) {
            $query_args['p'] = $args['specificPost'];
            $query_args['posts_per_page'] = 1;
        }

        // Handle post inclusion
        if (!empty($args['includePosts'])) {
            $query_args['post__in'] = array_map('intval', $args['includePosts']);
        }

        // Handle post exclusion
        if (!empty($args['excludePosts'])) {
            $query_args['post__not_in'] = array_map('intval', $args['excludePosts']);
        }

        // Handle parent posts only
        if (isset($args['parentPostsOnly']) && $args['parentPostsOnly']) {
            $query_args['post_parent'] = 0;
        }

        // Handle children of specific posts
        if (!empty($args['childrenOfPosts'])) {
            $query_args['post_parent__in'] = array_map('intval', $args['childrenOfPosts']);
        }

        // Handle meta query
        if (!empty($args['meta_query']['queries'])) {
            $query_args['meta_query'] = [
                'relation' => $args['meta_query']['relation'] ?? 'AND'
            ];
            
            foreach ($args['meta_query']['queries'] as $meta_query) {
                if (!empty($meta_query['key'])) {
                    $meta_condition = [
                        'key' => \sanitize_text_field($meta_query['key']),
                        'compare' => $meta_query['compare'] ?? '='
                    ];
                    
                    if (isset($meta_query['value']) && $meta_query['value'] !== '') {
                        $meta_condition['value'] = \sanitize_text_field($meta_query['value']);
                    }
                    
                    if (isset($meta_query['type'])) {
                        $meta_condition['type'] = $meta_query['type'];
                    }
                    
                    $query_args['meta_query'][] = $meta_condition;
                }
            }
        }

        // Handle taxonomy query
        if (!empty($args['tax_query']['queries'])) {
            $query_args['tax_query'] = [
                'relation' => $args['tax_query']['relation'] ?? 'AND'
            ];
            
            foreach ($args['tax_query']['queries'] as $tax_query) {
                if (!empty($tax_query['taxonomy']) && !empty($tax_query['terms'])) {
                    $tax_condition = [
                        'taxonomy' => \sanitize_text_field($tax_query['taxonomy']),
                        'field' => $tax_query['field'] ?? 'term_id',
                        'terms' => $tax_query['terms'],
                        'operator' => $tax_query['operator'] ?? 'IN'
                    ];
                    
                    $query_args['tax_query'][] = $tax_condition;
                }
            }
        }

        /**
         * Filter custom query arguments
         * 
         * @param array $query_args WP_Query arguments
         * @param array $args Original custom arguments
         */
        return \apply_filters('orbitools/query_loop/custom_query_args', $query_args, $args);
    }

    /**
     * Execute the query and handle errors
     *
     * @param array $query_args WP_Query arguments
     * @return \WP_Query|null Query result or null on error
     */
    private function execute_query(array $query_args): ?\WP_Query
    {
        try {
            $query = new \WP_Query($query_args);
            
            // Log query for debugging if WP_DEBUG is enabled
            if (\defined('WP_DEBUG') && WP_DEBUG) {
                \error_log('Orbitools Query Loop SQL: ' . $query->request);
            }
            
            return $query;
        } catch (\Exception $e) {
            // Log error
            \error_log('Orbitools Query Loop Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Render query results as HTML
     *
     * @param \WP_Query|null $query Query result
     * @param array $query_parameters Original query parameters
     * @param string $query_id Unique query ID
     * @return string Rendered HTML
     */
    private function render_query_results(?\WP_Query $query, array $query_parameters, string $query_id): string
    {
        // Get wrapper attributes
        $wrapper_attributes = \get_block_wrapper_attributes([
            'class' => 'orb-query-loop',
            'data-query-id' => $query_id
        ]);

        // Handle query errors
        if (!$query || !$query->have_posts()) {
            $debug_info = '';
            if (\defined('WP_DEBUG') && WP_DEBUG && $query) {
                $debug_info = sprintf(
                    '<div style="background: #f0f0f0; padding: 10px; margin: 10px 0; font-family: monospace; font-size: 12px;"><strong>Debug Info:</strong><br>Found Posts: %d<br>SQL: %s</div>',
                    $query->found_posts,
                    \esc_html($query->request)
                );
            }
            
            return sprintf(
                '<div %s><p class="orb-query-loop__no-results">%s</p>%s</div>',
                $wrapper_attributes,
                \esc_html__('No posts found.', 'orbitools'),
                $debug_info
            );
        }

        // Start building HTML
        $html = sprintf('<div %s>', $wrapper_attributes);
        
        // Add layout classes based on display settings
        $display = $query_parameters['display'] ?? [];
        $layout = $display['layout'] ?? [];
        $layout_type = $layout['type'] ?? 'grid';
        
        if ($layout_type === 'grid') {
            $grid_columns = $layout['gridColumns'] ?? '3';
            $html .= sprintf('<div class="orb-query-loop__grid orb-query-loop__grid--cols-%s">', \esc_attr($grid_columns));
        } else {
            $html .= '<div class="orb-query-loop__list">';
        }

        // Loop through posts
        while ($query->have_posts()) {
            $query->the_post();
            $html .= $this->render_post_item(get_post(), $layout_type);
        }
        
        // Reset post data
        \wp_reset_postdata();
        
        // Close layout container
        $html .= '</div>';
        
        // Add pagination if needed
        if (!isset($query_parameters['args']['noPaging']) || !$query_parameters['args']['noPaging']) {
            $html .= $this->render_pagination($query);
        }
        
        // Close main container
        $html .= '</div>';

        return $html;
    }

    /**
     * Render individual post item
     *
     * @param \WP_Post $post Post object
     * @param string $layout_type Layout type (grid/list)
     * @return string Rendered post HTML
     */
    private function render_post_item(\WP_Post $post, string $layout_type): string
    {
        $html = sprintf('<article class="orb-query-loop__item orb-query-loop__item--%s" data-post-id="%d">', 
            \esc_attr($layout_type),
            $post->ID
        );
        
        // Basic post content - can be enhanced later
        $html .= sprintf(
            '<h3 class="orb-query-loop__title"><a href="%s">%s</a></h3>',
            \esc_url(\get_permalink($post->ID)),
            \esc_html(\get_the_title($post->ID))
        );
        
        $html .= sprintf(
            '<div class="orb-query-loop__excerpt">%s</div>',
            \wp_kses_post(\get_the_excerpt($post->ID))
        );
        
        $html .= sprintf(
            '<div class="orb-query-loop__meta">%s</div>',
            \esc_html(\get_the_date('', $post->ID))
        );
        
        $html .= '</article>';
        
        return $html;
    }

    /**
     * Render pagination controls
     *
     * @param \WP_Query $query Query object
     * @return string Pagination HTML
     */
    private function render_pagination(\WP_Query $query): string
    {
        if ($query->max_num_pages <= 1) {
            return '';
        }

        $pagination = \paginate_links([
            'total' => $query->max_num_pages,
            'current' => max(1, \get_query_var('paged')),
            'prev_text' => \__('&laquo; Previous', 'orbitools'),
            'next_text' => \__('Next &raquo;', 'orbitools'),
            'type' => 'array'
        ]);

        if (!$pagination) {
            return '';
        }

        $html = '<nav class="orb-query-loop__pagination" aria-label="' . \esc_attr__('Query results pagination', 'orbitools') . '">';
        $html .= '<ul class="orb-query-loop__pagination-list">';
        
        foreach ($pagination as $page_link) {
            $html .= '<li class="orb-query-loop__pagination-item">' . $page_link . '</li>';
        }
        
        $html .= '</ul></nav>';

        return $html;
    }

    /**
     * Get default settings
     */
    public function get_default_settings(): array
    {
        return [];
    }

    /**
     * Get available taxonomies for frontend filtering
     * 
     * @param array $post_types Selected post types
     * @return array Array of taxonomy options
     */
    public function get_available_taxonomies_for_filtering(array $post_types = []): array
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
         * Filter the available taxonomies for frontend filtering
         * 
         * @param array $taxonomies Array of taxonomy options
         * @param array $post_types Selected post types
         */
        return apply_filters('orbitools_query_loop_filter_taxonomies', $taxonomies, $post_types);
    }
    
    /**
     * Get frontend filter control types
     * 
     * @return array Array of available filter control types
     */
    public function get_frontend_filter_types(): array
    {
        $default_types = [
            'dropdown' => __('Dropdown Select', 'orbitools'),
            'checkboxes' => __('Checkboxes', 'orbitools'),
            'multiselect' => __('Multi-Select', 'orbitools')
        ];
        
        /**
         * Filter the available frontend filter control types
         * 
         * @param array $types Array of filter control type options
         */
        return apply_filters('orbitools_query_loop_frontend_filter_types', $default_types);
    }
}
