<?php

namespace Orbitools\Modules\Block_Manager;

use Orbitools\Core\Abstracts\Module_Base;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Block Manager module.
 *
 * Walks `WP_Block_Type_Registry` and exposes the result via REST so
 * the React admin can present an "enable / disable per block"
 * picker. A `disabled` setting (array of block names) is subtracted
 * from `allowed_block_types_all` so the editor inserter actually
 * respects the choices.
 *
 * Icon resolution: most core blocks register their icon in JS
 * (registerBlockType in their editor script) — PHP can't see those.
 * We work around it by tapping into the editor itself: every time
 * a real block editor loads, an inline script reads
 * wp.blocks.getBlockTypes(), serialises each icon via
 * wp.element.renderToString(), and POSTs the lot to
 * /orbitools/v1/block-icons, which stores them in a transient. The
 * Block Manager then overlays the cached icons on top of whatever
 * PHP's WP_Block_Type::$icon provides.
 *
 * @package Orbitools
 * @since   3.3.0
 */
final class Block_Manager extends Module_Base
{
    /**
     * Transient holding block name → serialised SVG/dashicon-name
     * string, written by the editor-side collector script. No
     * expiry: every editor load refreshes it, and the file stays
     * fresh by virtue of normal editing activity.
     */
    private const ICON_CACHE_KEY = 'orbitools_block_icons';

    /**
     * Default deny-list for Orbital sites — curated from an actual
     * production install (2026-05-29). These are the blocks we
     * never use across the fleet, so on a fresh install with the
     * Block Manager module enabled they start off disabled and
     * the user only has to flip on the handful they want.
     *
     * Stored in orbitools_settings as `block-manager_disabled`
     * once the user touches anything; until then we just serve
     * this list at the API boundary so the UI reflects them as
     * disabled. Override by toggling any block back ON and saving.
     */
    private const DEFAULT_DISABLED = [
        'core/buttons',
        'core/button',
        'core/column',
        'core/columns',
        'core/comment-template',
        'core/home-link',
        'core/navigation-link',
        'core/group',
        'core/more',
        'core/navigation-overlay-close',
        'core/nextpage',
        'core/navigation-submenu',
        'core/spacer',
        'core/text-columns',
        'core/embed',
        'core/audio',
        'core/cover',
        'core/footnotes',
        'core/math',
        'core/verse',
        'core/preformatted',
        'core/pullquote',
        'core/post-author',
        'core/breadcrumbs',
        'core/comment-content',
        'core/comment-date',
        'core/rss',
        'core/legacy-widget',
        'core/search',
        'core/latest-comments',
        'core/archives',
        'core/calendar',
        'core/latest-posts',
        'core/page-list-item',
        'core/tag-cloud',
        'core/widget-group',
        'core/post-comments',
        'core/terms-query',
        'core/term-template',
        'core/term-name',
        'core/term-description',
        'core/term-count',
        'core/template-part',
        'core/read-more',
        'core/query-total',
        'core/query-title',
        'core/query',
        'core/query-pagination-previous',
        'core/post-navigation-link',
        'core/post-template',
        'core/post-terms',
        'core/query-pagination',
        'core/query-pagination-numbers',
        'core/query-no-results',
        'core/loginout',
        'core/navigation',
        'core/query-pagination-next',
        'core/post-excerpt',
        'core/post-date',
        'core/post-featured-image',
        'core/post-content',
        'core/comments-title',
        'core/comments-pagination-previous',
        'core/comments-pagination-next',
        'core/post-comments-count',
        'core/post-comments-form',
        'core/comments-pagination-numbers',
        'core/comments-pagination',
        'core/post-comments-link',
        'core/comments',
        'core/comment-reply-link',
        'core/comment-edit-link',
        'core/comment-author-name',
        'core/video',
        'core/gallery',
        'core/categories',
        'core/page-list',
        'core/accordion',
        'core/accordion-heading',
        'core/accordion-panel',
        'core/accordion-item',
    ];

    public function get_slug(): string
    {
        return 'block-manager';
    }

    public function get_name(): string
    {
        return \__('Block Manager', 'orbitools');
    }

    public function get_description(): string
    {
        return \__('List every block registered on the site and toggle which ones appear in the editor inserter.', 'orbitools');
    }

    public function init(): void
    {
        // Priority 100 so any plugin that returns a tighter
        // allowlist at the default priority still wins — we just
        // subtract our deny list from whatever they hand us.
        \add_filter('allowed_block_types_all', [$this, 'filter_allowed_blocks'], 100, 2);
        \add_action('rest_api_init', [$this, 'register_rest_routes']);
        // Drop the icon-collector inline script into every block
        // editor page (post.php, site editor, widgets, etc.) so the
        // cache stays fresh.
        \add_action('enqueue_block_editor_assets', [$this, 'enqueue_icon_collector']);
        // Inject our default deny-list into the settings response
        // when the user hasn't saved anything yet, so the React UI
        // renders those toggles as already-off on a fresh install.
        \add_filter('orbitools/settings_defaults', [$this, 'apply_default_disabled'], 10, 2);
    }

    /**
     * Apply the curated default deny-list when the user hasn't
     * stored anything yet. As soon as they save (even an empty
     * list), the stored value wins.
     *
     * @param array<string,mixed> $settings
     * @param string              $slug
     * @return array<string,mixed>
     */
    public function apply_default_disabled(array $settings, string $slug): array
    {
        if ($slug !== $this->get_slug()) {
            return $settings;
        }
        if (!array_key_exists('disabled', $settings)) {
            $settings['disabled'] = self::DEFAULT_DISABLED;
        }
        return $settings;
    }

    /**
     * Subtract the user's disabled-block list from whatever WP /
     * other plugins decided the allowed set is.
     *
     * @param array<int,string>|bool $allowed
     * @param mixed                  $context
     * @return array<int,string>|bool
     */
    public function filter_allowed_blocks($allowed, $context)
    {
        $disabled = $this->get_disabled_blocks();
        if (empty($disabled)) {
            return $allowed;
        }

        // Upstream said "nothing allowed" — leave them be.
        if ($allowed === false) {
            return false;
        }

        // Upstream said "everything allowed" → start from the full
        // registry and remove our blocks.
        if ($allowed === true) {
            $registered = \WP_Block_Type_Registry::get_instance()->get_all_registered();
            return array_values(array_diff(array_keys($registered), $disabled));
        }

        if (is_array($allowed)) {
            return array_values(array_diff($allowed, $disabled));
        }

        return $allowed;
    }

    public function register_rest_routes(): void
    {
        \register_rest_route('orbitools/v1', '/blocks', [
            [
                'methods'             => 'GET',
                'callback'            => [$this, 'rest_list_blocks'],
                'permission_callback' => function () {
                    return \current_user_can('manage_options');
                },
            ],
        ]);
        \register_rest_route('orbitools/v1', '/block-icons', [
            [
                'methods'             => 'POST',
                'callback'            => [$this, 'rest_save_icons'],
                // Editor permission, since this fires from inside
                // the block editor on a normal post-edit pageload.
                'permission_callback' => function () {
                    return \current_user_can('edit_posts');
                },
            ],
        ]);
    }

    public function rest_list_blocks(): \WP_REST_Response
    {
        $registered   = \WP_Block_Type_Registry::get_instance()->get_all_registered();
        $cached_icons = $this->get_cached_icons();
        $blocks       = [];
        foreach ($registered as $name => $type) {
            $name_str = (string) $name;
            if ($this->is_orbital_block($name_str)) {
                continue;
            }
            $blocks[] = $this->serialise_block($name_str, $type, $cached_icons);
        }
        // Stable order: by category then title.
        usort($blocks, static function (array $a, array $b): int {
            $cmp = strcmp($a['category'], $b['category']);
            return $cmp !== 0 ? $cmp : strcmp($a['title'], $b['title']);
        });
        return new \WP_REST_Response([
            'blocks'          => $blocks,
            // Lets the UI nudge the user to open the editor once
            // when nothing's been cached yet.
            'cache_populated' => !empty($cached_icons),
        ]);
    }

    /**
     * Receive the icon dump from the editor-side collector and
     * store it in a transient. Replaces wholesale rather than
     * merging — last editor load wins, which keeps the cache in
     * sync with whatever blocks are actually registered now.
     */
    public function rest_save_icons(\WP_REST_Request $request): \WP_REST_Response
    {
        $payload = $request->get_param('icons');
        if (!is_array($payload)) {
            return new \WP_REST_Response(['saved' => 0]);
        }
        $clean = [];
        foreach ($payload as $name => $svg) {
            if (!is_string($name) || $name === '' || !is_string($svg) || $svg === '') {
                continue;
            }
            // Cap individual icon size at 16KB so a runaway SVG
            // can't bloat the option indefinitely.
            if (strlen($svg) > 16384) {
                continue;
            }
            $clean[$name] = $svg;
        }
        if (!empty($clean)) {
            // TTL 0 = no expiry; rewritten on every editor load.
            \set_transient(self::ICON_CACHE_KEY, $clean, 0);
        }
        return new \WP_REST_Response(['saved' => count($clean)]);
    }

    /**
     * Inline JS dropped into block editor pages. Reads the JS-side
     * block registry (the only place where most core block icons
     * actually live — PHP's WP_Block_Type::$icon is null for them)
     * and POSTs an icon map to our REST endpoint.
     *
     * Uses `wp.element.renderToString` to serialise React-element
     * icons to SVG markup we can store and re-render later.
     */
    public function enqueue_icon_collector(): void
    {
        $bootstrap = \wp_json_encode([
            'url'   => \esc_url_raw(\rest_url('orbitools/v1/block-icons')),
            'nonce' => \wp_create_nonce('wp_rest'),
        ]);
        if (!is_string($bootstrap)) {
            return;
        }

        // The script runs after wp-blocks loads. We delay collection
        // by 1.5s to give late-registering blocks (some plugins
        // register on `init` or later) a chance to land in the
        // registry before we snapshot it.
        $script = <<<JS
(function () {
    if (typeof wp === 'undefined' || !wp.blocks || !wp.element || !wp.apiFetch) {
        return;
    }
    var data = {$bootstrap};
    var collect = function () {
        var types = wp.blocks.getBlockTypes();
        var icons = {};
        types.forEach(function (type) {
            var icon = type && type.icon;
            if (!icon) { return; }
            try {
                if (typeof icon === 'string') {
                    icons[type.name] = icon;
                    return;
                }
                if (icon.src) {
                    if (typeof icon.src === 'string') {
                        icons[type.name] = icon.src;
                    } else {
                        icons[type.name] = wp.element.renderToString(icon.src);
                    }
                    return;
                }
                icons[type.name] = wp.element.renderToString(icon);
            } catch (e) {
                // Skip icons that can't be serialised.
            }
        });
        wp.apiFetch({
            path: 'orbitools/v1/block-icons',
            method: 'POST',
            data: { icons: icons }
        }).catch(function () { /* best-effort cache */ });
    };
    if (wp.domReady) {
        wp.domReady(function () { setTimeout(collect, 1500); });
    } else {
        setTimeout(collect, 2000);
    }
}());
JS;

        \wp_add_inline_script('wp-blocks', $script, 'after');
    }

    /**
     * Our blocks register under `orb/` or `orbital/` — both prefixes
     * are recognised by the theme's allow-list and by every existing
     * site. Matching both keeps history-compatible installs covered.
     */
    private function is_orbital_block(string $name): bool
    {
        return strpos($name, 'orb/') === 0 || strpos($name, 'orbital/') === 0;
    }

    /**
     * @param array<string,string> $cached_icons
     * @return array<string,mixed>
     */
    private function serialise_block(string $name, \WP_Block_Type $type, array $cached_icons): array
    {
        return [
            'name'        => $name,
            'title'       => is_string($type->title ?? null) && $type->title !== '' ? $type->title : $name,
            'category'    => is_string($type->category ?? null) && $type->category !== '' ? $type->category : 'uncategorized',
            'description' => is_string($type->description ?? null) ? $type->description : '',
            // Editor-cache wins; PHP's block.json icon (string OR
            // the {src,background,foreground} object form) is the
            // fallback. We do NOT keep a hardcoded core-block icon
            // map any more — those guesses were wrong often enough
            // to create more confusion than they cured.
            'icon'        => $cached_icons[$name] ?? $this->serialise_icon($type),
        ];
    }

    /**
     * Return the icon as a string if we can:
     *   - block.json's `icon` as a dashicon slug ("format-image") or
     *     inline SVG markup ("<svg…")
     *   - block.json's `icon` as the object form `{src, background,
     *     foreground}` — we extract `src` when it's a string
     * null when the icon is a React element / non-serialisable;
     * the editor cache (above) covers those.
     */
    private function serialise_icon(\WP_Block_Type $type): ?string
    {
        $icon = $type->icon ?? null;
        if (is_string($icon) && $icon !== '') {
            return $icon;
        }
        if (is_array($icon) && isset($icon['src']) && is_string($icon['src']) && $icon['src'] !== '') {
            return $icon['src'];
        }
        if (is_object($icon) && isset($icon->src) && is_string($icon->src) && $icon->src !== '') {
            return $icon->src;
        }
        return null;
    }

    /**
     * @return array<string,string>
     */
    private function get_cached_icons(): array
    {
        $cached = \get_transient(self::ICON_CACHE_KEY);
        if (!is_array($cached)) {
            return [];
        }
        $out = [];
        foreach ($cached as $name => $svg) {
            if (is_string($name) && $name !== '' && is_string($svg) && $svg !== '') {
                $out[$name] = $svg;
            }
        }
        return $out;
    }

    /**
     * Read the user's disabled list, falling back to our curated
     * default when nothing's been stored yet. Pass `null` as the
     * sentinel so we can distinguish "never saved" from "saved an
     * empty list" (the latter should disable nothing).
     *
     * @return array<int,string>
     */
    private function get_disabled_blocks(): array
    {
        $value = $this->get_setting('disabled', null);
        if ($value === null) {
            return self::DEFAULT_DISABLED;
        }
        if (!is_array($value)) {
            return [];
        }
        $names = [];
        foreach ($value as $v) {
            if (is_string($v) && $v !== '') {
                $names[] = $v;
            }
        }
        return $names;
    }
}
