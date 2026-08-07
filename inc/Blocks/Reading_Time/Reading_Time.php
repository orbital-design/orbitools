<?php

declare(strict_types=1);

namespace Orbitools\Blocks\Reading_Time;

use Orbitools\Core\Abstracts\Module_Base;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Reading Time — estimate how long a post takes to read.
 *
 * Counts the words in the rendered post content (so server-rendered
 * blocks are included), adds a small allowance per image, divides by
 * the configured words-per-minute, and rounds up to whole minutes.
 * The result is cached in `_orbitools_reading_time` post meta on
 * every `post_updated` so the runtime cost is one meta read per
 * render.
 *
 * Surfaces:
 *   1. The `orb/reading-time` block, server-rendered from the meta.
 *   2. The static accessor `Reading_Time::get_reading_time()` for
 *      theme code that wants the formatted markup or the raw number.
 *
 * Ported from the dream-and-leap theme's `Logger\Reading_Time\Component`.
 * Tuneable settings (WPM, suffix, whether to count images) live on
 * the module's settings page; the theme version had them hardcoded.
 *
 * @package Orbitools
 * @since   1.0.0
 */
final class Reading_Time extends Module_Base
{
    protected const VERSION = '1.0.0';

    /**
     * Post-meta key the calculated reading time is cached in.
     * Prefixed with an underscore so it stays out of the default
     * custom-fields meta box.
     */
    private const META_KEY = '_orbitools_reading_time';

    /**
     * Object-cache group + key prefix for the rendered-content cache.
     * Bumped to a single set/delete on `post_updated` so the
     * expensive `apply_filters('the_content')` only runs once per
     * write rather than once per reading-time read.
     */
    private const RENDER_CACHE_GROUP    = 'orbitools_reading_time';
    private const RENDER_CACHE_DURATION = DAY_IN_SECONDS;

    /**
     * Per-image reading allowance in seconds. The first image is
     * heaviest (gallery hero / hero shot), the next nine taper down,
     * and everything past that is the flat `IMAGE_TIME_REMAINING`.
     * Lifted from the theme version — same model, same numbers.
     *
     * @var array<int,int>
     */
    private const IMAGE_TIME_FIRST_TEN = [12, 11, 10, 9, 8, 7, 6, 5, 4, 3];
    private const IMAGE_TIME_REMAINING = 3;

    public function get_slug(): string
    {
        return 'reading-time';
    }

    public function get_name(): string
    {
        return \__('Reading Time', 'orbitools');
    }

    public function get_description(): string
    {
        return \__('Estimate how long a post takes to read, exposed as the orb/reading-time block and a static accessor for themes.', 'orbitools');
    }

    public function init(): void
    {
        \add_action('post_updated', [$this, 'recalc_on_post_save'], 10, 3);

        if (\did_action('init')) {
            $this->register_block();
        } else {
            \add_action('init', [$this, 'register_block']);
        }
    }

    public function get_default_settings(): array
    {
        return [
            'wpm'          => 270,
            'suffix'       => 'min read',
            'count_images' => true,
        ];
    }

    // =========================================================================
    // Block registration
    // =========================================================================

    public function register_block(): void
    {
        $block_dir = ORBITOOLS_DIR . 'build/blocks/reading-time/';

        if (file_exists($block_dir . 'block.json')) {
            \register_block_type($block_dir, [
                'render_callback' => [$this, 'render_callback'],
            ]);
        }
    }

    /**
     * Server-render the block. Pulls the cached reading time off the
     * post being rendered and wraps it in the same schema-friendly
     * markup the theme version emits.
     *
     * @param array<string,mixed> $attributes
     */
    public function render_callback(array $attributes, string $content, \WP_Block $block): string
    {
        $post_id = $this->resolve_post_id($block);
        $output  = self::get_reading_time($post_id, false);

        if ($output === null) {
            return '';
        }

        $wrapper = \get_block_wrapper_attributes();
        // Strip the WP-auto-added `wp-block-orb-reading-time` so we
        // own the CSS surface, matching how Video / Marquee do it.
        $wrapper = \preg_replace('/\bwp-block-orb-reading-time\s*/', '', $wrapper) ?? $wrapper;

        return sprintf('<div %s>%s</div>', $wrapper, $output);
    }

    /**
     * Prefer the block's render context (`postId`) when one is
     * available — falls back to the loop's current post. Both can be
     * absent in REST contexts; the caller handles `null`.
     */
    private function resolve_post_id(\WP_Block $block): ?int
    {
        $from_context = $block->context['postId'] ?? null;
        if (is_int($from_context) && $from_context > 0) {
            return $from_context;
        }
        $current = \get_the_ID();
        return $current ? (int) $current : null;
    }

    // =========================================================================
    // Theme-facing accessor
    // =========================================================================

    /**
     * Get the estimated reading time as either the formatted HTML
     * surface (schema.org `timeRequired` microdata) or just the raw
     * minutes value.
     *
     * Static so theme code can call it without going through the
     * Module_Manager:
     *
     *     echo \Orbitools\Blocks\Reading_Time\Reading_Time::get_reading_time($post_id);
     *
     * Falls back to `'< 1'` when the cached value is empty / 0 —
     * matches the theme version's behaviour.
     *
     * @param int|null $post_id
     * @param bool     $raw      If true, returns just the number (escaped). Otherwise the wrapped HTML span.
     */
    public static function get_reading_time(?int $post_id = null, bool $raw = false): ?string
    {
        $post = \get_post($post_id);
        if (!$post || !$post->ID) {
            return null;
        }

        $reading_time = \get_post_meta($post->ID, self::META_KEY, true);
        $numeric      = (int) $reading_time;

        if ($raw) {
            return \esc_html((string) $reading_time);
        }

        if ($reading_time === '' || $reading_time === false || $reading_time === 0 || $reading_time === '0') {
            $reading_time = '< 1';
            $numeric      = 1;
        }

        $suffix = self::resolved_suffix();

        return sprintf(
            '<span class="orb-reading-time" itemprop="timeRequired" content="PT%dM"><span class="orb-reading-time__value">%s</span> <span class="orb-reading-time__suffix">%s</span></span>',
            $numeric,
            \esc_html((string) $reading_time),
            \esc_html($suffix)
        );
    }

    private static function resolved_suffix(): string
    {
        $settings = \get_option('orbitools_settings', []);
        $raw      = $settings['reading-time_suffix'] ?? 'min read';
        return is_string($raw) && $raw !== '' ? $raw : 'min read';
    }

    // =========================================================================
    // Calculation + caching
    // =========================================================================

    /**
     * `post_updated` handler — refresh the cached minutes whenever
     * the body of a viewable post changes. Drops the rendered-content
     * cache unconditionally so a re-save with no body change still
     * picks up fresh shortcode / block output the next time the
     * theme calls `get_reading_time()`.
     */
    public function recalc_on_post_save(int $post_id, $post_after, $post_before): void
    {
        if (!$post_after instanceof \WP_Post) {
            return;
        }
        if (!\is_post_type_viewable($post_after->post_type)) {
            return;
        }

        \wp_cache_delete('content_' . $post_id, self::RENDER_CACHE_GROUP);

        $body_changed = !($post_before instanceof \WP_Post)
            || $post_before->post_content !== $post_after->post_content;

        if (!\metadata_exists('post', $post_id, self::META_KEY)) {
            $minutes = $this->calculate_reading_time($post_id);
            \add_post_meta($post_id, self::META_KEY, $minutes, true);
            return;
        }

        if ($body_changed) {
            $minutes = $this->calculate_reading_time($post_id);
            \update_post_meta($post_id, self::META_KEY, $minutes);
        }
    }

    /**
     * Run the actual word + image arithmetic against the rendered
     * content. Public so themes / tooling can call it for a
     * recalculate workflow (e.g. wp-cli), but the normal read path is
     * `get_reading_time()` which pulls from cached meta.
     */
    public function calculate_reading_time(?int $post_id = null): int
    {
        if (!$post_id) {
            $post_id = (int) \get_the_ID();
            if (!$post_id) {
                return 0;
            }
        }

        $content = $this->get_rendered_content($post_id);
        if ($content === '') {
            return 0;
        }

        $settings     = (array) \get_option('orbitools_settings', []);
        $wpm          = max(50, (int) ($settings['reading-time_wpm'] ?? 270));
        $count_images = !array_key_exists('reading-time_count_images', $settings)
            || !empty($settings['reading-time_count_images']);

        $text       = \wp_strip_all_tags($content);
        $word_count = str_word_count($text);

        $image_time = 0;
        if ($count_images) {
            \preg_match_all('/<img\s/i', $content, $matches);
            $image_count = is_array($matches[0]) ? count($matches[0]) : 0;

            for ($i = 0; $i < $image_count; $i++) {
                $image_time += $i < 10 ? self::IMAGE_TIME_FIRST_TEN[$i] : self::IMAGE_TIME_REMAINING;
            }
        }

        $seconds = ($word_count / $wpm) * 60 + $image_time;
        return (int) ceil($seconds / 60);
    }

    /**
     * Render-context cache for the post content — `apply_filters
     * ('the_content', …)` is the expensive call, and the result is
     * stable until the next save. One read per post-save then served
     * from cache until invalidated by the save hook.
     */
    private function get_rendered_content(int $post_id): string
    {
        $cache_key = 'content_' . $post_id;
        $cached    = \wp_cache_get($cache_key, self::RENDER_CACHE_GROUP);

        if ($cached !== false) {
            return (string) $cached;
        }

        $raw = (string) \get_post_field('post_content', $post_id);
        if ($raw === '') {
            return '';
        }

        $rendered = (string) \apply_filters('the_content', $raw);
        \wp_cache_set($cache_key, $rendered, self::RENDER_CACHE_GROUP, self::RENDER_CACHE_DURATION);

        return $rendered;
    }
}
