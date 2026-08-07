<?php

namespace Orbitools\Blocks\Spacer;

use Orbitools\Core\Abstracts\Module_Base;
use Orbitools\Core\Helpers\Minifier;
use Orbitools\Core\Helpers\Spacing_Utils;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Spacer Block
 *
 * Registers and manages the Spacer block for adding responsive spacing between content
 */
class Spacer extends Module_Base
{
    /**
     * Module version
     */
    protected const VERSION = '1.0.0';

    /**
     * Get the module's unique slug identifier
     */
    public function get_slug(): string
    {
        return 'spacer-block';
    }

    /**
     * Get the module's display name
     */
    public function get_name(): string
    {
        return \__('Spacer Block', 'orbitools');
    }

    /**
     * Get the module's description
     */
    public function get_description(): string
    {
        return \__('Responsive spacing block with height controls', 'orbitools');
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
     * Initialize the Spacer block
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
            $this->setup_css_generation();
        } else {
            \add_action('init', [$this, 'register_block']);
            \add_action('init', [$this, 'setup_css_generation']);
        }
    }

    /**
     * Register the Spacer block
     */
    public function register_block(): void
    {
        $block_dir = ORBITOOLS_DIR . 'build/blocks/spacer/';

        if (file_exists($block_dir . 'block.json')) {
            \register_block_type($block_dir);
        }
    }

    /**
     * Get default settings
     */
    public function get_default_settings(): array
    {
        return [];
    }

    /**
     * Setup CSS generation for spacer block
     */
    public function setup_css_generation(): void
    {
        // Enqueue the frontend style on wp_enqueue_scripts so it prints
        // in <head>. The previous render_block-based late enqueue relied
        // on print_late_styles() firing in the footer, which doesn't
        // happen reliably in block themes — the style silently never
        // printed and spacer heights had no CSS behind them. Same direct
        // pattern as Gaps_CSS_Generator.
        \add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_styles']);

        // Add inline styles for block editor
        \add_action('enqueue_block_editor_assets', [$this, 'enqueue_editor_styles']);
    }

    /**
     * Enqueue the frontend spacer CSS (inline).
     *
     * Conditional where we can verify it cheaply: on singular views we
     * skip the CSS when the queried post doesn't contain an orb/spacer.
     * On template-driven views (archives, FSE templates) a spacer may
     * live in a template part we can't cheaply inspect here, so we load
     * unconditionally rather than risk missing it — correctness over a
     * few KB of inline CSS.
     */
    public function enqueue_frontend_styles(): void
    {
        // Filter to allow themes to disable frontend CSS generation
        if (!\apply_filters('orbitools_spacer_frontend_css', true)) {
            return;
        }

        if (\is_singular()) {
            $post = \get_queried_object();
            if ($post instanceof \WP_Post && !\has_block('orb/spacer', $post)) {
                return;
            }
        }

        $css = $this->generate_spacer_css();
        if (!empty($css)) {
            \wp_register_style('orbitools-spacer-frontend', false);
            \wp_enqueue_style('orbitools-spacer-frontend');
            \wp_add_inline_style('orbitools-spacer-frontend', $css);
        }
    }

    /**
     * Enqueue editor styles with filter
     */
    public function enqueue_editor_styles(): void
    {
        // Filter to allow themes to disable editor CSS generation
        if (!\apply_filters('orbitools_spacer_editor_css', true)) {
            return;
        }

        $css = $this->generate_spacer_css();
        if (!empty($css)) {
            // Create a dummy stylesheet handle and enqueue with inline CSS
            \wp_register_style('orbitools-spacer-editor', false);
            \wp_enqueue_style('orbitools-spacer-editor');
            \wp_add_inline_style('orbitools-spacer-editor', $css);
        }
    }

    /**
     * Generate CSS for all spacer classes
     */
    private function generate_spacer_css(): string
    {
        $spacing_sizes = Spacing_Utils::get_spacing_sizes();
        $breakpoints = Spacing_Utils::get_breakpoints();

        if (empty($spacing_sizes)) {
            return '';
        }

        $css = '';

        // Base spacer styles
        $css .= ".orb-spacer {\n";
        $css .= "    display: flex;\n";
        $css .= "    width: 100%;\n";
        $css .= "}\n\n";

        // Special case: zero height
        $css .= ".orb-spacer--0 {\n";
        $css .= "    min-height: 0;\n";
        $css .= "}\n\n";

        // Special case: fill height
        $css .= ".orb-spacer--fill {\n";
        $css .= "    display: flex;\n";
        $css .= "    flex: 1;\n";
        $css .= "}\n\n";

        // Generate spacing size classes. No `, {$size}` fallback: WordPress
        // emits --wp--preset--spacing--{slug} for every preset, and this CSS
        // is only generated when those same presets exist — so the var is
        // always defined and the fallback is dead weight. Slug 0 is
        // special-cased above (there's no preset var for it).
        foreach ($spacing_sizes as $spacing) {
            $slug = $spacing['slug'];

            if ((string) $slug === '0') {
                continue;
            }

            $css .= ".orb-spacer--{$slug} {\n";
            $css .= "    min-height: var(--wp--preset--spacing--{$slug});\n";
            $css .= "}\n\n";
        }

        // Generate responsive classes for all breakpoints. Desktop-
        // first cascade (tablet then mobile), each honouring its own
        // `query` direction (defaults to max-width) so they line up
        // with WordPress's device-preview canvas widths.
        foreach ($breakpoints as $breakpoint) {
            $breakpoint_slug = $breakpoint['slug'];
            $breakpoint_value = $breakpoint['value'];
            $breakpoint_query = $breakpoint['query'] ?? 'max-width';

            $css .= "@media ({$breakpoint_query}: {$breakpoint_value}) {\n";

            // Zero height for this breakpoint
            $css .= "    .{$breakpoint_slug}\:orb-spacer--0 {\n";
            $css .= "        min-height: 0;\n";
            $css .= "    }\n\n";

            // Fill for this breakpoint
            $css .= "    .{$breakpoint_slug}\:orb-spacer--fill {\n";
            $css .= "        display: flex;\n";
            $css .= "        flex: 1;\n";
            $css .= "    }\n\n";

            // Spacing sizes for this breakpoint (no fallback; slug 0 is
            // special-cased above).
            foreach ($spacing_sizes as $spacing) {
                $slug = $spacing['slug'];

                if ((string) $slug === '0') {
                    continue;
                }

                $css .= "    .{$breakpoint_slug}\:orb-spacer--{$slug} {\n";
                $css .= "        min-height: var(--wp--preset--spacing--{$slug});\n";
                $css .= "    }\n\n";
            }

            $css .= "}\n\n";
        }

        return Minifier::css($css);
    }
}