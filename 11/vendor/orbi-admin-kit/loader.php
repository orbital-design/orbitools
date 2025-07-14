<?php
/**
 * OrbiTools AdminKit Loader
 *
 * Simple autoloader for the OrbiTools AdminKit.
 * Include this file to make the framework available in your plugin.
 *
 * @package    Orbi\AdminKit
 * @version    1.0.0
 * @since      1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define framework constants
if ( ! defined( 'ORBI_ADMIN_KIT_VERSION' ) ) {
	define( 'ORBI_ADMIN_KIT_VERSION', '1.0.0' );
}

if ( ! defined( 'ORBI_ADMIN_KIT_PATH' ) ) {
	define( 'ORBI_ADMIN_KIT_PATH', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'ORBI_ADMIN_KIT_URL' ) ) {
	define( 'ORBI_ADMIN_KIT_URL', plugin_dir_url( __FILE__ ) );
}

/**
 * Load the main framework class
 *
 * @since 1.0.0
 */
function orbi_admin_kit_load() {
	// Load base field class
	if ( ! class_exists( 'Orbi\\AdminKit\\Field_Base' ) ) {
		require_once ORBI_ADMIN_KIT_PATH . 'fields/class-orbital-field-base.php';
	}

	// Load field registry
	if ( ! class_exists( 'Orbi\\AdminKit\\Field_Registry' ) ) {
		require_once ORBI_ADMIN_KIT_PATH . 'class-orbital-field-registry.php';
	}

	// Load main framework class
	if ( ! class_exists( 'Orbi\\AdminKit\\Admin_Kit' ) ) {
		require_once ORBI_ADMIN_KIT_PATH . 'class-orbital-admin-framework.php';
	}

	// Initialize field registry
	if ( class_exists( 'Orbi\\AdminKit\\Field_Registry' ) ) {
		Orbi\AdminKit\Field_Registry::init();
	}
}

/**
 * Create a new admin framework instance
 *
 * Convenience function for creating framework instances.
 *
 * @since 1.0.0
 * @param string $slug Unique slug for the admin page.
 * @return Orbi\AdminKit\Admin_Kit Framework instance.
 */
function orbi_admin_kit( $slug ) {
	orbi_admin_kit_load();
	return new Orbi\AdminKit\Admin_Kit( $slug );
}

/**
 * Check if framework is available
 *
 * @since 1.0.0
 * @return bool True if framework is loaded.
 */
function orbi_admin_kit_available() {
	return class_exists( 'Orbi\\AdminKit\\Admin_Kit' );
}

// Auto-load the framework
orbi_admin_kit_load();