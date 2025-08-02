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
     * Get column width percentages based on grid system
     *
     * @since 1.0.0
     * @param string $grid_system The grid system ('5' or '12')
     * @return array Column width mappings
     */
    private function get_column_widths($grid_system = '12'): array
    {
        if ($grid_system === '5') {
            // 5-column grid system
            return [
                '1' => '20%',
                '2' => '40%',
                '3' => '60%',
                '4' => '80%',
                '5' => '100%',
            ];
        }
        
        // Default 12-column grid system
        return [
            '1'  => '8.333333%',
            '2'  => '16.666667%',
            '3'  => '25%',
            '4'  => '33.333333%',
            '5'  => '41.666667%',
            '6'  => '50%',
            '7'  => '58.333333%',
            '8'  => '66.666667%',
            '9'  => '75%',
            '10' => '83.333333%',
            '11' => '91.666667%',
            '12' => '100%',
        ];
    }

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
        // Column width CSS is now handled by the Flex Layout Controls static CSS file
        // This method is kept for backwards compatibility but no longer generates CSS
        return;
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
        
        // Generate CSS for both grid systems
        $grid_systems = ['5', '12'];
        
        foreach ($grid_systems as $grid_system) {
            $column_widths = $this->get_column_widths($grid_system);
            
            $css .= "\n/* {$grid_system}-column grid system */\n";
            
            // Base (mobile-first) column width classes
            foreach ($column_widths as $class => $width) {
                $css .= ".flex-cols-{$class} { width: {$width} !important; }\n";
            }
            
            // Responsive breakpoint classes
            foreach ($this->breakpoints as $breakpoint => $min_width) {
                $css .= "\n@media (min-width: {$min_width}) {\n";
                
                foreach ($column_widths as $class => $width) {
                    $css .= "  .flex-cols-{$breakpoint}-{$class} { width: {$width} !important; }\n";
                }
                
                $css .= "}\n";
            }
        }
        
        // Additional utility classes
        $css .= "\n/* Column width utilities */\n";
        $css .= ".flex-cols-auto { width: auto !important; }\n";
        $css .= ".flex-cols-full { width: 100% !important; }\n";
        
        // Box sizing for column width elements
        $css .= "\n/* Ensure proper box-sizing for column widths */\n";
        $css .= "[class*='flex-cols-'] { box-sizing: border-box; }\n";
        
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
        
        // Always add base flex-cols class
        $classes[] = 'flex-cols';
        
        foreach ($column_widths as $breakpoint => $width) {
            if (empty($width) || $width === 'auto') {
                continue;
            }
            
            // Convert from old format (1_col, 2_cols) to new format (1, 2)
            $columnNumber = $this->extractColumnNumber($width);
            
            if ($breakpoint === 'base') {
                $classes[] = "flex-cols--{$columnNumber}";
            } else {
                $classes[] = "flex-cols--{$breakpoint}-{$columnNumber}";
            }
        }
        
        return $classes;
    }
    
    /**
     * Extract column number from width key
     * 
     * @param string $width The width key (e.g., '1', '2', '3' or legacy '1_col', '2_cols')
     * @return string The column number (e.g., '1', '2', '3')
     */
    private function extractColumnNumber(string $width): string
    {
        // Handle new simple integer format
        if (is_numeric($width)) {
            return $width;
        }
        
        // Handle legacy formats for backwards compatibility
        if ($width === '1_col') {
            return '1';
        }
        
        // Match pattern like '2_cols', '3_cols', etc.
        if (preg_match('/^(\d+)_cols$/', $width, $matches)) {
            return $matches[1];
        }
        
        // Fallback - return as is
        return $width;
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