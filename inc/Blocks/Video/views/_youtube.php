<?php

/**
 * YouTube privacy-embed facade — GDPR-friendly placeholder that lazy-
 * loads the iframe via JS on click. Mirrors `_youtube.twig`.
 *
 * Locals: $video (array), $is_preview (bool)
 */

if (!defined('ABSPATH')) {
    exit;
}

if (empty($video['id'])) {
    return;
}

$tag = !empty($is_preview) ? 'div' : 'a';
?>
<<?php echo $tag; ?>
    class="orb-video__facade"
    <?php if (empty($is_preview)) : ?>
        href="https://www.youtube.com/watch?v=<?php echo \esc_attr($video['id']); ?>"
        aria-label="Play YouTube video"
    <?php endif; ?>
    data-video-id="<?php echo \esc_attr($video['id']); ?>"
    data-params="<?php echo \esc_attr($video['params']); ?>"
    <?php if (!empty($video['pause_on_scroll'])) : ?>data-pause-on-scroll<?php endif; ?>
    <?php if (!empty($video['pause_others'])) : ?>data-pause-others<?php endif; ?>
>
    <img
        class="orb-video__thumb"
        src="<?php echo \esc_url($video['poster']); ?>"
        <?php if (!empty($video['fallback'])) : ?>data-fallback="<?php echo \esc_url($video['fallback']); ?>"<?php endif; ?>
        alt=""
        loading="lazy"
        decoding="async"
    />
    <span class="orb-video__play" aria-hidden="true">
        <svg viewBox="0 0 68 48" focusable="false">
            <path d="M66.52 7.74c-.78-2.93-2.49-5.41-5.42-6.19C55.79.13 34 0 34 0S12.21.13 6.9 1.55c-2.93.78-4.63 3.26-5.42 6.19C.06 13.05 0 24 0 24s.06 10.95 1.48 16.26c.78 2.93 2.49 5.41 5.42 6.19C12.21 47.87 34 48 34 48s21.79-.13 27.1-1.55c2.93-.78 4.64-3.26 5.42-6.19C67.94 34.95 68 24 68 24s-.06-10.95-1.48-16.26z" fill="#ff0000"/>
            <path d="M45 24L27 14v20" fill="#fff"/>
        </svg>
    </span>
</<?php echo $tag; ?>>
