<?php

namespace Orbitools\Blocks\Spacer;

use Orbitools\Core\Abstracts\Module_Base;
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
        // Add inline styles for frontend
        \add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_styles']);

        // Add inline styles for block editor
        \add_action('enqueue_block_editor_assets', [$this, 'enqueue_editor_styles']);
    }

    /**
     * Enqueue frontend styles with filter
     */
    public function enqueue_frontend_styles(): void
    {
        // Filter to allow themes to disable frontend CSS generation
        if (!\apply_filters('orbitools_spacer_frontend_css', true)) {
            return;
        }

        $css = $this->generate_spacer_css();
        if (!empty($css)) {
            // Create a dummy stylesheet handle and enqueue with inline CSS
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

        // Generate spacing size classes
        foreach ($spacing_sizes as $spacing) {
            $slug = $spacing['slug'];
            $size = $spacing['size'];

            $css .= ".orb-spacer--{$slug} {\n";
            $css .= "    min-height: var(--wp--preset--spacing--{$slug}, {$size});\n";
            $css .= "}\n\n";
        }

        // Generate responsive classes for all breakpoints
        foreach ($breakpoints as $breakpoint) {
            $breakpoint_slug = $breakpoint['slug'];
            $breakpoint_value = $breakpoint['value'];

            $css .= "@media (min-width: {$breakpoint_value}) {\n";

            // Zero height for this breakpoint
            $css .= "    .{$breakpoint_slug}\:orb-spacer--0 {\n";
            $css .= "        min-height: 0;\n";
            $css .= "    }\n\n";

            // Fill for this breakpoint
            $css .= "    .{$breakpoint_slug}\:orb-spacer--fill {\n";
            $css .= "        display: flex;\n";
            $css .= "        flex: 1;\n";
            $css .= "    }\n\n";

            // Spacing sizes for this breakpoint
            foreach ($spacing_sizes as $spacing) {
                $slug = $spacing['slug'];
                $size = $spacing['size'];

                $css .= "    .{$breakpoint_slug}\:orb-spacer--{$slug} {\n";
                $css .= "        min-height: var(--wp--preset--spacing--{$slug}, {$size});\n";
                $css .= "    }\n\n";
            }

            $css .= "}\n\n";
        }

        return $css;
    }
}