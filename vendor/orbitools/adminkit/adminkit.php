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
 * Get or create an admin framework instance
 *
 * Convenience function for creating framework instances.
 *
 * @since 1.0.0
 * @param string $slug Unique slug for the admin page.
 * @return Orbitools\AdminKit\Admin_Kit|null Framework instance or null on failure.
 */
function AdminKit($slug)
{
    if (!AdminKit_load()) {
        return null;
    }
    
    if (empty($slug) || !is_string($slug)) {
        error_log('AdminKit Error: Invalid slug provided');
        return null;
    }
    
    // Create new instance
    return new Orbitools\AdminKit\Admin_Kit($slug);
}


