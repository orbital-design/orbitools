<?php

/**
 * Breadcrumbs View Class
 *
 * Handles rendering of the breadcrumb navigation toolbar.
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
     * Render the complete breadcrumbs section
     *
     * @since 1.0.0
     */
    public function render_breadcrumbs()
    {
        if (!$this->should_render_breadcrumbs()) {
            return;
        }

        $this->render_breadcrumbs_section();
    }

    /**
     * Check if breadcrumbs should be rendered on current screen
     *
     * Uses Instance Registry to determine if this AdminKit instance
     * owns the current page and should render its breadcrumbs.
     *
     * @since 1.0.0
     * @return bool
     */
    private function should_render_breadcrumbs()
    {
        // Use Instance Registry for more accurate detection
        if (class_exists('Orbitools\AdminKit\Instance_Registry')) {
            return \Orbitools\AdminKit\Instance_Registry::is_instance_page($this->admin_kit->get_slug());
        }
        
        // Fallback to original method if registry not available
        $screen = get_current_screen();
        return $screen && strpos($screen->id, $this->admin_kit->get_slug()) !== false;
    }

    /**
     * Render breadcrumbs section
     *
     * @since 1.0.0
     */
    private function render_breadcrumbs_section()
    {
        ?>
        <div class="adminkit adminkit-toolbar">
            <?php $this->render_breadcrumb_navigation(); ?>
            <?php $this->render_nav_actions(); ?>
        </div>
        <?php
    }

    /**
     * Render breadcrumb navigation
     *
     * @since 1.0.0
     */
    private function render_breadcrumb_navigation()
    {
        $breadcrumb_data = $this->get_breadcrumb_data();
        
        if (empty($breadcrumb_data['tabs'])) {
            return;
        }
        ?>
        <nav class="adminkit-toolbar__breadcrumbs">
            <ol class="adminkit-toolbar__breadcrumb-list">
                <?php $this->render_breadcrumb_items($breadcrumb_data); ?>
            </ol>
        </nav>
        <?php
    }

    /**
     * Get breadcrumb data
     *
     * @since 1.0.0
     * @return array
     */
    private function get_breadcrumb_data()
    {
        $current_tab = $this->admin_kit->get_current_tab();
        $current_section = $this->admin_kit->get_current_section();
        
        return array(
            'page_title' => $this->admin_kit->get_page_title(),
            'tabs' => $this->admin_kit->get_tabs(),
            'current_tab' => $current_tab,
            'current_section' => $current_section,
            'sections' => $this->get_current_sections($current_tab)
        );
    }

    /**
     * Get sections for current tab
     *
     * @since 1.0.0
     * @param string $current_tab Current tab key
     * @return array
     */
    private function get_current_sections($current_tab)
    {
        $structure = $this->admin_kit->get_content_structure();
        return isset($structure[$current_tab]['sections']) ? $structure[$current_tab]['sections'] : array();
    }

    /**
     * Render breadcrumb items
     *
     * @since 1.0.0
     * @param array $data Breadcrumb data
     */
    private function render_breadcrumb_items($data)
    {
        // Page title
        $this->render_breadcrumb_item($data['page_title']);
        
        // Current tab
        if ($data['current_tab'] && isset($data['tabs'][$data['current_tab']])) {
            $this->render_breadcrumb_item($data['tabs'][$data['current_tab']], true, true);
        }
        
        // Current section
        if ($data['current_section'] && isset($data['sections'][$data['current_section']])) {
            $this->render_breadcrumb_item($data['sections'][$data['current_section']], true, true);
        }
    }

    /**
     * Render individual breadcrumb item
     *
     * @since 1.0.0
     * @param string $text Item text
     * @param bool   $with_separator Whether to include separator
     * @param bool   $is_current Whether this is the current item
     */
    private function render_breadcrumb_item($text, $with_separator = false, $is_current = false)
    {
        $text_class = 'adminkit-toolbar__breadcrumb-text';
        if ($is_current) {
            $text_class .= ' adminkit-toolbar__breadcrumb-text--current';
        }
        ?>
        <li class="adminkit-toolbar__breadcrumb-item">
            <?php if ($with_separator) : ?>
                <span class="adminkit-toolbar__breadcrumb-separator">›</span>
            <?php endif; ?>
            <span class="<?php echo esc_attr($text_class); ?>">
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
    private function render_nav_actions()
    {
        ?>
        <div class="adminkit-toolbar__nav-actions">
            <?php
            // Hook for navigation actions (save buttons, etc.)
            do_action($this->admin_kit->get_func_slug() . '_render_nav_actions');

            // Default save button (if no custom actions provided)
            if (!has_action($this->admin_kit->get_func_slug() . '_render_nav_actions')) {
                $this->render_default_nav_actions();
            }
            ?>
        </div>
        <?php
    }

    /**
     * Render default navigation actions
     *
     * @since 1.0.0
     */
    private function render_default_nav_actions()
    {
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