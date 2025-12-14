<?php

/**
 * OrbiTools AdminKit (Refactored)
 *
 * A lightweight, standalone admin page framework for WordPress plugins.
 * Provides a clean API for building admin pages with tabs, sections, and fields
 * using WordPress hooks and filters.
 *
 * @package    Orbitools\AdminKit
 * @version    1.0.0
 * @author     OrbiTools
 * @since      1.0.0
 */

namespace Orbitools\AdminKit;


// Prevent direct access
if (! defined('ABSPATH')) {
    exit;
}


/**
 * OrbiTools AdminKit Class
 *
 * Core framework class that handles admin page creation, rendering,
 * and settings management through a hook-based system.
 *
 * @since 1.0.0
 */
class Admin_Kit
{

    /**
     * Framework version
     *
     * @since 1.0.0
     * @var string
     */
    const VERSION = '1.0.0';

    /**
     * Framework slug identifier
     *
     * @since 1.0.0
     * @var string
     */
    private $slug;

    /**
     * Function-safe slug for hooks
     *
     * @since 1.0.0
     * @var string
     */
    private $func_slug;

    /**
     * Page title
     *
     * @since 1.0.0
     * @var string
     */
    private $page_title = '';

    /**
     * Page description
     *
     * @since 1.0.0
     * @var string
     */
    private $page_description = '';

    /**
     * Page header image URL
     *
     * @since 1.0.0
     * @var string
     */
    private $page_header_image = '';

    /**
     * Page header background color
     *
     * @since 1.0.0
     * @var string
     */
    private $page_header_bg_color = '';

    /**
     * Hide title and description visually
     *
     * @since 1.0.0
     * @var bool
     */
    private $hide_title_description = false;

    /**
     * Menu configuration
     *
     * @since 1.0.0
     * @var array
     */
    private $menu_config = array();

    /**
     * Pages configuration for multi-page mode
     *
     * @since 1.0.0
     * @var array
     */
    private $pages_config = array();

    /**
     * Field ID validation flag
     *
     * @since 1.0.0
     * @var bool
     */
    private $field_ids_validated = false;

    /**
     * Page builder instance
     *
     * @since 1.0.0
     * @var \Orbitools\AdminKit\Classes\Page_Builder
     */
    private $page_builder;


    /**
     * Initialize the framework
     *
     * @since 1.0.0
     * @param string $slug Unique slug for this admin page.
     */
    public function __construct($slug)
    {
        $this->slug      = $slug;
        $this->func_slug = str_replace('-', '_', $slug);

        // Set all default values
        $this->page_title = 'AdminKit';
        $this->page_description = 'Extensible modular admin framework by Orbital';
        $this->page_header_image = $this->get_framework_url() . 'assets/orbi-logo.svg';
        $this->page_header_bg_color = '#32A3E2';
        $this->hide_title_description = false;

        // Set default menu configuration
        $this->menu_config = array(
            'menu_type'  => 'submenu',
            'parent'     => 'options-general.php',
            'page_title' => 'Settings',
            'menu_title' => 'Settings',
            'capability' => 'manage_options',
            'icon_url'   => 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="322" height="322" fill="none"><path fill="#fff" fill-rule="evenodd" d="M71.096 27.45A160.999 160.999 0 0 1 160.369.013 159.624 159.624 0 0 1 275.03 46.53a159.612 159.612 0 0 1 46.964 114.477 160.99 160.99 0 0 1-99.242 148.678A160.999 160.999 0 0 1 3.171 192.798 160.99 160.99 0 0 1 71.096 27.45Zm45.655 198.564a78.138 78.138 0 0 0 43.409 13.167 78.22 78.22 0 0 0 78.134-78.132 78.133 78.133 0 1 0-121.543 64.965Zm149.52-151.706c0 12.54-10.166 22.705-22.706 22.705-12.539 0-22.705-10.166-22.705-22.705 0-12.54 10.166-22.705 22.705-22.705 12.54 0 22.706 10.165 22.706 22.705Z" clip-rule="evenodd"/></svg>'),
            'position'   => null,
        );
    }

    /**
     * Initialize framework hooks
     *
     * @since 1.0.0
     */
    private function init_hooks()
    {
        // Fire action before admin_menu so external plugins can register pages
        add_action('admin_menu', array($this, 'fire_register_pages_action'), 5);
        add_action('admin_menu', array($this, 'add_admin_page'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_ajax_orbitools_adminkit_save_settings_' . $this->slug, array($this, 'ajax_save_settings'));

        // Always add global header after admin bar but before #wpbody
        add_action('in_admin_header', array($this, 'render_header'));

        // Add breadcrumbs after header
        add_action('in_admin_header', array($this, 'render_breadcrumbs'), 20);

        // Modify admin footer on our pages
        add_filter('admin_footer_text', array($this, 'admin_footer_text'));

        // Add AdminKit body class
        add_filter('admin_body_class', array($this, 'add_admin_body_class'));
    }

    /**
     * Fire action for external plugins to register pages
     *
     * This action fires just before admin_menu so external plugins
     * can register their pages with the AdminKit navigation.
     *
     * @since 1.0.0
     */
    public function fire_register_pages_action()
    {
        /**
         * Action fired before AdminKit adds its admin menus.
         *
         * Use this hook to register external pages with AdminKit.
         *
         * @param Admin_Kit $admin_kit The AdminKit instance.
         * @param string    $slug      The AdminKit instance slug.
         */
        do_action($this->func_slug . '_register_pages', $this, $this->slug);
    }

    /**
     * Initialize AdminKit with configuration array
     *
     * @since 1.0.0
     * @param array $config Configuration array.
     * @return Admin_Kit Returns self for method chaining.
     */
    public function init($config = array())
    {
        if (isset($config['title'])) {
            $this->set_page_title($config['title']);
        }
        if (isset($config['description'])) {
            $this->set_page_description($config['description']);
        }
        if (isset($config['header_image'])) {
            $this->set_page_header_image($config['header_image']);
        }
        if (isset($config['header_bg_color'])) {
            $this->set_page_header_bg_color($config['header_bg_color']);
        }
        if (isset($config['hide_title_description'])) {
            $this->set_hide_title_description($config['hide_title_description']);
        }
        if (isset($config['menu'])) {
            $this->set_menu_config($config['menu']);
        }
        if (isset($config['pages'])) {
            $this->set_pages_config($config['pages']);
        }

        $this->init_hooks();
        return $this;
    }

    /**
     * Set page title
     *
     * @since 1.0.0
     * @param string $title Page title.
     */
    public function set_page_title($title)
    {
        $this->page_title = $title;
    }

    /**
     * Set page description
     *
     * @since 1.0.0
     * @param string $description Page description.
     */
    public function set_page_description($description)
    {
        $this->page_description = $description;
    }

    /**
     * Set page header image
     *
     * @since 1.0.0
     * @param string $image_url Header image URL.
     */
    public function set_page_header_image($image_url)
    {
        $this->page_header_image = $image_url;
    }

    /**
     * Set page header background color
     *
     * @since 1.0.0
     * @param string $color Header background color (any valid CSS color value).
     */
    public function set_page_header_bg_color($color)
    {
        $this->page_header_bg_color = $color;
    }

    /**
     * Set whether to hide title and description visually
     *
     * @since 1.0.0
     * @param bool $hide Whether to hide title and description visually.
     */
    public function set_hide_title_description($hide)
    {
        $this->hide_title_description = (bool) $hide;
    }

    /**
     * Set menu configuration
     *
     * @since 1.0.0
     * @param array $config Menu configuration array.
     */
    public function set_menu_config($config)
    {
        // Validate menu_type if provided
        if (isset($config['menu_type']) && !in_array($config['menu_type'], array('menu', 'submenu'))) {
            $config['menu_type'] = 'submenu'; // Default to submenu if invalid
        }

        $this->menu_config = array_merge($this->menu_config, $config);
    }

    /**
     * Set pages configuration for multi-page mode
     *
     * Pages config format:
     * array(
     *     'page_key' => array(
     *         'title' => 'Page Title',
     *         'icon' => array('type' => 'svg', 'value' => '...'), // Optional
     *         'menu_title' => 'Menu Title', // Optional, defaults to title
     *     ),
     *     ...
     * )
     *
     * @since 1.0.0
     * @param array $config Pages configuration array.
     */
    public function set_pages_config($config)
    {
        $this->pages_config = $config;
    }

    /**
     * Register an external page from another plugin
     *
     * Allows other plugins to add pages to this AdminKit instance's
     * navigation and submenu. The page will appear in the header nav
     * and WordPress admin submenu.
     *
     * Example usage from another plugin:
     * ```php
     * add_action('plugins_loaded', function() {
     *     if (function_exists('orbitools_register_page')) {
     *         orbitools_register_page('my-plugin', array(
     *             'title' => 'My Plugin',
     *             'menu_title' => 'My Plugin',
     *             'icon' => array('type' => 'dashicon', 'value' => 'admin-plugins'),
     *             'callback' => 'my_plugin_render_page',
     *             'capability' => 'manage_options', // Optional, defaults to parent capability
     *             'skip_menu' => true, // Optional, if true AdminKit won't register a WP menu
     *             'url' => 'admin.php?page=my-plugin', // Required if skip_menu is true
     *         ));
     *     }
     * });
     * ```
     *
     * @since 1.0.0
     * @param string $page_key   Unique key for the page (will be prefixed with slug).
     * @param array  $page_config Page configuration array:
     *                            - title: Page title (required)
     *                            - menu_title: Menu title (optional, defaults to title)
     *                            - icon: Icon array with 'type' and 'value' (optional)
     *                            - callback: Render callback (required unless skip_menu)
     *                            - capability: Required capability (optional)
     *                            - skip_menu: If true, only adds to header nav, not WP menu (optional)
     *                            - url: Custom URL for navigation (required if skip_menu is true)
     * @return bool True if registered successfully, false if page key already exists.
     */
    public function register_external_page($page_key, $page_config)
    {
        // Don't allow overwriting existing pages
        if (isset($this->pages_config[$page_key])) {
            return false;
        }

        // Mark as external page so we know it has a custom callback
        $page_config['_external'] = true;

        // Add to pages config
        $this->pages_config[$page_key] = $page_config;

        return true;
    }

    /**
     * Get pages configuration
     *
     * @since 1.0.0
     * @return array
     */
    public function get_pages_config()
    {
        return $this->pages_config;
    }

    /**
     * Check if multi-page mode is enabled
     *
     * @since 1.0.0
     * @return bool
     */
    public function is_multi_page_mode()
    {
        return !empty($this->pages_config);
    }

    /**
     * Check if the current page is an external page
     *
     * External pages are registered by other plugins and have their own
     * settings forms, so AdminKit should not show its save button.
     *
     * @since 1.0.0
     * @return bool True if current page is external, false otherwise.
     */
    public function is_current_page_external()
    {
        $page_key = $this->get_current_page_key();

        if (empty($page_key) || !isset($this->pages_config[$page_key])) {
            return false;
        }

        $page_config = $this->pages_config[$page_key];

        return is_array($page_config) && !empty($page_config['_external']);
    }

    /**
     * Get current page key from URL
     *
     * @since 1.0.0
     * @return string
     */
    public function get_current_page_key()
    {
        if (!$this->is_multi_page_mode()) {
            return '';
        }

        $current_page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';

        // Check if we're on the main page
        if ($current_page === $this->slug) {
            // Return first page key
            $pages = array_keys($this->pages_config);
            return !empty($pages) ? $pages[0] : '';
        }

        // Check if we're on a subpage
        if (strpos($current_page, $this->slug . '-') === 0) {
            $page_key = str_replace($this->slug . '-', '', $current_page);
            if (isset($this->pages_config[$page_key])) {
                return $page_key;
            }
        }

        // Check external pages with custom URLs (skip_menu pages)
        foreach ($this->pages_config as $page_key => $page_config) {
            if (is_array($page_config) && !empty($page_config['skip_menu']) && !empty($page_config['url'])) {
                // Extract page slug from URL (e.g., "admin.php?page=ott-settings" -> "ott-settings")
                $url_parts = parse_url($page_config['url']);
                if (isset($url_parts['query'])) {
                    parse_str($url_parts['query'], $query_params);
                    if (isset($query_params['page']) && $query_params['page'] === $current_page) {
                        return $page_key;
                    }
                }
            }
        }

        return '';
    }

    /**
     * Get URL for a specific page
     *
     * @since 1.0.0
     * @param string $page_key Page key.
     * @return string
     */
    public function get_page_url($page_key)
    {
        if (!$this->is_multi_page_mode()) {
            return '';
        }

        // Check if this page has a custom URL (for external pages with skip_menu)
        if (isset($this->pages_config[$page_key]) && is_array($this->pages_config[$page_key])) {
            $page_config = $this->pages_config[$page_key];
            if (!empty($page_config['url'])) {
                return admin_url($page_config['url']);
            }
        }

        $pages = array_keys($this->pages_config);
        $first_page_key = !empty($pages) ? $pages[0] : '';

        // First page uses the main slug
        if ($page_key === $first_page_key) {
            $page_slug = $this->slug;
        } else {
            $page_slug = $this->slug . '-' . $page_key;
        }

        return admin_url('admin.php?page=' . $page_slug);
    }

    /**
     * Check if current admin screen is one of our pages
     *
     * @since 1.0.0
     * @return bool
     */
    public function is_our_admin_page()
    {
        $screen = get_current_screen();
        if (!$screen) {
            return false;
        }

        // Check main page and subpages
        if (strpos($screen->id, $this->slug) !== false) {
            return true;
        }

        // Check external pages with custom URLs (skip_menu pages)
        $current_page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
        foreach ($this->pages_config as $page_key => $page_config) {
            if (is_array($page_config) && !empty($page_config['skip_menu']) && !empty($page_config['url'])) {
                // Extract page slug from URL (e.g., "admin.php?page=ott-settings" -> "ott-settings")
                $url_parts = parse_url($page_config['url']);
                if (isset($url_parts['query'])) {
                    parse_str($url_parts['query'], $query_params);
                    if (isset($query_params['page']) && $query_params['page'] === $current_page) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Add WordPress admin notice
     *
     * @since 1.0.0
     * @param string $message Notice message.
     * @param string $type Notice type (success, error, warning, info).
     * @param bool   $dismissible Whether notice is dismissible.
     */
    public function add_notice($message, $type = 'info', $dismissible = true)
    {
        add_action('admin_notices', function () use ($message, $type, $dismissible) {
            $class = 'notice';

            // Convert type to WordPress notice class
            switch ($type) {
                case 'success':
                    $class .= ' notice-success';
                    break;
                case 'error':
                    $class .= ' notice-error';
                    break;
                case 'warning':
                    $class .= ' notice-warning';
                    break;
                case 'info':
                default:
                    $class .= ' notice-info';
                    break;
            }

            if ($dismissible) {
                $class .= ' is-dismissible';
            }

            printf('<div class="%s"><p>%s</p></div>', esc_attr($class), esc_html($message));
        });
    }

    /**
     * Render admin page using page builder
     *
     * @since 1.0.0
     */
    public function render_admin_page()
    {
        $this->get_page_builder()->build_page();
    }

    /**
     * Render header using page builder
     *
     * @since 1.0.0
     */
    public function render_header()
    {
        $this->get_page_builder()->build_header();
    }

    /**
     * Render breadcrumbs using page builder
     *
     * @since 1.0.0
     */
    public function render_breadcrumbs()
    {
        // Only render breadcrumbs on our admin pages
        if (!$this->is_our_admin_page()) {
            return;
        }

        $this->get_page_builder()->build_breadcrumbs();
    }

    /**
     * Modify admin footer text on our pages
     *
     * @since 1.0.0
     * @param string $text The current admin footer text
     * @return string
     */
    public function admin_footer_text($text)
    {
        // Only modify footer on our admin pages
        if (!$this->is_our_admin_page()) {
            return $text;
        }

        return sprintf(
            /* translators: %1$s: Link to OrbiTools website */
            ' Modular and extensible admin page built with <a href="%1$s" target="_blank">AdminKit</a> lovingly created by <a href="%2$s" target="_blank">Orbital Design</a>.',
            esc_url('https://github.com/orbital-design/orbitools'),
            esc_url('https://orbital.co.uk/'),
        );
    }

    /**
     * Get page builder instance (lazy loading)
     *
     * @since 1.0.0
     * @return \Orbitools\AdminKit\Classes\Page_Builder
     */
    private function get_page_builder()
    {
        if (!$this->page_builder) {
            $this->page_builder = new \Orbitools\AdminKit\Classes\Page_Builder($this);
        }
        return $this->page_builder;
    }


    // Public getter methods for view components to access private properties

    /**
     * Get framework slug
     *
     * @since 1.0.0
     * @return string
     */
    public function get_slug()
    {
        return $this->slug;
    }

    /**
     * Get framework function slug
     *
     * @since 1.0.0
     * @return string
     */
    public function get_func_slug()
    {
        return $this->func_slug;
    }

    /**
     * Get page title
     *
     * @since 1.0.0
     * @return string
     */
    public function get_page_title()
    {
        return $this->page_title;
    }

    /**
     * Get page description
     *
     * @since 1.0.0
     * @return string
     */
    public function get_page_description()
    {
        return $this->page_description;
    }

    /**
     * Get page header image
     *
     * @since 1.0.0
     * @return string
     */
    public function get_page_header_image()
    {
        return $this->page_header_image;
    }

    /**
     * Get page header background color
     *
     * @since 1.0.0
     * @return string
     */
    public function get_page_header_bg_color()
    {
        return $this->page_header_bg_color;
    }

    /**
     * Get hide title description setting
     *
     * @since 1.0.0
     * @return bool
     */
    public function get_hide_title_description()
    {
        return $this->hide_title_description;
    }

    /**
     * Get framework version
     *
     * @since 1.0.0
     * @return string
     */
    public function get_version()
    {
        return self::VERSION;
    }

    /**
     * Get framework URL
     *
     * @since 1.0.0
     * @return string Framework URL.
     */
    public function get_framework_url()
    {
        return ORBITOOLS_ADMINKIT_URL;
    }


    // The following methods are preserved from the original implementation
    // to maintain full functionality. They handle WordPress admin integration,
    // settings management, and data processing.

    /**
     * Add admin pages to WordPress admin menu
     *
     * Creates multiple admin pages based on the pages configuration.
     * Each page becomes a separate WordPress admin page with its own URL.
     *
     * @since 1.0.0
     */
    public function add_admin_page()
    {
        $menu_type = isset($this->menu_config['menu_type']) ? $this->menu_config['menu_type'] : 'submenu';
        $this->add_multi_page_menus($menu_type);
    }

    /**
     * Add multiple page menus for multi-page mode
     *
     * @since 1.0.0
     * @param string $menu_type Menu type (menu or submenu).
     */
    private function add_multi_page_menus($menu_type)
    {
        $pages = $this->pages_config;
        $page_keys = array_keys($pages);
        $first_page_key = !empty($page_keys) ? $page_keys[0] : '';

        if ($menu_type === 'menu') {
            // Add top-level menu page (uses first page)
            add_menu_page(
                $this->page_title,
                $this->menu_config['menu_title'],
                $this->menu_config['capability'],
                $this->slug,
                array($this, 'render_admin_page'),
                $this->menu_config['icon_url'],
                $this->menu_config['position']
            );

            // Add submenu pages for each page in config
            foreach ($pages as $page_key => $page_config) {
                // Skip menu registration for external pages with skip_menu option
                // These pages will still appear in header nav but register their own WP menu
                $skip_menu = is_array($page_config) && !empty($page_config['skip_menu']);
                if ($skip_menu) {
                    continue;
                }

                $page_title = is_array($page_config) ? ($page_config['title'] ?? ucfirst($page_key)) : $page_config;
                $menu_title = is_array($page_config) ? ($page_config['menu_title'] ?? $page_title) : $page_title;
                $capability = is_array($page_config) ? ($page_config['capability'] ?? $this->menu_config['capability']) : $this->menu_config['capability'];

                // External pages use their custom callback, internal pages use render_admin_page
                $is_external = is_array($page_config) && !empty($page_config['_external']);
                $callback = $is_external && isset($page_config['callback']) ? $page_config['callback'] : array($this, 'render_admin_page');

                // First page uses the main slug (replaces the auto-created submenu)
                if ($page_key === $first_page_key) {
                    add_submenu_page(
                        $this->slug,
                        $page_title,
                        $menu_title,
                        $capability,
                        $this->slug,
                        $callback
                    );
                } else {
                    // Other pages use slug-pagekey format
                    add_submenu_page(
                        $this->slug,
                        $page_title,
                        $menu_title,
                        $capability,
                        $this->slug . '-' . $page_key,
                        $callback
                    );
                }
            }
        } else {
            // Submenu mode - add all pages under the parent
            $parent = isset($this->menu_config['parent']) ? $this->menu_config['parent'] : 'options-general.php';

            foreach ($pages as $page_key => $page_config) {
                // Skip menu registration for external pages with skip_menu option
                $skip_menu = is_array($page_config) && !empty($page_config['skip_menu']);
                if ($skip_menu) {
                    continue;
                }

                $page_title = is_array($page_config) ? ($page_config['title'] ?? ucfirst($page_key)) : $page_config;
                $menu_title = is_array($page_config) ? ($page_config['menu_title'] ?? $page_title) : $page_title;
                $capability = is_array($page_config) ? ($page_config['capability'] ?? $this->menu_config['capability']) : $this->menu_config['capability'];

                // External pages use their custom callback, internal pages use render_admin_page
                $is_external = is_array($page_config) && !empty($page_config['_external']);
                $callback = $is_external && isset($page_config['callback']) ? $page_config['callback'] : array($this, 'render_admin_page');

                // First page uses the main slug
                if ($page_key === $first_page_key) {
                    add_submenu_page(
                        $parent,
                        $page_title,
                        $menu_title,
                        $capability,
                        $this->slug,
                        $callback
                    );
                } else {
                    add_submenu_page(
                        $parent,
                        $page_title,
                        $menu_title,
                        $capability,
                        $this->slug . '-' . $page_key,
                        $callback
                    );
                }
            }
        }
    }

    /**
     * Enqueue framework assets
     *
     * @since 1.0.0
     * @param string $hook_suffix Current admin page hook suffix.
     */
    public function enqueue_assets($hook_suffix)
    {
        // Only enqueue on our admin page
        if (strpos($hook_suffix, $this->slug) === false && !$this->is_our_admin_page()) {
            return;
        }

        // Enqueue styles
        wp_enqueue_style(
            'orbitools-adminkit',
            $this->get_framework_url() . 'assets/admin-framework.css',
            array(),
            self::VERSION
        );

        // Enqueue single script file (reverting from modular approach)
        wp_enqueue_script(
            'orbitools-adminkit',
            $this->get_framework_url() . 'assets/admin-framework.js',
            array(),
            self::VERSION,
            true
        );

        // Localize script
        wp_localize_script('orbitools-adminkit', 'orbitoolsAdminKit', array(
            'slug' => $this->slug,
            'nonce' => wp_create_nonce('orbitools_adminkit_' . $this->slug),
            'ajaxurl' => admin_url('admin-ajax.php'),
            'strings' => array(
                'saving' => __('Saving...', 'orbitools-adminkit'),
                'saved' => __('Settings saved!', 'orbitools-adminkit'),
                'error' => __('Error saving settings. Please try again.', 'orbitools-adminkit'),
            )
        ));

        // Hook for additional assets
        do_action($this->func_slug . '_enqueue_assets', $hook_suffix);
    }

    /**
     * Register settings with WordPress
     *
     * @since 1.0.0
     */
    public function register_settings()
    {
        register_setting($this->slug . '_settings', $this->slug . '_settings', array(
            'sanitize_callback' => array($this, 'sanitize_settings_data'),
        ));
    }

    /**
     * Sanitize settings data
     *
     * @since 1.0.0
     * @param array $input Raw input data.
     * @return array Sanitized data.
     */
    public function sanitize_settings_data($input)
    {
        $sanitized = array();
        $settings = $this->get_content_fields();

        // Flatten settings array to get all field configurations
        $all_fields = array();
        foreach ($settings as $tab_fields) {
            $all_fields = array_merge($all_fields, $tab_fields);
        }

        // Handle configured fields
        foreach ($all_fields as $field) {
            if (! isset($field['id'])) {
                continue;
            }

            $field_id = $field['id'];
            $field_value = isset($input[$field_id]) ? $input[$field_id] : '';

            // Apply field-specific sanitization
            $sanitized[$field_id] = $this->sanitize_setting($field_value, $field);
        }

        // Handle module fields specially (they don't match the configured field IDs)
        foreach ($input as $key => $value) {
            if (strpos($key, '_enabled') !== false) {
                // This is a module enable/disable field
                $sanitized[$key] = $this->sanitize_setting($value, array('type' => 'checkbox'));
            } elseif (! array_key_exists($key, $sanitized)) {
                // Handle any other fields that weren't in the configuration
                $sanitized[$key] = $this->sanitize_setting($value, array('type' => 'text'));
            }
        }

        return apply_filters($this->func_slug . '_pre_save_settings', $sanitized);
    }

    /**
     * Sanitize individual setting
     *
     * @since 1.0.0
     * @param mixed $value Field value.
     * @param array $field Field configuration.
     * @return mixed Sanitized value.
     */
    private function sanitize_setting($value, $field)
    {
        // Allow custom sanitization per field
        $sanitized = apply_filters($this->func_slug . '_sanitize_setting', $value, $field);

        // If custom sanitization was applied, return it
        if ($sanitized !== $value) {
            return $sanitized;
        }

        // Default sanitization based on field type
        switch ($field['type']) {
            case 'text':
            case 'textarea':
                return sanitize_text_field($value);
            case 'email':
                return sanitize_email($value);
            case 'url':
                return esc_url_raw($value);
            case 'number':
                return intval($value);
            case 'checkbox':
                // Multi-checkbox (has options) should return array
                if (isset($field['options']) && is_array($field['options'])) {
                    return is_array($value) ? array_map('sanitize_text_field', $value) : array();
                }
                // Single checkbox returns 1 or 0
                return $value ? 1 : 0;
            case 'select':
            case 'radio':
                $options = isset($field['options']) ? $field['options'] : array();
                return array_key_exists($value, $options) ? $value : '';
            default:
                return sanitize_text_field($value);
        }
    }

    /**
     * AJAX handler for saving settings
     *
     * @since 1.0.0
     */
    public function ajax_save_settings()
    {
        // Verify nonce
        if (! wp_verify_nonce($_POST['adminkit_nonce'], 'orbitools_adminkit_' . $this->slug)) {
            wp_send_json_error('Invalid nonce');
        }

        // Check capabilities
        if (! current_user_can($this->menu_config['capability'])) {
            wp_send_json_error('Insufficient permissions');
        }

        // Process settings
        $settings_raw = isset($_POST['settings']) ? $_POST['settings'] : '';

        if (is_string($settings_raw)) {
            $settings_data = json_decode(stripslashes($settings_raw), true);
        } else {
            $settings_data = is_array($settings_raw) ? $settings_raw : array();
        }

        if (! is_array($settings_data)) {
            $settings_data = array();
        }

        // Save settings
        $result = $this->save_settings($settings_data);

        if ($result) {
            wp_send_json_success(array(
                'message' => 'Settings saved successfully'
            ));
        } else {
            wp_send_json_error('Failed to save settings');
        }
    }

    /**
     * Save settings to database
     *
     * @since 1.0.0
     * @param array $settings_data Settings data to save.
     * @return bool Success status.
     */
    private function save_settings($settings_data)
    {
        // Sanitize data
        $sanitized_data = $this->sanitize_settings_data($settings_data);

        // Get current settings and merge with new data
        // This is critical for multi-page mode where each page only submits its own fields
        $current_settings = get_option($this->slug . '_settings', array());

        // Handle module enabled checkboxes specially:
        // Only do this when saving from the dashboard page (where module toggles exist).
        // Check if we're on dashboard by looking for module_management field in submitted data,
        // or if any _enabled key was actually submitted (meaning we're on the right page).
        $is_dashboard_save = isset($settings_data['module_management']) ||
                             $this->has_enabled_key_in_data($settings_data);

        if ($is_dashboard_save) {
            // If a _enabled key exists in current settings but not in submitted data,
            // it means the checkbox was unchecked (browsers don't send unchecked checkboxes).
            foreach ($current_settings as $key => $value) {
                if (strpos($key, '_enabled') !== false && !array_key_exists($key, $sanitized_data)) {
                    $sanitized_data[$key] = 0;
                }
            }
        }

        $merged_settings = array_merge($current_settings, $sanitized_data);

        // Save to database
        $result = update_option($this->slug . '_settings', $merged_settings);

        // update_option returns false if the value is the same (no change)
        // In this case, we still consider it a "success" since the data is correct
        if ($result === false) {
            // Check if the data is actually the same (no change) vs a real error
            $updated_settings = get_option($this->slug . '_settings', array());
            if ($updated_settings === $merged_settings) {
                // Data is correct, just no change - treat as success
                $result = true;
            }
        }

        // Trigger post-save action
        do_action($this->func_slug . '_post_save_settings', $merged_settings, $result);

        return $result;
    }

    /**
     * Check if submitted data contains any _enabled keys
     *
     * Used to detect if we're saving from the dashboard page where module toggles exist.
     *
     * @since 1.0.0
     * @param array $data Submitted data.
     * @return bool True if any _enabled key exists in data.
     */
    private function has_enabled_key_in_data($data)
    {
        foreach ($data as $key => $value) {
            if (strpos($key, '_enabled') !== false) {
                return true;
            }
        }
        return false;
    }

    // ============================================================================
    // CORE CONTENT FILTERS - Required for page initialization
    // ============================================================================

    /**
     * Get content structure configuration
     *
     * This filter is essential for page initialization. It defines:
     * - Available tabs and their titles
     * - Sections within each tab
     * - Display modes (cards/tabs) for each section
     * - Overall page navigation structure
     *
     * @since 1.0.0
     * @return array Complete content structure configuration
     */
    public function get_content_structure()
    {
        return apply_filters($this->func_slug . '_adminkit_structure', array());
    }

    /**
     * Get content fields configuration
     *
     * This filter is essential for content rendering. It defines:
     * - All form fields and their properties
     * - Field types, default values, and validation rules
     * - Which section each field belongs to
     * - Field descriptions and labels
     *
     * @since 1.0.0
     * @return array Complete content fields configuration
     */
    public function get_content_fields()
    {
        return apply_filters($this->func_slug . '_adminkit_fields', array());
    }

    // ============================================================================
    // DATA ACCESS METHODS - Used by view components
    // ============================================================================

    /**
     * Get pages for navigation
     *
     * Returns the pages configuration for header navigation.
     * Each page becomes a separate WordPress admin page.
     *
     * @since 1.0.0
     * @return array Pages array with title and optional icon.
     */
    public function get_tabs()
    {
        $tabs = array();
        foreach ($this->pages_config as $page_key => $page_config) {
            $tabs[$page_key] = is_array($page_config) ? $page_config : array('title' => $page_config);
        }
        return $tabs;
    }

    /**
     * Get sections for a tab/page
     *
     * @since 1.0.0
     * @param string $tab_key Tab/page key.
     * @return array Sections array.
     */
    public function get_sections($tab_key)
    {
        $structure = $this->get_content_structure();
        return isset($structure[$tab_key]['sections']) ? $structure[$tab_key]['sections'] : array();
    }

    /**
     * Get section display mode
     *
     * @since 1.0.0
     * @param string $tab_key Tab/page key.
     * @return string Display mode (tabs or cards).
     */
    public function get_section_display_mode($tab_key)
    {
        $structure = $this->get_content_structure();
        return isset($structure[$tab_key]['display_mode']) ? $structure[$tab_key]['display_mode'] : 'cards';
    }


    /**
     * Get current page key
     *
     * Returns the key of the currently displayed admin page.
     *
     * @since 1.0.0
     * @return string Current page key.
     */
    public function get_active_tab()
    {
        return $this->get_current_page_key();
    }

    /**
     * Get current page key (alias for get_active_tab)
     *
     * @since 1.0.0
     * @return string Current page key.
     */
    public function get_current_tab()
    {
        return $this->get_current_page_key();
    }

    /**
     * Get current section
     *
     * @since 1.0.0
     * @return string Current section key.
     */
    public function get_current_section()
    {
        $current_tab = $this->get_current_tab();
        return $this->get_active_section($current_tab);
    }

    /**
     * Get active section for a tab
     *
     * @since 1.0.0
     * @param string $tab_key Tab key.
     * @return string Active section key.
     */
    public function get_active_section($tab_key)
    {
        $sections = $this->get_sections($tab_key);
        $current_section = isset($_GET['section']) ? sanitize_text_field($_GET['section']) : '';

        // If no section specified or invalid section, use first section
        if (empty($current_section) || ! array_key_exists($current_section, $sections)) {
            $current_section = ! empty($sections) ? key($sections) : '';
        }

        return $current_section;
    }

    /**
     * Get page URL
     *
     * Returns the WordPress admin URL for a specific page.
     *
     * @since 1.0.0
     * @param string $tab_key Page key.
     * @return string Page URL.
     */
    public function get_tab_url($tab_key)
    {
        return $this->get_page_url($tab_key);
    }

    /**
     * Get field value
     *
     * @since 1.0.0
     * @param string $field_id Field ID.
     * @param mixed  $default Default value.
     * @return mixed Field value.
     */
    public function get_field_value($field_id, $default = '')
    {
        $settings = get_option($this->slug . '_settings', array());
        return isset($settings[$field_id]) ? $settings[$field_id] : $default;
    }

    /**
     * Render individual field (preserved for backward compatibility)
     *
     * @since 1.0.0
     * @param array $field Field configuration.
     */
    public function render_field($field)
    {
        if (! isset($field['type']) || ! isset($field['id'])) {
            return;
        }

        // Get field value
        $default = isset($field['std']) ? $field['std'] : '';
        $value = $this->get_field_value($field['id'], $default);

        // Create field instance using registry
        $field_instance = Field_Registry::create_field($field, $value, $this);

        if (!$field_instance) {
            // Fallback for unregistered field types
            echo '<p class="field__error">Unknown field type: ' . esc_html($field['type']) . '</p>';
            return;
        }


        // Build CSS classes using BEM methodology
        $css_classes = array(
            'field',
            'field--' . esc_attr($field['type'])
        );

        // Add custom classes if specified
        if (isset($field['class']) && ! empty($field['class'])) {
            if (is_array($field['class'])) {
                $css_classes = array_merge($css_classes, $field['class']);
            } else {
                $custom_classes = explode(' ', $field['class']);
                $css_classes = array_merge($css_classes, $custom_classes);
            }
        }

        // Add state modifiers
        if (isset($field['required']) && $field['required']) {
            $css_classes[] = 'field--required';
        }

        if (isset($field['disabled']) && $field['disabled']) {
            $css_classes[] = 'field--disabled';
        }

        // Add conditional class if field has conditions
        if ($field_instance->has_conditions()) {
            $css_classes[] = 'field--conditional';
        }

        // Build data attributes
        $data_attributes = array(
            'data-field-id' => esc_attr($field['id']),
            'data-field-type' => esc_attr($field['type'])
        );

        // Add conditional data attributes
        if ($field_instance->has_conditions()) {
            $conditional_attrs = $field_instance->get_conditional_data_attributes();
            $data_attributes = array_merge($data_attributes, $conditional_attrs);
        }

        // Build data attributes string
        $data_attr_string = '';
        foreach ($data_attributes as $attr => $value) {
            $data_attr_string .= ' ' . esc_attr($attr) . '="' . esc_attr($value) . '"';
        }

?>
<div class="<?php echo esc_attr(implode(' ', array_filter($css_classes))); ?>"<?php echo $data_attr_string; ?>>
    <?php
        // Enqueue field-specific assets
        Field_Registry::enqueue_field_assets($field_instance);
        ?>
    <div class="field__wrapper">
        <?php
                    // For simple fields (text, etc.), render label then input
                    if (! isset($field['options']) || ! is_array($field['options'])) {
                        $field_instance->render_label();
                        echo '<div class="field__input-wrapper">';
                        $field_instance->render();
                        echo '</div>';
                    } else {
                        // For grouped fields (checkboxes, radios), the field handles its own structure
                        $field_instance->render();
                    }

                    // Always render description at the end
                    $field_instance->render_description();
                    ?>
    </div>
</div>
<?php
    }

    /**
     * Add AdminKit body class to admin pages
     *
     * @since 1.0.0
     * @param string $classes Existing body classes.
     * @return string Modified body classes.
     */
    public function add_admin_body_class($classes)
    {
        if ($this->is_our_admin_page()) {
            $classes .= ' is-adminKit';
        }
        return $classes;
    }
}