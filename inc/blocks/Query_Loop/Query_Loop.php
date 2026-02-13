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

        // Load built-in templates
        $this->register_builtin_templates();

        // Register immediately if init has already fired, otherwise hook into it
        if (\did_action('init')) {
            $this->register_block();
            $this->register_rest_endpoints();
        } else {
            \add_action('init', [$this, 'register_block']);
            \add_action('rest_api_init', [$this, 'register_rest_endpoints']);
        }
    }

    /**
     * Register built-in template function
     */
    private function register_builtin_templates(): void
    {
        // Built-in default template function is handled directly in render_with_template
        // No need to register a global function
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

        // Get the user-defined query ID for filter targeting (empty string if not set)
        $user_query_id = $attributes['queryId'] ?? '';

        // Build the query based on type
        if ($query_type === 'inherit') {
            $query_args = $this->build_inherit_query($block, $user_query_id);
        } else {
            $query_args = $this->build_custom_query($query_parameters['args'] ?? [], $user_query_id);
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
     * @param string $query_id User-defined query ID for filter targeting
     * @return array WP_Query arguments
     */
    private function build_inherit_query(\WP_Block $block, string $query_id = ''): array
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
        $paged = \get_query_var('paged') ? \get_query_var('paged') : (\get_query_var('page') ? \get_query_var('page') : 1);

        $query_args = [
            'post_type' => $context['postType'] ?? 'page',
            'posts_per_page' => isset($context['query']['perPage']) ? $context['query']['perPage'] : 10,
            'post_status' => 'publish',
            'no_found_rows' => false, // Enable pagination
            'paged' => $paged,
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
         * @param string $query_id User-defined query ID for targeting specific queries
         */
        return \apply_filters('orbitools/query_loop/inherit_query_args', $query_args, $context, $query_id);
    }

    /**
     * Build query arguments for custom type (uses block attributes)
     *
     * @param array $args Custom query arguments from block attributes
     * @param string $query_id User-defined query ID for filter targeting
     * @return array WP_Query arguments
     */
    private function build_custom_query(array $args, string $query_id = ''): array
    {
        // If no args provided, return special flag to show "no parameters" message
        if (empty($args)) {
            return ['__no_parameters_set__' => true];
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
            // If no post types specified, use 'any' to avoid WordPress defaulting to 'post'
            $query_args['post_type'] = 'any';
        }

        // Handle posts per page and pagination
        if (isset($args['noPaging']) && $args['noPaging']) {
            $query_args['nopaging'] = true;
        } else {
            $query_args['posts_per_page'] = $args['postsPerPage'] ?? 10;

            // Set current page for pagination
            if (!empty($args['paged'])) {
                $paged = \get_query_var('paged') ? \get_query_var('paged') : (\get_query_var('page') ? \get_query_var('page') : 1);
                $query_args['paged'] = $paged;
            }
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
         * @param string $query_id User-defined query ID for targeting specific queries
         */
        return \apply_filters('orbitools/query_loop/custom_query_args', $query_args, $args, $query_id);
    }

    /**
     * Execute the query and handle errors
     *
     * @param array $query_args WP_Query arguments
     * @return \WP_Query|null Query result or null on error
     */
    private function execute_query(array $query_args): ?\WP_Query
    {
        // Check for special "no parameters" flag
        if (isset($query_args['__no_parameters_set__'])) {
            return null;
        }
        
        try {
            $query = new \WP_Query($query_args);
            
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
        // Get wrapper attributes and filter out WordPress block classes
        $wrapper_attributes = \get_block_wrapper_attributes();
        
        // Extract class names from the wrapper attributes string
        $existing_classes = '';
        if (preg_match('/class=["\']([^"\']*)["\']/', $wrapper_attributes, $matches)) {
            $existing_classes = $matches[1];
        }
        
        // Remove unwanted WordPress block classes while keeping useful ones
        $filtered_classes = $this->filter_wordpress_classes($existing_classes, ['wp-block-orb-query-loop']);
        
        // Combine our BEM base class with filtered WordPress classes
        $final_classes = trim('orb-query-loop ' . $filtered_classes);
        
        // Replace the class attribute in wrapper attributes with our cleaned version
        $wrapper_attributes = preg_replace('/class=["\'][^"\']*["\']/', 'class="' . \esc_attr($final_classes) . '"', $wrapper_attributes);
        
        // Add data-query-id if not present
        if (strpos($wrapper_attributes, 'data-query-id') === false) {
            $wrapper_attributes .= ' data-query-id="' . \esc_attr($query_id) . '"';
        }

        // Handle different empty states
        if (!$query || !$query->have_posts()) {
            // Check if this is a "no parameters set" situation (custom query with no args)
            $query_type = $query_parameters['type'] ?? 'inherit';
            $query_args = $query_parameters['args'] ?? [];
            $is_no_parameters = ($query_type === 'custom' && empty($query_args));
            
            if ($is_no_parameters) {
                // No parameters set - show friendly setup message
                $default_message = '<div class="orb-query-loop__no-parameters">' .
                    '<h3>' . \esc_html__('Ready to build your query!', 'orbitools') . '</h3>' .
                    '<p>' . \esc_html__('No query parameters have been set yet. Use the Query Builder panel in the editor to configure what content you\'d like to display.', 'orbitools') . '</p>' .
                    '</div>';
                
                /**
                 * Filter the "no parameters set" message for Query Loop block
                 * 
                 * @param string $message HTML message to display
                 * @param array $query_parameters The query parameters (empty in this case)
                 * @param string $query_id Unique query ID for this block instance
                 */
                $message = \apply_filters('orbitools/query_loop/no_parameters_message', $default_message, $query_parameters, $query_id);
                
                // Try template-based message system
                $message = $this->render_with_message_template('no_parameters', $message, $query_parameters, $query_id);
                
                return sprintf('<div %s>%s</div>', $wrapper_attributes, $message);
            } else {
                // Query ran but no posts found
                $default_message = '<p class="orb-query-loop__no-results">' . 
                    \esc_html__('No posts found matching your query parameters.', 'orbitools') . 
                    '</p>';
                
                /**
                 * Filter the "no posts found" message for Query Loop block
                 * 
                 * @param string $message HTML message to display  
                 * @param array $query_parameters The query parameters that were used
                 * @param string $query_id Unique query ID for this block instance
                 * @param \WP_Query|null $query The executed query object
                 */
                $message = \apply_filters('orbitools/query_loop/no_results_message', $default_message, $query_parameters, $query_id, $query);
                
                // Try template-based message system
                $message = $this->render_with_message_template('no_results', $message, $query_parameters, $query_id, $query);
                
                return sprintf('<div %s>%s</div>', $wrapper_attributes, $message);
            }
        }

        // Start building HTML
        $html = sprintf('<div %s>', $wrapper_attributes);
        
        // Add results wrapper with layout data attributes
        $display = $query_parameters['display'] ?? [];
        $layout = $display['layout'] ?? [];
        $layout_type = $layout['type'] ?? 'grid';
        $grid_columns = $layout['gridColumns'] ?? '3';
        
        // Build results wrapper with data attributes
        $results_attrs = sprintf(
            'class="orb-query-loop__results" data-layout="%s"',
            \esc_attr($layout_type)
        );
        
        // Add columns data attribute only for grid layout
        if ($layout_type === 'grid') {
            $results_attrs .= sprintf(' data-cols="%s"', \esc_attr($grid_columns));
        }
        
        $html .= sprintf('<div %s>', $results_attrs);

        // Get selected template
        $selected_template = $query_parameters['display']['template'] ?? 'default';
        
        // Ensure template is a string for array key usage
        if (!is_string($selected_template) && !is_numeric($selected_template)) {
            $selected_template = 'default';
        }
        
        $available_templates = $this->get_available_templates($layout_type);
        
        // Ensure selected template exists, fallback to default
        if (!isset($available_templates[$selected_template])) {
            $selected_template = 'default';
        }
        

        // Loop through posts
        while ($query->have_posts()) {
            $query->the_post();
            $html .= $this->render_post_item(get_post(), $layout_type, $selected_template, $grid_columns);
        }
        
        // Reset post data
        \wp_reset_postdata();
        
        // Close results container
        $html .= '</div>';
        
        // Add pagination if needed - check both noPaging and paged settings
        $noPaging = $query_parameters['args']['noPaging'] ?? false;
        $paged = $query_parameters['args']['paged'] ?? false;
        $pagination_type = $query_parameters['args']['paginationType'] ?? 'pages';

        if (!$noPaging && $paged) {
            if ($pagination_type === 'load-more') {
                $html .= $this->render_load_more($query, $query_parameters);
            } else {
                $html .= $this->render_pagination($query);
            }
        }
        
        // Close main container
        $html .= '</div>';

        return $html;
    }

    /**
     * Render individual post item using template
     *
     * @param \WP_Post $post Post object
     * @param string $layout_type Layout type (grid/list)
     * @param string $template_key Template key
     * @param string $columns Number of columns (for grid layouts)
     * @return string Rendered post HTML
     */
    private function render_post_item(\WP_Post $post, string $layout_type, string $template_key, string $columns = '3'): string
    {
        
        // Use template function if available
        return $this->render_with_template($post, $layout_type, $template_key, $columns);
    }

    /**
     * Built-in default template function
     *
     * @param \WP_Post $post Post object
     * @param string $layout_type Layout type
     * @param string $columns Number of columns (for grid layouts)
     * @return string Rendered HTML
     */
    private function render_default_template(\WP_Post $post, string $layout_type, string $columns = '3'): string
    {
        $html = '<article class="orb-query-loop__item orb-query-loop__item--' . esc_attr($layout_type) . '" data-post-id="' . $post->ID . '" data-template="default">';
        
        // Featured image
        if (has_post_thumbnail($post->ID)) {
            $html .= '<div class="orb-query-loop__featured-image">';
            $html .= '<a href="' . esc_url(get_permalink($post->ID)) . '" aria-label="' . esc_attr(sprintf(__('Read more about %s', 'orbitools'), get_the_title($post->ID))) . '">';
            $html .= get_the_post_thumbnail($post->ID, 'medium', ['class' => 'orb-query-loop__image']);
            $html .= '</a>';
            $html .= '</div>';
        }
        
        $html .= '<div class="orb-query-loop__content">';
        $html .= '<header class="orb-query-loop__header">';
        
        // Title
        $html .= '<h3 class="orb-query-loop__title">';
        $html .= '<a href="' . esc_url(get_permalink($post->ID)) . '">';
        $html .= esc_html(get_the_title($post->ID));
        $html .= '</a>';
        $html .= '</h3>';
        
        // Meta
        $html .= '<div class="orb-query-loop__meta">';
        $html .= '<time class="orb-query-loop__date" datetime="' . esc_attr(get_the_date('c', $post->ID)) . '">';
        $html .= esc_html(get_the_date('', $post->ID));
        $html .= '</time>';
        
        if (get_post_type($post->ID) === 'post') {
            $html .= '<span class="orb-query-loop__author">';
            $html .= esc_html(sprintf(__('by %s', 'orbitools'), get_the_author_meta('display_name', $post->post_author)));
            $html .= '</span>';
        }
        
        $html .= '</div>'; // meta
        $html .= '</header>'; // header
        
        // Excerpt
        if (has_excerpt($post->ID) || $post->post_content) {
            $html .= '<div class="orb-query-loop__excerpt">';
            $html .= wp_kses_post(get_the_excerpt($post->ID));
            $html .= '</div>';
        }
        
        // Categories for posts
        if (get_post_type($post->ID) === 'post') {
            $categories = get_the_category($post->ID);
            if (!empty($categories)) {
                $html .= '<div class="orb-query-loop__categories">';
                foreach ($categories as $category) {
                    $html .= '<span class="orb-query-loop__category">';
                    $html .= '<a href="' . esc_url(get_category_link($category->term_id)) . '">';
                    $html .= esc_html($category->name);
                    $html .= '</a>';
                    $html .= '</span>';
                }
                $html .= '</div>';
            }
        }
        
        // Footer
        $html .= '<footer class="orb-query-loop__footer">';
        $html .= '<a href="' . esc_url(get_permalink($post->ID)) . '" class="orb-query-loop__read-more">';
        $html .= esc_html__('Read More', 'orbitools');
        $html .= '</a>';
        $html .= '</footer>';
        
        $html .= '</div>'; // content
        $html .= '</article>';
        
        return $html;
    }

    /**
     * Render post item using template function
     *
     * @param \WP_Post $post Post object
     * @param string $layout_type Layout type
     * @param string $template_key Template key
     * @param string $columns Number of columns (for grid layouts)
     * @return string Rendered HTML
     */
    private function render_with_template(\WP_Post $post, string $layout_type, string $template_key, string $columns = '3'): string
    {
        // Get available templates for this layout
        $available_templates = $this->get_available_templates($layout_type);
        
        // Check if template exists and has a callback
        if (isset($available_templates[$template_key]) && isset($available_templates[$template_key]['callback'])) {
            $callback = $available_templates[$template_key]['callback'];
            
            // Call the template callback
            if (is_callable($callback)) {
                return \call_user_func($callback, $post, $layout_type, $columns);
            }
        }
        
        // Template not found or callback not callable, fallback to built-in default
        return $this->render_default_template($post, $layout_type, $columns);
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

        $paged = \get_query_var('paged') ? \get_query_var('paged') : (\get_query_var('page') ? \get_query_var('page') : 1);

        $pagination = \paginate_links([
            'total' => $query->max_num_pages,
            'current' => max(1, $paged),
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
     * Render load more button
     *
     * @param \WP_Query $query Query object
     * @param array $query_parameters Full query parameters for the AJAX request
     * @return string Load more button HTML
     */
    private function render_load_more(\WP_Query $query, array $query_parameters): string
    {
        if ($query->max_num_pages <= 1) {
            return '';
        }

        $paged = \get_query_var('paged') ? \get_query_var('paged') : (\get_query_var('page') ? \get_query_var('page') : 1);

        $html = '<div class="orb-query-loop__load-more"';
        $html .= ' data-page="' . \esc_attr($paged) . '"';
        $html .= ' data-max-pages="' . \esc_attr($query->max_num_pages) . '"';
        $html .= ' data-query-params="' . \esc_attr(\wp_json_encode($query_parameters)) . '"';
        $html .= ' data-rest-url="' . \esc_attr(\rest_url('orbitools/v1/query-loop/load-more')) . '"';
        $html .= ' data-nonce="' . \esc_attr(\wp_create_nonce('wp_rest')) . '"';
        $html .= '>';
        $html .= '<button type="button" class="orb-query-loop__load-more-btn">';
        $html .= \esc_html__('Load more', 'orbitools');
        $html .= '</button>';
        $html .= '</div>';

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

    /**
     * Filter out unwanted WordPress block classes
     *
     * @param string $class_string The original class string
     * @param array $classes_to_remove Classes to remove from the string
     * @return string Filtered class string
     */
    private function filter_wordpress_classes(string $class_string, array $classes_to_remove = []): string
    {
        if (empty($class_string)) {
            return '';
        }

        // Split classes into array
        $classes = explode(' ', $class_string);
        
        // Filter out unwanted classes
        $filtered_classes = array_filter($classes, function($class) use ($classes_to_remove) {
            $class = trim($class);
            if (empty($class)) {
                return false;
            }
            
            // Remove specific classes
            if (in_array($class, $classes_to_remove)) {
                return false;
            }
            
            return true;
        });
        
        return implode(' ', $filtered_classes);
    }

    /**
     * Get available templates for Query Loop
     *
     * @param string $layout_type Layout type (grid/list/etc)
     * @return array Array of template options with metadata
     */
    public function get_available_templates(string $layout_type = 'grid'): array
    {
        $templates = [];
        
        // Add built-in default template
        $templates['default'] = [
            'label' => 'Default',
            'description' => 'Built-in default template',
            'callback' => [$this, 'render_default_template'],
            'layouts' => ['grid', 'list'] // Default template works with all layouts
        ];
        
        /**
         * Filter available Query Loop templates
         * 
         * Register your templates using this hook:
         * 
         * add_filter('orbitools/query_loop/available_templates', function($templates, $layout_type) {
         *     $templates['my-template'] = [
         *         'label' => 'My Custom Template',
         *         'description' => 'Description of my template',
         *         'callback' => 'my_template_function_name',
         *         'layouts' => ['grid', 'list'] // Optional: specify supported layouts
         *     ];
         *     return $templates;
         * }, 10, 2);
         * 
         * function my_template_function_name($post, $layout_type, $columns) {
         *     // Template implementation with access to post, layout, and columns
         * }
         * 
         * @param array $templates Array of template data
         * @param string $layout_type Layout type being requested
         */
        $all_templates = \apply_filters('orbitools/query_loop/available_templates', $templates, $layout_type);
        
        // Filter templates by layout support
        $filtered_templates = [];
        foreach ($all_templates as $key => $template) {
            // If no layouts specified, assume it supports all layouts
            $supported_layouts = $template['layouts'] ?? ['grid', 'list'];
            
            if (in_array($layout_type, $supported_layouts)) {
                $filtered_templates[$key] = $template;
            }
        }
        
        return $filtered_templates;
    }


    /**
     * Get template options for frontend dropdown
     *
     * @param string $layout_type Layout type
     * @return array Simple array for select options
     */
    public function get_template_options(string $layout_type = 'grid'): array
    {
        $templates = $this->get_available_templates($layout_type);
        $options = [];
        
        foreach ($templates as $key => $template) {
            $options[] = [
                'label' => $template['label'],
                'value' => $key
            ];
        }
        
        return $options;
    }

    /**
     * Register REST API endpoints for template options
     */
    public function register_rest_endpoints(): void
    {
        \register_rest_route('orbitools/v1', '/query-loop/templates', [
            'methods' => 'GET',
            'callback' => [$this, 'get_template_options_rest'],
            'permission_callback' => function() {
                return \current_user_can('edit_posts');
            },
            'args' => [
                'layout' => [
                    'required' => false,
                    'default' => 'grid',
                    'sanitize_callback' => 'sanitize_text_field'
                ]
            ]
        ]);

        \register_rest_route('orbitools/v1', '/query-loop/load-more', [
            'methods' => 'POST',
            'callback' => [$this, 'load_more_rest'],
            'permission_callback' => '__return_true',
            'args' => [
                'query_parameters' => [
                    'required' => true,
                    'type' => 'object',
                ],
                'page' => [
                    'required' => true,
                    'type' => 'integer',
                    'sanitize_callback' => 'absint',
                ],
            ],
        ]);

        \register_rest_route('orbitools/v1', '/query-loop/message-templates', [
            'methods' => 'GET',
            'callback' => [$this, 'get_message_template_options_rest'],
            'permission_callback' => function() {
                return \current_user_can('edit_posts');
            },
            'args' => [
                'message_type' => [
                    'required' => false,
                    'default' => 'no_parameters',
                    'sanitize_callback' => 'sanitize_text_field'
                ]
            ]
        ]);
    }

    /**
     * REST API callback for load more posts
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function load_more_rest(\WP_REST_Request $request): \WP_REST_Response
    {
        $query_parameters = $request->get_param('query_parameters');
        $page = $request->get_param('page');

        if (empty($query_parameters) || !$page) {
            return new \WP_REST_Response(['html' => '', 'has_more' => false], 400);
        }

        $query_type = $query_parameters['type'] ?? 'custom';
        $args = $query_parameters['args'] ?? [];

        // Build query args (reuse existing method for custom queries)
        if ($query_type === 'custom') {
            $query_args = $this->build_custom_query($args);
        } else {
            // For inherit type via load-more, treat as custom with provided args
            $query_args = $this->build_custom_query($args);
        }

        // Override the paged parameter with the requested page
        $query_args['paged'] = $page;

        $query = $this->execute_query($query_args);

        if (!$query || !$query->have_posts()) {
            return new \WP_REST_Response(['html' => '', 'has_more' => false], 200);
        }

        // Get display settings
        $display = $query_parameters['display'] ?? [];
        $layout = $display['layout'] ?? [];
        $layout_type = $layout['type'] ?? 'grid';
        $grid_columns = $layout['gridColumns'] ?? '3';
        $selected_template = $display['template'] ?? 'default';

        if (!is_string($selected_template) && !is_numeric($selected_template)) {
            $selected_template = 'default';
        }

        $available_templates = $this->get_available_templates($layout_type);
        if (!isset($available_templates[$selected_template])) {
            $selected_template = 'default';
        }

        $html = '';
        while ($query->have_posts()) {
            $query->the_post();
            $html .= $this->render_post_item(\get_post(), $layout_type, $selected_template, $grid_columns);
        }
        \wp_reset_postdata();

        return new \WP_REST_Response([
            'html' => $html,
            'has_more' => $page < $query->max_num_pages,
        ], 200);
    }

    /**
     * REST API callback to get template options
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function get_template_options_rest(\WP_REST_Request $request): \WP_REST_Response
    {
        $layout_type = $request->get_param('layout') ?? 'grid';
        $templates = $this->get_template_options($layout_type);
        
        return new \WP_REST_Response($templates, 200);
    }

    /**
     * Get message template options for frontend dropdown
     *
     * @param string $message_type Message type
     * @return array Simple array for select options
     */
    public function get_message_template_options(string $message_type = 'no_parameters'): array
    {
        $templates = $this->get_available_message_templates($message_type);
        $options = [];
        
        foreach ($templates as $key => $template) {
            $options[] = [
                'label' => $template['label'],
                'value' => $key
            ];
        }
        
        return $options;
    }

    /**
     * REST API callback to get message template options
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function get_message_template_options_rest(\WP_REST_Request $request): \WP_REST_Response
    {
        $message_type = $request->get_param('message_type') ?? 'no_parameters';
        $templates = $this->get_message_template_options($message_type);
        
        return new \WP_REST_Response($templates, 200);
    }

    /**
     * Get available message templates for Query Loop empty states
     *
     * @param string $message_type Type of message ('no_parameters' or 'no_results')
     * @return array Array of message template data
     */
    public function get_available_message_templates(string $message_type = 'no_parameters'): array
    {
        $templates = [];
        
        // Add built-in default template
        $templates['default'] = [
            'label' => 'Default',
            'description' => 'Built-in default message',
            'callback' => [$this, 'render_default_message_template'],
            'message_types' => ['no_parameters', 'no_results'] // Default works for both types
        ];
        
        /**
         * Filter available Query Loop message templates
         * 
         * Register your message templates using this hook:
         * 
         * add_filter('orbitools/query_loop/available_message_templates', function($templates, $message_type) {
         *     $templates['my-message'] = [
         *         'label' => 'My Custom Message',
         *         'description' => 'Description of my message template',
         *         'callback' => 'my_message_function_name',
         *         'message_types' => ['no_parameters'] // Optional: specify supported types
         *     ];
         *     return $templates;
         * }, 10, 2);
         * 
         * function my_message_function_name($message_type, $query_parameters, $query_id, $query = null) {
         *     // Message template implementation
         * }
         * 
         * @param array $templates Array of message template data
         * @param string $message_type Message type being requested
         */
        $all_templates = \apply_filters('orbitools/query_loop/available_message_templates', $templates, $message_type);
        
        // Filter templates by message type support
        $filtered_templates = [];
        foreach ($all_templates as $key => $template) {
            // If no message_types specified, assume it supports all types
            $supported_types = $template['message_types'] ?? ['no_parameters', 'no_results'];
            
            if (in_array($message_type, $supported_types)) {
                $filtered_templates[$key] = $template;
            }
        }
        
        return $filtered_templates;
    }

    /**
     * Render message using template function
     *
     * @param string $message_type Type of message ('no_parameters' or 'no_results')
     * @param string $fallback_message Fallback message if no template found
     * @param array $query_parameters Query parameters
     * @param string $query_id Unique query ID
     * @param \WP_Query|null $query Query object (for no_results type)
     * @return string Rendered message HTML
     */
    private function render_with_message_template(string $message_type, string $fallback_message, array $query_parameters, string $query_id, ?\WP_Query $query = null): string
    {
        // Get available templates for this message type
        $available_templates = $this->get_available_message_templates($message_type);
        
        // Get selected message template from query parameters
        $selected_template = $query_parameters['display']['messageTemplate'] ?? 'default';
        
        // Check if template exists and has a callback
        if (isset($available_templates[$selected_template]) && isset($available_templates[$selected_template]['callback'])) {
            $callback = $available_templates[$selected_template]['callback'];
            
            // Call the template callback
            if (is_callable($callback)) {
                return \call_user_func($callback, $message_type, $query_parameters, $query_id, $query);
            }
        }
        
        // Template not found or callback not callable, return fallback
        return $fallback_message;
    }

    /**
     * Built-in default message template function
     *
     * @param string $message_type Type of message ('no_parameters' or 'no_results')
     * @param array $query_parameters Query parameters
     * @param string $query_id Unique query ID
     * @param \WP_Query|null $query Query object (for no_results type)
     * @return string Rendered HTML
     */
    private function render_default_message_template(string $message_type, array $query_parameters, string $query_id, ?\WP_Query $query = null): string
    {
        if ($message_type === 'no_parameters') {
            return '<div class="orb-query-loop__no-parameters">' .
                '<h3>' . \esc_html__('Ready to build your query!', 'orbitools') . '</h3>' .
                '<p>' . \esc_html__('No query parameters have been set yet. Use the Query Builder panel in the editor to configure what content you\'d like to display.', 'orbitools') . '</p>' .
                '</div>';
        } else {
            return '<p class="orb-query-loop__no-results">' . 
                \esc_html__('No posts found matching your query parameters.', 'orbitools') . 
                '</p>';
        }
    }
}
