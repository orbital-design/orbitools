<?php

/**
 * Vimeo privacy-embed facade — GDPR-friendly placeholder with dnt=1.
 * Mirrors `_vimeo.twig`.
 *
 * Locals: $video (array)
 */

if (!defined('ABSPATH')) {
    exit;
}

if (empty($video['id'])) {
    return;
}
?>
<a
    class="orb-video__facade"
    href="https://vimeo.com/<?php echo \esc_attr($video['id']); ?>"
    data-video-id="<?php echo \esc_attr($video['id']); ?>"
    data-params="<?php echo \esc_attr($video['params']); ?>"
    data-provider="vimeo"
    <?php if (!empty($video['pause_on_scroll'])) : ?>data-pause-scroll<?php endif; ?>
    aria-label="Play Vimeo video"
>
    <?php if (!empty($video['poster'])) : ?>
        <img
            class="orb-video__thumb"
            src="<?php echo \esc_url($video['poster']); ?>"
            alt=""
            loading="lazy"
            decoding="async"
        />
    <?php endif; ?>
    <span class="orb-video__play orb-video__play--vimeo" aria-hidden="true">
        <svg viewBox="0 0 68 48" focusable="false">
            <path d="M66.52 7.74c-.78-2.93-2.49-5.41-5.42-6.19C55.79.13 34 0 34 0S12.21.13 6.9 1.55c-2.93.78-4.63 3.26-5.42 6.19C.06 13.05 0 24 0 24s.06 10.95 1.48 16.26c.78 2.93 2.49 5.41 5.42 6.19C12.21 47.87 34 48 34 48s21.79-.13 27.1-1.55c2.93-.78 4.64-3.26 5.42-6.19C67.94 34.95 68 24 68 24s-.06-10.95-1.48-16.26z" fill="#1ab7ea"/>
            <path d="M45 24L27 14v20" fill="#fff"/>
        </svg>
    </span>
</a>
