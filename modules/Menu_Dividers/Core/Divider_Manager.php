<?php

/**
 * Menu Dividers Manager
 *
 * Handles the core functionality for menu dividers, including adding a custom
 * meta box for divider items and processing dividers for frontend display.
 *
 * @package    Orbitools
 * @subpackage Modules/Menu_Dividers/Core
 * @since      1.0.0
 */

namespace Orbitools\Modules\Menu_Dividers\Core;


// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Divider Manager Class
 *
 * @since 1.0.0
 */
class Divider_Manager
{
    /**
     * Add custom meta box for dividers
     *
     * @since 1.0.0
     */
    public function add_divider_meta_box()
    {
        add_meta_box(
            'add-divider',
            __('Add Divider', 'orbitools'),
            array($this, 'render_divider_meta_box'),
            'nav-menus',
            'side',
            'default'
        );

        // Add AJAX handler
        add_action('wp_ajax_add_menu_divider', array($this, 'handle_add_divider_ajax'));
    }

    /**
     * Handle AJAX request to add menu divider
     *
     * @since 1.0.0
     */
    public function handle_add_divider_ajax()
    {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'add-divider-nonce')) {
            wp_send_json_error(__('Security check failed', 'orbitools'));
        }

        // Check permissions
        if (!current_user_can('edit_theme_options')) {
            wp_send_json_error(__('You do not have permission to do this', 'orbitools'));
        }

        $menu_id = isset($_POST['menu_id']) ? intval($_POST['menu_id']) : 0;
        if (!$menu_id) {
            wp_send_json_error(__('No menu selected', 'orbitools'));
        }

        // Generate unique divider ID
        $unique_id = 'divider_' . uniqid() . '_' . time();

        // Create menu item
        $menu_item_data = array(
            'menu-item-type' => 'custom',
            'menu-item-title' => $unique_id,
            'menu-item-url' => '#',
            'menu-item-status' => 'publish'
        );

        $menu_item_id = wp_update_nav_menu_item($menu_id, 0, $menu_item_data);

        if (!is_wp_error($menu_item_id) && $menu_item_id) {
            // Mark as divider
            update_post_meta($menu_item_id, '_menu_item_is_divider', '1');
            wp_send_json_success(__('Divider added successfully', 'orbitools'));
        } else {
            wp_send_json_error(__('Failed to create menu item', 'orbitools'));
        }
    }

    /**
     * Render the divider meta box
     *
     * @since 1.0.0
     */
    public function render_divider_meta_box()
    {
        // Get current menu being edited - WordPress stores it in different ways
        $nav_menu_selected_id = 0;

        if (isset($_REQUEST['menu']) && $_REQUEST['menu'] > 0) {
            $nav_menu_selected_id = (int) $_REQUEST['menu'];
        } elseif (isset($_GET['menu']) && $_GET['menu'] > 0) {
            $nav_menu_selected_id = (int) $_GET['menu'];
        } else {
            // Try to get from the form if it exists
            global $nav_menu_selected_id;
            if (isset($nav_menu_selected_id) && $nav_menu_selected_id > 0) {
                // Use the global
            } else {
                // Get the first available menu
                $menus = wp_get_nav_menus();
                if (!empty($menus)) {
                    $nav_menu_selected_id = $menus[0]->term_id;
                }
            }
        }
?>
<div class="divider-options">
    <p><?php _e("Add a visual divider between menu items. This creates a non-clickable separator line.", 'orbitools'); ?>
    </p>

    <button type="button" id="add-divider-btn" class="button button-primary"
        style="width: 100%;"><?php esc_html_e('Add Divider', 'orbitools'); ?></button>
    <input type="hidden" id="divider-nonce" value="<?php echo wp_create_nonce('add-divider-nonce'); ?>" />
    <input type="hidden" id="divider-menu-id" value="<?php echo esc_attr($nav_menu_selected_id); ?>" />
    <?php if ($nav_menu_selected_id == 0): ?>
    <p style="color: #d63638; margin-top: 10px;"><?php _e('Please select a menu to add dividers to.', 'orbitools'); ?>
    </p>
    <?php endif; ?>
</div>
<?php
    }

    /**
     * Process menu objects to handle dividers
     *
     * @since 1.0.0
     * @param array  $items Menu items.
     * @param object $args  Menu arguments.
     * @return array Modified menu items.
     */
    public function process_menu_dividers($items, $args)
    {
        $processed_items = array();

        foreach ($items as $item) {
            $is_divider = get_post_meta($item->ID, '_menu_item_is_divider', true);

            if ($is_divider) {
                // This is a divider - modify the item for divider display
                $item->url = '';
                $item->classes[] = 'menu-divider';

                // Mark as divider for CSS/JS targeting
                $item->is_divider = true;
                $item->target = '';
                $item->xfn = '';
                $item->title = ''; // Remove the unique ID from display

                $processed_items[] = $item;
            } else {
                // Regular menu item
                $processed_items[] = $item;
            }
        }

        return $processed_items;
    }

    /**
     * Setup divider menu item properties
     *
     * @since 1.0.0
     * @param object $menu_item Menu item object.
     * @return object Modified menu item object.
     */
    public function setup_divider_menu_item($menu_item)
    {
        // Check if this is a divider item
        $is_divider = get_post_meta($menu_item->ID, '_menu_item_is_divider', true);

        if ($is_divider) {
            // Add divider class to the classes array only if it doesn't already exist
            if (!in_array('menu__item--divider', $menu_item->classes)) {
                $menu_item->classes[] = 'menu__item--divider';
            }

            // Set type display for admin
            $menu_item->type_label = 'Divider';
        }

        return $menu_item;
    }
}