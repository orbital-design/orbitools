<?php

namespace Orbitools\Blocks\Group;

use Orbitools\Core\Abstracts\Module_Base;
use Orbitools\Controls\Spacings_Controls\SpacingsRenderer;

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
    }

    /**
     * Register the Group block
     */
    public function register_block(): void
    {
        $block_dir = ORBITOOLS_DIR . 'build/blocks/group/';

        if (file_exists($block_dir . 'block.json')) {
            \register_block_type($block_dir);
        }
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
        // Sanitize and default attributes
        $tagName = \sanitize_text_field($attributes['tagName'] ?? 'div');
        $templateLock = $attributes['templateLock'] ?? false;
        $allowedBlocks = $attributes['allowedBlocks'] ?? null;

        // Validate tag name - ensure it's a valid HTML tag
        $allowed_tags = ['div', 'header', 'main', 'section', 'article', 'aside', 'footer', 'nav', 'figure', 'details', 'summary', 'fieldset', 'hgroup'];
        if (!in_array($tagName, $allowed_tags, true)) {
            $tagName = 'div';
        }

        // Build base classes
        $group_block_classes = [
            'orb-group'
        ];

        // Build base classes and add OrbiTools spacing controls
        $base_classes = $this->get_css_classes($group_block_classes);
        $classes_with_spacings = SpacingsRenderer::add_spacings($base_classes, $attributes);

        // Get wrapper attributes from WordPress
        $wrapper_attributes = \get_block_wrapper_attributes([
            'class' => $classes_with_spacings
        ]);

        $allowed_html = $this->get_kses_allowed_html();

        // Start building the HTML structure
        $html = sprintf(
            '<%s %s>',
            \esc_attr($tagName),
            \wp_kses_post($wrapper_attributes)
        );

        // Add inner wrapper for content
        $html .= '<div class="orb-group__inner">';
        
        // Add inner blocks content
        $html .= \wp_kses($content, $allowed_html);

        // Close wrappers
        $html .= '</div>';
        $html .= sprintf('</%s>', \esc_attr($tagName));

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
                if (!is_string($should_include)) {
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