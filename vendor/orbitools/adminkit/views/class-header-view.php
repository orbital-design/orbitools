<?php

/**
 * Header View Class (Simplified)
 *
 * Handles rendering of the admin page header with navigation.
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
     * Render the complete header
     *
     * @since 1.0.0
     */
    public function render_header()
    {
        if (!$this->should_render()) {
            return;
        }

        $this->render_header_html();
    }

    /**
     * Check if header should be rendered
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
     * Render header HTML
     *
     * @since 1.0.0
     */
    private function render_header_html()
    {
        $bg_color = $this->admin_kit->get_page_header_bg_color();
        $image_url = $this->admin_kit->get_page_header_image();
        $title = $this->admin_kit->get_page_title();
        $description = $this->admin_kit->get_page_description();
        $hide_text = $this->admin_kit->get_hide_title_description();
        
        ?>
        <div class="adminkit adminkit-header" <?php if ($bg_color) echo 'style="background-color: ' . esc_attr($bg_color) . '"'; ?>>
            <div class="adminkit-header__content">
                <?php if ($image_url): ?>
                    <div class="adminkit-header__image">
                        <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($title); ?>" class="adminkit-header__img" />
                    </div>
                <?php endif; ?>
                
                <div class="adminkit-header__text<?php if ($hide_text) echo ' screen-reader-text'; ?>">
                    <h1 class="adminkit-header__title"><?php echo esc_html($title); ?></h1>
                    <?php if ($description): ?>
                        <p class="adminkit-header__description"><?php echo esc_html($description); ?></p>
                    <?php endif; ?>
                </div>
                
                <?php $this->render_navigation(); ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render navigation
     *
     * @since 1.0.0
     */
    private function render_navigation()
    {
        $tabs = $this->get_all_tabs();
        
        if (empty($tabs)) {
            return;
        }

        $active_tab = $this->admin_kit->get_active_tab();
        $is_child_page = $this->is_child_page();

        ?>
        <nav class="adminkit-nav">
            <?php foreach ($tabs as $tab_key => $tab_label): ?>
                <?php $this->render_nav_item($tab_key, $tab_label, $active_tab, $is_child_page); ?>
            <?php endforeach; ?>
        </nav>
        <?php
    }

    /**
     * Get all tabs (regular + child pages)
     *
     * @since 1.0.0
     * @return array
     */
    private function get_all_tabs()
    {
        $tabs = $this->admin_kit->get_tabs();
        $child_pages = $this->get_child_pages();
        
        return array_merge($tabs, $child_pages);
    }

    /**
     * Get child pages
     *
     * @since 1.0.0
     * @return array
     */
    private function get_child_pages()
    {
        global $submenu;
        
        $parent_slug = $this->admin_kit->get_slug();
        $child_pages = array();
        
        if (!isset($submenu[$parent_slug])) {
            return $child_pages;
        }
        
        foreach ($submenu[$parent_slug] as $priority => $submenu_item) {
            if ($priority === 0 || !current_user_can($submenu_item[1])) {
                continue;
            }
            
            $child_pages['child_' . sanitize_key($submenu_item[2])] = $submenu_item[0];
        }
        
        return $child_pages;
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

    /**
     * Render navigation item
     *
     * @since 1.0.0
     * @param string $tab_key Tab key
     * @param string $tab_label Tab label
     * @param string $active_tab Active tab
     * @param bool $is_child_page Whether we're on a child page
     */
    private function render_nav_item($tab_key, $tab_label, $active_tab, $is_child_page)
    {
        $is_child_tab = strpos($tab_key, 'child_') === 0;
        
        // Determine item type and URL
        if ($is_child_tab) {
            $item_type = 'link';
            $url = admin_url('admin.php?page=' . str_replace('child_', '', $tab_key));
            $data_tab = null;
        } else {
            if ($is_child_page) {
                $item_type = 'link';
                $url = admin_url('admin.php?page=' . $this->admin_kit->get_slug() . '&tab=' . $tab_key);
                $data_tab = null;
            } else {
                $item_type = 'tab';
                $url = $this->admin_kit->get_tab_url($tab_key);
                $data_tab = $tab_key;
            }
        }
        
        // Build classes
        $classes = array('adminkit-nav__item', 'adminkit-nav__item--' . $item_type);
        
        // Add active class
        if ($this->is_active_item($tab_key, $active_tab, $is_child_page)) {
            $classes[] = 'adminkit-nav__item--active';
        }
        
        ?>
        <a href="<?php echo esc_url($url); ?>" 
           class="<?php echo esc_attr(implode(' ', $classes)); ?>"
           <?php if ($data_tab): ?>data-tab="<?php echo esc_attr($data_tab); ?>"<?php endif; ?>>
            <?php echo esc_html($tab_label); ?>
        </a>
        <?php
    }

    /**
     * Check if item is active
     *
     * @since 1.0.0
     * @param string $tab_key Tab key
     * @param string $active_tab Active tab
     * @param bool $is_child_page Whether we're on a child page
     * @return bool
     */
    private function is_active_item($tab_key, $active_tab, $is_child_page)
    {
        $is_child_tab = strpos($tab_key, 'child_') === 0;
        
        if ($is_child_page && $is_child_tab) {
            // On child pages, check if this child tab matches current page
            $current_page = isset($_GET['page']) ? $_GET['page'] : '';
            return $current_page === str_replace('child_', '', $tab_key);
        }
        
        if (!$is_child_page && !$is_child_tab) {
            // On main pages, check if this regular tab is active
            return $active_tab === $tab_key;
        }
        
        return false;
    }
}