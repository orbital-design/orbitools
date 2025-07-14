<?php
/**
 * Orbital Admin Framework - Example Usage
 *
 * This file demonstrates how to use the Orbital Admin Framework
 * to create admin pages with the same ease as OptionsKit.
 *
 * CRITICAL: Field IDs must be unique across the entire framework instance!
 * Duplicate IDs will cause data conflicts and fields overwriting each other.
 *
 * @package    Orbital_Admin_Framework
 * @since      1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Example: Creating an admin page with the framework
 */
function example_orbital_admin_page() {
	// Load the framework
	require_once plugin_dir_path( __FILE__ ) . 'loader.php';
	
	// Create admin page instance
	$admin = orbital_admin_framework( 'my-plugin-settings' );
	$admin->set_page_title( 'My Plugin Settings' );
	$admin->set_page_description( 'Configure your plugin settings below.' );
	
	// Configure menu (optional - defaults to Settings submenu)
	$admin->set_menu_config( array(
		'parent'     => 'options-general.php',  // Settings submenu
		'page_title' => 'My Plugin Settings',
		'menu_title' => 'My Plugin',
		'capability' => 'manage_options',
	) );
}

/**
 * Define admin structure
 * 
 * This unified approach defines tabs, sections, and display modes in one place.
 */
add_filter( 'my_plugin_settings_admin_structure', function( $structure ) {
	return array(
		'general' => array(
			'title' => 'General Settings',
			'display_mode' => 'tabs',  // 'tabs' or 'cards'
			'sections' => array(
				'basic'        => 'Basic Settings',
				'integrations' => 'Integrations',
			),
		),
		'appearance' => array(
			'title' => 'Appearance',
			'display_mode' => 'cards',
			'sections' => array(
				'styling' => 'Visual Styling',
				'layout'  => 'Layout Options',
			),
		),
		'advanced' => array(
			'title' => 'Advanced Options',
			'display_mode' => 'tabs',
			'sections' => array(
				'performance' => 'Performance',
				'debugging'   => 'Debug Options',
			),
		),
	);
} );


/**
 * Define settings fields
 * 
 * Fields are kept separate for flexibility. Each field should have a 'section' 
 * parameter that matches a section key defined in the admin structure above.
 * 
 * IMPORTANT: Each field's 'id' must be unique across ALL tabs and sections!
 * The ID is used for HTML names, database keys, and value mapping.
 */
add_filter( 'my_plugin_settings_settings', function( $settings ) {
	return array(
		'general' => array(
			array(
				'id'      => 'plugin_enabled',        // ← Must be unique!
				'name'    => 'Enable Plugin',
				'desc'    => 'Turn the plugin functionality on or off.',
				'type'    => 'checkbox',
				'std'     => true,
				'section' => 'basic',
			),
			array(
				'id'      => 'api_key',
				'name'    => 'API Key',
				'desc'    => 'Enter your API key for external services.',
				'type'    => 'text',
				'std'     => '',
				'section' => 'basic',
			),
			array(
				'id'      => 'service_provider',
				'name'    => 'Service Provider',
				'desc'    => 'Choose your preferred service provider.',
				'type'    => 'select',
				'options' => array(
					'provider1' => 'Provider One',
					'provider2' => 'Provider Two',
					'provider3' => 'Provider Three',
				),
				'std'     => 'provider1',
				'section' => 'integrations',
			),
		),
		'appearance' => array(
			array(
				'id'      => 'theme_color',
				'name'    => 'Theme Color',
				'desc'    => 'Choose the primary color for your theme.',
				'type'    => 'select',
				'options' => array(
					'blue'   => 'Blue',
					'green'  => 'Green',
					'red'    => 'Red',
					'purple' => 'Purple',
				),
				'std'     => 'blue',
				'section' => 'styling',
			),
			array(
				'id'      => 'show_header',
				'name'    => 'Show Header',
				'desc'    => 'Display the header section.',
				'type'    => 'checkbox',
				'std'     => true,
				'section' => 'layout',
			),
		),
		'advanced' => array(
			array(
				'id'      => 'cache_enabled',
				'name'    => 'Enable Caching',
				'desc'    => 'Enable caching for better performance.',
				'type'    => 'checkbox',
				'std'     => true,
				'section' => 'performance',
			),
			array(
				'id'      => 'debug_mode',
				'name'    => 'Debug Mode',
				'desc'    => 'Enable debug logging.',
				'type'    => 'checkbox',
				'std'     => false,
				'section' => 'debugging',
			),
		),
	);
} );

/**
 * Add custom content to header section
 */
add_action( 'my_plugin_settings_render_header', function() {
	echo '<div class="header-actions">';
	echo '<a href="#" class="button">Documentation</a>';
	echo '<a href="#" class="button">Support</a>';
	echo '</div>';
} );

/**
 * Add custom content between sections (example: after notices)
 */
add_action( 'my_plugin_settings_after_notices', function() {
	echo '<div class="custom-banner">';
	echo '<p><strong>Pro Tip:</strong> Check out our documentation for advanced configuration options.</p>';
	echo '</div>';
} );

/**
 * Customize framework labels
 */
add_filter( 'my_plugin_settings_labels', function( $labels ) {
	$labels['save_success'] = 'Your settings have been saved successfully!';
	$labels['save_error']   = 'Oops! Something went wrong. Please try again.';
	return $labels;
} );

/**
 * Hook into settings save (example: clear cache when settings change)
 */
add_action( 'my_plugin_settings_post_save_settings', function( $settings_data, $success ) {
	if ( $success ) {
		// Clear any caches
		wp_cache_flush();
		
		// Log the save action
		error_log( 'My Plugin settings saved: ' . print_r( $settings_data, true ) );
	}
}, 10, 2 );

/**
 * Initialize the example admin page
 * 
 * USAGE: Just call this function in your plugin's main file
 * or during the 'plugins_loaded' action.
 */
// example_orbital_admin_page();

/**
 * =============================================================================
 * URL-BASED NAVIGATION & DEEP LINKING
 * =============================================================================
 * 
 * The Orbital Admin Framework supports URL-based navigation for both tabs
 * and sections, allowing users to bookmark and share direct links to specific
 * admin sections.
 * 
 * URL STRUCTURE:
 * ?page={page-slug}&tab={tab-key}&section={section-key}
 * 
 * EXAMPLES:
 * Basic page:        ?page=my-plugin-settings
 * Specific tab:      ?page=my-plugin-settings&tab=advanced
 * Specific section:  ?page=my-plugin-settings&tab=advanced&section=debugging
 * 
 * HOW IT WORKS:
 * 
 * 1. TAB NAVIGATION:
 *    - URL parameter 'tab' determines active main tab
 *    - If 'tab' is missing or invalid, first tab is used
 *    - When switching tabs via clicking, URL updates automatically
 *    - Section parameter is cleared when switching tabs (defaults to first section)
 * 
 * 2. SECTION NAVIGATION (SUB-TABS):
 *    - URL parameter 'section' determines active section within a tab
 *    - If 'section' is missing or invalid, first section in tab is used
 *    - When switching sections via clicking, URL updates automatically
 *    - Section must belong to the current active tab to be valid
 * 
 * 3. DEEP LINKING:
 *    - Users can bookmark URLs with specific tab/section combinations
 *    - Shared links will open directly to the intended admin section
 *    - Invalid combinations gracefully fall back to defaults
 * 
 * 4. URL UPDATES:
 *    - All navigation updates the browser URL without page reload
 *    - Browser back/forward buttons work with the navigation
 *    - URLs are always shareable and bookmarkable
 * 
 * IMPLEMENTATION EXAMPLES:
 * 
 * // Create shareable links in your admin interface:
 * $general_basic_url = add_query_arg([
 *     'page' => 'my-plugin-settings',
 *     'tab' => 'general',
 *     'section' => 'basic'
 * ], admin_url('admin.php'));
 * 
 * $advanced_debug_url = add_query_arg([
 *     'page' => 'my-plugin-settings', 
 *     'tab' => 'advanced',
 *     'section' => 'debugging'
 * ], admin_url('admin.php'));
 * 
 * // Use in help text or documentation:
 * echo '<a href="' . esc_url($advanced_debug_url) . '">Configure Debug Settings</a>';
 * 
 * BENEFITS:
 * - Users can bookmark specific admin sections
 * - Support documentation can link directly to relevant settings
 * - Improved user experience with persistent navigation state
 * - Better accessibility and usability
 * 
 * JAVASCRIPT EVENTS:
 * The framework triggers custom events for tab and section changes:
 * 
 * document.addEventListener('orbital:tabChanged', function(e) {
 *     console.log('Tab changed to:', e.detail.tabKey);
 * });
 * 
 * document.addEventListener('orbital:sectionChanged', function(e) {
 *     console.log('Section changed to:', e.detail.sectionKey);
 * });
 */