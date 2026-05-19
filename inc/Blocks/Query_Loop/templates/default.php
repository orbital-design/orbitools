<?php
/**
 * Template Name: Plugin Default
 * Description: The default template for Query Loop results with basic post information
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
 * Default Query Loop Template
 * 
 * Available variables:
 * @var WP_Post $post Current post object
 * @var string $layout_type Layout type (grid/list)
 * @var array $template_args Additional template arguments
 */

?>
<article class="orb-query-loop__item orb-query-loop__item--<?php echo esc_attr($layout_type); ?>" data-post-id="<?php echo $post->ID; ?>" data-template="plugin-default">
    
    <?php if (has_post_thumbnail($post->ID)) : ?>
        <div class="orb-query-loop__featured-image">
            <a href="<?php echo esc_url(get_permalink($post->ID)); ?>" aria-label="<?php echo esc_attr(sprintf(__('Read more about %s', 'orbitools'), get_the_title($post->ID))); ?>">
                <?php echo get_the_post_thumbnail($post->ID, 'medium', ['class' => 'orb-query-loop__image']); ?>
            </a>
        </div>
    <?php endif; ?>
    
    <div class="orb-query-loop__content">
        <header class="orb-query-loop__header">
            <h3 class="orb-query-loop__title">
                <a href="<?php echo esc_url(get_permalink($post->ID)); ?>">
                    <?php echo esc_html(get_the_title($post->ID)); ?>
                </a>
            </h3>
            
            <div class="orb-query-loop__meta">
                <time class="orb-query-loop__date" datetime="<?php echo esc_attr(get_the_date('c', $post->ID)); ?>">
                    <?php echo esc_html(get_the_date('', $post->ID)); ?>
                </time>
                
                <?php if (get_post_type($post->ID) === 'post') : ?>
                    <span class="orb-query-loop__author">
                        <?php echo esc_html(sprintf(__('by %s', 'orbitools'), get_the_author_meta('display_name', $post->post_author))); ?>
                    </span>
                <?php endif; ?>
            </div>
        </header>
        
        <?php if (has_excerpt($post->ID) || $post->post_content) : ?>
            <div class="orb-query-loop__excerpt">
                <?php echo wp_kses_post(get_the_excerpt($post->ID)); ?>
            </div>
        <?php endif; ?>
        
        <?php 
        // Show categories for posts
        if (get_post_type($post->ID) === 'post') {
            $categories = get_the_category($post->ID);
            if (!empty($categories)) : ?>
                <div class="orb-query-loop__categories">
                    <?php foreach ($categories as $category) : ?>
                        <span class="orb-query-loop__category">
                            <a href="<?php echo esc_url(get_category_link($category->term_id)); ?>">
                                <?php echo esc_html($category->name); ?>
                            </a>
                        </span>
                    <?php endforeach; ?>
                </div>
            <?php endif;
        }
        
        // Show tags for posts
        if (get_post_type($post->ID) === 'post') {
            $tags = get_the_tags($post->ID);
            if (!empty($tags)) : ?>
                <div class="orb-query-loop__tags">
                    <?php foreach ($tags as $tag) : ?>
                        <span class="orb-query-loop__tag">
                            <a href="<?php echo esc_url(get_tag_link($tag->term_id)); ?>">
                                <?php echo esc_html($tag->name); ?>
                            </a>
                        </span>
                    <?php endforeach; ?>
                </div>
            <?php endif;
        } ?>
        
        <footer class="orb-query-loop__footer">
            <a href="<?php echo esc_url(get_permalink($post->ID)); ?>" class="orb-query-loop__read-more">
                <?php echo esc_html__('Read More', 'orbitools'); ?>
            </a>
        </footer>
    </div>
</article>