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
	if ( ! class_exists( 'Orbitools\AdminKit\\Field_Base' ) ) {
		require_once ORBI_ADMIN_KIT_PATH . 'fields/class-orbital-field-base.php';
	}

	// Load field registry
	if ( ! class_exists( 'Orbitools\AdminKit\\Field_Registry' ) ) {
		require_once ORBI_ADMIN_KIT_PATH . 'classes/class-orbital-field-registry.php';
	}

	// Load view classes
	if ( ! class_exists( 'Orbitools\AdminKit\\Views\\Header_View' ) ) {
		require_once ORBI_ADMIN_KIT_PATH . 'views/class-header-view.php';
	}
	
	if ( ! class_exists( 'Orbitools\AdminKit\\Views\\Navigation_View' ) ) {
		require_once ORBI_ADMIN_KIT_PATH . 'views/class-navigation-view.php';
	}
	
	if ( ! class_exists( 'Orbitools\AdminKit\\Views\\Notice_Manager' ) ) {
		require_once ORBI_ADMIN_KIT_PATH . 'views/class-notice-manager.php';
	}
	
	if ( ! class_exists( 'Orbitools\AdminKit\\Views\\Content_View' ) ) {
		require_once ORBI_ADMIN_KIT_PATH . 'views/class-content-view.php';
	}
	
	if ( ! class_exists( 'Orbitools\AdminKit\\Views\\Footer_View' ) ) {
		require_once ORBI_ADMIN_KIT_PATH . 'views/class-footer-view.php';
	}
	
	// Load page builder
	if ( ! class_exists( 'Orbitools\AdminKit\\Classes\\Page_Builder' ) ) {
		require_once ORBI_ADMIN_KIT_PATH . 'classes/class-page-builder.php';
	}

	// Load main framework class
	if ( ! class_exists( 'Orbitools\AdminKit\\Admin_Kit' ) ) {
		require_once ORBI_ADMIN_KIT_PATH . 'classes/class-orbital-admin-framework.php';
	}

	// Initialize field registry
	if ( class_exists( 'Orbitools\AdminKit\\Field_Registry' ) ) {
		Orbitools\AdminKit\Field_Registry::init();
	}
}

/**
 * Create a new admin framework instance
 *
 * Convenience function for creating framework instances.
 *
 * @since 1.0.0
 * @param string $slug Unique slug for the admin page.
 * @return Orbitools\AdminKit\Admin_Kit Framework instance.
 */
function orbi_admin_kit( $slug ) {
	orbi_admin_kit_load();
	return new Orbitools\AdminKit\Admin_Kit( $slug );
}

/**
 * Check if framework is available
 *
 * @since 1.0.0
 * @return bool True if framework is loaded.
 */
function orbi_admin_kit_available() {
	return class_exists( 'Orbitools\AdminKit\\Admin_Kit' );
}

// Auto-load the framework
orbi_admin_kit_load();