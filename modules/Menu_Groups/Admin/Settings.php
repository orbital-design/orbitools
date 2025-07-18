<?php

/**
 * Menu Groups Settings
 *
 * Handles settings definitions and admin structure for the Menu Groups module.
 *
 * @package    Orbitools
 * @subpackage Modules/Menu_Groups/Admin
 * @since      1.0.0
 */

namespace Orbitools\Modules\Menu_Groups\Admin;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Menu Groups Settings Class
 *
 * @since 1.0.0
 */
class Settings
{
    /**
     * Initialize AJAX handlers
     *
     * @since 1.0.0
     */
    public static function init()
    {
        // Any AJAX handlers can be registered here
    }

    /**
     * Get admin structure for the Menu Groups module
     *
     * @since 1.0.0
     * @return array Admin structure configuration.
     */
    public static function get_admin_structure(): array
    {
        return array(
            'sections' => array(
                'menu-groups' => __('Menu Groups', 'orbitools'),
            )
        );
    }

    /**
     * Get field definitions for the Menu Groups module
     *
     * @since 1.0.0
     * @return array Field definitions array.
     */
    public static function get_field_definitions(): array
    {
        return array(
            // Module enable/disable toggle
            array(
                'id'      => 'menu_groups_enabled',
                'type'    => 'checkbox',
                'title'   => __('Enable Menu Groups', 'orbitools'),
                'desc'    => __('Enable menu group functionality to organize menu items with group headings.', 'orbitools'),
                'std'     => false,
                'section' => 'menu-groups',
            ),

            // Group heading style
            array(
                'id'      => 'menu_groups_heading_style',
                'type'    => 'select',
                'title'   => __('Group Heading Style', 'orbitools'),
                'desc'    => __('Choose how group headings should be displayed in menus.', 'orbitools'),
                'options' => array(
                    'default'    => __('Default (H3 heading)', 'orbitools'),
                    'minimal'    => __('Minimal (subtle text)', 'orbitools'),
                    'emphasized' => __('Emphasized (bold with separator)', 'orbitools'),
                ),
                'std'     => 'default',
                'section' => 'menu-groups',
            ),

            // Group separator
            array(
                'id'      => 'menu_groups_show_separator',
                'type'    => 'checkbox',
                'title'   => __('Show Group Separators', 'orbitools'),
                'desc'    => __('Add visual separators between menu groups.', 'orbitools'),
                'std'     => true,
                'section' => 'menu-groups',
            ),

            // Custom CSS classes
            array(
                'id'      => 'menu_groups_custom_classes',
                'type'    => 'text',
                'title'   => __('Custom CSS Classes', 'orbitools'),
                'desc'    => __('Add custom CSS classes to group headings (space-separated).', 'orbitools'),
                'std'     => '',
                'section' => 'menu-groups',
            ),
        );
    }
}