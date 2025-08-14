<?php

namespace Orbitools\Blocks\Read_More;

use Orbitools\Core\Abstracts\Module_Base;
use Orbitools\Controls\Spacings_Controls\SpacingsRenderer;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Read_More Block
 *
 * Registers and manages the Read More block for creating flexible layout containers
 */
class Read_More extends Module_Base
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
        return 'read-more-block';
    }

    /**
     * Get the module's display name
     */
    public function get_name(): string
    {
        return \__('Read More Block', 'orbitools');
    }

    /**
     * Get the module's description
     */
    public function get_description(): string
    {
        return \__('Accordion like container for hiding and showing simple content', 'orbitools');
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
     * Initialize the Read More block
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
     * Register the Read More block
     */
    public function register_block(): void
    {
        $block_dir = ORBITOOLS_DIR . 'build/blocks/read-more/';

        if (file_exists($block_dir . 'block.json')) {
            \register_block_type($block_dir, [
                'render_callback' => [$this, 'render_callback']
            ]);
        }
    }

    /**
     * Render callback for Read More block
     *
     * Creates an accessible collapsible content area with toggle button.
     * Uses BEM naming convention and filters out WordPress block classes.
     *
     * @param array    $attributes Block attributes (contains buttonText)
     * @param string   $content    Block inner content from editor
     * @param \WP_Block $block      Block instance
     * @return string  Rendered HTML with proper accessibility attributes
     */
    public function render_callback(array $attributes, string $content, \WP_Block $block): string
    {
        // Extract button texts and icon from block attributes with fallbacks
        $open_text = $attributes['openText'] ?? __('Read More', 'orbitools');
        $close_text = $attributes['closeText'] ?? __('Read Less', 'orbitools');
        $icon_type = $attributes['iconType'] ?? 'chevron';
        
        // Generate unique IDs to avoid conflicts when multiple blocks exist on same page
        // wp_unique_id() returns incrementing numbers: 1, 2, 3, etc.
        $unique_id = 'read-more-' . \wp_unique_id();
        $content_id = $unique_id . '-content';  // e.g. "read-more-1-content"
        $button_id = $unique_id . '-button';    // e.g. "read-more-1-button"

        // Get WordPress wrapper attributes (includes default classes, alignment, etc.)
        $wrapper_attributes = \get_block_wrapper_attributes();
        
        // Extract class names from the wrapper attributes string
        $existing_classes = '';
        if (preg_match('/class=["\']([^"\']*)["\']/', $wrapper_attributes, $matches)) {
            $existing_classes = $matches[1];
        }
        
        // Remove unwanted WordPress block classes while keeping useful ones (alignment, spacing, etc.)
        $filtered_classes = $this->filter_wordpress_classes($existing_classes, ['wp-block-orb-read-more']);
        
        // Combine our BEM base class with filtered WordPress classes
        $final_classes = trim('orb-read-more ' . $filtered_classes);
        
        // Replace the class attribute in wrapper attributes with our cleaned version
        $wrapper_attributes = preg_replace('/class=["\'][^"\']*["\']/', 'class="' . \esc_attr($final_classes) . '"', $wrapper_attributes);

        // Start building the HTML structure
        $html = sprintf(
            '<div %s>',
            $wrapper_attributes
        );

        // Generate icon HTML based on selected type
        $icon_html = $this->get_icon_html($icon_type);
        
        // Toggle button with comprehensive accessibility attributes
        // - type="button" prevents form submission
        // - aria-expanded="false" indicates collapsed state (JS will toggle this)
        // - aria-controls links to content area for screen readers
        // - BEM naming with JS hook class
        // - data attributes store both text states and icon type for JS toggling
        $html .= sprintf(
            '<button type="button" id="%s" class="orb-read-more__toggle" aria-expanded="false" aria-controls="%s" data-open-text="%s" data-close-text="%s" data-icon-type="%s">%s%s</button>',
            \esc_attr($button_id),
            \esc_attr($content_id),
            \esc_attr($open_text),
            \esc_attr($close_text),
            \esc_attr($icon_type),
            \esc_html($open_text),
            $icon_html
        );

        // Content container gets no spacing classes - inner wrapper handles spacing
        $content_base_classes = 'orb-read-more__content';

        // Collapsible content container with spacing classes
        // - aria-hidden="true" indicates hidden state (JS will toggle this)
        // - display: none initially hides content (JS animations will override this)
        $html .= sprintf(
            '<div id="%s" class="%s" aria-hidden="true" style="display: none;">',
            \esc_attr($content_id),
            \esc_attr($content_base_classes)
        );

        // Render inner blocks individually (avoids wp-block wrapper issue)
        $inner_blocks_content = '';
        if (!empty($block->inner_blocks)) {
            foreach ($block->inner_blocks as $inner_block) {
                $inner_blocks_content .= $inner_block->render();
            }
        }

        // Wrap content in inner div for smooth animations and apply spacing classes
        $inner_base_classes = 'orb-read-more__inner';
        $inner_classes_with_spacing = SpacingsRenderer::add_spacings($inner_base_classes, $attributes);
        
        $html .= '<div class="' . \esc_attr($inner_classes_with_spacing) . '">';
        
        if (!empty($inner_blocks_content)) {
            $html .= $inner_blocks_content;
        } else {
            // Show helpful placeholder when no content has been added
            $html .= '<p>' . \esc_html__('Add content blocks here to show when expanded.', 'orbitools') . '</p>';
        }
        
        $html .= '</div>';

        // Close content container
        $html .= '</div>';
        
        // Close main wrapper
        $html .= '</div>';

        return $html;
    }

    /**
     * Filter out unwanted WordPress classes
     * 
     * @param string $class_names Space-separated class names
     * @param array $classes_to_filter Array of class names to remove
     * @return string Filtered class names
     */
    private function filter_wordpress_classes(string $class_names, array $classes_to_filter = []): string
    {
        if (empty($class_names)) {
            return '';
        }
        
        $classes = explode(' ', $class_names);
        $filtered = array_filter($classes, function ($class) use ($classes_to_filter) {
            return !empty($class) && !in_array($class, $classes_to_filter);
        });
        
        return implode(' ', $filtered);
    }

    /**
     * Generate icon HTML based on icon type
     * 
     * @param string $icon_type The type of icon (none, chevron, arrow, plus)
     * @return string Icon HTML or empty string if none
     */
    private function get_icon_html(string $icon_type): string
    {
        $default_icons = [
            'none' => '',
            'chevron' => '<span class="orb-read-more__icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-right"><path d="m9 18 6-6-6-6"/></svg></span>',
            'arrow' => '<span class="orb-read-more__icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg></span>',
            'plus' => '<span class="orb-read-more__icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg></span>'
        ];

        // Apply filter to allow customization of icons
        $icons = \apply_filters('orbitools/read_more/icons', $default_icons);

        return $icons[$icon_type] ?? $icons['chevron'];
    }

    /**
     * Get default settings
     */
    public function get_default_settings(): array
    {
        return [];
    }
}
