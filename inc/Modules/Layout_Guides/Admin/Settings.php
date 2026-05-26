<?php

/**
 * Layout Guides Settings Configuration
 *
 * Provides default values + accessor for the saved orbitools_settings
 * row. The AdminKit-era field-definition / preview-HTML helpers were
 * removed in v3 Phase 7; the React admin reads the field schema from
 * module.json instead.
 *
 * @package    Orbitools
 * @subpackage Modules/Layout_Guides/Admin
 * @since      1.0.0
 */

namespace Orbitools\Modules\Layout_Guides\Admin;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Settings
{
    /**
     * Get default settings configuration
     *
     * @return array Default settings array.
     */
    public static function get_defaults()
    {
        return array(
            'layout_guides_show_grids' => true,
            'layout_guides_show_rulers' => true,
            'layout_guides_grid_gutter' => 'var(--gutter)',
            'layout_guides_opacity' => '0.3',
            'layout_guides_color' => '#32a3e2',
            'layout_guides_toggle_key' => 'ctrl+shift+g',
        );
    }

    /**
     * Get current settings with defaults
     *
     * @return array Current settings merged with defaults.
     */
    public static function get_current_settings()
    {
        $saved_settings = get_option('orbitools_settings', array());
        return wp_parse_args($saved_settings, self::get_defaults());
    }
}
