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
                // Simple refresh approach - let WordPress handle the HTML and CSS handle the styling
                window.location.reload();
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
            // Add group class to the classes array
            $menu_item->classes[] = 'menu-item-group';
            
            // Set type display for admin
            $menu_item->type_label = 'Group';
        }
        
        return $menu_item;
    }

    /**
     * Add JavaScript to add CSS classes to group items and clean up interface
     *
     * @since 1.0.0
     */
    public function add_group_classes_script()
    {
        ?>
        <script>
        jQuery(document).ready(function($) {
            // Function to process group items
            function processGroupItems() {
                $('.item-type').each(function() {
                    if ($(this).text() === 'Group') {
                        var $menuItem = $(this).closest('.menu-item');
                        $menuItem.addClass('menu-item-group');
                        
                        // Hide URL field for group items
                        $menuItem.find('label:contains("URL")').parent().hide();
                        
                        // Change "Navigation Label" to "Group Name"
                        $menuItem.find('label:contains("Navigation Label")').each(function() {
                            if ($(this).text().includes('Navigation Label')) {
                                $(this).html($(this).html().replace('Navigation Label', 'Group Name'));
                            }
                        });
                    }
                });
            }
            
            // Run on page load
            processGroupItems();
            
            // Watch for changes in the menu structure
            if (window.MutationObserver) {
                var observer = new MutationObserver(function(mutations) {
                    var shouldReprocess = false;
                    
                    mutations.forEach(function(mutation) {
                        if (mutation.type === 'childList' || mutation.type === 'attributes') {
                            // Check if menu items were added or modified
                            if (mutation.target.classList && (
                                mutation.target.classList.contains('menu-item') ||
                                mutation.target.classList.contains('menu-item-settings') ||
                                $(mutation.target).find('.menu-item').length > 0
                            )) {
                                shouldReprocess = true;
                            }
                        }
                    });
                    
                    if (shouldReprocess) {
                        setTimeout(processGroupItems, 100);
                    }
                });
                
                observer.observe(document.body, {
                    childList: true,
                    subtree: true,
                    attributes: true,
                    attributeFilter: ['class', 'style']
                });
            }
            
            // Also reprocess when menu items are expanded/collapsed
            $(document).on('click', '.item-edit', function() {
                setTimeout(processGroupItems, 100);
            });
        });
        </script>
        
        <style>
        /* Hide URL field for group items */
        .menu-item-group .field-url {
            display: none !important;
        }
        
        /* Hide additional fields that might appear */
        .menu-item-group .field-link-target,
        .menu-item-group .field-attr-title,
        .menu-item-group .field-css-classes,
        .menu-item-group .field-xfn {
            display: none !important;
        }
        </style>
        <?php
    }
}