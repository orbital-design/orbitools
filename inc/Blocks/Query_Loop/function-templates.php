<?php
/**
 * Function-Based Templates for Query Loop
 * 
 * These are example high-performance template functions that can be
 * registered in theme's functions.php or a plugin.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Card-style template function
 * 
 * @param WP_Post $post Post object
 * @param string $layout_type Layout type
 * @param array $template_data Template metadata
 * @return string Rendered HTML
 */
function orbitools_query_loop_template_card($post, $layout_type, $template_data) {
    $html = '<article class="orb-query-loop__item orb-query-loop__item--card" data-post-id="' . $post->ID . '" data-template="function-card">';
    
    // Featured image as background
    if (has_post_thumbnail($post->ID)) {
        $image_url = get_the_post_thumbnail_url($post->ID, 'medium');
        $html .= '<div class="card-image" style="background-image: url(' . esc_url($image_url) . ');">';
        $html .= '<div class="card-overlay">';
    }
    
    $html .= '<div class="card-content">';
    $html .= '<h3 class="card-title">';
    $html .= '<a href="' . esc_url(get_permalink($post->ID)) . '">';
    $html .= esc_html(get_the_title($post->ID));
    $html .= '</a>';
    $html .= '</h3>';
    
    $html .= '<p class="card-excerpt">';
    $html .= esc_html(wp_trim_words(get_the_excerpt($post->ID), 20));
    $html .= '</p>';
    
    $html .= '<span class="card-date">';
    $html .= esc_html(get_the_date('M j, Y', $post->ID));
    $html .= '</span>';
    $html .= '</div>'; // card-content
    
    if (has_post_thumbnail($post->ID)) {
        $html .= '</div>'; // card-overlay
        $html .= '</div>'; // card-image
    }
    
    $html .= '</article>';
    
    return $html;
}

/**
 * Minimal template function
 * 
 * @param WP_Post $post Post object
 * @param string $layout_type Layout type
 * @param array $template_data Template metadata
 * @return string Rendered HTML
 */
function orbitools_query_loop_template_minimal($post, $layout_type, $template_data) {
    return '<div class="orb-query-loop__item orb-query-loop__item--minimal" data-template="function-minimal">' .
           '<a href="' . esc_url(get_permalink($post->ID)) . '" class="minimal-link">' .
           '<span class="minimal-title">' . esc_html(get_the_title($post->ID)) . '</span>' .
           '<span class="minimal-date">' . esc_html(get_the_date('M j', $post->ID)) . '</span>' .
           '</a>' .
           '</div>';
}

/**
 * List-style template function
 * 
 * @param WP_Post $post Post object
 * @param string $layout_type Layout type
 * @param array $template_data Template metadata
 * @return string Rendered HTML
 */
function orbitools_query_loop_template_list($post, $layout_type, $template_data) {
    $html = '<article class="orb-query-loop__item orb-query-loop__item--list" data-template="function-list">';
    
    $html .= '<div class="list-meta">';
    $html .= '<time class="list-date">' . esc_html(get_the_date('M j', $post->ID)) . '</time>';
    
    // Post type indicator
    $post_type_obj = get_post_type_object(get_post_type($post->ID));
    if ($post_type_obj) {
        $html .= '<span class="list-type">' . esc_html($post_type_obj->labels->singular_name) . '</span>';
    }
    
    $html .= '</div>';
    
    $html .= '<div class="list-content">';
    $html .= '<h3 class="list-title">';
    $html .= '<a href="' . esc_url(get_permalink($post->ID)) . '">';
    $html .= esc_html(get_the_title($post->ID));
    $html .= '</a>';
    $html .= '</h3>';
    
    $html .= '<p class="list-excerpt">';
    $html .= esc_html(wp_trim_words(get_the_excerpt($post->ID), 15));
    $html .= '</p>';
    $html .= '</div>';
    
    $html .= '</article>';
    
    return $html;
}

/**
 * Register function-based templates with Query Loop system
 * 
 * Uses WordPress hooks/filters - the proper way!
 */
add_filter('orbitools/query_loop/available_templates', function($templates, $layout_type) {
    
    // Add function-based templates to the list
    if (function_exists('orbitools_query_loop_template_card')) {
        $templates['function-card'] = [
            'label' => 'Card Style (Function)',
            'description' => 'High-performance card-style template using functions',
            'path' => '', // No file path needed for function-based templates
            'type' => 'function',
            'metadata' => [
                'Template Name' => 'Card Style (Function)',
                'Description' => 'High-performance card-style template',
                'Author' => 'OrbiTools',
                'Version' => '1.0.0',
                'Supports' => ['featured-images', 'excerpts', 'dates']
            ]
        ];
    }
    
    if (function_exists('orbitools_query_loop_template_minimal')) {
        $templates['function-minimal'] = [
            'label' => 'Minimal (Function)',
            'description' => 'Ultra-fast minimal template',
            'path' => '',
            'type' => 'function',
            'metadata' => [
                'Template Name' => 'Minimal (Function)',
                'Description' => 'Ultra-fast minimal template',
                'Author' => 'OrbiTools',
                'Version' => '1.0.0',
                'Supports' => ['titles', 'dates']
            ]
        ];
    }
    
    if (function_exists('orbitools_query_loop_template_list')) {
        $templates['function-list'] = [
            'label' => 'List Style (Function)',
            'description' => 'Clean list-style template',
            'path' => '',
            'type' => 'function',
            'metadata' => [
                'Template Name' => 'List Style (Function)',
                'Description' => 'Clean list-style template',
                'Author' => 'OrbiTools',
                'Version' => '1.0.0',
                'Supports' => ['excerpts', 'dates', 'post-types']
            ]
        ];
    }
    
    return $templates;
}, 10, 2);