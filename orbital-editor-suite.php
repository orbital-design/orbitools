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
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 */
function run_orbital_editor_suite() {
    $plugin = new Orbital\Editor_Suite\Plugin();
    $plugin->run();
}

/**
 * Initialize the plugin.
 */
add_action('plugins_loaded', 'run_orbital_editor_suite');

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
    $settings_link = '<a href="' . admin_url('admin.php?page=orbital-editor-suite') . '">' . 
        __('Settings', 'orbital-editor-suite') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'orbital_editor_suite_settings_link');

/**
 * Add plugin meta links.
 */
function orbital_editor_suite_meta_links($links, $file) {
    if ($file === plugin_basename(__FILE__)) {
        $links[] = '<a href="https://github.com/orbital-design/orbital-editor-suite" target="_blank">' . 
            __('GitHub', 'orbital-editor-suite') . '</a>';
        $links[] = '<a href="' . admin_url('admin.php?page=orbital-editor-suite-updates') . '">' . 
            __('Updates', 'orbital-editor-suite') . '</a>';
    }
    return $links;
}
add_filter('plugin_row_meta', 'orbital_editor_suite_meta_links', 10, 2);