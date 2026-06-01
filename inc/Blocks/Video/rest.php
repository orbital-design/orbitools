<?php

declare(strict_types=1);

/**
 * Video block — REST endpoint for sideloading provider thumbnails into
 * the media library. Loaded by the Video module class.
 *
 * Endpoint: POST /wp-json/orbitools/v1/video/sideload-poster
 * Body:     { "provider": "vimeo" | "youtube", "video_id": "<id>" }
 * Returns:  { "id": 42, "url": "https://.../wp-content/uploads/..." }
 *
 * Once sideloaded, the attachment ID is cached per provider+video so
 * subsequent requests return the existing attachment instead of
 * re-downloading.
 *
 * Ported 1:1 from the dream-and-leap theme. Only change: namespace
 * `orb/v1` → `orbitools/v1`.
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('rest_api_init', static function (): void {
    register_rest_route(
        'orbitools/v1',
        '/video/sideload-poster',
        [
            'methods'  => 'POST',
            'callback' => 'orbitools_video_rest_sideload_poster',
            'permission_callback' => static function (): bool {
                return current_user_can('upload_files');
            },
            'args' => [
                'provider' => [
                    'required' => true,
                    'sanitize_callback' => 'sanitize_key',
                    'validate_callback' => static function ($value): bool {
                        return in_array($value, ['vimeo', 'youtube'], true);
                    },
                ],
                'video_id' => [
                    'required'          => true,
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => static function ($value): bool {
                        // YouTube IDs are 11 chars [A-Za-z0-9_-]; Vimeo are numeric.
                        return is_string($value) && (bool) preg_match('/^[A-Za-z0-9_-]+$/', $value);
                    },
                ],
            ],
        ]
    );
});

/**
 * Resolve a Vimeo video ID → thumbnail URL + suggested title via the
 * public oEmbed endpoint. Returns [url, title] or a WP_Error.
 */
function orbitools_video_resolve_vimeo_thumb(string $video_id)
{
    $oembed_url = 'https://vimeo.com/api/oembed.json?url='
        . rawurlencode('https://vimeo.com/' . $video_id)
        . '&width=1280';

    $response = wp_remote_get($oembed_url, ['timeout' => 5]);
    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
        return new WP_Error(
            'orbitools_vimeo_fetch_failed',
            'Failed to fetch Vimeo data.',
            ['status' => 502]
        );
    }

    $data = json_decode((string) wp_remote_retrieve_body($response), true);
    $thumb_url = isset($data['thumbnail_url']) ? (string) $data['thumbnail_url'] : '';
    if ($thumb_url === '') {
        return new WP_Error(
            'orbitools_vimeo_no_thumb',
            'Vimeo returned no thumbnail.',
            ['status' => 404]
        );
    }

    $title = isset($data['title']) ? sanitize_text_field((string) $data['title']) : '';
    return [$thumb_url, $title ?: ('Vimeo ' . $video_id)];
}

/**
 * Resolve a YouTube video ID → thumbnail URL. We try maxresdefault
 * first (1280×720) and fall back to hqdefault (480×360) since older
 * or lower-quality uploads don't get a maxres image.
 */
function orbitools_video_resolve_youtube_thumb(string $video_id)
{
    $candidates = [
        'https://i.ytimg.com/vi/' . $video_id . '/maxresdefault.jpg',
        'https://i.ytimg.com/vi/' . $video_id . '/hqdefault.jpg',
    ];

    foreach ($candidates as $url) {
        $response = wp_remote_head($url, ['timeout' => 5, 'redirection' => 2]);
        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            return [$url, 'YouTube ' . $video_id];
        }
    }

    return new WP_Error(
        'orbitools_youtube_no_thumb',
        'No YouTube thumbnail available for this video.',
        ['status' => 404]
    );
}

/**
 * Endpoint handler: resolve thumb URL → download → sideload → return
 * attachment id + URL. Cached per provider+video_id so repeat picks
 * of the same video skip the download.
 */
function orbitools_video_rest_sideload_poster(WP_REST_Request $request)
{
    $provider = (string) $request->get_param('provider');
    $video_id = (string) $request->get_param('video_id');

    // Cache: option keyed by provider+video_id → attachment ID. Skip
    // re-download if we've sideloaded this before and the attachment
    // still exists.
    $cache_key = 'orbitools_video_poster_attachment_' . $provider . '_' . $video_id;
    $cached_id = (int) get_option($cache_key, 0);
    if ($cached_id > 0 && get_post($cached_id)) {
        return [
            'id'  => $cached_id,
            'url' => wp_get_attachment_url($cached_id),
        ];
    }

    $resolved = $provider === 'vimeo'
        ? orbitools_video_resolve_vimeo_thumb($video_id)
        : orbitools_video_resolve_youtube_thumb($video_id);

    if (is_wp_error($resolved)) {
        return $resolved;
    }
    [$thumb_url, $title] = $resolved;

    // Sideload the thumbnail. download_url + media_handle_sideload
    // give us a proper attachment with metadata + intermediate sizes.
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $tmp = download_url($thumb_url, 10);
    if (is_wp_error($tmp)) {
        return new WP_Error(
            'orbitools_video_download_failed',
            'Failed to download thumbnail.',
            ['status' => 502]
        );
    }

    $file_array = [
        'name'     => $provider . '-' . $video_id . '-poster.jpg',
        'tmp_name' => $tmp,
    ];

    $attachment_id = media_handle_sideload($file_array, 0, $title);

    if (is_wp_error($attachment_id)) {
        if (file_exists($tmp)) {
            @unlink($tmp);
        }
        return new WP_Error(
            'orbitools_video_sideload_failed',
            'Failed to import thumbnail into the media library.',
            ['status' => 500]
        );
    }

    update_option($cache_key, (int) $attachment_id, false);

    return [
        'id'  => (int) $attachment_id,
        'url' => wp_get_attachment_url((int) $attachment_id),
    ];
}
