<?php

/**
 * OrbiTools AdminKit Loader
 *
 * Simple autoloader for the OrbiTools AdminKit.
 * Include this file to make the framework available in your plugin.
 *
 * @package    Orbitools\AdminKit
 * @version    1.0.0
 * @since      1.0.0
 */

// Prevent direct access
if (! defined('ABSPATH')) {
    exit;
}

// Define framework constants
if (! defined('ORBITOOLS_ADMINKIT_VERSION')) {
    define('ORBITOOLS_ADMINKIT_VERSION', '1.0.0');
}

if (! defined('ORBITOOLS_ADMINKIT_PATH')) {
    define('ORBITOOLS_ADMINKIT_PATH', plugin_dir_path(__FILE__));
}

if (! defined('ORBITOOLS_ADMINKIT_URL')) {
    define('ORBITOOLS_ADMINKIT_URL', plugin_dir_url(__FILE__));
}

/**
 * AdminKit Loader Class
 *
 * Singleton pattern to ensure framework is loaded only once.
 *
 * @since 1.0.0
 */
class AdminKit_Loader
{
    private static $instance = null;
    private static $loaded = false;

    /**
     * Get singleton instance
     *
     * @return AdminKit_Loader
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct()
    {
        // Empty constructor
    }

    /**
     * Load the framework classes
     *
     * @since 1.0.0
     * @return bool True if loaded successfully, false otherwise
     */
    public function load()
    {
        if (self::$loaded) {
            return true;
        }

        try {
            // Register autoloader
            spl_autoload_register([$this, 'autoload']);

            // Load core dependencies in order
            $this->loadCoreDependencies();

            // Initialize field registry
            if (class_exists('Orbitools\AdminKit\\Field_Registry')) {
                Orbitools\AdminKit\Field_Registry::init();
            }

            self::$loaded = true;
            return true;
        } catch (Exception $e) {
            error_log('AdminKit Load Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * PSR-4 Autoloader for AdminKit classes
     *
     * @param string $class The class name
     */
    public function autoload($class)
    {
        $prefix = 'Orbitools\\AdminKit\\';
        $baseDir = ORBITOOLS_ADMINKIT_PATH;

        // Check if class uses the namespace prefix
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }

        // Get the relative class name
        $relativeClass = substr($class, $len);

        // Map class to file path
        $file = $this->mapClassToFile($relativeClass, $baseDir);

        // If file exists, require it
        if ($file && file_exists($file)) {
            require_once $file;
        }
    }

    /**
     * Map class name to file path
     *
     * @param string $class
     * @param string $baseDir
     * @return string|null
     */
    private function mapClassToFile($class, $baseDir)
    {
        $classMap = [
            'Field_Base' => 'fields/class-field-base.php',
            'Field_Registry' => 'classes/class-field-registry.php',
            'Views\\Header_View' => 'views/class-header-view.php',
            'Views\\Breadcrumbs_View' => 'views/class-breadcrumbs-view.php',
            'Views\\Content_View' => 'views/class-content-view.php',
            'Views\\Footer_View' => 'views/class-footer-view.php',
            'Classes\\Page_Builder' => 'classes/class-page-builder.php',
            'Admin_Kit' => 'classes/class-admin-framework.php',
        ];

        return isset($classMap[$class]) ? $baseDir . $classMap[$class] : null;
    }

    /**
     * Load core dependencies that need to be loaded first
     */
    private function loadCoreDependencies()
    {
        $coreFiles = [
            'fields/class-field-base.php',
            'classes/class-field-registry.php',
        ];

        foreach ($coreFiles as $file) {
            $filePath = ORBITOOLS_ADMINKIT_PATH . $file;
            if (file_exists($filePath)) {
                require_once $filePath;
            } else {
                throw new Exception("Core file not found: {$filePath}");
            }
        }
    }

    /**
     * Check if framework is loaded
     *
     * @return bool
     */
    public static function isLoaded()
    {
        return self::$loaded;
    }
}

/**
 * Load the main framework class
 *
 * @since 1.0.0
 * @return bool True if loaded successfully
 */
function AdminKit_load()
{
    return AdminKit_Loader::getInstance()->load();
}

/**
 * Global registry of AdminKit instances
 *
 * @global array $adminkit_instances
 */
global $adminkit_instances;
$adminkit_instances = array();

/**
 * Get or create an admin framework instance
 *
 * Convenience function for creating framework instances.
 * Instances are stored globally for access by external plugins.
 *
 * @since 1.0.0
 * @param string $slug Unique slug for the admin page.
 * @return Orbitools\AdminKit\Admin_Kit|null Framework instance or null on failure.
 */
function AdminKit($slug)
{
    global $adminkit_instances;

    if (!AdminKit_load()) {
        return null;
    }

    if (empty($slug) || !is_string($slug)) {
        error_log('AdminKit Error: Invalid slug provided');
        return null;
    }

    // Create new instance and store it globally
    $instance = new Orbitools\AdminKit\Admin_Kit($slug);
    $adminkit_instances[$slug] = $instance;

    return $instance;
}

/**
 * Get an existing AdminKit instance by slug
 *
 * @since 1.0.0
 * @param string $slug The AdminKit instance slug.
 * @return Orbitools\AdminKit\Admin_Kit|null The instance or null if not found.
 */
function AdminKit_get($slug)
{
    global $adminkit_instances;
    return isset($adminkit_instances[$slug]) ? $adminkit_instances[$slug] : null;
}

/**
 * Check if OrbiTools AdminKit is available
 *
 * External plugins can use this to check if they can integrate.
 *
 * @since 1.0.0
 * @param string $slug Optional. Check if a specific AdminKit instance exists.
 * @return bool True if available.
 */
function orbitools_adminkit_available($slug = '')
{
    if (!AdminKit_Loader::isLoaded() && !AdminKit_load()) {
        return false;
    }

    if ($slug) {
        return AdminKit_get($slug) !== null;
    }

    return true;
}

/**
 * Register an external page with an AdminKit instance
 *
 * Allows external plugins to add pages to AdminKit's navigation and submenu.
 * This should be called on the appropriate `{func_slug}_register_pages` action
 * or on `plugins_loaded` (the page will be queued for registration).
 *
 * Example usage:
 * ```php
 * // Method 1: Using the action hook (recommended)
 * add_action('orbitools_register_pages', function($admin_kit, $slug) {
 *     $admin_kit->register_external_page('my-plugin', array(
 *         'title' => 'My Plugin',
 *         'menu_title' => 'My Plugin',
 *         'icon' => array('type' => 'dashicon', 'value' => 'admin-plugins'),
 *         'callback' => 'my_plugin_render_page',
 *     ));
 * }, 10, 2);
 *
 * // Method 2: Using the helper function
 * add_action('plugins_loaded', function() {
 *     if (function_exists('orbitools_register_page')) {
 *         orbitools_register_page('orbitools', 'my-plugin', array(
 *             'title' => 'My Plugin',
 *             'menu_title' => 'My Plugin',
 *             'icon' => array('type' => 'dashicon', 'value' => 'admin-plugins'),
 *             'callback' => 'my_plugin_render_page',
 *         ));
 *     }
 * });
 * ```
 *
 * @since 1.0.0
 * @param string $adminkit_slug The AdminKit instance slug to add page to.
 * @param string $page_key      Unique key for the page.
 * @param array  $page_config   Page configuration array:
 *                              - title: (required) Page title
 *                              - menu_title: (optional) Menu title, defaults to title
 *                              - icon: (optional) Icon array with 'type' and 'value'
 *                              - callback: (required) Callable for rendering the page
 *                              - capability: (optional) Required capability, defaults to parent
 * @return bool True if registered successfully.
 */
function orbitools_register_page($adminkit_slug, $page_key, $page_config)
{
    global $adminkit_pending_pages;

    // Try to get the AdminKit instance
    $admin_kit = AdminKit_get($adminkit_slug);

    if ($admin_kit) {
        // Instance exists, register directly
        return $admin_kit->register_external_page($page_key, $page_config);
    }

    // Instance doesn't exist yet, queue for later registration
    if (!isset($adminkit_pending_pages)) {
        $adminkit_pending_pages = array();
    }

    if (!isset($adminkit_pending_pages[$adminkit_slug])) {
        $adminkit_pending_pages[$adminkit_slug] = array();

        // Set up the hook to register when the instance is ready
        $func_slug = str_replace('-', '_', $adminkit_slug);
        add_action($func_slug . '_register_pages', function($admin_kit, $slug) use ($adminkit_slug) {
            global $adminkit_pending_pages;

            if (isset($adminkit_pending_pages[$adminkit_slug])) {
                foreach ($adminkit_pending_pages[$adminkit_slug] as $page_key => $config) {
                    $admin_kit->register_external_page($page_key, $config);
                }
                // Clear the queue
                unset($adminkit_pending_pages[$adminkit_slug]);
            }
        }, 10, 2);
    }

    // Add to pending queue
    $adminkit_pending_pages[$adminkit_slug][$page_key] = $page_config;

    return true;
}


