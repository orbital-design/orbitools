<?php

namespace Orbitools\Blocks\Grid;

use Orbitools\Core\Abstracts\Module_Base;
use Orbitools\Controls\Spacings_Controls\SpacingsRenderer;
use Orbitools\Controls\Content_Width_Controls\Content_Width_Renderer;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Grid Block
 *
 * Registers and manages the Grid block — a CSS-Grid container whose cells
 * carry responsive column spans.
 */
class Grid extends Module_Base
{
    /**
     * Module version
     */
    protected const VERSION = '1.0.0';

    /**
     * Classes that belong on the full-bleed outer wrapper when content is
     * constrained — alignfull plus background / text-colour classes so the
     * colour spans the viewport while the grid is capped at content width.
     */
    private const WRAPPER_CLASS_REGEX = '/^(?:has-background|has-text-color|has-link-color|has-.*-background-color|has-.*-color|has-vivid-.*|has-pale-.*|has-luminous-.*)$/';

    /**
     * Classes excluded from the inner constrained grid (they live on the outer
     * wrapper, or are the block/alignment classes WordPress added).
     */
    private const INNER_EXCLUDE_REGEX = '/^(?:alignfull|alignwide|wp-block-orb-grid|has-background|has-text-color|has-link-color|has-.*-background-color|has-.*-color|has-vivid-.*|has-pale-.*|has-luminous-.*)$/';

    /**
     * Get the module's unique slug identifier
     */
    public function get_slug(): string
    {
        return 'grid-block';
    }

    /**
     * Get the module's display name
     */
    public function get_name(): string
    {
        return \__('Grid', 'orbitools');
    }

    /**
     * Get the module's description
     */
    public function get_description(): string
    {
        return \__('CSS-Grid layout container with responsive column spans', 'orbitools');
    }

    /**
     * Get the module's version
     */
    public function get_version(): string
    {
        return self::VERSION;
    }

    /**
     * Check if the module is currently enabled
     */
    public function is_enabled(): bool
    {
        return true;
    }

    /**
     * Initialize the Grid block
     */
    public function init(): void
    {
        // Prevent multiple registrations
        static $registered = false;
        if ($registered) {
            return;
        }
        $registered = true;

        // Register immediately if init has already fired, otherwise hook into it
        if (\did_action('init')) {
            $this->register_block();
        } else {
            \add_action('init', [$this, 'register_block']);
        }
    }

    /**
     * Register the Grid block
     */
    public function register_block(): void
    {
        $block_dir = ORBITOOLS_DIR . 'build/blocks/grid/';

        if (file_exists($block_dir . 'block.json')) {
            \register_block_type($block_dir, [
                'render_callback' => [$this, 'render_callback']
            ]);
        }
    }

    /**
     * Render callback for Grid block
     *
     * @param array     $attributes Block attributes
     * @param string    $content    Block inner content
     * @param \WP_Block $block      Block instance
     * @return string   Rendered HTML
     */
    public function render_callback(array $attributes, string $content, \WP_Block $block): string
    {
        // Default values - must match block.json defaults
        $defaults = [
            'columnSystem'  => 12,
            'stackOnMobile' => true,
            'align'         => '',
        ];

        // Merge attributes with defaults
        $attributes = array_merge($defaults, $attributes);

        // Extract attributes
        $column_system   = (int) $attributes['columnSystem'];
        $stack_on_mobile = (bool) $attributes['stackOnMobile'];

        // The grid track count travels on the grid container as a custom prop.
        $grid_cols_decl = '--orb-grid-cols:' . $column_system . ';';

        // Render inner blocks
        $inner_blocks_content = '';
        if (!empty($block->inner_blocks)) {
            foreach ($block->inner_blocks as $inner_block) {
                $inner_blocks_content .= $inner_block->render();
            }
        }

        // Full-width grids can constrain their cells to the content / wide
        // width while the background bleeds full-width. That needs the
        // nested-wrapper pattern (outer full-bleed, inner constrained grid).
        // Width resolves via the global Content Width control.
        $needs_wrapper = Content_Width_Renderer::needs_constraint($attributes);

        if (!$needs_wrapper) {
            // Single .orb-grid wrapper carries everything. Letting WordPress
            // compose class + style avoids the duplicate `style` attribute a
            // manual rebuild would produce.
            $extra = [
                'class' => SpacingsRenderer::add_spacings('orb-grid', $attributes),
                'style' => $grid_cols_decl,
            ];
            if ($stack_on_mobile) {
                // Drives the mobile single-column collapse in CSS.
                $extra['data-stacked'] = 'true';
            }
            $wrapper_attributes = \get_block_wrapper_attributes($extra);

            return sprintf('<div %s>%s</div>', $wrapper_attributes, $inner_blocks_content);
        }

        // Nested wrapper: outer div bleeds full-width and carries alignfull +
        // background/text colour classes; the inner .orb-grid is the grid
        // container, constrained and centred via data-constrain.
        $wrapper_attributes = \get_block_wrapper_attributes();

        $existing_classes = '';
        if (preg_match('/class=["\']([^"\']*)["\']/', $wrapper_attributes, $matches)) {
            $existing_classes = $matches[1];
        }
        $existing_style = '';
        if (preg_match('/style=["\']([^"\']*)["\']/', $wrapper_attributes, $matches)) {
            $existing_style = $matches[1];
        }

        // Outer keeps full-bleed/background classes; inner takes the rest.
        $outer_classes = $this->get_wrapper_classes($existing_classes);
        $inner_classes = $this->get_inner_classes($existing_classes);

        // Inner grid: semantic class + carried-over classes + has-gap spacings.
        $base_classes = trim('orb-grid ' . $inner_classes);
        $all_classes  = SpacingsRenderer::add_spacings($base_classes, $attributes);

        // Merge the column-count custom property ahead of any supports style
        // (border-radius, background image) so the inner div has one style attr.
        $inner_style = $grid_cols_decl . $existing_style;

        // Any non-class, non-style attrs WordPress emitted (e.g. an anchor id)
        // ride along on the inner grid, matching Row Layout.
        $other_attrs = preg_replace('/\s*(?:class|style)=["\'][^"\']*["\']/', '', $wrapper_attributes);
        $other_attrs = trim($other_attrs);
        $other_html  = $other_attrs ? ' ' . $other_attrs : '';

        $data_attrs = ' data-constrain="' . \esc_attr(Content_Width_Renderer::constrain_value($attributes)) . '"';
        if ($stack_on_mobile) {
            $data_attrs .= ' data-stacked="true"';
        }

        return sprintf(
            '<div class="%s"><div class="%s" style="%s"%s%s>%s</div></div>',
            \esc_attr($outer_classes),
            \esc_attr($all_classes),
            \esc_attr($inner_style),
            $other_html,
            $data_attrs,
            $inner_blocks_content
        );
    }

    /**
     * Classes for the outer full-bleed wrapper (constrained mode).
     *
     * Keeps alignfull plus any background / text-colour classes so the colour
     * bleeds the full viewport width while the inner grid is constrained.
     */
    private function get_wrapper_classes(string $class_names): string
    {
        $wrapper_classes = ['alignfull'];

        foreach (array_filter(explode(' ', $class_names)) as $class) {
            if (preg_match(self::WRAPPER_CLASS_REGEX, $class)) {
                $wrapper_classes[] = $class;
            }
        }

        return implode(' ', array_unique($wrapper_classes));
    }

    /**
     * Classes for the inner constrained grid (constrained mode).
     *
     * Everything WordPress emitted except alignment, the block class, and the
     * colour classes that belong on the full-bleed outer wrapper.
     */
    private function get_inner_classes(string $class_names): string
    {
        $inner_classes = [];

        foreach (array_filter(explode(' ', $class_names)) as $class) {
            if (!preg_match(self::INNER_EXCLUDE_REGEX, $class)) {
                $inner_classes[] = $class;
            }
        }

        return implode(' ', $inner_classes);
    }

    /**
     * Get default settings
     */
    public function get_default_settings(): array
    {
        return [];
    }
}
