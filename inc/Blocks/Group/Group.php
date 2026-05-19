<?php

namespace Orbitools\Blocks\Group;

use Orbitools\Core\Abstracts\Module_Base;
use Orbitools\Controls\Spacings_Controls\SpacingsRenderer;
use Orbitools\Controls\AspectRatio_Controls\AspectRatioRenderer;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Group Block
 *
 * Registers and manages the Group block for creating flexible layout containers
 * with semantic HTML tag support and various layout options.
 */
class Group extends Module_Base
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
        return 'group-block';
    }

    /**
     * Get the module's display name
     */
    public function get_name(): string
    {
        return \__('Group Block', 'orbitools');
    }

    /**
     * Get the module's description
     */
    public function get_description(): string
    {
        return \__('Flexible layout container block for organizing other blocks with semantic HTML support.', 'orbitools');
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
     * Initialize the Group block
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
        
        // Filter CSS selectors for theme.json styling support
        \add_filter('wp_get_block_css_selector', [$this, 'filter_block_css_selector'], 10, 3);
    }

    /**
     * Register the Group block
     */
    public function register_block(): void
    {
        $block_dir = ORBITOOLS_DIR . 'build/blocks/group/';

        if (file_exists($block_dir . 'block.json')) {
            \register_block_type($block_dir, [
                'render_callback' => [$this, 'render_callback']
            ]);
        }
    }

    /**
     * Filter block CSS selector to remove WordPress core classes
     */
    public function filter_block_css_selector($selector, $feature, $block_type): string
    {
        // Remove WordPress core classes from selectors for our block
        if ($block_type && $block_type->name === 'orb/group') {
            // Replace wp-block-orb-group with orb-group
            $selector = str_replace('.wp-block-orb-group', '.orb-group', $selector);
            
            // Remove other wp- classes from selectors
            $selector = preg_replace('/\.wp-[a-zA-Z0-9\-_]+/', '', $selector);
            
            // Remove is- classes from selectors  
            $selector = preg_replace('/\.is-[a-zA-Z0-9\-_]+/', '', $selector);
            
            // Clean up any double dots or spaces
            $selector = preg_replace('/\.+/', '.', $selector);
            $selector = preg_replace('/\s+/', ' ', $selector);
            $selector = trim($selector);
        }
        
        return $selector;
    }

    /**
     * Render callback for Group block
     *
     * Creates a flexible layout container with semantic HTML tag support.
     * Renders inner blocks with proper spacing and layout classes.
     *
     * @param array    $attributes Block attributes containing group settings
     * @param string   $content    Block inner content from editor
     * @param \WP_Block $block      Block instance
     * @return string  Rendered HTML with proper group structure and styles
     */
    public function render_callback(array $attributes, string $content, \WP_Block $block): string
    {
        // Sanitize tag name
        $tagName = \sanitize_text_field($attributes['tagName'] ?? 'div');
        $allowed_tags = ['div', 'header', 'main', 'section', 'article', 'aside', 'footer', 'nav', 'figure', 'details', 'summary', 'fieldset', 'hgroup'];
        if (!in_array($tagName, $allowed_tags, true)) {
            $tagName = 'div';
        }

        // Get layout type and corresponding class
        $layout = $attributes['layout'] ?? null;
        $layoutType = $layout['type'] ?? 'group';
        $variationClass = $this->getVariationClass($layoutType);

        // Content width constraint
        $align = $attributes['align'] ?? '';
        $restrict_content_width = $attributes['restrictContentWidth'] ?? false;
        $needs_wrapper = ($align === 'full') && $restrict_content_width;

        // Get wrapper attributes from WordPress and extract existing classes
        $wrapper_attributes = \get_block_wrapper_attributes();
        $existing_classes = '';
        if (preg_match('/class=["\']([^"\']*)["\']/', $wrapper_attributes, $matches)) {
            $existing_classes = $matches[1];
        }

        if ($needs_wrapper) {
            // Dual-wrapper: outer gets alignment + background/color, inner gets layout + spacings
            $wrapper_classes = $this->get_wrapper_classes($existing_classes);
            $inner_classes = $this->get_inner_classes($existing_classes);

            $has_variation_class = strpos(' ' . $inner_classes . ' ', ' ' . $variationClass . ' ') !== false;
            $base_classes = $has_variation_class
                ? $inner_classes
                : trim($variationClass . ' ' . $inner_classes);
            $all_classes = SpacingsRenderer::add_spacings($base_classes, $attributes);
            $all_classes = AspectRatioRenderer::add_aspect_ratio($all_classes, $attributes);
        } else {
            // Filter out WordPress core classes (wp-block-orb-group, wp-*, is-*)
            $filtered_classes = $this->filter_wordpress_classes($existing_classes);

            // Build our classes: variation class + filtered WP classes + spacings
            $has_variation_class = strpos(' ' . $filtered_classes . ' ', ' ' . $variationClass . ' ') !== false;
            $base_classes = $has_variation_class
                ? $filtered_classes
                : trim($variationClass . ' ' . $filtered_classes);
            $all_classes = SpacingsRenderer::add_spacings($base_classes, $attributes);
            $all_classes = AspectRatioRenderer::add_aspect_ratio($all_classes, $attributes);
        }

        // Extract non-class attributes (style, id, etc.)
        $other_attrs = preg_replace('/class=["\'][^"\']*["\']/', '', $wrapper_attributes);
        $other_attrs = trim($other_attrs);

        // Generate flex data attributes for row/stack variants
        $data_attrs_html = '';
        if ($layoutType === 'group-row' || $layoutType === 'group-stack') {
            $data_attrs_html = $this->generate_flex_data_attributes($attributes, $layoutType);
        }

        // Content constraint data attribute
        if ($needs_wrapper) {
            $data_attrs_html .= ' data-constrain="true"';
        }

        // Render inner blocks
        $inner_blocks_content = '';
        if (!empty($block->inner_blocks)) {
            foreach ($block->inner_blocks as $inner_block) {
                $inner_blocks_content .= $inner_block->render();
            }
        }

        if ($needs_wrapper) {
            // Full-width with content constraint: outer wrapper + inner layout div
            return sprintf(
                '<%s class="%s"><%s%s class="%s"%s>%s</%s></%s>',
                \esc_attr($tagName),
                \esc_attr($wrapper_classes),
                'div',
                $other_attrs ? ' ' . $other_attrs : '',
                \esc_attr($all_classes),
                $data_attrs_html,
                $inner_blocks_content,
                'div',
                \esc_attr($tagName)
            );
        }

        return sprintf(
            '<%s%s class="%s"%s>%s</%s>',
            \esc_attr($tagName),
            $other_attrs ? ' ' . $other_attrs : '',
            \esc_attr($all_classes),
            $data_attrs_html,
            $inner_blocks_content,
            \esc_attr($tagName)
        );
    }

    /**
     * Filter out WordPress core classes from the class string
     */
    private function filter_wordpress_classes(string $classes): string
    {
        $class_array = array_filter(explode(' ', $classes));
        $filtered = array_filter($class_array, function ($class) {
            // Remove wp-block-orb-group and other wp- prefixed classes
            if (strpos($class, 'wp-') === 0) {
                return false;
            }
            // Remove is- prefixed classes
            if (strpos($class, 'is-') === 0) {
                return false;
            }
            return true;
        });
        return implode(' ', $filtered);
    }

    /**
     * Generate flex data attributes for row/stack variants
     */
    private function generate_flex_data_attributes(array $attributes, string $layoutType): string
    {
        $data_attrs = [];

        $align_items = $attributes['alignItems'] ?? 'stretch';
        $justify_content = $attributes['justifyContent'] ?? 'flex-start';
        $flex_wrap = $attributes['flexWrap'] ?? 'nowrap';

        $align_mappings = [
            'flex-start' => 'start',
            'flex-end' => 'end',
            'center' => 'center',
        ];

        $justify_mappings = [
            'flex-start' => 'start',
            'flex-end' => 'end',
            'center' => 'center',
            'space-between' => 'between',
            'space-around' => 'around',
            'space-evenly' => 'evenly',
        ];

        // Cross-axis alignment (only when non-default)
        if ($align_items !== 'stretch') {
            $data_attrs['data-align'] = $align_mappings[$align_items] ?? $align_items;
        }

        // Main-axis alignment (only when non-default)
        if ($justify_content !== 'flex-start') {
            $data_attrs['data-justify'] = $justify_mappings[$justify_content] ?? $justify_content;
        }

        // Flex wrap (row only, only when non-default)
        if ($layoutType === 'group-row' && $flex_wrap !== 'nowrap') {
            $data_attrs['data-wrap'] = $flex_wrap;
        }

        $html = '';
        foreach ($data_attrs as $attr => $value) {
            $html .= ' ' . \esc_attr($attr) . '="' . \esc_attr($value) . '"';
        }

        return $html;
    }

    /**
     * Get variation-specific class name based on layout type
     */
    private function getVariationClass(string $layoutType): string
    {
        switch ($layoutType) {
            case 'group-row':
                return 'orb-row';
            case 'group-stack':
                return 'orb-stack';
            default:
                return 'orb-group';
        }
    }

    /**
     * Regex for wrapper-bound classes (background/text colors)
     */
    private const WRAPPER_CLASS_REGEX = '/^(?:has-background|has-text-color|has-link-color|has-.*-background-color|has-.*-color|has-vivid-.*|has-pale-.*|has-luminous-.*)$/';

    /**
     * Regex for inner-excluded classes (wrapper classes + alignment + block name)
     */
    private const INNER_EXCLUDE_REGEX = '/^(?:alignfull|alignwide|wp-block-orb-group|has-background|has-text-color|has-link-color|has-.*-background-color|has-.*-color|has-vivid-.*|has-pale-.*|has-luminous-.*)$/';

    /**
     * Get classes for the outer wrapper div (alignment + background/text colors)
     */
    private function get_wrapper_classes(string $class_names): string
    {
        if (empty($class_names)) {
            return 'alignfull';
        }

        $classes = array_filter(explode(' ', $class_names));
        $wrapper_classes = ['alignfull'];

        foreach ($classes as $class) {
            if (preg_match(self::WRAPPER_CLASS_REGEX, $class)) {
                $wrapper_classes[] = $class;
            }
        }

        return implode(' ', array_unique($wrapper_classes));
    }

    /**
     * Get classes for the inner div (layout and other functionality)
     */
    private function get_inner_classes(string $class_names): string
    {
        if (empty($class_names)) {
            return '';
        }

        $classes = array_filter(explode(' ', $class_names));
        $inner_classes = [];

        foreach ($classes as $class) {
            // Exclude wrapper-bound and WP core classes
            if (!preg_match(self::INNER_EXCLUDE_REGEX, $class) && strpos($class, 'wp-') !== 0 && strpos($class, 'is-') !== 0) {
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