<?php

/**
 * Self-hosted video files (MP4 / WebM). Mirrors `_file.twig`.
 *
 * Locals: $video (array), $is_preview (bool)
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<?php if (!empty($video['source']) && is_array($video['source'])) : ?>
    <video class="orb-video__player"
           poster="<?php echo \esc_url($video['poster'] ?? ''); ?>"
           preload="none"
           <?php echo $video['attrs']['as_string'] ?? ''; ?>>
        <?php foreach ($video['source'] as $key => $file) : ?>
            <source src="<?php echo \esc_attr($file); ?>" type="video/<?php echo \esc_attr($key); ?>" />
        <?php endforeach; ?>

        <?php // Fallback text for browsers that don't support video ?>
        <div class="orb-video__notice">
            <p><?php echo \esc_html__('Your browser does not support the video tag.', 'orbitools'); ?></p>
        </div>
    </video>
<?php elseif (!empty($is_preview)) : ?>
    <?php // Empty state for editor ?>
    <div class="orb-video__placeholder">
        <p><?php echo \esc_html__('Please upload a video file.', 'orbitools'); ?></p>
    </div>
<?php endif; ?>
