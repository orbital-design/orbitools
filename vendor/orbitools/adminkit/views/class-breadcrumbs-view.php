<?php

/**
 * Breadcrumbs View Class (Simplified)
 *
 * Handles rendering of the breadcrumb toolbar with navigation actions.
 * Clean, focused implementation with minimal complexity.
 *
 * @package    Orbitools\AdminKit\Views
 * @since      1.0.0
 */

namespace Orbitools\AdminKit\Views;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Breadcrumbs View Class
 *
 * @since 1.0.0
 */
class Breadcrumbs_View
{
    /**
     * AdminKit instance
     *
     * @since 1.0.0
     * @var \Orbitools\AdminKit\Admin_Kit
     */
    private $admin_kit;

    /**
     * Constructor
     *
     * @since 1.0.0
     * @param \Orbitools\AdminKit\Admin_Kit $admin_kit AdminKit instance
     */
    public function __construct($admin_kit)
    {
        $this->admin_kit = $admin_kit;
    }

    /**
     * Render the complete breadcrumbs toolbar
     *
     * @since 1.0.0
     */
    public function render_breadcrumbs()
    {
        if (!$this->should_render()) {
            return;
        }

        $this->render_toolbar_html();
    }

    /**
     * Check if breadcrumbs should be rendered
     *
     * @since 1.0.0
     * @return bool
     */
    private function should_render()
    {
        if (class_exists('Orbitools\AdminKit\Instance_Registry')) {
            return \Orbitools\AdminKit\Instance_Registry::is_instance_page($this->admin_kit->get_slug());
        }
        
        $screen = get_current_screen();
        return $screen && strpos($screen->id, $this->admin_kit->get_slug()) !== false;
    }

    /**
     * Render toolbar HTML
     *
     * @since 1.0.0
     */
    private function render_toolbar_html()
    {
        ?>
        <div class="adminkit adminkit-toolbar">
            <nav class="adminkit-toolbar__breadcrumbs">
                <ol class="adminkit-toolbar__breadcrumb-list">
                    <?php $this->render_breadcrumb_trail(); ?>
                </ol>
            </nav>
            
            <div class="adminkit-toolbar__nav-actions">
                <?php $this->render_actions(); ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render the appropriate breadcrumb trail
     *
     * @since 1.0.0
     */
    private function render_breadcrumb_trail()
    {
        if ($this->is_child_page()) {
            $this->render_child_page_breadcrumbs();
        } else {
            $this->render_main_page_breadcrumbs();
        }
    }

    /**
     * Render breadcrumbs for main AdminKit pages
     *
     * @since 1.0.0
     */
    private function render_main_page_breadcrumbs()
    {
        $page_title = $this->admin_kit->get_page_title();
        $current_tab = $this->admin_kit->get_current_tab();
        $current_section = $this->admin_kit->get_current_section();
        
        // Get tab and section names
        $tabs = $this->admin_kit->get_tabs();
        $tab_name = isset($tabs[$current_tab]) ? $tabs[$current_tab] : '';
        
        $sections = $this->get_sections($current_tab);
        $section_name = isset($sections[$current_section]) ? $sections[$current_section] : '';
        
        // Render breadcrumb trail
        $this->render_breadcrumb($page_title);
        
        if ($tab_name) {
            $this->render_breadcrumb($tab_name, true, true);
        }
        
        if ($section_name) {
            $this->render_breadcrumb($section_name, true, true);
        }
    }

    /**
     * Render breadcrumbs for child pages
     *
     * @since 1.0.0
     */
    private function render_child_page_breadcrumbs()
    {
        $page_title = $this->admin_kit->get_page_title();
        $child_page_title = $this->get_child_page_title();
        
        // Render: Parent Page > Child Page
        $this->render_breadcrumb($page_title);
        
        if ($child_page_title) {
            $this->render_breadcrumb($child_page_title, true, true);
        }
    }

    /**
     * Get the current child page title
     *
     * @since 1.0.0
     * @return string
     */
    private function get_child_page_title()
    {
        global $submenu;
        
        $current_page = isset($_GET['page']) ? $_GET['page'] : '';
        $parent_slug = $this->admin_kit->get_slug();
        
        if (!isset($submenu[$parent_slug]) || !$current_page) {
            return '';
        }
        
        foreach ($submenu[$parent_slug] as $submenu_item) {
            if ($submenu_item[2] === $current_page) {
                return $submenu_item[0];
            }
        }
        
        return '';
    }

    /**
     * Get sections for a tab
     *
     * @since 1.0.0
     * @param string $tab_key Tab key
     * @return array
     */
    private function get_sections($tab_key)
    {
        $structure = $this->admin_kit->get_content_structure();
        return isset($structure[$tab_key]['sections']) ? $structure[$tab_key]['sections'] : array();
    }

    /**
     * Render a breadcrumb item
     *
     * @since 1.0.0
     * @param string $text Item text
     * @param bool $with_separator Whether to include separator
     * @param bool $is_current Whether this is the current item
     */
    private function render_breadcrumb($text, $with_separator = false, $is_current = false)
    {
        ?>
        <li class="adminkit-toolbar__breadcrumb-item">
            <?php if ($with_separator): ?>
                <span class="adminkit-toolbar__breadcrumb-separator">›</span>
            <?php endif; ?>
            <span class="adminkit-toolbar__breadcrumb-text<?php if ($is_current) echo ' adminkit-toolbar__breadcrumb-text--current'; ?>">
                <?php echo esc_html($text); ?>
            </span>
        </li>
        <?php
    }

    /**
     * Render navigation actions
     *
     * @since 1.0.0
     */
    private function render_actions()
    {
        // Allow custom actions via hook
        do_action($this->admin_kit->get_func_slug() . '_render_nav_actions');

        // Only show default save button on main AdminKit pages, not child pages
        if (!has_action($this->admin_kit->get_func_slug() . '_render_nav_actions') && !$this->is_child_page()) {
            ?>
            <button type="submit" 
                    class="adminkit-toolbar__save-btn button button-primary" 
                    form="orbi-settings-form"
                    aria-describedby="orbi-save-btn-desc">
                <span class="adminkit-toolbar__save-btn-text"><?php esc_html_e('Save Settings', 'orbitools-adminkit'); ?></span>
            </button>
            <span id="orbi-save-btn-desc" class="screen-reader-text">
                <?php esc_html_e('Save all settings changes', 'orbitools-adminkit'); ?>
            </span>
            <?php
        }
    }

    /**
     * Check if current page is a child page
     *
     * @since 1.0.0
     * @return bool
     */
    private function is_child_page()
    {
        if (class_exists('Orbitools\AdminKit\Instance_Registry')) {
            $page_info = \Orbitools\AdminKit\Instance_Registry::get_page_info();
            return $page_info['owner'] === $this->admin_kit->get_slug() && $page_info['is_child'];
        }

        return false;
    }
}