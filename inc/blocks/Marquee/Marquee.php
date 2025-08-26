<?php
/**
 * Marquee Block
 * 
 * Registers and manages the Marquee block for creating scrolling content containers.
 * Supports horizontal and vertical scrolling with customizable speed, direction, and hover behavior.
 * 
 * @package Orbitools
 * @subpackage Blocks
 * @since 1.0.0
 */

namespace Orbitools\Blocks\Marquee;

use Orbitools\Core\Abstracts\Module_Base;
use Orbitools\Core\Traits\Block_Utilities;
use Orbitools\Controls\Spacings_Controls\SpacingsRenderer;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Marquee Block Class
 * 
 * @since 1.0.0
 */
class Marquee extends Module_Base
{
    use Block_Utilities;

    /**
     * Module version
     * 
     * @var string
     */
    protected const VERSION = '1.0.0';

    /**
     * Valid orientation values
     * 
     * @var array
     */
    private const VALID_ORIENTATIONS = ['x', 'y'];

    /**
     * Valid direction values
     * 
     * @var array
     */
    private const VALID_DIRECTIONS = ['normal', 'reverse'];

    /**
     * Valid hover state values
     * 
     * @var array
     */
    private const VALID_HOVER_STATES = ['paused', 'running'];

    /**
     * Default animation speed
     * 
     * @var string
     */
    private const DEFAULT_SPEED = '10s';

    /**
     * Get the module's unique slug identifier
     * 
     * @return string Module slug
     * @since 1.0.0
     */
    public function get_slug(): string
    {
        return 'marquee-block';
    }

    /**
     * Get the module's display name
     * 
     * @return string Module name
     * @since 1.0.0
     */
    public function get_name(): string
    {
        return \__('Marquee Block', 'orbitools');
    }

    /**
     * Get the module's description
     * 
     * @return string Module description
     * @since 1.0.0
     */
    public function get_description(): string
    {
        return \__('Block for displaying scrolling content across both axis.', 'orbitools');
    }

    /**
     * Get the module's version
     * 
     * @return string Module version
     * @since 1.0.0
     */
    public function get_version(): string
    {
        return self::VERSION;
    }

    /**
     * Check if the module is currently enabled
     * 
     * @return bool True if enabled
     * @since 1.0.0
     */
    public function is_enabled(): bool
    {
        return true;
    }

    /**
     * Initialize the Marquee block
     * 
     * @return void
     * @since 1.0.0
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
     * 
     * @return void
     * @since 1.0.0
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
     *
     * @param array    $attributes Block attributes containing marquee settings
     * @param string   $content    Block inner content from editor
     * @param \WP_Block $block     Block instance
     * @return string  Rendered HTML with proper marquee structure and styles
     * @since 1.0.0
     */
    public function render_callback(array $attributes, string $content, \WP_Block $block): string
    {
        // Validate and sanitize attributes
        $validated_attrs = $this->validate_attributes($attributes);
        
        // Build wrapper attributes
        $wrapper_attrs = $this->build_wrapper_attributes($validated_attrs, $attributes);
        
        // Generate and return HTML
        return $this->generate_marquee_html($wrapper_attrs, $content, $validated_attrs, $attributes);
    }

    /**
     * Validate and sanitize block attributes
     * 
     * @param array $attributes Raw block attributes
     * @return array Validated and sanitized attributes
     * @since 1.0.0
     */
    private function validate_attributes(array $attributes): array
    {
        // Sanitize overlay color - handle null return from sanitize_hex_color
        $overlay_color = '';
        if (!empty($attributes['overlayColor'])) {
            $sanitized_color = \sanitize_hex_color($attributes['overlayColor']);
            $overlay_color = $sanitized_color !== null ? $sanitized_color : '';
        }

        return [
            'orientation' => $this->validate_enum(
                \sanitize_text_field($attributes['orientation'] ?? 'x'),
                self::VALID_ORIENTATIONS,
                'x'
            ),
            'direction' => $this->validate_enum(
                \sanitize_text_field($attributes['direction'] ?? 'normal'),
                self::VALID_DIRECTIONS,
                'normal'
            ),
            'hoverState' => $this->validate_enum(
                \sanitize_text_field($attributes['hoverState'] ?? 'paused'),
                self::VALID_HOVER_STATES,
                'paused'
            ),
            'speed' => $this->validate_speed(
                \sanitize_text_field($attributes['speed'] ?? self::DEFAULT_SPEED),
                self::DEFAULT_SPEED
            ),
            'overlayColor' => $overlay_color
        ];
    }

    /**
     * Build wrapper attributes for the marquee block
     * 
     * @param array $validated_attrs Validated attributes
     * @param array $original_attrs Original attributes (for spacing data)
     * @return array Wrapper attributes array
     * @since 1.0.0
     */
    private function build_wrapper_attributes(array $validated_attrs, array $original_attrs): array
    {
        // Build CSS classes
        $marquee_classes = [
            'orb-marquee',
            'has-overlay-color' => !empty($validated_attrs['overlayColor']),
        ];

        // Build inline styles
        $marquee_styles = [
            '--marquee-overlay-color' => $validated_attrs['overlayColor'] ?: 'transparent',
        ];

        // Get base classes and add spacing controls
        $base_classes = $this->get_css_classes($marquee_classes);
        $classes_with_spacings = SpacingsRenderer::add_padding($base_classes, $original_attrs);

        return [
            'class' => \esc_attr($classes_with_spacings),
            'style' => \esc_attr($this->get_inline_styles($marquee_styles)),
            'data-orientation' => \esc_attr($validated_attrs['orientation']),
            'data-direction' => \esc_attr($validated_attrs['direction']),
            'data-hover' => \esc_attr($validated_attrs['hoverState']),
            'data-speed' => \esc_attr($validated_attrs['speed']),
        ];
    }

    /**
     * Generate the complete marquee HTML structure
     * 
     * @param array $wrapper_attrs Wrapper attributes
     * @param string $content Inner content
     * @param array $validated_attrs Validated attributes
     * @param array $original_attrs Original attributes (for spacing data)
     * @return string Complete HTML markup
     * @since 1.0.0
     */
    private function generate_marquee_html(
        array $wrapper_attrs, 
        string $content, 
        array $validated_attrs,
        array $original_attrs
    ): string {
        // Get allowed HTML for content filtering
        $allowed_html = $this->get_kses_allowed_html();

        // Build content classes with gap spacing
        $content_base_classes = 'orb-marquee__content';
        $content_classes = SpacingsRenderer::add_gap($content_base_classes, $original_attrs);

        // Get block wrapper attributes and filter out wp-block-orb-marquee class
        $block_wrapper = \get_block_wrapper_attributes($wrapper_attrs);
        // Remove the wp-block-orb-marquee class that WordPress automatically adds
        $block_wrapper = preg_replace('/\bwp-block-orb-marquee\s*/', '', $block_wrapper);
        
        // Start building HTML
        $html = sprintf(
            '<div %s>',
            $block_wrapper
        );

        // Add wrapper for scrolling content
        $html .= '<div class="orb-marquee__wrapper">';

        // Add main content
        // Empty span creates a phantom flex gap for proper spacing between duplicates
        $html .= sprintf(
            '<div class="%s">%s<span aria-hidden="true"></span></div>',
            \esc_attr($content_classes),
            \wp_kses($content, $allowed_html)
        );

        // Close wrapper
        $html .= '</div>';

        // Add overlay if color is set
        if (!empty($validated_attrs['overlayColor'])) {
            $html .= '<div class="orb-marquee__overlay" aria-hidden="true"></div>';
        }

        // Close main wrapper
        $html .= '</div>';

        return $html;
    }

    /**
     * Get default settings for the module
     * 
     * @return array Empty array as no default settings needed
     * @since 1.0.0
     */
    public function get_default_settings(): array
    {
        return [];
    }
}