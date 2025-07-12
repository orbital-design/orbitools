<?php
/**
 * Plugin Name: Orbital Editor Suite
 * Plugin URI: https://github.com/orbital-design/orbital-editor-suite
 * Description: Professional suite of editor enhancements with typography utilities and modern admin panel
 * Version: 1.0.0
 * Author: Orbital Design
 * Author URI: https://orbital.com
 * Text Domain: orbital-editor-suite
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package Orbital_Editor_Suite
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Current plugin version.
 */
define('ORBITAL_EDITOR_SUITE_VERSION', '1.0.0');

/**
 * Plugin directory path.
 */
define('ORBITAL_EDITOR_SUITE_PATH', plugin_dir_path(__FILE__));

/**
 * Plugin directory URL.
 */
define('ORBITAL_EDITOR_SUITE_URL', plugin_dir_url(__FILE__));

/**
 * Plugin file path.
 */
define('ORBITAL_EDITOR_SUITE_FILE', __FILE__);

/**
 * Load Composer autoloader.
 */
if (file_exists(ORBITAL_EDITOR_SUITE_PATH . 'vendor/autoload.php')) {
    require_once ORBITAL_EDITOR_SUITE_PATH . 'vendor/autoload.php';
}

/**
 * The code that runs during plugin activation.
 */
function activate_orbital_editor_suite() {
    require_once ORBITAL_EDITOR_SUITE_PATH . 'includes/class-activator.php';
    Orbital\Editor_Suite\Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_orbital_editor_suite() {
    // Currently no deactivation tasks
}

register_activation_hook(__FILE__, 'activate_orbital_editor_suite');
register_deactivation_hook(__FILE__, 'deactivate_orbital_editor_suite');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require ORBITAL_EDITOR_SUITE_PATH . 'includes/class-plugin.php';

/**
 * Begins execution of the plugin.
 *
 * Everything runs on plugins_loaded - no complicated timing.
 */
function run_orbital_editor_suite() {
    static $initialized = false;
    if ($initialized) {
        error_log('run_orbital_editor_suite() already initialized - skipping');
        return;
    }
    $initialized = true;
    error_log('run_orbital_editor_suite() called - first time');
    
    $plugin = new Orbital\Editor_Suite\Plugin();
    $plugin->run();
}

/**
 * Initialize everything on plugins_loaded.
 */
// add_action('plugins_loaded', 'run_orbital_editor_suite');

/**
 * Simple OptionsKit test - bypass all our complex code
 */
require_once ORBITAL_EDITOR_SUITE_PATH . 'simple-optionskit.php';

/**
 * Load plugin textdomain for internationalization.
 */
function orbital_editor_suite_load_textdomain() {
    load_plugin_textdomain(
        'orbital-editor-suite',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages/'
    );
}
add_action('init', 'orbital_editor_suite_load_textdomain');

/**
 * Add settings link on plugin page.
 */
function orbital_editor_suite_settings_link($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=orbital-editor-suite') . '">Settings</a>';
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'orbital_editor_suite_settings_link');

/**
 * Add plugin meta links.
 */
function orbital_editor_suite_meta_links($links, $file) {
    if ($file === plugin_basename(__FILE__)) {
        $links[] = '<a href="https://github.com/orbital-design/orbital-editor-suite" target="_blank">GitHub</a>';
        $links[] = '<a href="' . admin_url('admin.php?page=orbital-editor-suite-updates') . '">Updates</a>';
    }
    return $links;
}
add_filter('plugin_row_meta', 'orbital_editor_suite_meta_links', 10, 2);