<?php

declare(strict_types=1);

namespace Orbitools\Modules\Post_Templates;

use Orbitools\Core\Abstracts\Module_Base;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Post Templates module — config-driven block-template management
 * keyed on a CPT/taxonomy pair.
 *
 * Handles two template flavours per pair:
 *
 *   1. **Post templates**   — auto-loaded when creating a post of the
 *                             configured CPT, picked from a list of synced
 *                             patterns assigned to the matching pattern
 *                             category. Driven by the editor modal in
 *                             assets/editor.js.
 *   2. **Archive templates** — a synced pattern assigned to a taxonomy term
 *                             that themes render on that term's archive
 *                             page via {@see ::get_archive_template_id()}.
 *
 * Pairs come from two sources, in this order:
 *
 *   1. The repeater on the module's settings page (stored under the
 *      `post-templates_configs` key in `orbitools_settings`).
 *   2. The `orbitools/post_templates/configs` filter, which receives the
 *      UI-stored rows as its initial value — themes can add to (or
 *      override) them in code by returning a modified array:
 *
 *          add_filter('orbitools/post_templates/configs', function ($configs) {
 *              $configs[] = ['post_type' => 'resources', 'taxonomy' => 'resource-type', 'label' => 'Resource'];
 *              return $configs;
 *          });
 *
 * Ported from the dream-and-leap theme's `Logger\Post_Templates\Component`.
 *
 * @package Orbitools
 * @since   1.0.0
 */
final class Post_Templates extends Module_Base
{
    protected const VERSION = '1.0.0';

    /**
     * Registered configurations, keyed by post type.
     *
     * @var array<string, array<string, mixed>>
     */
    private array $configs = [];

    /**
     * Lookup table: taxonomy slug → config array.
     *
     * @var array<string, array<string, mixed>>
     */
    private array $by_taxonomy = [];

    public function get_slug(): string
    {
        return 'post-templates';
    }

    public function get_name(): string
    {
        return \__('Post Templates', 'orbitools');
    }

    public function get_description(): string
    {
        return \__('Auto-load synced block patterns when creating posts of configured CPTs, plus optional per-taxonomy archive templates.', 'orbitools');
    }

    public function init(): void
    {
        $stored  = self::stored_configs();
        $configs = (array) \apply_filters('orbitools/post_templates/configs', $stored);

        foreach ($configs as $cfg) {
            if (!is_array($cfg)) {
                continue;
            }
            $pt    = (string) ($cfg['post_type'] ?? '');
            $tax   = (string) ($cfg['taxonomy']  ?? '');
            $label = (string) ($cfg['label']     ?? '');

            if ($pt === '' || $tax === '' || $label === '') {
                continue;
            }

            $full = [
                'post_type'                => $pt,
                'taxonomy'                 => $tax,
                'label'                    => $label,
                // Post templates
                'pattern_category'         => "{$pt}-templates",
                'pattern_label'            => "{$label} Templates",
                'placeholder_class'        => "{$pt}-template-placeholder",
                'meta_key'                 => "{$pt}_template_pattern",
                // Archive templates
                'archive_pattern_category' => "{$pt}-archive-templates",
                'archive_pattern_label'    => "{$label} Archive Templates",
                'archive_meta_key'         => "{$pt}_archive_template_pattern",
            ];

            $this->configs[$pt]      = $full;
            $this->by_taxonomy[$tax] = $full;
        }

        // Nothing to do without configured pairs — bail before wiring hooks.
        if (empty($this->configs)) {
            return;
        }

        \add_action('init', [$this, 'register_pattern_categories']);
        \add_action('init', [$this, 'register_empty_templates'], 20);
        \add_action('set_object_terms', [$this, 'apply_template_on_type_change'], 10, 6);
        \add_action('enqueue_block_editor_assets', [$this, 'enqueue_editor_script']);
        \add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_styles']);
        \add_action('wp_ajax_orbitools_pt_get_template', [$this, 'ajax_get_template']);

        foreach ($this->by_taxonomy as $tax => $cfg) {
            \add_action("{$tax}_edit_form_fields", [$this, 'add_pattern_field'], 10, 2);
            \add_action("edited_{$tax}",          [$this, 'save_pattern_field'], 10, 2);

            \add_filter("manage_edit-{$tax}_columns",  [$this, 'add_template_column']);
            \add_filter("manage_edit-{$tax}_columns",  [$this, 'add_archive_template_column'], 11);
            \add_filter("manage_{$tax}_custom_column", [$this, 'render_template_column'], 10, 3);
            \add_filter("manage_{$tax}_custom_column", [$this, 'render_archive_template_column'], 10, 3);
            \add_filter("{$tax}_row_actions",          [$this, 'add_row_action'], 10, 2);
        }
    }

    public function get_default_settings(): array
    {
        return [];
    }

    // =========================================================================
    // Pattern Categories
    // =========================================================================

    /**
     * Register pattern categories for all configured post types.
     */
    public function register_pattern_categories(): void
    {
        foreach ($this->configs as $cfg) {
            // Post template category
            $slug  = $cfg['pattern_category'];
            $label = $cfg['pattern_label'];

            if (\function_exists('register_block_pattern_category')) {
                \register_block_pattern_category($slug, ['label' => $label]);
            }

            if (!\term_exists($slug, 'wp_pattern_category')) {
                \wp_insert_term($label, 'wp_pattern_category', ['slug' => $slug]);
            }

            // Archive template category
            $archive_slug  = $cfg['archive_pattern_category'];
            $archive_label = $cfg['archive_pattern_label'];

            if (\function_exists('register_block_pattern_category')) {
                \register_block_pattern_category($archive_slug, ['label' => $archive_label]);
            }

            if (!\term_exists($archive_slug, 'wp_pattern_category')) {
                \wp_insert_term($archive_label, 'wp_pattern_category', ['slug' => $archive_slug]);
            }
        }
    }

    // =========================================================================
    // Taxonomy List Table — Post Template Column
    // =========================================================================

    /**
     * @param array<string, string> $columns
     * @return array<string, string>
     */
    public function add_template_column(array $columns): array
    {
        $position = array_search('description', array_keys($columns), true);
        $offset   = $position !== false ? $position + 1 : count($columns);

        $header = '<span class="orb-pt-tooltip" data-label="' . \esc_attr__('Post Template', 'orbitools') . '">'
            . '<span class="orb-pt-column-icon">'
            . '<span class="screen-reader-text">' . \esc_html__('Post Template', 'orbitools') . '</span>'
            . '</span></span>';

        return array_slice($columns, 0, $offset, true)
            + ['orb-template' => $header]
            + array_slice($columns, $offset, null, true);
    }

    public function render_template_column(string $content, string $column_name, int $term_id): string
    {
        if ($column_name !== 'orb-template') {
            return $content;
        }

        return $this->render_status_column($term_id, 'meta_key');
    }

    // =========================================================================
    // Taxonomy List Table — Archive Template Column
    // =========================================================================

    /**
     * @param array<string, string> $columns
     * @return array<string, string>
     */
    public function add_archive_template_column(array $columns): array
    {
        $position = array_search('orb-template', array_keys($columns), true);
        $offset   = $position !== false ? $position + 1 : count($columns);

        $header = '<span class="orb-pt-tooltip" data-label="' . \esc_attr__('Archive Template', 'orbitools') . '">'
            . '<span class="orb-pt-column-icon">'
            . '<span class="screen-reader-text">' . \esc_html__('Archive Template', 'orbitools') . '</span>'
            . '</span></span>';

        return array_slice($columns, 0, $offset, true)
            + ['orb-archive-template' => $header]
            + array_slice($columns, $offset, null, true);
    }

    public function render_archive_template_column(string $content, string $column_name, int $term_id): string
    {
        if ($column_name !== 'orb-archive-template') {
            return $content;
        }

        return $this->render_status_column($term_id, 'archive_meta_key');
    }

    // =========================================================================
    // Taxonomy List Table — Row Actions
    // =========================================================================

    /**
     * @param array<string, string> $actions
     */
    public function add_row_action(array $actions, \WP_Term $term): array
    {
        $cfg = $this->by_taxonomy[$term->taxonomy] ?? null;

        if (!$cfg) {
            return $actions;
        }

        // Post template action
        $pattern = \get_term_meta($term->term_id, $cfg['meta_key'], true);

        if (!empty($pattern)) {
            $edit_url = $this->get_pattern_edit_url((string) $pattern);

            if ($edit_url) {
                /* translators: %s: post type label, e.g. "Resource" */
                $link_text = sprintf(\esc_html__('Edit %s Template', 'orbitools'), $cfg['label']);
                $actions['edit-template'] = '<a href="' . \esc_url($edit_url) . '">' . $link_text . '</a>';
            }
        }

        // Archive template action
        $archive_pattern = \get_term_meta($term->term_id, $cfg['archive_meta_key'], true);

        if (!empty($archive_pattern)) {
            $archive_edit_url = $this->get_pattern_edit_url((string) $archive_pattern);

            if ($archive_edit_url) {
                /* translators: %s: post type label, e.g. "Resource" */
                $archive_link_text = sprintf(\esc_html__('Edit %s Archive Template', 'orbitools'), $cfg['label']);
                $actions['edit-archive-template'] = '<a href="' . \esc_url($archive_edit_url) . '">' . $archive_link_text . '</a>';
            }
        }

        return $actions;
    }

    // =========================================================================
    // Shared Column Renderer
    // =========================================================================

    /**
     * Render a status icon column for a given meta key type.
     *
     * @param string $meta_key_key Config key: 'meta_key' or 'archive_meta_key'.
     */
    private function render_status_column(int $term_id, string $meta_key_key): string
    {
        $term = \get_term($term_id);

        if (!$term || \is_wp_error($term)) {
            return '';
        }

        $cfg = $this->by_taxonomy[$term->taxonomy] ?? null;

        if (!$cfg) {
            return '';
        }

        $pattern = \get_term_meta($term_id, $cfg[$meta_key_key], true);

        if (!empty($pattern)) {
            $edit_url = $this->get_pattern_edit_url((string) $pattern);
            $title    = \esc_attr__('Edit template', 'orbitools');
            $label    = \esc_attr__('Template assigned', 'orbitools');
            $icon     = '<span class="orb-pt-status orb-pt-status--yes"></span>';

            if ($edit_url) {
                return '<a class="orb-pt-icon" href="' . \esc_url($edit_url) . '" title="' . $title . '" data-label="' . $label . '">'
                    . $icon . '</a>';
            }

            return '<span class="orb-pt-icon" title="' . $title . '" data-label="' . $label . '">' . $icon . '</span>';
        }

        return '<span class="orb-pt-icon" title="' . \esc_attr__('No template', 'orbitools') . '" data-label="' . \esc_attr__('No template', 'orbitools') . '">'
            . '<span class="orb-pt-status orb-pt-status--no"></span></span>';
    }

    /**
     * Get the block editor URL for a synced pattern (`wp_block/{ID}`).
     */
    private function get_pattern_edit_url(string $pattern_slug): ?string
    {
        if (strpos($pattern_slug, 'wp_block/') !== 0) {
            return null;
        }

        $pattern_id = (int) str_replace('wp_block/', '', $pattern_slug);

        if (!$pattern_id || !\get_post($pattern_id)) {
            return null;
        }

        return \admin_url('post.php?post=' . $pattern_id . '&action=edit');
    }

    // =========================================================================
    // Placeholder Templates
    // =========================================================================

    /**
     * Register empty placeholder block templates for each configured CPT.
     * The placeholder paragraph carries a per-CPT class so the editor JS
     * can detect "this post is still empty" and prompt for a template.
     */
    public function register_empty_templates(): void
    {
        foreach ($this->configs as $cfg) {
            $pto = \get_post_type_object($cfg['post_type']);

            if (!$pto) {
                continue;
            }

            $pto->template = [
                ['core/paragraph', [
                    'className'   => $cfg['placeholder_class'],
                    'content'     => '',
                    'placeholder' => 'Waiting for template...',
                ]],
            ];
        }
    }

    // =========================================================================
    // Auto-apply Template on Taxonomy Change
    // =========================================================================

    /**
     * @param array<int|string> $terms
     * @param array<int>        $tt_ids
     * @param array<int>        $old_tt_ids
     */
    public function apply_template_on_type_change(
        int $object_id,
        array $terms,
        array $tt_ids,
        string $taxonomy,
        bool $append,
        array $old_tt_ids
    ): void {
        $cfg = $this->by_taxonomy[$taxonomy] ?? null;

        if (!$cfg) {
            return;
        }

        $post = \get_post($object_id);
        if (!$post || $post->post_type !== $cfg['post_type']) {
            return;
        }

        if (!$this->has_placeholder_content($object_id, $cfg['placeholder_class'])) {
            return;
        }

        if (empty($terms)) {
            return;
        }

        $term_id = is_array($terms) ? (int) $terms[0] : (int) $terms;
        $term    = \get_term($term_id, $taxonomy);

        if (!$term || \is_wp_error($term)) {
            return;
        }

        $template = $this->get_template_for_term($term->slug, $cfg);

        if (!$template) {
            return;
        }

        \wp_update_post([
            'ID'           => $object_id,
            'post_content' => \serialize_blocks($template),
        ]);
    }

    // =========================================================================
    // Taxonomy Term Edit — Pattern Fields
    // =========================================================================

    /**
     * @param \WP_Term $term
     */
    public function add_pattern_field($term, string $taxonomy): void
    {
        $cfg = $this->by_taxonomy[$taxonomy] ?? null;

        if (!$cfg) {
            return;
        }

        // Post template field
        $meta_key        = $cfg['meta_key'];
        $current_pattern = \get_term_meta($term->term_id, $meta_key, true);
        $patterns        = $this->get_available_patterns($cfg['pattern_category']);
        $field_id        = "{$cfg['post_type']}-template-pattern";
        ?>
        <tr class="form-field">
            <th scope="row">
                <label for="<?php echo \esc_attr($field_id); ?>"><?php \esc_html_e('Post Template Pattern', 'orbitools'); ?></label>
            </th>
            <td>
                <select name="<?php echo \esc_attr($meta_key); ?>" id="<?php echo \esc_attr($field_id); ?>" class="postform">
                    <option value="">&mdash; <?php \esc_html_e('Use Default Template', 'orbitools'); ?> &mdash;</option>
                    <?php foreach ($patterns as $pattern_slug => $pattern_data) : ?>
                        <option value="<?php echo \esc_attr($pattern_slug); ?>" <?php \selected($current_pattern, $pattern_slug); ?>>
                            <?php echo \esc_html($pattern_data['title']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="description">
                    <?php
                    printf(
                        /* translators: %s: pattern category label */
                        \esc_html__('Select a block pattern to use as the post template for this type. Create synced patterns in %1$sAppearance &rarr; Patterns%2$s and assign them to the %3$s category.', 'orbitools'),
                        '<strong>',
                        '</strong>',
                        '&ldquo;' . \esc_html($cfg['pattern_label']) . '&rdquo;'
                    );
                    ?>
                </p>
            </td>
        </tr>

        <?php
        // Archive template field
        $archive_meta_key        = $cfg['archive_meta_key'];
        $archive_current_pattern = \get_term_meta($term->term_id, $archive_meta_key, true);
        $archive_patterns        = $this->get_available_patterns($cfg['archive_pattern_category']);
        $archive_field_id        = "{$cfg['post_type']}-archive-template-pattern";
        ?>
        <tr class="form-field">
            <th scope="row">
                <label for="<?php echo \esc_attr($archive_field_id); ?>"><?php \esc_html_e('Archive Template Pattern', 'orbitools'); ?></label>
            </th>
            <td>
                <select name="<?php echo \esc_attr($archive_meta_key); ?>" id="<?php echo \esc_attr($archive_field_id); ?>" class="postform">
                    <option value="">&mdash; <?php \esc_html_e('No Archive Template', 'orbitools'); ?> &mdash;</option>
                    <?php foreach ($archive_patterns as $pattern_slug => $pattern_data) : ?>
                        <option value="<?php echo \esc_attr($pattern_slug); ?>" <?php \selected($archive_current_pattern, $pattern_slug); ?>>
                            <?php echo \esc_html($pattern_data['title']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="description">
                    <?php
                    printf(
                        /* translators: %s: pattern category label */
                        \esc_html__('Select a block pattern for the archive page when filtering by this type. Create synced patterns in %1$sAppearance &rarr; Patterns%2$s and assign them to the %3$s category.', 'orbitools'),
                        '<strong>',
                        '</strong>',
                        '&ldquo;' . \esc_html($cfg['archive_pattern_label']) . '&rdquo;'
                    );
                    ?>
                </p>
            </td>
        </tr>
        <?php
    }

    public function save_pattern_field(int $term_id, int $tt_id): void
    {
        if (!isset($_POST['_wpnonce']) || !\wp_verify_nonce(\sanitize_text_field((string) \wp_unslash($_POST['_wpnonce'])), 'update-tag_' . $term_id)) {
            return;
        }

        if (!\current_user_can('manage_categories')) {
            return;
        }

        foreach ($this->by_taxonomy as $cfg) {
            foreach ([$cfg['meta_key'], $cfg['archive_meta_key']] as $meta_key) {
                if (!isset($_POST[$meta_key])) {
                    continue;
                }

                $value = \sanitize_text_field((string) \wp_unslash($_POST[$meta_key]));

                if ($value === '') {
                    \delete_term_meta($term_id, $meta_key);
                } else {
                    \update_term_meta($term_id, $meta_key, $value);
                }
            }
        }
    }

    // =========================================================================
    // Archive Template — Public API
    // =========================================================================

    /**
     * Static accessor for themes / other code. Resolves the archive
     * template wp_block ID for a taxonomy term — defaults to the current
     * queried object when called with no args (i.e. on a taxonomy archive).
     *
     * Reads the configs filter directly so callers don't need the module
     * instance, and so this works even before the module's hooks have fired.
     */
    public static function get_archive_template_id(?int $term_id = null, ?string $taxonomy = null): ?int
    {
        if ($term_id === null || $taxonomy === null) {
            $queried = \get_queried_object();
            if (!$queried instanceof \WP_Term) {
                return null;
            }
            $term_id  = $queried->term_id;
            $taxonomy = $queried->taxonomy;
        }

        $stored   = self::stored_configs();
        $configs  = (array) \apply_filters('orbitools/post_templates/configs', $stored);
        $meta_key = null;

        foreach ($configs as $cfg) {
            if (!is_array($cfg)) {
                continue;
            }
            if (($cfg['taxonomy'] ?? '') === $taxonomy) {
                $pt = (string) ($cfg['post_type'] ?? '');
                if ($pt === '') {
                    return null;
                }
                $meta_key = "{$pt}_archive_template_pattern";
                break;
            }
        }

        if ($meta_key === null) {
            return null;
        }

        $pattern_slug = \get_term_meta($term_id, $meta_key, true);

        if (empty($pattern_slug) || strpos((string) $pattern_slug, 'wp_block/') !== 0) {
            return null;
        }

        $pattern_id = (int) str_replace('wp_block/', '', (string) $pattern_slug);

        if (!$pattern_id || !\get_post($pattern_id)) {
            return null;
        }

        return $pattern_id;
    }

    // =========================================================================
    // Editor Script + Admin CSS
    // =========================================================================

    /**
     * Enqueue the post-template-loader script when the editor opens for
     * a configured CPT.
     */
    public function enqueue_editor_script(): void
    {
        global $post;

        if (!$post) {
            return;
        }

        $cfg = $this->configs[$post->post_type] ?? null;

        if (!$cfg) {
            return;
        }

        $all_terms = \get_terms([
            'taxonomy'   => $cfg['taxonomy'],
            'hide_empty' => false,
        ]);

        $valid_term_ids = [];
        if (!empty($all_terms) && !\is_wp_error($all_terms)) {
            foreach ($all_terms as $term) {
                if (!empty(\get_term_meta($term->term_id, $cfg['meta_key'], true))) {
                    $valid_term_ids[] = $term->term_id;
                }
            }
        }

        $script_rel = 'inc/Modules/Post_Templates/assets/editor.js';
        $script_abs = ORBITOOLS_DIR . $script_rel;
        $version    = file_exists($script_abs) ? (string) filemtime($script_abs) : self::VERSION;

        \wp_enqueue_script(
            'orbitools-post-templates',
            ORBITOOLS_URL . $script_rel,
            ['wp-blocks', 'wp-element', 'wp-editor', 'wp-data', 'wp-components'],
            $version,
            true
        );

        \wp_localize_script(
            'orbitools-post-templates',
            'orbitoolsPostTemplates',
            [
                'ajaxUrl'          => \admin_url('admin-ajax.php'),
                'nonce'            => \wp_create_nonce('orbitools_post_templates'),
                'taxonomy'         => $cfg['taxonomy'],
                'postType'         => $cfg['post_type'],
                'placeholderClass' => $cfg['placeholder_class'],
                'validTermIds'     => $valid_term_ids,
                'label'            => $cfg['label'],
            ]
        );
    }

    /**
     * Load the taxonomy list column styles on the relevant edit-tags screens only.
     */
    public function enqueue_admin_styles(string $hook): void
    {
        if ($hook !== 'edit-tags.php' && $hook !== 'term.php') {
            return;
        }

        $screen = \function_exists('get_current_screen') ? \get_current_screen() : null;
        if (!$screen || !isset($this->by_taxonomy[$screen->taxonomy])) {
            return;
        }

        $style_rel = 'inc/Modules/Post_Templates/assets/admin.css';
        $style_abs = ORBITOOLS_DIR . $style_rel;
        $version   = file_exists($style_abs) ? (string) filemtime($style_abs) : self::VERSION;

        \wp_enqueue_style(
            'orbitools-post-templates-admin',
            ORBITOOLS_URL . $style_rel,
            [],
            $version
        );
    }

    // =========================================================================
    // AJAX
    // =========================================================================

    /**
     * Return the parsed template content for a given term slug + post type.
     */
    public function ajax_get_template(): void
    {
        \check_ajax_referer('orbitools_post_templates', 'nonce');

        $term_slug = isset($_POST['term_slug']) ? \sanitize_text_field((string) \wp_unslash($_POST['term_slug'])) : '';
        $post_type = isset($_POST['post_type']) ? \sanitize_text_field((string) \wp_unslash($_POST['post_type'])) : '';

        if ($term_slug === '' || $post_type === '') {
            \wp_send_json_error('Missing term_slug or post_type');
            return;
        }

        $cfg = $this->configs[$post_type] ?? null;

        if (!$cfg) {
            \wp_send_json_error('Unknown post type');
            return;
        }

        $template = $this->get_template_for_term($term_slug, $cfg);

        if ($template === null) {
            \wp_send_json_error('No template found for this type');
            return;
        }

        \wp_send_json_success(['content' => \serialize_blocks($template)]);
    }

    // =========================================================================
    // Helpers (private)
    // =========================================================================

    /**
     * Read the UI-stored configs out of `orbitools_settings`. Filters
     * out rows missing any of the three required keys so downstream
     * code can assume well-formed entries.
     *
     * Static so {@see ::get_archive_template_id()} can use it without
     * an instance.
     *
     * @return array<int, array{post_type: string, taxonomy: string, label: string}>
     */
    private static function stored_configs(): array
    {
        $settings = \get_option('orbitools_settings', []);
        if (!is_array($settings)) {
            return [];
        }

        $rows = $settings['post-templates_configs'] ?? [];
        if (!is_array($rows)) {
            return [];
        }

        $out = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $pt    = isset($row['post_type']) ? (string) $row['post_type'] : '';
            $tax   = isset($row['taxonomy'])  ? (string) $row['taxonomy']  : '';
            $label = isset($row['label'])     ? (string) $row['label']     : '';

            if ($pt === '' || $tax === '' || $label === '') {
                continue;
            }

            $out[] = ['post_type' => $pt, 'taxonomy' => $tax, 'label' => $label];
        }
        return $out;
    }

    private function has_placeholder_content(int $post_id, string $placeholder_class): bool
    {
        $content = \get_post_field('post_content', $post_id);
        return strpos((string) $content, $placeholder_class) !== false;
    }

    /**
     * @param array<string, mixed> $cfg
     * @return array<int, array<string, mixed>>|null Parsed blocks or null.
     */
    private function get_template_for_term(string $type_slug, array $cfg): ?array
    {
        $term = \get_term_by('slug', $type_slug, $cfg['taxonomy']);

        if (!$term || \is_wp_error($term)) {
            return null;
        }

        $pattern_slug = \get_term_meta($term->term_id, $cfg['meta_key'], true);

        if (empty($pattern_slug)) {
            return null;
        }

        return $this->get_pattern_template((string) $pattern_slug);
    }

    /**
     * Get parsed blocks for a synced pattern (`wp_block/{ID}`).
     *
     * @return array<int, array<string, mixed>>|null
     */
    private function get_pattern_template(string $pattern_slug): ?array
    {
        if (strpos($pattern_slug, 'wp_block/') !== 0) {
            return null;
        }

        $pattern_id   = (int) str_replace('wp_block/', '', $pattern_slug);
        $pattern_post = \get_post($pattern_id);

        if (!$pattern_post || $pattern_post->post_type !== 'wp_block' || empty($pattern_post->post_content)) {
            return null;
        }

        return \parse_blocks($pattern_post->post_content);
    }

    /**
     * Get available synced patterns for a pattern category, indexed by
     * the `wp_block/{ID}` slug the rest of the module expects.
     *
     * @return array<string, array{title: string, content: string, categories: array<int, string>}>
     */
    private function get_available_patterns(string $category_slug): array
    {
        $synced_patterns = \get_posts([
            'post_type'      => 'wp_block',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'tax_query'      => [
                [
                    'taxonomy' => 'wp_pattern_category',
                    'field'    => 'slug',
                    'terms'    => $category_slug,
                ],
            ],
            'orderby' => 'title',
            'order'   => 'ASC',
        ]);

        $patterns = [];
        foreach ($synced_patterns as $p) {
            $patterns['wp_block/' . $p->ID] = [
                'title'      => $p->post_title,
                'content'    => $p->post_content,
                'categories' => [$category_slug],
            ];
        }

        return $patterns;
    }
}
