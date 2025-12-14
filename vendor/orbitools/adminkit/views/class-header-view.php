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
<div class="adminkit adminkit-header"
    <?php if ($bg_color) echo 'style="background-color: ' . esc_attr($bg_color) . '"'; ?>>
    <div class="adminkit-header__content">
        <?php if ($image_url): ?>
        <div class="adminkit-header__image">
            <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($title); ?>"
                class="adminkit-header__img" />
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
        $tabs = $this->admin_kit->get_tabs();

        if (empty($tabs)) {
            return;
        }

        $active_tab = $this->admin_kit->get_active_tab();

    ?>
<nav class="adminkit-nav">
    <?php foreach ($tabs as $tab_key => $tab_label): ?>
    <?php $this->render_nav_item($tab_key, $tab_label, $active_tab); ?>
    <?php endforeach; ?>
</nav>
<?php
    }


    /**
     * Render navigation item
     *
     * @since 1.0.0
     * @param string $tab_key Tab key
     * @param string $tab_label Tab label or array with title and icon
     * @param string $active_tab Active tab
     */
    private function render_nav_item($tab_key, $tab_label, $active_tab)
    {
        $is_active = $active_tab === $tab_key;
        $is_multi_page = $this->admin_kit->is_multi_page_mode();

        $classes = array('adminkit-nav__item');

        if ($is_active) {
            $classes[] = 'adminkit-nav__item--active';
        }

        // In multi-page mode, nav items are actual page links
        if ($is_multi_page) {
            $classes[] = 'adminkit-nav__item--page';
        }

        // Handle tab data (could be string or array with icon)
        $tab_title = is_array($tab_label) ? ($tab_label['title'] ?? $tab_label) : $tab_label;
        $tab_icon = is_array($tab_label) ? ($tab_label['icon'] ?? null) : null;

    ?>
<a href="<?php echo esc_url($this->admin_kit->get_tab_url($tab_key)); ?>"
    class="<?php echo esc_attr(implode(' ', $classes)); ?>"
    <?php if (!$is_multi_page): ?>data-page="<?php echo esc_attr($tab_key); ?>"<?php endif; ?>
    id="<?php echo esc_attr('adminkit-nav-' . $tab_key); ?>">
    <?php if ($tab_icon): ?>
        <span class="adminkit-nav__icon">
            <?php echo $this->render_icon($tab_icon); ?>
        </span>
    <?php endif; ?>
    <span class="adminkit-nav__text">
        <?php echo esc_html($tab_title); ?>
    </span>
</a>
<?php
    }

    /**
     * Render icon based on type
     *
     * @since 1.0.0
     * @param string|array $icon Icon data
     * @return string Icon HTML
     */
    private function render_icon($icon)
    {
        if (is_string($icon)) {
            // Handle different icon types
            if (strpos($icon, '<svg') === 0) {
                // SVG icon
                return $icon;
            } elseif (strpos($icon, 'dashicons-') === 0) {
                // Dashicon
                return '<span class="dashicons ' . esc_attr($icon) . '"></span>';
            } else {
                // Custom CSS class
                return '<span class="' . esc_attr($icon) . '"></span>';
            }
        } elseif (is_array($icon)) {
            // Array format: ['type' => 'dashicon|svg|class', 'value' => '...']
            $type = $icon['type'] ?? 'class';
            $value = $icon['value'] ?? '';
            
            switch ($type) {
                case 'dashicon':
                    return '<span class="dashicons dashicons-' . esc_attr($value) . '"></span>';
                case 'svg':
                    return $value; // Assuming value contains the full SVG
                case 'class':
                default:
                    return '<span class="' . esc_attr($value) . '"></span>';
            }
        }
        
        return '';
    }
}