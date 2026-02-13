<?php

/**
 * Plugin Name:     Orbitools
 * Plugin URI:      https://github.com/orbital-design/orbitools
 * Description:     A modern, extensible WordPress plugin.
 * Version:         1.0.1
 * Author:          Adam Cullen, Orbital Design
 * Author URI:      https://orbital.co.uk
 * Text Domain:     orbitools
 * Domain Path:     /languages
 * Requires at least: 5.0
 * Tested up to:    6.4
 * Requires PHP:    7.4
 * Network:         false
 * License:         GPL v2 or later
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Define plugin constants.
 */
define('ORBITOOLS_VERSION', '1.0.1');
define('ORBITOOLS_FILE', __FILE__);
define('ORBITOOLS_BASENAME', plugin_basename(__FILE__));
define('ORBITOOLS_DIR', plugin_dir_path(__FILE__));
define('ORBITOOLS_URL', plugin_dir_url(__FILE__));
define('ORBITOOLS_MIN_PHP', '7.4');
define('ORBITOOLS_MIN_WP', '5.0');

/**
 * Check PHP version compatibility and show admin notice if not met.
 */
if (version_compare(PHP_VERSION, ORBITOOLS_MIN_PHP, '<')) {
    add_action('admin_notices', function () {
        if (current_user_can('activate_plugins')) {
            echo '<div class="notice notice-error"><p>';
            printf(
                /* translators: %s: minimum PHP version required */
                esc_html__('Orbitools requires PHP version %s or higher. Please update PHP to use this plugin.', 'orbitools'),
                ORBITOOLS_MIN_PHP
            );
            echo '</p></div>';
        }
    });
    return;
}

/**
 * Check WordPress version compatibility and show admin notice if not met.
 */
if (version_compare(get_bloginfo('version'), ORBITOOLS_MIN_WP, '<')) {
    add_action('admin_notices', function () {
        if (current_user_can('activate_plugins')) {
            echo '<div class="notice notice-error"><p>';
            printf(
                /* translators: %s: minimum WordPress version required */
                esc_html__('Orbitools requires WordPress version %s or higher. Please update WordPress to use this plugin.', 'orbitools'),
                ORBITOOLS_MIN_WP
            );
            echo '</p></div>';
        }
    });
    return;
}

/**
 * Include the Composer autoloader if it exists.
 */
if (file_exists(ORBITOOLS_DIR . 'vendor/autoload.php')) {
    require_once ORBITOOLS_DIR . 'vendor/autoload.php';
} else {
    add_action('admin_notices', function () {
        if (current_user_can('activate_plugins')) {
            echo '<div class="notice notice-error"><p>';
            esc_html_e('Orbitools: Composer autoloader not found. Please run "composer install".', 'orbitools');
            echo '</p></div>';
        }
    });
    return;
}

/**
 * Class Orbitools
 *
 * The main plugin class for Orbitools.
 */
final class Orbitools
{
    /**
     * Holds the singleton instance.
     *
     * @var Orbitools|null
     */
    private static ?Orbitools $instance = null;

    /**
     * Prevent direct instantiation.
     */
    private function __construct() {}

    /**
     * Prevent cloning.
     */
    private function __clone() {}

    /**
     * Prevent unserialization.
     */
    public function __wakeup(): void
    {
        throw new \Exception('Cannot unserialize singleton');
    }

    /**
     * Returns the singleton instance of the plugin.
     *
     * @return Orbitools
     */
    public static function instance(): Orbitools
    {
        if (self::$instance === null) {
            self::$instance = new self();
            self::$instance->init();
        }
        return self::$instance;
    }

    /**
     * Initializes the plugin.
     *
     * @return void
     */
    public function init(): void
    {
        add_action('init', [$this, 'load_textdomain']);

        if (class_exists('\Orbitools\Core\Loader')) {
            $loader = new \Orbitools\Core\Loader();
            $loader->init();
        }
    }

    /**
     * Loads the plugin textdomain for translations.
     *
     * @return void
     */
    public function load_textdomain(): void
    {
        load_plugin_textdomain('orbitools', false, dirname(ORBITOOLS_BASENAME) . '/languages');
    }

    /**
     * Get plugin data.
     *
     * @param string $field Optional. Field to retrieve. Default empty (all data).
     * @return array|string
     */
    public function get_orbitools_data(string $field = '')
    {
        static $plugin_data = null;

        if ($plugin_data === null) {
            if (!function_exists('get_plugin_data')) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            }
            $plugin_data = get_plugin_data(ORBITOOLS_FILE);
        }

        return $field ? ($plugin_data[$field] ?? '') : $plugin_data;
    }
}

/**
 * Plugin activation hook.
 *
 * @return void
 */
function orbitools_activate(): void
{
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'orbitools_activate');

/**
 * Plugin deactivation hook.
 *
 * @return void
 */
function orbitools_deactivate(): void
{
    flush_rewrite_rules();

    // Check if data cleanup is enabled
    $settings = get_option('orbitools_settings', array());
    $reset_on_deactivation = !empty($settings['reset_on_deactivation']) && $settings['reset_on_deactivation'] !== '0';

    if ($reset_on_deactivation) {
        // Clean up plugin data
        orbitools_cleanup_plugin_data();
    }
}

/**
 * Clean up all plugin data.
 *
 * @return void
 */
function orbitools_cleanup_plugin_data(): void
{
    global $wpdb;

    // Remove plugin options
    delete_option('orbitools_settings');

    // Clean up Typography Presets transients using prepared statements
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
            '_transient_orbitools_%'
        )
    );
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
            '_transient_timeout_orbitools_%'
        )
    );

    // Clean up updater transients
    delete_transient('orbitools_remote_version');
    delete_transient('orbitools_changelog');
    delete_transient('orbitools_last_checked');

    // Clean up WordPress object cache
    wp_cache_delete('orbitools_typography_presets', 'theme_json');

    // Clear theme.json related transients
    delete_transient('theme_json_data_user');
    delete_transient('theme_json_data_theme');

    // Clear any other plugin-related transients
    delete_site_transient('update_plugins');

    // Force flush caches
    wp_cache_flush();
}
register_deactivation_hook(__FILE__, 'orbitools_deactivate');


/**
 * Initialize the Orbitools plugin on plugins_loaded.
 */
add_action('plugins_loaded', ['Orbitools', 'instance']);
