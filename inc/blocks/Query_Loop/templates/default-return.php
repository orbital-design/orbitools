<?php
/**
 * Template Name: Plugin Default (High Performance)
 * Description: High-performance template that returns HTML instead of echoing
 * Preview: default-preview.jpg
 * Author: OrbiTools
 * Version: 1.0.0
 * Supports: featured-images, excerpts, categories, tags, authors, dates
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * High Performance Query Loop Template (Return-based)
 * 
 * Available variables:
 * @var WP_Post $post Current post object
 * @var string $layout_type Layout type (grid/list)
 * @var array $template_args Additional template arguments
 */

// Build HTML string (much faster than output buffering)
$html = '<article class="orb-query-loop__item orb-query-loop__item--' . esc_attr($layout_type) . '" data-post-id="' . $post->ID . '" data-template="plugin-default-return">';

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

// Tags for posts
if (get_post_type($post->ID) === 'post') {
    $tags = get_the_tags($post->ID);
    if (!empty($tags)) {
        $html .= '<div class="orb-query-loop__tags">';
        foreach ($tags as $tag) {
            $html .= '<span class="orb-query-loop__tag">';
            $html .= '<a href="' . esc_url(get_tag_link($tag->term_id)) . '">';
            $html .= esc_html($tag->name);
            $html .= '</a>';
            $html .= '</span>';
        }
        $html .= '</div>';
    }
}

// Footer
$html .= '<footer class="orb-query-loop__footer">';
$html .= '<a href="' . esc_url(get_permalink($post->ID)) . '" class="orb-query-loop__read-more">';
$html .= esc_html__('Read More (Fast)', 'orbitools');
$html .= '</a>';
$html .= '</footer>';

$html .= '</div>'; // content
$html .= '</article>';

// Return the HTML (much faster than echo + output buffering)
return $html;