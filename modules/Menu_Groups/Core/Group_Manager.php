<?php

/**
 * Menu Groups Manager
 *
 * Handles the core functionality for menu groups, including adding a custom
 * meta box for group headings and processing groups for frontend display.
 *
 * @package    Orbitools
 * @subpackage Modules/Menu_Groups/Core
 * @since      1.0.0
 */

namespace Orbitools\Modules\Menu_Groups\Core;


// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Group Manager Class
 *
 * @since 1.0.0
 */
class Group_Manager
{
    /**
     * Add custom meta box for group headings
     *
     * @since 1.0.0
     */
    public function add_group_meta_box()
    {
        add_meta_box(
            'add-group',
            __('Add Group', 'orbitools'),
            array($this, 'render_group_meta_box'),
            'nav-menus',
            'side',
            'default'
        );
        
        // Add AJAX handler
        add_action('wp_ajax_add_menu_group', array($this, 'handle_add_group_ajax'));
    }

    /**
     * Handle AJAX request to add menu group
     *
     * @since 1.0.0
     */
    public function handle_add_group_ajax()
    {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'add-group-nonce')) {
            wp_send_json_error(__('Security check failed', 'orbitools'));
        }

        // Check permissions
        if (!current_user_can('edit_theme_options')) {
            wp_send_json_error(__('You do not have permission to do this', 'orbitools'));
        }

        // Get and sanitize input
        $title = isset($_POST['group_title']) ? sanitize_text_field($_POST['group_title']) : '';
        if (empty($title)) {
            $title = __('Group', 'orbitools');
        }

        $menu_id = isset($_POST['menu_id']) ? intval($_POST['menu_id']) : 0;
        if (!$menu_id) {
            wp_send_json_error(__('No menu selected', 'orbitools'));
        }

        // Create menu item
        $menu_item_data = array(
            'menu-item-type' => 'custom',
            'menu-item-title' => $title,
            'menu-item-url' => '#',
            'menu-item-status' => 'publish'
        );

        $menu_item_id = wp_update_nav_menu_item($menu_id, 0, $menu_item_data);

        if (!is_wp_error($menu_item_id) && $menu_item_id) {
            // Mark as group
            update_post_meta($menu_item_id, '_menu_item_group', '1');
            wp_send_json_success(__('Group added successfully', 'orbitools'));
        } else {
            wp_send_json_error(__('Failed to create menu item', 'orbitools'));
        }
    }

    /**
     * Render the group headings meta box
     *
     * @since 1.0.0
     */
    public function render_group_meta_box()
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
<div class="group-options" style="">
    <p><?php _e("Add a new menu item with just a name for grouping related items, even if they're not parent and child.", 'orbitools'); ?>
    </p>
    <label for="group-title">
        <?php _e('Group Name:', 'orbitools'); ?>
    </label>
    <input type="text" id="group-title" name="group-title" class="widefat"
        placeholder="<?php esc_attr_e('Enter group name', 'orbitools'); ?>" />

    <button type="button" id="add-group-btn" class="button button-primary" style="width: 100%;"><?php esc_html_e('Add Group', 'orbitools'); ?></button>
    <input type="hidden" id="group-nonce" value="<?php echo wp_create_nonce('add-group-nonce'); ?>" />
    <input type="hidden" id="menu-id" value="<?php echo esc_attr($nav_menu_selected_id); ?>" />
    <?php if ($nav_menu_selected_id == 0): ?>
        <p style="color: #d63638; margin-top: 10px;"><?php _e('Please select a menu to add groups to.', 'orbitools'); ?></p>
    <?php endif; ?>
</div>
<?php
    }



    /**
     * Process menu objects to handle groups
     *
     * @since 1.0.0
     * @param array  $items Menu items.
     * @param object $args  Menu arguments.
     * @return array Modified menu items.
     */
    public function process_menu_groups($items, $args)
    {
        $processed_items = array();

        foreach ($items as $item) {
            $is_group = get_post_meta($item->ID, '_menu_item_group', true);

            if ($is_group) {
                // This is a group - modify the item for group display
                $item->url = '';
                $item->classes[] = 'menu-group';

                // Mark as group for CSS/JS targeting
                $item->is_group = true;
                $item->target = '';
                $item->xfn = '';

                $processed_items[] = $item;
            } else {
                // Regular menu item
                $processed_items[] = $item;
            }
        }

        return $processed_items;
    }

    /**
     * Setup group menu item properties
     *
     * @since 1.0.0
     * @param object $menu_item Menu item object.
     * @return object Modified menu item object.
     */
    public function setup_group_menu_item($menu_item)
    {
        // Check if this is a group item
        $is_group = get_post_meta($menu_item->ID, '_menu_item_group', true);
        
        if ($is_group) {
            // Add group class to the classes array only if it doesn't already exist
            if (!in_array('menu-item-group', $menu_item->classes)) {
                $menu_item->classes[] = 'menu-item-group';
            }
            
            // Set type display for admin
            $menu_item->type_label = 'Group';
        }
        
        return $menu_item;
    }

}