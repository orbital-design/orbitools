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

use Orbitools\Modules\Menu_Groups\Admin\Settings_Helper;

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
    }

    /**
     * Render the group headings meta box
     *
     * @since 1.0.0
     */
    public function render_group_meta_box()
    {
?>
<div class="group-options" style="">
    <p><?php _e("Add a new menu item with just a name for grouping related items, even if they're not parent and child.", 'orbitools'); ?>
    </p>
    <label for="group-title">
        <?php _e('Group Name:', 'orbitools'); ?>
    </label>
    <input type="text" id="group-title" class="widefat"
        placeholder="<?php esc_attr_e('Enter group name', 'orbitools'); ?>" />

    <button type="button" id="add-group-btn" class="button button-primary" style="width: 100%;">
        <?php _e('Add Group', 'orbitools'); ?>
    </button>
</div>

<script>
jQuery(document).ready(function($) {
    $('#add-group-btn').click(function() {
        var title = $('#group-title').val() || '<?php _e('Group', 'orbitools'); ?>';

        // Get current menu ID from the page
        var menuId = $('#menu').val();
        if (!menuId) {
            alert('Please select a menu first');
            return;
        }

        // Add the group to the menu
        $.post(ajaxurl, {
            action: 'add_menu_group',
            'menu-item-title': title,
            'menu-item-type': 'custom',
            'menu-item-url': '#',
            'menu-item-group': '1',
            'menu': menuId,
            'nonce': '<?php echo wp_create_nonce('add-menu-group'); ?>'
        }, function(response) {
            console.log('Add group response:', response);
            if (response.success) {
                // Create menu item HTML and add it to the menu
                var itemId = response.data.menu_item_id;
                var title = response.data.title;

                var menuItemHtml = '<li id="menu-item-' + itemId +
                    '" class="menu-item menu-item-depth-0 menu-item-custom">' +
                    '<div class="menu-item-bar">' +
                    '<div class="menu-item-handle">' +
                    '<span class="item-title"><span class="menu-item-title">' + title +
                    '</span> <span class="is-submenu" style="display: none;">sub item</span></span>' +
                    '<span class="item-controls">' +
                    '<span class="item-type">Group</span>' +
                    '<span class="item-order hide-if-js">' +
                    '<a href="#" class="item-move-up"><abbr title="Move up">↑</abbr></a>' +
                    ' | ' +
                    '<a href="#" class="item-move-down"><abbr title="Move down">↓</abbr></a>' +
                    '</span>' +
                    '<a class="item-edit" id="edit-' + itemId + '" href="#">Edit</a>' +
                    '</span>' +
                    '</div>' +
                    '</div>' +
                    '<div class="menu-item-settings wp-clearfix" id="menu-item-settings-' +
                    itemId + '">' +
                    '<input class="menu-item-data-db-id" type="hidden" name="menu-item-db-id[' +
                    itemId + ']" value="' + itemId + '" />' +
                    '<input class="menu-item-data-object-id" type="hidden" name="menu-item-object-id[' +
                    itemId + ']" value="' + itemId + '" />' +
                    '<input class="menu-item-data-parent-id" type="hidden" name="menu-item-parent-id[' +
                    itemId + ']" value="0" />' +
                    '<input class="menu-item-data-position" type="hidden" name="menu-item-position[' +
                    itemId + ']" value="1" />' +
                    '<input class="menu-item-data-type" type="hidden" name="menu-item-type[' +
                    itemId + ']" value="custom" />' +
                    '<input class="menu-item-data-title" type="hidden" name="menu-item-title[' +
                    itemId + ']" value="' + title + '" />' +
                    '<input class="menu-item-data-url" type="hidden" name="menu-item-url[' +
                    itemId + ']" value="#" />' +
                    '<input type="hidden" name="menu-item-group[' + itemId + ']" value="1" />' +
                    '<p class="field-move hide-if-no-js description description-wide">' +
                    '<label><span>Move</span>' +
                    '<a href="#" class="menus-move-up">Up one</a>' +
                    '<a href="#" class="menus-move-down">Down one</a>' +
                    '<a href="#" class="menus-move-left"></a>' +
                    '<a href="#" class="menus-move-right"></a>' +
                    '<a href="#" class="menus-move-top">To the top</a>' +
                    '</label>' +
                    '</p>' +
                    '<p class="description description-wide">' +
                    '<label for="edit-menu-item-title-' + itemId + '">Navigation Label<br />' +
                    '<input type="text" id="edit-menu-item-title-' + itemId +
                    '" class="widefat edit-menu-item-title" name="menu-item-title[' + itemId +
                    ']" value="' + title + '" />' +
                    '</label>' +
                    '</p>' +
                    '<p class="description description-wide">' +
                    '<strong>Group</strong> - This item will appear as a non-clickable group in your menu.' +
                    '</p>' +
                    '<div class="menu-item-actions description-wide submitbox">' +
                    '<a class="item-delete submitdelete deletion" id="delete-' + itemId +
                    '" href="#">Remove</a>' +
                    '<span class="meta-sep hide-if-no-js"> | </span>' +
                    '<a class="item-cancel submitcancel hide-if-no-js" id="cancel-' + itemId +
                    '" href="#">Cancel</a>' +
                    '</div>' +
                    '</div>' +
                    '</li>';

                // Add to the menu
                $('#menu-to-edit').append(menuItemHtml);

                // Initialize the new menu item
                wpNavMenu.initOneMenu($('#menu-item-' + itemId));

                // Show success message
                $('.group-options').append(
                    '<div class="notice notice-success is-dismissible" style="margin-top: 10px;"><p>Group added successfully!</p></div>'
                );
                setTimeout(function() {
                    $('.notice-success').fadeOut();
                }, 3000);
            } else {
                alert('Error adding group: ' + response.data);
            }
        }).fail(function(xhr, status, error) {
            console.error('AJAX error:', status, error);
            alert('Network error occurred');
        });

        // Clear the form
        $('#group-title').val('');
    });
});
</script>
<?php
    }

    /**
     * AJAX handler to add a menu group
     *
     * @since 1.0.0
     */
    public function ajax_add_group()
    {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'add-menu-group')) {
            wp_send_json_error('Security check failed');
            return;
        }

        // Check permissions
        if (!current_user_can('edit_theme_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }

        // Get and sanitize input
        $title = isset($_POST['menu-item-title']) ? sanitize_text_field($_POST['menu-item-title']) : '';
        if (empty($title)) {
            $title = __('Group', 'orbitools');
        }

        // Get the current menu being edited
        $menu_id = isset($_POST['menu']) ? intval($_POST['menu']) : 0;
        if (!$menu_id) {
            wp_send_json_error('No menu specified. Please select a menu first.');
            return;
        }

        // Verify menu exists
        $menu = wp_get_nav_menu_object($menu_id);
        if (!$menu) {
            wp_send_json_error('Invalid menu specified');
            return;
        }

        // Create a new menu item
        $menu_item_data = array(
            'menu-item-type' => 'custom',
            'menu-item-title' => $title,
            'menu-item-url' => '#',
            'menu-item-status' => 'publish'
        );

        $menu_item_id = wp_update_nav_menu_item($menu_id, 0, $menu_item_data);

        if (is_wp_error($menu_item_id)) {
            wp_send_json_error('Failed to create menu item: ' . $menu_item_id->get_error_message());
            return;
        }

        if (!$menu_item_id) {
            wp_send_json_error('Failed to create menu item - no ID returned');
            return;
        }

        // Mark this as a group
        update_post_meta($menu_item_id, '_menu_item_group', '1');

        wp_send_json_success(array(
            'message' => 'Group added successfully',
            'menu_item_id' => $menu_item_id,
            'title' => $title
        ));
    }

    /**
     * Save group fields when menu item is updated
     *
     * @since 1.0.0
     * @param int $menu_id         Menu ID.
     * @param int $menu_item_db_id Menu item ID.
     * @param array $menu_item_args Menu item arguments.
     */
    public function save_group_fields($menu_id, $menu_item_db_id, $menu_item_args)
    {
        // Handle group metadata
        if (isset($_POST['menu-item-group'][$menu_item_db_id])) {
            update_post_meta($menu_item_db_id, '_menu_item_group', '1');
        }

        if (isset($_POST['menu-item-style'][$menu_item_db_id])) {
            $style = sanitize_text_field($_POST['menu-item-style'][$menu_item_db_id]);
            update_post_meta($menu_item_db_id, '_menu_item_group_style', $style);
        }
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
        if (!Settings_Helper::is_module_enabled()) {
            return $items;
        }

        $processed_items = array();
        $settings = Settings_Helper::get_all_settings();

        foreach ($items as $item) {
            $is_group = get_post_meta($item->ID, '_menu_item_group', true);

            if ($is_group) {
                // This is a group
                $group_style = get_post_meta($item->ID, '_menu_item_group_style', true);
                if (empty($group_style)) {
                    $group_style = $settings['heading_style'];
                }

                // Modify the item for group display
                $item->url = '';
                $item->classes[] = 'menu-group';
                $item->classes[] = 'menu-group--' . $group_style;

                // Add custom classes if defined
                if (!empty($settings['custom_classes'])) {
                    $custom_classes = explode(' ', $settings['custom_classes']);
                    $item->classes = array_merge($item->classes, $custom_classes);
                }

                // Mark as group for CSS/JS targeting
                $item->is_group = true;
                $item->target = '';
                $item->xfn = '';

                $processed_items[] = $item;

                // Add separator if enabled
                if ($settings['show_separators'] && count($processed_items) > 1) {
                    $separator = clone $item;
                    $separator->title = '';
                    $separator->url = '';
                    $separator->classes = array('menu-group-separator');
                    $separator->is_group_separator = true;
                    $separator->target = '';
                    $separator->xfn = '';

                    // Insert separator before group
                    array_splice($processed_items, -1, 0, array($separator));
                }
            } else {
                // Regular menu item
                $processed_items[] = $item;
            }
        }

        return $processed_items;
    }
}