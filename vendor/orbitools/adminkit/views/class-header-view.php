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
     * Child page slugs mapping
     *
     * @since 1.0.0
     * @var array
     */
    private $child_page_slugs = array();

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
     * Uses Instance Registry to determine if this AdminKit instance
     * owns the current page and should render its header.
     *
     * @since 1.0.0
     * @return bool
     */
    private function should_render_header()
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
    <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($title); ?>" class="adminkit-header__img" />
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

        // Check if we're on a child page
        $is_child_page = !$this->is_top_level_adminkit_page();

    ?>
<nav class="adminkit-nav">
    <?php foreach ($tabs_data['tabs'] as $tab_key => $tab_label) : ?>
    <?php $this->render_tab_item($tab_key, $tab_label, $tabs_data['active_tab'], $is_child_page); ?>
    <?php endforeach; ?>
</nav>
<?php
    }

    /**
     * Get tabs data for main AdminKit page
     *
     * @since 1.0.0
     * @return array
     */
    private function get_tabs_data()
    {
        $tabs = $this->admin_kit->get_tabs();

        // Add child pages to main page tabs
        if ($this->should_include_child_pages()) {
            $child_pages = $this->get_child_pages();
            $tabs = array_merge($tabs, $child_pages);
        }

        return array(
            'tabs' => $tabs,
            'active_tab' => $this->admin_kit->get_active_tab()
        );
    }

    /**
     * Check if child pages should be included in the tab navigation
     *
     * @since 1.0.0
     * @return bool
     */
    private function should_include_child_pages()
    {
        // Include child pages on both main pages and child pages
        // (child pages need the full navigation to navigate back to main tabs and other child pages)
        return true;
    }

    /**
     * Check if current page is a top-level AdminKit page
     *
     * @since 1.0.0
     * @return bool
     */
    private function is_top_level_adminkit_page()
    {
        // Use Instance Registry if available
        if (class_exists('Orbitools\AdminKit\Instance_Registry')) {
            $page_info = \Orbitools\AdminKit\Instance_Registry::get_page_info();

            // If this AdminKit instance owns the page and it's not a child
            return $page_info['owner'] === $this->admin_kit->get_slug() && !$page_info['is_child'];
        }

        // Fallback: check WordPress admin page parent
        $parent = get_admin_page_parent();
        $current_page = isset($_GET['page']) ? $_GET['page'] : '';

        // If no parent or parent is the same as current page, it's top-level
        return empty($parent) || $parent === $current_page;
    }

    /**
     * Get child pages for the current AdminKit page
     *
     * @since 1.0.0
     * @return array
     */
    private function get_child_pages()
    {
        global $submenu;

        $child_pages = array();
        $parent_slug = $this->admin_kit->get_slug();

        // Check if there are submenus for this parent
        if (isset($submenu[$parent_slug])) {
            foreach ($submenu[$parent_slug] as $priority => $submenu_item) {
                // Skip the first item (usually points to the parent page itself)
                if ($priority === 0) {
                    continue;
                }

                // Extract submenu data
                $title = $submenu_item[0];
                $capability = $submenu_item[1];
                $menu_slug = $submenu_item[2];

                // Check if user has capability to see this page
                if (!current_user_can($capability)) {
                    continue;
                }

                // Create a tab key from the menu slug
                $tab_key = 'child_' . sanitize_key($menu_slug);

                // Store the mapping between tab key and actual menu slug
                $this->child_page_slugs[$tab_key] = $menu_slug;

                // Add to child pages array
                $child_pages[$tab_key] = $title;
            }
        }

        return $child_pages;
    }

    /**
     * Get the actual menu slug for a child page tab key
     *
     * @since 1.0.0
     * @param string $tab_key The child page tab key
     * @return string The actual menu slug
     */
    private function get_child_page_slug($tab_key)
    {
        if (isset($this->child_page_slugs[$tab_key])) {
            return $this->child_page_slugs[$tab_key];
        }

        // Fallback: remove the 'child_' prefix
        return str_replace('child_', '', $tab_key);
    }

    /**
     * Render tab item using new BEM structure
     *
     * @since 1.0.0
     * @param string $tab_key Tab key
     * @param string $tab_label Tab label
     * @param string $active_tab Currently active tab
     * @param bool $is_child_page Whether we're on a child page
     */
    private function render_tab_item($tab_key, $tab_label, $active_tab, $is_child_page)
    {
        // Check if this is a child page tab
        $is_child_tab = strpos($tab_key, 'child_') === 0;

        if ($is_child_page) {
            // On child pages, all items are links (no JavaScript)
            $item_type = 'link';
        } else {
            // On main pages, child tabs are links, regular tabs use JavaScript
            $item_type = $is_child_tab ? 'link' : 'tab';
        }

        // Build CSS classes
        $item_class = 'adminkit-nav__item adminkit-nav__item--' . $item_type;

        // Add active class logic
        if ($is_child_page) {
            // On child pages, check if this child tab matches the current page
            if ($is_child_tab) {
                $current_page = isset($_GET['page']) ? $_GET['page'] : '';
                $child_slug = $this->get_child_page_slug($tab_key);
                if ($current_page === $child_slug) {
                    $item_class .= ' adminkit-nav__item--active';
                }
            }
            // Regular tabs on child pages don't get active class (they're navigation links)
        } else {
            // On main pages, regular tabs get active class based on active_tab
            if (!$is_child_tab && $active_tab === $tab_key) {
                $item_class .= ' adminkit-nav__item--active';
            }
            // Child tabs on main pages don't get active class (they're navigation links)
        }

        // Determine URL and attributes
        if ($is_child_tab) {
            // Child page link
            $menu_slug = $this->get_child_page_slug($tab_key);
            $url = admin_url('admin.php?page=' . $menu_slug);
            $data_tab = null;
        } else {
            // Regular tab
            if ($is_child_page) {
                // On child pages, link back to main page with tab
                $url = admin_url('admin.php?page=' . $this->admin_kit->get_slug() . '&tab=' . $tab_key);
                $data_tab = null;
            } else {
                // On main pages, use JavaScript switching
                $url = $this->admin_kit->get_tab_url($tab_key);
                $data_tab = $tab_key;
            }
        }

    ?>
<a href="<?php echo esc_url($url); ?>" class="<?php echo esc_attr($item_class); ?>"
    <?php if ($data_tab): ?>data-tab="<?php echo esc_attr($data_tab); ?>" <?php endif; ?>>
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
<button type="submit" class="adminkit-toolbar__save-btn button button-primary" form="orbi-settings-form"
    aria-describedby="orbi-save-btn-desc">
    <span class="adminkit-toolbar__save-btn-text"><?php esc_html_e('Save Settings', 'orbitools-adminkit'); ?></span>
</button>
<span id="orbi-save-btn-desc" class="screen-reader-text">
    <?php esc_html_e('Save all settings changes', 'orbitools-adminkit'); ?>
</span>
<?php
    }
}