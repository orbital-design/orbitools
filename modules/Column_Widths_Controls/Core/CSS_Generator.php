<?php

/**
 * Column Widths Controls CSS Generator
 *
 * Generates and enqueues CSS for column width controls.
 *
 * @package    Orbitools
 * @subpackage Modules/Column_Widths_Controls/Core
 * @since      1.0.0
 */

namespace Orbitools\Modules\Column_Widths_Controls\Core;

use Orbitools\Modules\Column_Widths_Controls\Admin\Settings_Helper;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * CSS Generator Class
 *
 * Handles generating and enqueuing CSS for column width controls.
 *
 * @since 1.0.0
 */
class CSS_Generator
{
    /**
     * Column width percentages for 12-column grid
     *
     * @since 1.0.0
     * @var array
     */
    private $column_widths = [
        '1_col'  => '8.333333%',
        '2_cols' => '16.666667%',
        '3_cols' => '25%',
        '4_cols' => '33.333333%',
        '5_cols' => '41.666667%',
        '6_cols' => '50%',
        '7_cols' => '58.333333%',
        '8_cols' => '66.666667%',
        '9_cols' => '75%',
        '10_cols' => '83.333333%',
        '11_cols' => '91.666667%',
        '12_cols' => '100%',
    ];

    /**
     * Breakpoint configurations
     *
     * @since 1.0.0
     * @var array
     */
    private $breakpoints = [
        'sm' => '576px',
        'md' => '768px',
        'lg' => '992px',
        'xl' => '1200px',
    ];

    /**
     * Initialize CSS generation
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        // Hook into wp_enqueue_scripts for frontend
        add_action('wp_enqueue_scripts', array($this, 'enqueue_column_widths_css'));
        
        // Hook into admin_enqueue_scripts for editor
        add_action('admin_enqueue_scripts', array($this, 'enqueue_column_widths_css'));
    }

    /**
     * Enqueue column widths CSS
     *
     * @since 1.0.0
     */
    public function enqueue_column_widths_css(): void
    {
        // Only enqueue if module is enabled and CSS output is enabled
        if (!Settings_Helper::is_module_enabled() || !Settings_Helper::output_column_widths_css()) {
            return;
        }

        // Generate cache key based on current configuration
        $cache_key = 'orbitools_column_widths_css_' . md5(serialize($this->column_widths) . serialize($this->breakpoints));
        
        // Try to get cached CSS
        $column_widths_css = get_transient($cache_key);
        
        if ($column_widths_css === false) {
            // Cache miss - generate CSS and cache it
            $column_widths_css = $this->generate_column_widths_css();
            
            // Cache for 24 hours
            set_transient($cache_key, $column_widths_css, 24 * HOUR_IN_SECONDS);
            
            // Clean up old cache entries
            $this->cleanup_old_cache();
        }

        // Register and enqueue a dummy stylesheet
        wp_register_style('orbitools-column-widths', false);
        wp_enqueue_style('orbitools-column-widths');

        // Add the CSS inline
        wp_add_inline_style('orbitools-column-widths', $column_widths_css);
    }

    /**
     * Generate column widths CSS
     *
     * @since 1.0.0
     * @return string Generated CSS
     */
    private function generate_column_widths_css(): string
    {
        $css = "/* Orbitools Column Widths Controls */\n";
        
        // Base (mobile-first) column width classes
        foreach ($this->column_widths as $class => $width) {
            $css .= ".has-orbitools-column-width-{$class} { width: {$width} !important; }\n";
        }
        
        // Responsive breakpoint classes
        foreach ($this->breakpoints as $breakpoint => $min_width) {
            $css .= "\n@media (min-width: {$min_width}) {\n";
            
            foreach ($this->column_widths as $class => $width) {
                $css .= "  .has-orbitools-column-width-{$breakpoint}-{$class} { width: {$width} !important; }\n";
            }
            
            $css .= "}\n";
        }
        
        // Additional utility classes
        $css .= "\n/* Column width utilities */\n";
        $css .= ".has-orbitools-column-width-auto { width: auto !important; }\n";
        $css .= ".has-orbitools-column-width-full { width: 100% !important; }\n";
        
        // Box sizing for column width elements
        $css .= "\n/* Ensure proper box-sizing for column widths */\n";
        $css .= "[class*='has-orbitools-column-width-'] { box-sizing: border-box; }\n";
        
        return $css;
    }

    /**
     * Generate CSS class name from column widths attribute
     *
     * @since 1.0.0
     * @param array $column_widths Column widths configuration
     * @return array CSS class names
     */
    public function generate_css_classes(array $column_widths): array
    {
        $classes = [];
        
        foreach ($column_widths as $breakpoint => $width) {
            if (empty($width) || $width === 'auto') {
                continue;
            }
            
            if ($breakpoint === 'base') {
                $classes[] = "has-orbitools-column-width-{$width}";
            } else {
                $classes[] = "has-orbitools-column-width-{$breakpoint}-{$width}";
            }
        }
        
        return $classes;
    }

    /**
     * Clean up old cached CSS entries
     *
     * @since 1.0.0
     */
    private function cleanup_old_cache(): void
    {
        global $wpdb;
        
        // Delete old column widths cache entries (keep only current one)
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_orbitools_column_widths_css_%' 
             OR option_name LIKE '_transient_timeout_orbitools_column_widths_css_%'"
        );
    }

    /**
     * Clear all column widths cache
     *
     * @since 1.0.0
     */
    public function clear_cache(): void
    {
        global $wpdb;
        
        // Delete all column widths related transients
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_orbitools_column_widths_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_orbitools_column_widths_%'");
        
        // Clear object cache
        wp_cache_delete('orbitools_column_widths', 'theme_json');
    }
}