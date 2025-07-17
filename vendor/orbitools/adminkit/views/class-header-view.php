<?php

/**
 * Header View Class
 *
 * Handles rendering of the admin page header including branding,
 * navigation tabs, and breadcrumb toolbar.
 *
 * @package    Orbitools\AdminKit\Views
 * @since      1.0.0
 */

namespace Orbitools\AdminKit\Views;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Header View Class
 *
 * @since 1.0.0
 */
class Header_View
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
     * Render the complete header section
     *
     * @since 1.0.0
     */
    public function render_header()
    {
        if (!$this->should_render_header()) {
            return;
        }

        $header_data = $this->get_header_data();
        
        $this->render_header_section($header_data);
        $this->render_toolbar_section();
    }

    /**
     * Check if header should be rendered on current screen
     *
     * @since 1.0.0
     * @return bool
     */
    private function should_render_header()
    {
        $screen = get_current_screen();
        return $screen && strpos($screen->id, $this->admin_kit->get_slug()) !== false;
    }

    /**
     * Get header data array
     *
     * @since 1.0.0
     * @return array
     */
    private function get_header_data()
    {
        return array(
            'bg_color' => $this->admin_kit->get_page_header_bg_color(),
            'image_url' => $this->admin_kit->get_page_header_image(),
            'title' => $this->admin_kit->get_page_title(),
            'description' => $this->admin_kit->get_page_description(),
            'hide_text' => $this->admin_kit->get_hide_title_description()
        );
    }

    /**
     * Render the main header section
     *
     * @since 1.0.0
     * @param array $data Header data
     */
    private function render_header_section($data)
    {
        ?>
        <div class="adminkit adminkit-header" <?php $this->render_header_style($data['bg_color']); ?>>
            <div class="adminkit-header__content">
                <?php $this->render_header_image($data['image_url'], $data['title']); ?>
                <?php $this->render_header_text($data); ?>
                <?php $this->render_tabs(); ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render header background style
     *
     * @since 1.0.0
     * @param string $bg_color Background color
     */
    private function render_header_style($bg_color)
    {
        if ($bg_color) {
            echo 'style="background-color: ' . esc_attr($bg_color) . '"';
        }
    }

    /**
     * Render header image
     *
     * @since 1.0.0
     * @param string $image_url Image URL
     * @param string $title Page title for alt text
     */
    private function render_header_image($image_url, $title)
    {
        if (!$image_url) {
            return;
        }
        ?>
        <div class="adminkit-header__image">
            <img src="<?php echo esc_url($image_url); ?>" 
                 alt="<?php echo esc_attr($title); ?>" 
                 class="adminkit-header__img" />
        </div>
        <?php
    }

    /**
     * Render header text content
     *
     * @since 1.0.0
     * @param array $data Header data
     */
    private function render_header_text($data)
    {
        $text_class = 'adminkit-header__text';
        if ($data['hide_text']) {
            $text_class .= ' screen-reader-text';
        }
        ?>
        <div class="<?php echo esc_attr($text_class); ?>">
            <h1 class="adminkit-header__title"><?php echo esc_html($data['title']); ?></h1>
            <?php if ($data['description']) : ?>
                <p class="adminkit-header__description"><?php echo esc_html($data['description']); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render navigation tabs
     *
     * @since 1.0.0
     */
    private function render_tabs()
    {
        $tabs_data = $this->get_tabs_data();
        
        if (empty($tabs_data['tabs'])) {
            return;
        }
        ?>
        <div class="orbi-admin__header-tabs">
            <nav class="orbi-admin__tabs-nav">
                <?php foreach ($tabs_data['tabs'] as $tab_key => $tab_label) : ?>
                    <?php $this->render_tab_link($tab_key, $tab_label, $tabs_data['active_tab']); ?>
                <?php endforeach; ?>
            </nav>
        </div>
        <?php
    }

    /**
     * Get tabs data
     *
     * @since 1.0.0
     * @return array
     */
    private function get_tabs_data()
    {
        return array(
            'tabs' => $this->admin_kit->get_tabs(),
            'active_tab' => $this->admin_kit->get_active_tab()
        );
    }

    /**
     * Render individual tab link
     *
     * @since 1.0.0
     * @param string $tab_key Tab key
     * @param string $tab_label Tab label
     * @param string $active_tab Currently active tab
     */
    private function render_tab_link($tab_key, $tab_label, $active_tab)
    {
        $link_class = 'orbi-admin__tab-link';
        if ($active_tab === $tab_key) {
            $link_class .= ' orbi-admin__tab-link--active';
        }
        ?>
        <a href="<?php echo esc_url($this->admin_kit->get_tab_url($tab_key)); ?>"
           class="<?php echo esc_attr($link_class); ?>"
           data-tab="<?php echo esc_attr($tab_key); ?>">
            <?php echo esc_html($tab_label); ?>
        </a>
        <?php
    }

    /**
     * Render toolbar section
     *
     * @since 1.0.0
     */
    private function render_toolbar_section()
    {
        ?>
        <div class="adminkit adminkit-toolbar">
            <?php $this->render_breadcrumbs(); ?>
            <?php $this->render_nav_actions(); ?>
        </div>
        <?php
    }

    /**
     * Render breadcrumb navigation
     *
     * @since 1.0.0
     */
    private function render_breadcrumbs()
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