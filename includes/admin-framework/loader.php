<?php
/**
 * Orbital Admin Framework Loader
 *
 * Simple autoloader for the Orbital Admin Framework.
 * Include this file to make the framework available in your plugin.
 *
 * @package    Orbital_Admin_Framework
 * @version    1.0.0
 * @since      1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define framework constants
if ( ! defined( 'ORBITAL_ADMIN_FRAMEWORK_VERSION' ) ) {
	define( 'ORBITAL_ADMIN_FRAMEWORK_VERSION', '1.0.0' );
}

if ( ! defined( 'ORBITAL_ADMIN_FRAMEWORK_PATH' ) ) {
	define( 'ORBITAL_ADMIN_FRAMEWORK_PATH', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'ORBITAL_ADMIN_FRAMEWORK_URL' ) ) {
	define( 'ORBITAL_ADMIN_FRAMEWORK_URL', plugin_dir_url( __FILE__ ) );
}

/**
 * Load the main framework class
 *
 * @since 1.0.0
 */
function orbital_admin_framework_load() {
	// Only load if not already loaded
	if ( ! class_exists( 'Orbital_Admin_Framework' ) ) {
		require_once ORBITAL_ADMIN_FRAMEWORK_PATH . 'class-orbital-admin-framework.php';
	}
}

/**
 * Create a new admin framework instance
 *
 * Convenience function for creating framework instances.
 *
 * @since 1.0.0
 * @param string $slug Unique slug for the admin page.
 * @return Orbital_Admin_Framework Framework instance.
 */
function orbital_admin_framework( $slug ) {
	orbital_admin_framework_load();
	return new Orbital_Admin_Framework( $slug );
}

/**
 * Check if framework is available
 *
 * @since 1.0.0
 * @return bool True if framework is loaded.
 */
function orbital_admin_framework_available() {
	return class_exists( 'Orbital_Admin_Framework' );
}

// Auto-load the framework
orbital_admin_framework_load();