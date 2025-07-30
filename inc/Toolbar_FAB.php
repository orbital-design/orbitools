<?php

namespace Orbitools;

if (!defined('ABSPATH')) {
    exit;
}

class Toolbar_FAB
{

    private $toolbar_items = array();

    // Array of parent IDs that are allowed to show their children
    private $allowed_children = array(
        'new-content'
    );

    public function __construct()
    {
        // Only work on frontend, not in admin
        if (!\is_admin()) {
            \add_action('init', array($this, 'init'));
            \add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
            \add_action('wp_footer', array($this, 'render_fab'));

            // Don't hide admin bar yet - we need it to capture items first
            \add_action('wp_before_admin_bar_render', array($this, 'capture_toolbar_items'));
            \add_action('admin_bar_menu', array($this, 'capture_toolbar_items'), 999999);

            // Hide admin bar after capturing items
            \add_action('wp_head', array($this, 'hide_admin_bar'));
        }
    }

    public function init()
    {
        require_once(ABSPATH . 'wp-includes/admin-bar.php');
        \_wp_admin_bar_init();
    }

    public function capture_toolbar_items($wp_admin_bar = null)
    {
        global $wp_admin_bar;

        if (!$wp_admin_bar) {
            return;
        }

        $nodes = $wp_admin_bar->get_nodes();

        if ($nodes && empty($this->toolbar_items)) {
            foreach ($nodes as $node) {
                // Skip the wp-logo and its children - we'll use wp-logo as our FAB button
                if ($node->id === 'wp-logo' || $node->parent === 'wp-logo') {
                    continue;
                }

                // Skip site-name children but keep the main site-name link
                if ($node->parent === 'site-name') {
                    continue;
                }

                $this->toolbar_items[] = array(
                    'id' => $node->id,
                    'parent' => $node->parent,
                    'title' => $node->title,
                    'href' => $node->href,
                    'meta' => $node->meta,
                    'group' => $node->group
                );
            }
        }
    }

    public function hide_admin_bar()
    {
?>
        <style>
            #wpadminbar {
                display: none !important;
            }

            html {
                margin-top: 0 !important;
            }

            * html body {
                margin-top: 0 !important;
            }
        </style>
    <?php
    }

    public function enqueue_assets()
    {
        // Create a dummy style handle to attach our CSS to
        \wp_register_style('orbitools-fab', false);
        \wp_enqueue_style('orbitools-fab');
        \wp_add_inline_style('orbitools-fab', $this->get_fab_styles());

        // Create a dummy script handle to attach our JS to
        \wp_register_script('orbitools-fab', false, array(), false, true);
        \wp_enqueue_script('orbitools-fab');
        \wp_add_inline_script('orbitools-fab', $this->get_fab_scripts());
    }

    private function get_fab_styles()
    {
        return '
        /* CSS Custom Properties for drawer offset */
        :root {
            --fab-drawer-offset: 0px;
        }

        /* Body transition for push effect */
        body {
            transition: margin-left 0.3s ease;
        }

        body.fab-drawer-open {
            margin-left: 320px;
            --fab-drawer-offset: 320px;
        }

        /* Auto-adjust common fixed elements */
        .fab-drawer-open .wp-block-navigation__responsive-container,
        .fab-drawer-open .wp-block-navigation__responsive-dialog,
        .fab-drawer-open .wp-block-navigation__responsive-close,
        .fab-drawer-open header[class*="fixed"],
        .fab-drawer-open nav[class*="fixed"],
        .fab-drawer-open .fixed-header,
        .fab-drawer-open .fixed-nav,
        .fab-drawer-open .sticky-header,
        .fab-drawer-open [class*="sticky-top"],
        .fab-drawer-open [class*="fixed-top"] {
            transform: translateX(var(--fab-drawer-offset));
            transition: transform 0.3s ease;
        }

        /* Specific selectors for common themes/plugins */
        .fab-drawer-open .site-header.fixed,
        .fab-drawer-open .navbar-fixed-top,
        .fab-drawer-open .fixed-navigation,
        .fab-drawer-open .masthead.fixed {
            transform: translateX(var(--fab-drawer-offset));
            transition: transform 0.3s ease;
        }

        #wp-toolbar-fab {
            position: fixed;
            bottom: 20px;
            left: 20px;
            z-index: 99999;
        }

        .fab-button {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: #23282d;
            color: #fff;
            border: none;
            cursor: pointer;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            position: relative;
        }

        .fab-button:hover {
            background: #32373c;
            transform: scale(1.1);
        }

        .fab-button.active {
            background: #0073aa;
        }

        .fab-menu {
            position: fixed;
            top: 0;
            left: -320px;
            width: 320px;
            height: 100vh;
            background: #23282d;
            box-shadow: 2px 0 10px rgba(0,0,0,0.3);
            overflow-y: auto;
            transition: left 0.3s ease;
            z-index: 99998;
        }

        .fab-menu.active {
            left: 0;
        }

        /* Drawer header */
        .fab-drawer-header {
            padding: 20px;
            background: #32373c;
            border-bottom: 1px solid #444;
            position: sticky;
            top: 0;
            z-index: 1;
        }

        .fab-drawer-title {
            color: #fff;
            font-size: 18px;
            font-weight: 600;
            margin: 0;
        }

        .fab-menu-item {
            padding: 0;
            margin: 0;
            list-style: none;
        }

        .fab-menu-item a,
        .fab-menu-item .fab-toggle-btn {
            display: block;
            padding: 10px 15px;
            color: #ccc;
            text-decoration: none;
            transition: all 0.2s ease;
            border-bottom: 1px solid #32373c;
            width: 100%;
            text-align: left;
            background: none;
            border: none;
            border-bottom: 1px solid #32373c;
            cursor: pointer;
        }

        .fab-menu-item a:hover,
        .fab-menu-item .fab-toggle-btn:hover {
            background: #32373c;
            color: #fff;
        }

        .fab-menu-item.has-submenu > .fab-toggle-btn:after {
            content: "▶";
            float: right;
            opacity: 0.5;
        }

        .fab-submenu {
            display: none;
            background: #1a1c1e;
            padding-left: 15px;
        }

        .fab-menu-item.open .fab-submenu {
            display: block;
        }

        .fab-menu-item.open > .fab-toggle-btn:after {
            transform: rotate(90deg);
            display: inline-block;
        }

        .fab-menu-group {
            border-top: 2px solid #32373c;
            margin-top: 5px;
            padding-top: 5px;
        }

        .fab-menu-group:first-child {
            border-top: none;
            margin-top: 0;
            padding-top: 0;
        }

        /* Section headers */
        .fab-section-header {
            padding: 15px 20px 10px;
            color: #0073aa;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #32373c;
            margin-bottom: 5px;
        }

        @media (max-width: 768px) {
            body.fab-drawer-open {
                margin-left: 280px;
                --fab-drawer-offset: 280px;
            }

            .fab-menu {
                width: 280px;
                left: -280px;
            }
        }

        @media (max-width: 600px) {
            body.fab-drawer-open {
                margin-left: 100vw;
                --fab-drawer-offset: 100vw;
            }

            .fab-menu {
                width: 100vw;
                left: -100vw;
            }

            #wp-toolbar-fab {
                bottom: 10px;
                right: 10px;
            }
        }
        ';
    }

    private function get_fab_scripts()
    {
        return '
        document.addEventListener("DOMContentLoaded", function() {
            const fabButton = document.querySelector(".fab-button");
            const fabMenu = document.querySelector(".fab-menu");

            // Auto-detect and handle fixed elements
            function handleFixedElements(isOpen) {
                const fixedElements = document.querySelectorAll("[style*=\'position: fixed\'], [style*=\'position:fixed\']");
                const computedFixedElements = [];

                // Find elements with computed fixed positioning
                document.querySelectorAll("*").forEach(function(el) {
                    if (getComputedStyle(el).position === "fixed" && !el.closest("#wp-toolbar-fab") && !el.closest(".fab-menu")) {
                        computedFixedElements.push(el);
                    }
                });

                // Combine both sets
                const allFixedElements = [...new Set([...fixedElements, ...computedFixedElements])];

                allFixedElements.forEach(function(el) {
                    if (isOpen) {
                        el.style.transition = "transform 0.3s ease";
                        el.style.transform = "translateX(var(--fab-drawer-offset))";
                        el.classList.add("fab-moved-element");
                    } else {
                        el.style.transform = "";
                        el.classList.remove("fab-moved-element");
                    }
                });
            }

            function openDrawer() {
                if (fabButton) fabButton.classList.add("active");
                if (fabMenu) fabMenu.classList.add("active");
                document.body.classList.add("fab-drawer-open");
                handleFixedElements(true);
            }

            function closeDrawer() {
                if (fabButton) fabButton.classList.remove("active");
                if (fabMenu) fabMenu.classList.remove("active");
                document.body.classList.remove("fab-drawer-open");
                handleFixedElements(false);
            }

            if (fabButton) {
                fabButton.addEventListener("click", function(e) {
                    e.preventDefault();
                    e.stopPropagation();

                    if (this.classList.contains("active")) {
                        closeDrawer();
                    } else {
                        openDrawer();
                    }
                });
            }

            // Close drawer when clicking outside
            document.addEventListener("click", function(e) {
                if (!e.target.closest("#wp-toolbar-fab") && !e.target.closest(".fab-menu")) {
                    closeDrawer();
                }
            });

            // Handle submenu toggles
            document.addEventListener("click", function(e) {
                if (e.target.matches(".fab-toggle-btn")) {
                    e.preventDefault();
                    e.target.parentElement.classList.toggle("open");
                }
            });

            // Close drawer on escape key
            document.addEventListener("keydown", function(e) {
                if (e.key === "Escape") {
                    closeDrawer();
                }
            });
        });
        ';
    }

    public function render_fab()
    {
        if (!\is_user_logged_in()) {
            return;
        }

        $menu_html = $this->build_menu_structure();

    ?>
        <div id="wp-toolbar-fab">
            <button class="fab-button" aria-label="Admin Menu">
                <span class="dashicons dashicons-wordpress" style="font-size: 24px;"></span>
            </button>
            <div class="fab-menu">
                <div class="fab-drawer-header">
                    <h3 class="fab-drawer-title">Admin Menu</h3>
                </div>
                <?php echo $menu_html; ?>
            </div>
        </div>
<?php
    }

    private function build_menu_structure()
    {
        $menu_html = '';
        $items_by_parent = array();
        $groups = array();

        // Separate items into left (primary) and right (secondary) sections
        $primary_items = array();
        $secondary_items = array();

        foreach ($this->toolbar_items as $item) {
            $parent = $item['parent'] ?: 'top';
            if (!isset($items_by_parent[$parent])) {
                $items_by_parent[$parent] = array();
            }

            // Determine if item belongs to right side (secondary) of admin bar
            $is_secondary = ($item['group'] === true && $item['parent'] === 'top-secondary') ||
                $item['parent'] === 'top-secondary' ||
                in_array($item['id'], array('my-account', 'user-actions', 'logout', 'top-secondary'));

            if ($item['group']) {
                if (!isset($groups[$parent])) {
                    $groups[$parent] = array();
                }
                if (!isset($groups[$parent][$item['group']])) {
                    $groups[$parent][$item['group']] = array();
                }
                $groups[$parent][$item['group']][] = $item;

                // Also categorize grouped items
                if ($is_secondary) {
                    $secondary_items[] = $item;
                } else {
                    $primary_items[] = $item;
                }
            } else {
                $items_by_parent[$parent][] = $item;

                if ($is_secondary) {
                    $secondary_items[] = $item;
                } else {
                    $primary_items[] = $item;
                }
            }
        }

        // Build primary (left side) menu
        $menu_html .= '<div class="fab-section-header">Site Functions</div>';
        $menu_html .= '<ul class="fab-menu-primary">';

        if (isset($items_by_parent['top'])) {
            foreach ($items_by_parent['top'] as $item) {
                $is_secondary = ($item['group'] === true && $item['parent'] === 'top-secondary') ||
                    $item['parent'] === 'top-secondary' ||
                    in_array($item['id'], array('my-account', 'user-actions', 'logout', 'top-secondary'));
                if (!$is_secondary) {
                    $menu_html .= $this->render_single_item($item, $items_by_parent);
                }
            }
        }

        if (isset($groups['top'])) {
            foreach ($groups['top'] as $group_items) {
                $has_primary = false;
                foreach ($group_items as $item) {
                    $is_secondary = ($item['group'] === true && $item['parent'] === 'top-secondary') ||
                        $item['parent'] === 'top-secondary' ||
                        in_array($item['id'], array('my-account', 'user-actions', 'logout', 'top-secondary'));
                    if (!$is_secondary) {
                        $has_primary = true;
                        break;
                    }
                }
                if ($has_primary) {
                    $menu_html .= '<li class="fab-menu-group">';
                    foreach ($group_items as $item) {
                        $is_secondary = ($item['group'] === true && $item['parent'] === 'top-secondary') ||
                            $item['parent'] === 'top-secondary' ||
                            in_array($item['id'], array('my-account', 'user-actions', 'logout', 'top-secondary'));
                        if (!$is_secondary) {
                            $menu_html .= $this->render_single_item($item, $items_by_parent);
                        }
                    }
                    $menu_html .= '</li>';
                }
            }
        }

        $menu_html .= '</ul>';

        // Build secondary (right side) menu
        $menu_html .= '<div class="fab-section-header">Account</div>';
        $menu_html .= '<ul class="fab-menu-secondary">';

        if (isset($items_by_parent['top'])) {
            foreach ($items_by_parent['top'] as $item) {
                $is_secondary = ($item['group'] === true && $item['parent'] === 'top-secondary') ||
                    $item['parent'] === 'top-secondary' ||
                    in_array($item['id'], array('my-account', 'user-actions', 'logout', 'top-secondary'));
                if ($is_secondary) {
                    $menu_html .= $this->render_single_item($item, $items_by_parent);
                }
            }
        }

        if (isset($groups['top'])) {
            foreach ($groups['top'] as $group_items) {
                $has_secondary = false;
                foreach ($group_items as $item) {
                    $is_secondary = ($item['group'] === true && $item['parent'] === 'top-secondary') ||
                        $item['parent'] === 'top-secondary' ||
                        in_array($item['id'], array('my-account', 'user-actions', 'logout', 'top-secondary'));
                    if ($is_secondary) {
                        $has_secondary = true;
                        break;
                    }
                }
                if ($has_secondary) {
                    $menu_html .= '<li class="fab-menu-group">';
                    foreach ($group_items as $item) {
                        $is_secondary = ($item['group'] === true && $item['parent'] === 'top-secondary') ||
                            $item['parent'] === 'top-secondary' ||
                            in_array($item['id'], array('my-account', 'user-actions', 'logout', 'top-secondary'));
                        if ($is_secondary) {
                            $menu_html .= $this->render_single_item($item, $items_by_parent);
                        }
                    }
                    $menu_html .= '</li>';
                }
            }
        }

        $menu_html .= '</ul>';

        return $menu_html;
    }

    private function render_single_item($item, $all_items_by_parent)
    {
        $has_children = isset($all_items_by_parent[$item['id']]) && in_array($item['id'], $this->allowed_children);
        $classes = array('fab-menu-item');

        if ($has_children) {
            $classes[] = 'has-submenu';
        }

        if (!empty($item['meta']['class'])) {
            $classes[] = $item['meta']['class'];
        }

        $html = '<li id="fab-' . \esc_attr($item['id']) . '" class="' . implode(' ', $classes) . '">';

        if ($has_children) {
            // Render as button for items with allowed children
            $html .= '<button type="button" class="fab-toggle-btn">' . $item['title'] . '</button>';
        } else {
            // Render as link for regular items
            $link_attrs = array(
                'href="' . \esc_url($item['href'] ?: '#') . '"'
            );

            if (!empty($item['meta']['onclick'])) {
                $link_attrs[] = 'onclick="' . \esc_attr($item['meta']['onclick']) . '"';
            }

            if (!empty($item['meta']['target'])) {
                $link_attrs[] = 'target="' . \esc_attr($item['meta']['target']) . '"';
            }

            if (!empty($item['meta']['title'])) {
                $link_attrs[] = 'title="' . \esc_attr($item['meta']['title']) . '"';
            }

            $html .= '<a ' . implode(' ', $link_attrs) . '>' . $item['title'] . '</a>';
        }

        if ($has_children) {
            $html .= '<ul class="fab-submenu">';
            $html .= $this->render_menu_items($all_items_by_parent[$item['id']], $all_items_by_parent, 0);
            $html .= '</ul>';
        }

        $html .= '</li>';

        return $html;
    }

    private function render_menu_items($items, $all_items_by_parent, $level = 0)
    {
        $html = '';

        foreach ($items as $item) {
            $has_children = isset($all_items_by_parent[$item['id']]) && in_array($item['id'], $this->allowed_children);
            $classes = array('fab-menu-item');

            if ($has_children) {
                $classes[] = 'has-submenu';
            }

            if (!empty($item['meta']['class'])) {
                $classes[] = $item['meta']['class'];
            }

            $html .= '<li id="fab-' . \esc_attr($item['id']) . '" class="' . implode(' ', $classes) . '">';

            $link_attrs = array(
                'href="' . \esc_url($item['href'] ?: '#') . '"'
            );

            if (!empty($item['meta']['onclick'])) {
                $link_attrs[] = 'onclick="' . \esc_attr($item['meta']['onclick']) . '"';
            }

            if (!empty($item['meta']['target'])) {
                $link_attrs[] = 'target="' . \esc_attr($item['meta']['target']) . '"';
            }

            if (!empty($item['meta']['title'])) {
                $link_attrs[] = 'title="' . \esc_attr($item['meta']['title']) . '"';
            }

            $html .= '<a ' . implode(' ', $link_attrs) . '>' . $item['title'] . '</a>';

            if ($has_children) {
                $html .= '<ul class="fab-submenu">';
                $html .= $this->render_menu_items($all_items_by_parent[$item['id']], $all_items_by_parent, $level + 1);
                $html .= '</ul>';
            }

            $html .= '</li>';
        }

        return $html;
    }
}
