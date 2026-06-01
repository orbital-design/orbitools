<?php

/**
 * Video Block
 *
 * Registers and manages the Video block. Detects the source provider
 * from the URL (YouTube, Vimeo, generic oEmbed) or falls back to
 * the uploaded MP4/WebM files. Dispatches to one of four PHP
 * partials with a normalised context.
 *
 * Ported 1:1 from the dream-and-leap theme's blocks/video/ —
 * Twig → PHP partials, theme `Logger\logger()->get_wp_classes_setup()`
 * helper replaced with an inline class builder, REST namespace
 * `orb/v1` moved to `orbitools/v1`. No behavioural changes.
 *
 * @package Orbitools
 * @subpackage Blocks
 * @since 1.0.0
 */

namespace Orbitools\Blocks\Video;

use Orbitools\Core\Abstracts\Module_Base;
use Orbitools\Core\Traits\Block_Utilities;

if (!defined('ABSPATH')) {
    exit;
}

class Video extends Module_Base
{
    use Block_Utilities;

    protected const VERSION = '1.0.0';

    public function get_slug(): string
    {
        return 'video-block';
    }

    public function get_name(): string
    {
        return \__('Video', 'orbitools');
    }

    public function get_description(): string
    {
        return \__('Embed a YouTube or Vimeo video, paste any embeddable URL, or upload a file.', 'orbitools');
    }

    public function get_version(): string
    {
        return self::VERSION;
    }

    public function is_enabled(): bool
    {
        return true;
    }

    public function init(): void
    {
        static $registered = false;
        if ($registered) {
            return;
        }
        $registered = true;

        // REST endpoint registration lives in rest.php (mirrors the
        // theme's file layout).
        require_once __DIR__ . '/rest.php';

        if (\did_action('init')) {
            $this->register_block();
        } else {
            \add_action('init', [$this, 'register_block']);
        }
    }

    public function register_block(): void
    {
        $block_dir = ORBITOOLS_DIR . 'build/blocks/video/';

        if (file_exists($block_dir . 'block.json')) {
            \register_block_type($block_dir, [
                'render_callback' => [$this, 'render_callback'],
            ]);
        }
    }

    /**
     * Server render for the Video block.
     *
     * Mirrors the theme's render.php: detect provider from the URL
     * (YouTube / Vimeo / generic oEmbed) or fall back to uploaded
     * file sources, build a normalised context, and include the
     * matching PHP partial.
     *
     * @param array<string,mixed> $attributes
     * @param string              $content
     * @param \WP_Block           $block
     * @return string
     */
    public function render_callback(array $attributes, string $content, \WP_Block $block): string
    {
        $video_url  = (string) ($attributes['videoUrl']  ?? '');
        $mp4_url    = (string) ($attributes['mp4Url']    ?? '');
        $webm_url   = (string) ($attributes['webmUrl']   ?? '');
        $poster_url = (string) ($attributes['posterUrl'] ?? '');

        $yt_id    = self::extract_youtube_id($video_url);
        $vimeo_id = self::extract_vimeo_id($video_url);

        if ($yt_id !== null) {
            $source = 'youtube';
        } elseif ($vimeo_id !== null) {
            $source = 'vimeo';
        } elseif ($mp4_url !== '' || $webm_url !== '') {
            $source = 'file';
        } elseif ($video_url !== '') {
            $source = 'embed';
        } else {
            $source = '';
        }

        $aspect_ratio = \sanitize_html_class($attributes['aspectRatio'] ?? '16-9');

        // Build per-source $video context (mirrors the theme exactly).
        $video = [];
        if ($source === 'youtube') {
            $params = ['autoplay=1'];
            if (!empty($attributes['ytDisableRelated'])) $params[] = 'rel=0';
            if (!empty($attributes['ytModestBranding'])) $params[] = 'modestbranding=1';
            if (!empty($attributes['playsinline']))      $params[] = 'playsinline=1';
            if (empty($attributes['controls']))          $params[] = 'controls=0';

            $video = [
                'id'              => $yt_id,
                'params'          => implode('&', $params),
                'poster'          => $poster_url !== '' ? $poster_url : ('https://i.ytimg.com/vi/' . $yt_id . '/maxresdefault.jpg'),
                'fallback'        => 'https://i.ytimg.com/vi/' . $yt_id . '/hqdefault.jpg',
                'pause_on_scroll' => !empty($attributes['ytPauseOnScroll']),
                'pause_others'    => !empty($attributes['ytPauseOthers']),
            ];
        } elseif ($source === 'vimeo') {
            $params = ['dnt=1'];
            if (!empty($attributes['vimeoHideTitle']))    $params[] = 'title=0';
            if (!empty($attributes['vimeoHideByline']))   $params[] = 'byline=0';
            if (!empty($attributes['vimeoHidePortrait'])) $params[] = 'portrait=0';
            if (!empty($attributes['playsinline']))       $params[] = 'playsinline=1';
            $params[] = 'color=1787FF';

            // Vimeo has no public thumbnail URL pattern — fetch via
            // oEmbed and cache per video id.
            $poster = $poster_url !== '' ? $poster_url : self::resolve_vimeo_thumb($vimeo_id);

            $video = [
                'id'              => $vimeo_id,
                'params'          => implode('&', $params),
                'poster'          => $poster,
                'pause_on_scroll' => !empty($attributes['vimeoPauseOnScroll']),
            ];
        } elseif ($source === 'file') {
            $sources = [];
            if ($mp4_url !== '')  $sources['mp4']  = $mp4_url;
            if ($webm_url !== '') $sources['webm'] = $webm_url;

            $attrs_str = [];
            if (!empty($attributes['autoplay']))    $attrs_str[] = 'autoplay';
            if (!empty($attributes['loop']))        $attrs_str[] = 'loop';
            if (!empty($attributes['muted']))       $attrs_str[] = 'muted';
            if (!empty($attributes['controls']))    $attrs_str[] = 'controls';
            if (!empty($attributes['playsinline'])) $attrs_str[] = 'playsinline';

            $video = [
                'source' => $sources,
                'poster' => $poster_url,
                'attrs'  => ['as_string' => implode(' ', $attrs_str)],
            ];
        } elseif ($source === 'embed') {
            $oembed = function_exists('wp_oembed_get') ? \wp_oembed_get($video_url) : false;
            $video = [
                'source' => [
                    'valid' => (bool) $oembed,
                    'value' => $oembed !== false ? $oembed : '',
                    'url'   => $video_url,
                ],
            ];
        }

        // Build class list. Replaces the theme's
        // `logger()->get_wp_classes_setup()` helper with an explicit
        // build using the standard WP wrapper attributes — then we
        // strip the `wp-block-orb-video` class WordPress auto-adds,
        // matching how Marquee / Collection do it.
        $base_class = 'orb-video';
        $additional_classes = ['has-' . $aspect_ratio . '-aspect-ratio'];

        $wrapper_attrs_arr = [
            'class' => implode(' ', array_filter([$base_class] + $additional_classes)),
            'data-source' => $source,
        ];
        $block_wrapper = \get_block_wrapper_attributes($wrapper_attrs_arr);
        $block_wrapper = preg_replace('/\bwp-block-orb-video\s*/', '', $block_wrapper);

        $context = [
            'is_preview' => defined('REST_REQUEST') && REST_REQUEST,
            'has_content' => $source !== '',
            'block_wrapper' => $block_wrapper,
            'base_class' => $base_class,
            'source' => $source,
            'video' => $video,
        ];

        ob_start();
        $this->include_view('block.php', $context);
        return (string) ob_get_clean();
    }

    /**
     * Include a partial under views/ with $context exposed as local
     * variables. Mirrors `Timber::render` for our PHP partials.
     *
     * @param string              $filename
     * @param array<string,mixed> $context
     */
    private function include_view(string $filename, array $context): void
    {
        $path = __DIR__ . '/views/' . $filename;
        if (!file_exists($path)) {
            return;
        }
        // Make context entries available as local vars to the partial.
        extract($context, EXTR_SKIP);
        include $path;
    }

    /**
     * Extract a YouTube video ID from various URL formats, or null.
     */
    public static function extract_youtube_id(string $url): ?string
    {
        if ($url === '') {
            return null;
        }
        if (preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/|v\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $url, $m)) {
            return $m[1];
        }
        return null;
    }

    /**
     * Extract a Vimeo video ID from various URL formats, or null.
     */
    public static function extract_vimeo_id(string $url): ?string
    {
        if ($url === '') {
            return null;
        }
        if (preg_match('#vimeo\.com/(?:video/)?(\d+)#', $url, $m)) {
            return $m[1];
        }
        return null;
    }

    /**
     * Resolve a Vimeo video to its largest thumbnail URL via the
     * public oEmbed endpoint. Cached for a day per video ID; '' on
     * failure (cached for an hour to avoid hammering on errors).
     */
    public static function resolve_vimeo_thumb(string $video_id): string
    {
        $cache_key = 'orb_vimeo_thumb_' . $video_id;
        $cached = \get_transient($cache_key);
        if ($cached !== false) {
            return (string) $cached;
        }

        $response = \wp_remote_get(
            'https://vimeo.com/api/oembed.json?url=' . rawurlencode('https://vimeo.com/' . $video_id) . '&width=1280',
            ['timeout' => 5]
        );

        if (\is_wp_error($response) || \wp_remote_retrieve_response_code($response) !== 200) {
            \set_transient($cache_key, '', HOUR_IN_SECONDS);
            return '';
        }

        $data = json_decode((string) \wp_remote_retrieve_body($response), true);
        $thumb = isset($data['thumbnail_url']) ? \esc_url_raw((string) $data['thumbnail_url']) : '';

        \set_transient($cache_key, $thumb, DAY_IN_SECONDS);
        return $thumb;
    }

    public function get_default_settings(): array
    {
        return [];
    }
}
