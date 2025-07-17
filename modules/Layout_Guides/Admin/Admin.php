<?php

/**
 * Layout Guides Admin Integration
 *
 * Handles admin-side integration for the Layout Guides module including
 * module registration, settings configuration, and admin interface.
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

/**
 * Layout Guides Admin Class
 *
 * Manages admin integration for the Layout Guides module.
 *
 * @since 1.0.0
 */
class Admin
{
    /**
     * Initialize admin functionality
     *
     * @since 1.0.0
     */
    public function init()
    {
        // Register the module with the main plugin
        add_filter('orbitools_available_modules', array($this, 'register_module_metadata'));

        // Add admin structure and fields
        add_filter('orbitools_adminkit_structure', array($this, 'add_admin_structure'));
        add_filter('orbitools_adminkit_fields', array($this, 'add_admin_fields'));

        // Add admin assets
        add_action('orbitools_enqueue_assets', array($this, 'enqueue_admin_assets'));
    }

    /**
     * Register module with main plugin
     *
     * @since 1.0.0
     * @param array $modules Existing modules array.
     * @return array Modified modules array.
     */
    public function register_module_metadata($modules)
    {
        $modules['layout_guides'] = array(
            'name'        => __('Layout Guides', 'orbitools'),
            'subtitle'    => __('Visual layout tools', 'orbitools'),
            'description' => __('Visual development tools that add user toggleable guides and rulers to the front end of the website for theme development and debugging.', 'orbitools'),
            'icon'        => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="#32a3e2" d="M.2 468.9C2.7 493.1 23.1 512 48 512h416c26.5 0 48-21.5 48-48v-96c0-26.5-21.5-48-48-48h-48v80c0 8.8-7.2 16-16 16s-16-7.2-16-16v-80h-64v80c0 8.8-7.2 16-16 16s-16-7.2-16-16v-80h-64v80c0 8.8-7.2 16-16 16s-16-7.2-16-16v-80h-80c-8.8 0-16-7.2-16-16s7.2-16 16-16h80v-64h-80c-8.8 0-16-7.2-16-16s7.2-16 16-16h80v-64h-80c-8.8 0-16-7.2-16-16s7.2-16 16-16h80V48c0-26.5-21.5-48-48-48H48C21.5 0 0 21.5 0 48v416c0 1.7.1 3.3.2 4.9z"/></svg>',
            'configure_url' => admin_url('admin.php?page=orbitools&tab=modules&section=layout_guides'),
        );

        return $modules;
    }

    /**
     * Add admin structure
     *
     * @since 1.0.0
     * @param array $structure Existing admin structure.
     * @return array Modified admin structure.
     */
    public function add_admin_structure($structure)
    {
        // Add to modules tab
        if (!isset($structure['modules'])) {
            $structure['modules'] = array(
                'title'        => __('Modules', 'orbitools'),
                'display_mode' => 'cards',
                'sections'     => array(),
            );
        }

        $structure['modules']['sections']['layout_guides'] = __('Layout Guides', 'orbitools');

        return $structure;
    }

    /**
     * Add admin fields
     *
     * @since 1.0.0
     * @param array $fields Existing admin fields.
     * @return array Modified admin fields.
     */
    public function add_admin_fields($fields)
    {
        if (!isset($fields['modules'])) {
            $fields['modules'] = array();
        }

        $fields['modules'] = array_merge(
            $fields['modules'],
            Settings::get_field_definitions()
        );

        return $fields;
    }

    /**
     * Enqueue admin assets
     *
     * @since 1.0.0
     * @param string $hook_suffix Current admin page hook.
     */
    public function enqueue_admin_assets($hook_suffix)
    {
        // Only enqueue on orbitools admin pages
        if (strpos($hook_suffix, 'orbitools') === false) {
            return;
        }

        wp_enqueue_style(
            'orbitools-layout-guides-admin',
            ORBITOOLS_URL . 'modules/Layout_Guides/css/admin-layout-guides.css',
            array(),
            '1.0.0'
        );
    }

    /**
     * Get preview HTML for admin
     *
     * @since 1.0.0
     * @return string Preview HTML.
     */
    private function get_preview_html()
    {
        return '
            <div class="layout-guides-preview">
                <div class="layout-guides-preview__container">
                    <div class="layout-guides-preview__grid">
                        <div class="layout-guides-preview__item"></div>
                        <div class="layout-guides-preview__item"></div>
                        <div class="layout-guides-preview__item"></div>
                    </div>
                    <div class="layout-guides-preview__baseline"></div>
                    <div class="layout-guides-preview__label">Visual Layout Guides</div>
                </div>
            </div>
        ';
    }
}