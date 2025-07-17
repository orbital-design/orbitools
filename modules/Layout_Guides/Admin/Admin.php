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
        add_filter('orbitools_available_modules', array($this, 'register_module'));
        
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
    public function register_module($modules)
    {
        $modules['layout_guides'] = array(
            'name'        => __('Layout Guides', 'orbitools'),
            'description' => __('Development tool that adds visual layout guides and debugging helpers for theme development.', 'orbitools'),
            'version'     => '1.0.0',
            'author'      => 'OrbiTools',
            'category'    => 'development',
            'tags'        => array('development', 'debugging', 'layout', 'guides'),
            'requires'    => array(),
            'settings'    => array(
                'layout_guides_enabled' => false,
            ),
            'preview'     => $this->get_preview_html(),
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