<?php
/**
 * Orbital Editor Suite - Main Settings Configuration
 *
 * Defines the core settings structure for the Orbital Editor Suite plugin.
 * This file contains the main plugin settings organized by tabs and sections
 * for use with the WP OptionsKit framework.
 *
 * Structure:
 * - Dashboard Tab: Plugin overview and module control switches
 * - Modules Tab: Settings for individual enabled modules (populated by modules)
 * - Settings Tab: Core plugin configuration options
 * - Updates Tab: Update and maintenance settings
 *
 * @package    Orbital_Editor_Suite
 * @subpackage Settings
 * @since      1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin settings array
 *
 * This array defines all the core plugin settings organized by tabs.
 * Individual modules can add their own settings via the OptionsKit filters.
 *
 * @since 1.0.0
 * @return array Complete settings configuration array
 */
return array(

	/*
	 * ===================================================================
	 * DASHBOARD TAB
	 * ===================================================================
	 * Plugin overview, status information, and module enable/disable controls
	 */
	'dashboard' => array(
		// Plugin status and version information
		array(
			'id'      => 'plugin_status',
			'name'    => __( 'Plugin Status', 'orbital-editor-suite' ),
			'type'    => 'html',
			'std'     => '<p><strong>' . __( 'Status:', 'orbital-editor-suite' ) . '</strong> ' . __( 'Active', 'orbital-editor-suite' ) . '</p>' .
						 '<p><strong>' . __( 'Version:', 'orbital-editor-suite' ) . '</strong> ' . ORBITAL_EDITOR_SUITE_VERSION . '</p>',
			'section' => 'overview',
		),

		// Active modules display (dynamically generated)
		array(
			'id'      => 'active_modules_count',
			'name'    => __( 'Active Modules', 'orbital-editor-suite' ),
			'type'    => 'html',
			'std'     => orbital_get_active_modules_html(),
			'section' => 'status',
		),

		// Typography Presets module enable/disable
		array(
			'id'      => 'typography_presets_enabled',
			'name'    => __( 'Typography Presets', 'orbital-editor-suite' ),
			'desc'    => __( 'Enable typography presets module', 'orbital-editor-suite' ),
			'type'    => 'checkbox',
			'std'     => '1',
			'section' => 'status',
		),

		// Future modules can add their enable/disable controls here via filters
	),

	/*
	 * ===================================================================
	 * MODULES TAB
	 * ===================================================================
	 * Settings for individual modules (populated dynamically by modules)
	 */
	'modules' => array(
		// Information about module settings
		array(
			'id'      => 'modules_info',
			'name'    => __( 'Module Settings', 'orbital-editor-suite' ),
			'type'    => 'html',
			'std'     => '<p>' . __( 'Settings for enabled modules will appear below.', 'orbital-editor-suite' ) . '</p>',
		),

		// Test checkbox for structure verification
		array(
			'id'      => 'test_checkbox',
			'name'    => __( 'Test Checkbox', 'orbital-editor-suite' ),
			'desc'    => __( 'This is a test checkbox', 'orbital-editor-suite' ),
			'type'    => 'checkbox',
			'std'     => false,
		),

		/*
		 * Module-specific settings are added here via OptionsKit filters.
		 * Each enabled module registers its own settings by hooking into:
		 * - orbital_editor_suite_registered_settings_sections (for sections)
		 * - orbital_editor_suite_registered_settings (for fields)
		 */
	),

	/*
	 * ===================================================================
	 * SETTINGS TAB
	 * ===================================================================
	 * Core plugin configuration and global settings
	 */
	'settings' => array(
		// Debug and development settings
		array(
			'id'      => 'debug_mode',
			'name'    => __( 'Debug Mode', 'orbital-editor-suite' ),
			'desc'    => __( 'Enable debug logging for troubleshooting', 'orbital-editor-suite' ),
			'type'    => 'checkbox',
			'std'     => false,
			'section' => 'general',
		),

		// Performance optimization settings
		array(
			'id'      => 'cache_css',
			'name'    => __( 'Cache Generated CSS', 'orbital-editor-suite' ),
			'desc'    => __( 'Cache CSS output for better performance', 'orbital-editor-suite' ),
			'type'    => 'checkbox',
			'std'     => true,
			'section' => 'performance',
		),

		// Data cleanup and maintenance settings
		array(
			'id'      => 'reset_on_deactivation',
			'name'    => __( 'Reset Data on Deactivation', 'orbital-editor-suite' ),
			'desc'    => __( 'Remove all plugin data when deactivating (cannot be undone)', 'orbital-editor-suite' ),
			'type'    => 'checkbox',
			'std'     => false,
			'section' => 'cleanup',
		),

		/*
		 * Additional core settings can be added here.
		 * Consider adding:
		 * - Asset loading preferences
		 * - Editor integration settings
		 * - Security and permissions
		 * - Import/export functionality
		 */
	),

	/*
	 * ===================================================================
	 * UPDATES TAB
	 * ===================================================================
	 * Plugin updates, maintenance, and version information
	 */
	'updates' => array(
		// Current version display
		array(
			'id'      => 'current_version',
			'name'    => __( 'Current Version', 'orbital-editor-suite' ),
			'type'    => 'html',
			'std'     => '<p>' . __( 'Version:', 'orbital-editor-suite' ) . ' ' . ORBITAL_EDITOR_SUITE_VERSION . '</p>',
			'section' => 'version',
		),

		// Automatic updates setting
		array(
			'id'      => 'auto_updates',
			'name'    => __( 'Automatic Updates', 'orbital-editor-suite' ),
			'desc'    => __( 'Enable automatic updates for this plugin', 'orbital-editor-suite' ),
			'type'    => 'checkbox',
			'std'     => false,
			'section' => 'auto',
		),

		// Update channel selection
		array(
			'id'      => 'update_channel',
			'name'    => __( 'Update Channel', 'orbital-editor-suite' ),
			'desc'    => __( 'Choose which updates to receive', 'orbital-editor-suite' ),
			'type'    => 'select',
			'options' => array(
				'stable' => __( 'Stable releases only', 'orbital-editor-suite' ),
				'beta'   => __( 'Include beta releases', 'orbital-editor-suite' ),
			),
			'std'     => 'stable',
			'section' => 'auto',
		),

		/*
		 * Additional update settings could include:
		 * - Backup before update
		 * - Update notifications
		 * - Rollback functionality
		 * - Update logs and history
		 */
	),

	/*
	 * ===================================================================
	 * EXTENSIBILITY NOTES
	 * ===================================================================
	 *
	 * This settings file provides the base structure for the plugin.
	 * Modules extend this structure by hooking into OptionsKit filters:
	 *
	 * 1. To add new tabs:
	 *    add_filter( 'orbital_editor_suite_settings_tabs', $callback );
	 *
	 * 2. To add new sections:
	 *    add_filter( 'orbital_editor_suite_registered_settings_sections', $callback );
	 *
	 * 3. To add new settings:
	 *    add_filter( 'orbital_editor_suite_registered_settings', $callback );
	 *
	 * Example module integration:
	 * - Typography Presets module adds 'typography' section under 'modules' tab
	 * - Future modules can add their own sections following the same pattern
	 *
	 * Settings structure for new modules:
	 * $settings['module_key'] = array(
	 *     array(
	 *         'id'      => 'setting_id',
	 *         'name'    => 'Setting Name',
	 *         'desc'    => 'Setting description',
	 *         'type'    => 'checkbox|text|select|etc',
	 *         'std'     => 'default_value',
	 *         'section' => 'section_name',
	 *     ),
	 * );
	 */
);