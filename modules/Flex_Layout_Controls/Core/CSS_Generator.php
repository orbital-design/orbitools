<?php

/**
 * Flex Layout Controls CSS Generator
 *
 * Generates CSS for flex layout controls applied to blocks.
 *
 * @package    Orbitools
 * @subpackage Modules/Flex_Layout_Controls/Core
 * @since      1.0.0
 */

namespace Orbitools\Modules\Flex_Layout_Controls\Core;

use Orbitools\Modules\Flex_Layout_Controls\Admin\Settings_Helper;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * CSS Generator Class
 *
 * Handles CSS generation for flex layout controls.
 *
 * @since 1.0.0
 */
class CSS_Generator
{
    /**
     * Cache key prefix for generated CSS
     *
     * @since 1.0.0
     * @var string
     */
    const CACHE_PREFIX = 'orbitools_flex_css_v2_';

    /**
     * Cache expiration time in seconds (24 hours)
     *
     * @since 1.0.0
     * @var int
     */
    const CACHE_EXPIRATION = 24 * 60 * 60;

    /**
     * Initialize CSS generation
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        // Hook into wp_head to output CSS on frontend
        add_action('wp_head', array($this, 'output_flex_css'), 10);
        
        // Hook into admin_head to output CSS in editor
        add_action('admin_head', array($this, 'output_flex_css'), 10);
    }

    /**
     * Output flex layout CSS in the page head
     *
     * @since 1.0.0
     */
    public function output_flex_css(): void
    {
        // Only output if module is enabled and CSS output is enabled
        if (!Settings_Helper::is_module_enabled() || !Settings_Helper::output_flex_css()) {
            return;
        }

        $css = $this->generate_flex_css();
        
        if (!empty($css)) {
            echo "<style id='orbitools-flex-layout-css'>\n" . $css . "\n</style>\n";
        }
    }

    /**
     * Generate CSS for all flex layout classes
     *
     * @since 1.0.0
     * @return string Generated CSS.
     */
    public function generate_flex_css(): string
    {
        $cache_key = self::CACHE_PREFIX . 'main';
        $cached_css = get_transient($cache_key);

        if ($cached_css !== false) {
            return $cached_css;
        }

        $css_rules = array();

        // Base flex container class with all defaults
        $css_rules[] = '.flex { display: flex; flex-direction: row; flex-wrap: nowrap; align-items: stretch; justify-content: flex-start; align-content: stretch; }';

        // Flex flow classes (only for non-defaults)
        $css_rules[] = '.flex-flow-column { flex-flow: column; }';
        $css_rules[] = '.flex-flow-wrap { flex-flow: wrap; }';
        $css_rules[] = '.flex-flow-column-wrap { flex-flow: column wrap; }';

        // Align items classes (excluding default stretch)
        $css_rules[] = '.flex-items-center { align-items: center; }';
        $css_rules[] = '.flex-items-flex-start { align-items: flex-start; }';
        $css_rules[] = '.flex-items-flex-end { align-items: flex-end; }';
        $css_rules[] = '.flex-items-baseline { align-items: baseline; }';

        // Justify content classes (excluding default flex-start)
        $css_rules[] = '.flex-justify-center { justify-content: center; }';
        $css_rules[] = '.flex-justify-flex-end { justify-content: flex-end; }';
        $css_rules[] = '.flex-justify-space-between { justify-content: space-between; }';
        $css_rules[] = '.flex-justify-space-around { justify-content: space-around; }';
        $css_rules[] = '.flex-justify-space-evenly { justify-content: space-evenly; }';

        // Align content classes (excluding default stretch)
        $css_rules[] = '.flex-content-center { align-content: center; }';
        $css_rules[] = '.flex-content-flex-start { align-content: flex-start; }';
        $css_rules[] = '.flex-content-flex-end { align-content: flex-end; }';
        $css_rules[] = '.flex-content-space-between { align-content: space-between; }';
        $css_rules[] = '.flex-content-space-around { align-content: space-around; }';
        $css_rules[] = '.flex-content-space-evenly { align-content: space-evenly; }';
        
        // Responsive stack on mobile
        $css_rules[] = '@media (max-width: 768px) { .flex-stack-mobile { flex-direction: column !important; } }';

        $css = implode("\n", $css_rules);

        // Cache the CSS
        set_transient($cache_key, $css, self::CACHE_EXPIRATION);

        return $css;
    }

    /**
     * Clear all cached CSS
     *
     * @since 1.0.0
     */
    public function clear_cache(): void
    {
        global $wpdb;
        
        // Clear all flex CSS transients
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
                '_transient_' . self::CACHE_PREFIX . '%',
                '_transient_timeout_' . self::CACHE_PREFIX . '%'
            )
        );

        // Clear object cache
        wp_cache_delete('orbitools_flex_layout', 'theme_json');
    }
}