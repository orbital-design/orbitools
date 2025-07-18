<?php

/**
 * Page Builder Class
 *
 * Coordinates all view components to build admin pages for AdminKit.
 *
 * @package    Orbitools\AdminKit
 * @version    1.0.0
 * @author     OrbiTools
 * @since      1.0.0
 */

namespace Orbitools\AdminKit\Classes;

use Orbitools\AdminKit\Views\Header_View;
use Orbitools\AdminKit\Views\Breadcrumbs_View;
use Orbitools\AdminKit\Views\Content_View;

// Prevent direct access
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Page Builder Class
 *
 * Responsible for coordinating all view components to render
 * complete admin pages with proper structure and hooks.
 *
 * @since 1.0.0
 */
class Page_Builder
{

    /**
     * Reference to the main AdminKit instance
     *
     * @since 1.0.0
     * @var \Orbitools\AdminKit\Admin_Kit
     */
    private $admin_kit;

    /**
     * Header view instance
     *
     * @since 1.0.0
     * @var Header_View
     */
    private $header_view;

    /**
     * Breadcrumbs view instance
     *
     * @since 1.0.0
     * @var Breadcrumbs_View
     */
    private $breadcrumbs_view;

    /**
     * Content view instance
     *
     * @since 1.0.0
     * @var Content_View
     */
    private $content_view;


    /**
     * Constructor
     *
     * @since 1.0.0
     * @param \Orbitools\AdminKit\Admin_Kit $admin_kit AdminKit instance
     */
    public function __construct($admin_kit)
    {
        $this->admin_kit = $admin_kit;
        $this->init_view_components();
    }

    /**
     * Initialize view components
     *
     * @since 1.0.0
     */
    private function init_view_components()
    {
        $this->header_view = new Header_View($this->admin_kit);
        $this->breadcrumbs_view = new Breadcrumbs_View($this->admin_kit);
        $this->content_view = new Content_View($this->admin_kit);
    }

    /**
     * Build and render complete admin page
     *
     * This method orchestrates all the components needed for a complete admin page
     *
     * @since 1.0.0
     */
    public function build_page()
    {
        // Define the page structure and components to include
        $page_components = array(
            'content'
        );

        // Allow filtering of page components
        $page_components = apply_filters(
            $this->admin_kit->get_func_slug() . '_page_components',
            $page_components
        );

?>
<div class="adminkit adminkit-content wrap" id="adminKit--<?php echo esc_attr($this->admin_kit->get_slug()); ?>">
    <h1 class="screen-reader-text">
        <?php echo esc_html($this->admin_kit->get_page_title()); ?>
    </h1>

    <?php do_action($this->admin_kit->get_func_slug() . '_before_header'); ?>

    <?php
            // Render each component in order
            foreach ($page_components as $component) {
                $this->render_component($component);
            }
            ?>

    <?php do_action($this->admin_kit->get_func_slug() . '_after_footer'); ?>

</div>
<?php
    }

    /**
     * Build and render global header
     *
     * @since 1.0.0
     */
    public function build_header()
    {
        $this->render_component('header');
    }

    /**
     * Build and render breadcrumbs
     *
     * @since 1.0.0
     */
    public function build_breadcrumbs()
    {
        $this->render_component('breadcrumbs');
    }

    /**
     * Render a specific component
     *
     * @since 1.0.0
     * @param string $component Component name to render
     */
    private function render_component($component)
    {
        switch ($component) {
            case 'content':
                $this->content_view->render_tab_content();
                do_action($this->admin_kit->get_func_slug() . '_after_content');
                break;

            case 'header':
                $this->header_view->render_header();
                break;

            case 'breadcrumbs':
                $this->breadcrumbs_view->render_breadcrumbs();
                break;

            default:
                // Allow custom components via action hook
                do_action($this->admin_kit->get_func_slug() . '_render_component_' . $component);
                break;
        }
    }

    /**
     * Get header view instance
     *
     * @since 1.0.0
     * @return Header_View
     */
    public function get_header_view()
    {
        return $this->header_view;
    }

    /**
     * Get content view instance
     *
     * @since 1.0.0
     * @return Content_View
     */
    public function get_content_view()
    {
        return $this->content_view;
    }

    /**
     * Add a component to the page structure
     *
     * @since 1.0.0
     * @param string $component Component name
     * @param string $position Where to add it ('before', 'after', 'replace')
     * @param string $reference_component Reference component for positioning
     */
    public function add_component($component, $position = 'after', $reference_component = 'content')
    {
        add_filter($this->admin_kit->get_func_slug() . '_page_components', function ($components) use ($component, $position, $reference_component) {
            $key = array_search($reference_component, $components);

            if ($key !== false) {
                switch ($position) {
                    case 'before':
                        array_splice($components, $key, 0, array($component));
                        break;
                    case 'after':
                        array_splice($components, $key + 1, 0, array($component));
                        break;
                    case 'replace':
                        $components[$key] = $component;
                        break;
                }
            } else {
                $components[] = $component;
            }

            return $components;
        });
    }

    /**
     * Remove a component from the page structure
     *
     * @since 1.0.0
     * @param string $component Component name to remove
     */
    public function remove_component($component)
    {
        add_filter($this->admin_kit->get_func_slug() . '_page_components', function ($components) use ($component) {
            return array_filter($components, function ($comp) use ($component) {
                return $comp !== $component;
            });
        });
    }
}