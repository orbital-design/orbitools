<?php

/**
 * Video block — outer wrapper. Includes the per-source partial when
 * we have content, or a placeholder when empty. Mirrors the theme's
 * `views/block.twig`.
 *
 * Available locals (from include_view extract):
 *   string $block_wrapper  HTML attributes string for the wrapper
 *   string $base_class     "orb-video"
 *   string $source         '' | 'youtube' | 'vimeo' | 'embed' | 'file'
 *   array  $video          per-source context (shape depends on $source)
 *   bool   $has_content    $source !== ''
 *   bool   $is_preview     true when rendering inside the editor / REST
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div <?php echo $block_wrapper; // already escaped via get_block_wrapper_attributes() ?>>
    <?php if (!empty($has_content)) : ?>
        <?php
        $partial = __DIR__ . '/_' . $source . '.php';
        if (file_exists($partial)) {
            include $partial;
        }
        ?>
    <?php else : ?>
        <div class="<?php echo \esc_attr($base_class); ?>__placeholder">
            <i class="fa-light fa-video" aria-hidden="true"></i>
            <p><?php echo \esc_html__('Add a video in the block settings', 'orbitools'); ?></p>
        </div>
    <?php endif; ?>
</div>
