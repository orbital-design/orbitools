<?php

namespace Orbitools\Blocks\Marquee;

use Orbitools\Core\Abstracts\Module_Base;
use Orbitools\Controls\Spacings_Controls\SpacingsRenderer;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Marquee Block
 *
 * Registers and manages the Marquee block for creating scrolling content containers
 */
class Marquee extends Module_Base
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
        return 'marquee-block';
    }

    /**
     * Get the module's display name
     */
    public function get_name(): string
    {
        return \__('Marquee Block', 'orbitools');
    }

    /**
     * Get the module's description
     */
    public function get_description(): string
    {
        return \__('Block for displaying scrolling content across both axis.', 'orbitools');
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
     * Initialize the Marquee block
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
     * Register the Marquee block
     */
    public function register_block(): void
    {
        $block_dir = ORBITOOLS_DIR . 'build/blocks/marquee/';

        if (file_exists($block_dir . 'block.json')) {
            \register_block_type($block_dir, [
                'render_callback' => [$this, 'render_callback']
            ]);
        }
    }

    /**
     * Render callback for Marquee block
     *
     * Creates scrolling content with customizable orientation, speed, and behavior.
     * Uses proper CSS class names and generates inline styles for animation.
     *
     * @param array    $attributes Block attributes containing marquee settings
     * @param string   $content    Block inner content from editor
     * @param \WP_Block $block      Block instance
     * @return string  Rendered HTML with proper marquee structure and styles
     */
    public function render_callback(array $attributes, string $content, \WP_Block $block): string
    {

        // Sanitize and default attributes
        $orientation = \sanitize_text_field($attributes['orientation'] ?? 'x');
        $direction = \sanitize_text_field($attributes['direction'] ?? 'normal');
        $hoverState = \sanitize_text_field($attributes['hoverState'] ?? 'paused');
        $speed = \sanitize_text_field($attributes['speed'] ?? '10s');
        $gap = \sanitize_text_field($attributes['gap'] ?? '40px');
        $overlayColor = isset($attributes['overlayColor']) ? \sanitize_hex_color($attributes['overlayColor']) : null;

        $marquee_block_classes = array(
            'orb-marquee',
            "orb-marquee--hover-{$hoverState}",
            'has-overlay-color' => !empty($overlayColor),
        );

        $marquee_block_styles = array(
            '--marquee-overlay-color' => $overlayColor ?: 'transparent',
        );

        // Build base classes and add OrbiTools spacing controls
        $base_classes = $this->get_css_classes($marquee_block_classes);
        $classes_with_spacings = SpacingsRenderer::add_spacings($base_classes, $attributes);

        $marquee_block_wrapper_attrs = array(
            'class' => \esc_attr($classes_with_spacings),
            'style' => \esc_attr($this->get_inline_styles($marquee_block_styles)),
            'data-orientation' => $orientation,
            'data-direction' => $direction,
            'data-hover' => $hoverState,
            'data-speed' => $speed,
        );

        $marquee_block_allowed_html = $this->get_kses_allowed_html();

        // Start building the HTML structure
        $html = sprintf(
            '<div %s>',
            \wp_kses_post(\get_block_wrapper_attributes($marquee_block_wrapper_attrs))
        );

        // Add wrapper for scrolling content
        $html .= '<div class="orb-marquee__wrapper">';

        // Add main content
        $html .= sprintf(
            '<div class="%s">%s</div>',
            \esc_attr('orb-marquee__content'),
            \wp_kses($content, $marquee_block_allowed_html)
        );

        // Close wrapper
        $html .= '</div>';

        // Add overlay if overlay color is set
        if (!empty($overlayColor)) {
            $html .= '<div class="orb-marquee__overlay" aria-hidden="true"></div>';
        }

        // Close main wrapper
        $html .= '</div>';

        return $html;
    }


    /**
     * Get default settings
     */
    public function get_default_settings(): array
    {
        return [];
    }

    /**
     * Array to css class.
     *
     * @param array<int|string, ?mixed> $classes_array css classes array.
     *
     * @return string
     * @since  1.0.0
     * @example
     * <code>
     *   ['class-a', 'class-b']
     *   // or
     *   ['class-a'=>true, 'class-b'=>false, 'class-c'=>'', 'class-e'=>null, 'class-d'=>'hello']
     * </code>
     */
    public function get_css_classes(array $classes_array = array()): string
    {
        $classes = array();
        foreach ($classes_array as $class_name => $should_include) {
            // Is class assign by numeric array. Like: ['class-a', 'class-b'].
            if (is_int($class_name)) {
                if (! is_string($should_include)) {
                    continue;
                }

                if ($this->is_empty_string($should_include)) {
                    continue;
                }

                $classes[] = $should_include;
                continue;
            }

            if (false === $should_include) {
                continue;
            }

            if (is_string($should_include) && $this->is_empty_string($should_include)) {
                continue;
            }

            if (is_null($should_include)) {
                continue;
            }

            if (is_array($should_include) && $this->is_empty_array($should_include)) {
                continue;
            }

            // Is class assign by associative array.
            // Like: ['class-a'=>true, 'class-b'=>false, class-c'=>'', 'class-d'=>'hello', 'class-x'=>null, 'class-y'=>array()].
            $classes[] = $class_name;
        }

        return implode(' ', array_unique($classes));
    }

    /**
     * Returns an array of allowed HTML tags and attributes for a given context.
     *
     * @param array<string, mixed> $args extra argument.
     *
     * @return array<string, mixed>
     * @since 1.0.0
     */
    public function get_kses_allowed_html(array $args = array()): array
    {
        $defaults = \wp_kses_allowed_html('post');

        $tags = array(
            'svg'   => array(
                'class',
                'aria-hidden',
                'aria-labelledby',
                'role',
                'xmlns',
                'width',
                'height',
                'viewbox',
                'height',
            ),
            'g'     => array('fill'),
            'title' => array('title'),
            'path'  => array('d', 'fill'),
        );

        $allowed_args = array_reduce(
            array_keys($tags),
            function (array $carry, string $tag) use ($tags) {
                $carry[$tag] = array_fill_keys($tags[$tag], true);

                return $carry;
            },
            array()
        );

        return array_merge($defaults, $allowed_args, $args);
    }

    /**
     * Generate Inline Style from array
     *
     * @param array<string, mixed> $inline_styles_array Inline style as array.
     *
     * @return string
     * @since  1.0.0
     */
    public function get_inline_styles(array $inline_styles_array = array()): string
    {
        $styles = array();

        foreach ($inline_styles_array as $property => $value) {
            if (is_null($value)) {
                continue;
            }
            if (is_bool($value)) {
                continue;
            }

            if (is_array($value)) {
                continue;
            }

            if (is_string($value) && $this->is_empty_string($value)) {
                continue;
            }

            $styles[] = sprintf('%s: %s;', \esc_attr($property), \esc_attr($value));
        }

        return implode(' ', array_unique($styles));
    }

    /**
     * Check is string is empty.
     *
     * @param string $check_value Check value.
     *
     * @return bool
     */
    public function is_empty_string(string $check_value = ''): bool
    {
        return 0 === strlen(trim($check_value));
    }

    /**
     * Check is array is all empty values.
     *
     * @param array<int|string, ?mixed> $items Check array.
     *
     * @return bool
     */
    public function is_array_each_empty_value(array $items = array()): bool
    {
        $checked = array_map(
            function ($value) {
                if (is_array($value) && ! $this->is_array_each_empty_value($value)) {
                    return true;
                }

                if (is_string($value) && ! $this->is_empty_string($value)) {
                    return true;
                }

                if (true === $value) {
                    return true;
                }

                return false;
            },
            $items
        );

        return ! in_array(true, array_unique($checked), true);
    }

    /**
     * Check numeric array is empty.
     *
     * @param array<int|string, ?mixed> $items Check array.
     *
     * @return bool
     */
    public function is_empty_array(array $items = array()): bool
    {
        return 0 === count($items);
    }
}
