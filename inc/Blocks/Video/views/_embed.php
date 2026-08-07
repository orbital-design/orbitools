<?php

/**
 * Third-party embed (oEmbed result). Mirrors `_embed.twig`.
 *
 * Locals: $video (array), $is_preview (bool)
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<?php if (!empty($video['source']['valid'])) : ?>
    <div class="orb-video__embed">
        <?php echo $video['source']['value']; // already an oEmbed HTML fragment ?>
    </div>
<?php elseif (!empty($video['source']['url'])) : ?>
    <?php // URL that couldn't be converted to embed ?>
    <div class="orb-video__embed orb-video__embed--url">
        <p>
            <?php echo \esc_html__('Video URL:', 'orbitools'); ?>
            <a href="<?php echo \esc_url($video['source']['url']); ?>" target="_blank" rel="noopener noreferrer">
                <?php echo \esc_html($video['source']['url']); ?>
            </a>
        </p>
    </div>
<?php elseif (!empty($is_preview)) : ?>
    <?php // Empty state in the editor preview ?>
    <div class="orb-video__placeholder">
        <p><?php echo \esc_html__('Please add a valid video embed code or URL', 'orbitools'); ?></p>
    </div>
<?php endif; ?>
