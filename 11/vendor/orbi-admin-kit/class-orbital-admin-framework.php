<?php
/**
 * OrbiTools AdminKit
 *
 * A lightweight, standalone admin page framework for WordPress plugins.
 * Provides a clean API for building admin pages with tabs, sections, and fields
 * using WordPress hooks and filters.
 *
 * @package    Orbi\AdminKit
 * @version    1.0.0
 * @author     OrbiTools
 * @since      1.0.0
 */

namespace Orbi\AdminKit;

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * OrbiTools AdminKit Class
 *
 * Core framework class that handles admin page creation, rendering,
 * and settings management through a hook-based system.
 *
 * @since 1.0.0
 */
class Admin_Kit {

	/**
	 * Framework version
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const VERSION = '1.0.0';

	/**
	 * Framework slug identifier
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $slug;

	/**
	 * Function-safe slug for hooks
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $func_slug;

	/**
	 * Page title
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $page_title = 'Settings';

	/**
	 * Page description
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $page_description = '';

	/**
	 * Menu configuration
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private $menu_config = array();

	/**
	 * Field ID validation flag
	 *
	 * @since 1.0.0
	 * @var bool
	 */
	private $field_ids_validated = false;

	/**
	 * Initialize the framework
	 *
	 * @since 1.0.0
	 * @param string $slug Unique slug for this admin page.
	 */
	public function __construct( $slug ) {
		$this->slug      = $slug;
		$this->func_slug = str_replace( '-', '_', $slug );

		// Set default menu configuration
		$this->menu_config = array(
			'parent'     => 'options-general.php',
			'page_title' => 'Settings',
			'menu_title' => 'Settings',
			'capability' => 'manage_options',
		);

		$this->init();
	}

	/**
	 * Initialize framework hooks
	 *
	 * @since 1.0.0
	 */
	private function init() {
		add_action( 'admin_menu', array( $this, 'add_admin_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'wp_ajax_orbi_admin_save_settings_' . $this->slug, array( $this, 'ajax_save_settings' ) );
	}

	/**
	 * Set page title
	 *
	 * @since 1.0.0
	 * @param string $title Page title.
	 */
	public function set_page_title( $title ) {
		$this->page_title = $title;
	}

	/**
	 * Set page description
	 *
	 * @since 1.0.0
	 * @param string $description Page description.
	 */
	public function set_page_description( $description ) {
		$this->page_description = $description;
	}

	/**
	 * Configure menu settings
	 *
	 * @since 1.0.0
	 * @param array $config Menu configuration array.
	 */
	public function set_menu_config( $config ) {
		$this->menu_config = wp_parse_args( $config, $this->menu_config );
	}

	/**
	 * Add admin page to WordPress menu
	 *
	 * @since 1.0.0
	 */
	public function add_admin_page() {
		// Allow filtering of menu configuration
		$menu = apply_filters( $this->func_slug . '_menu_config', $this->menu_config );

		$page_hook = add_submenu_page(
			$menu['parent'],
			$menu['page_title'],
			$menu['menu_title'],
			$menu['capability'],
			$this->slug,
			array( $this, 'render_admin_page' )
		);

		// Store page hook for targeting assets
		add_action( 'load-' . $page_hook, array( $this, 'page_loaded' ) );
		
		// Add body class for our framework pages
		add_filter( 'admin_body_class', array( $this, 'add_admin_body_class' ) );
	}

	/**
	 * Page loaded callback
	 *
	 * @since 1.0.0
	 */
	public function page_loaded() {
		// Hide all admin notices on framework pages
		$this->hide_all_admin_notices();
		
		do_action( $this->func_slug . '_page_loaded' );
	}
	
	/**
	 * Add body class for framework pages
	 *
	 * @since 1.0.0
	 * @param string $classes Existing body classes.
	 * @return string Modified body classes.
	 */
	public function add_admin_body_class( $classes ) {
		$screen = get_current_screen();
		
		if ( $screen && strpos( $screen->id, $this->slug ) !== false ) {
			$classes .= ' orbi-admin-kit-page';
		}
		
		return $classes;
	}
	
	/**
	 * Hide all admin notices on framework pages
	 *
	 * @since 1.0.0
	 */
	private function hide_all_admin_notices() {
		global $wp_filter;
		if ( isset( $wp_filter['admin_notices'] ) ) {
			unset( $wp_filter['admin_notices'] );
		}
		if ( isset( $wp_filter['all_admin_notices'] ) ) {
			unset( $wp_filter['all_admin_notices'] );
		}
	}

	/**
	 * Enqueue admin assets
	 *
	 * @since 1.0.0
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_assets( $hook ) {
		// Only load on our admin pages
		if ( strpos( $hook, $this->slug ) === false ) {
			return;
		}

		// Framework CSS
		wp_enqueue_style(
			'orbi-admin-kit',
			$this->get_framework_url() . 'assets/admin-framework.css',
			array(),
			self::VERSION
		);

		// Framework JS
		wp_enqueue_script(
			'orbi-admin-kit',
			$this->get_framework_url() . 'assets/admin-framework.js',
			array(), // No dependencies - pure vanilla JS
			self::VERSION,
			true
		);

		// Localize script data
		wp_localize_script(
			'orbi-admin-kit',
			'orbiAdminKit',
			array(
				'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
				'nonce'      => wp_create_nonce( 'orbi_admin_' . $this->slug ),
				'slug'       => $this->slug,
				'labels'     => $this->get_labels(),
			)
		);

		// Hook for additional assets
		do_action( $this->func_slug . '_enqueue_assets', $hook );
	}

	/**
	 * Register WordPress settings
	 *
	 * @since 1.0.0
	 */
	public function register_settings() {
		$settings = $this->get_settings();

		foreach ( $settings as $tab_key => $tab_settings ) {
			if ( ! is_array( $tab_settings ) ) {
				continue;
			}

			foreach ( $tab_settings as $setting ) {
				if ( ! isset( $setting['id'] ) ) {
					continue;
				}

				register_setting(
					$this->slug . '_settings',
					$setting['id'],
					array(
						'sanitize_callback' => array( $this, 'sanitize_setting' ),
					)
				);
			}
		}
	}

	/**
	 * Sanitize setting value
	 *
	 * @since 1.0.0
	 * @param mixed $value Setting value to sanitize.
	 * @return mixed Sanitized value.
	 */
	public function sanitize_setting( $value ) {
		// Apply filters for custom sanitization
		return apply_filters( $this->func_slug . '_sanitize_setting', $value );
	}

	/**
	 * Sanitize settings data using field registry
	 *
	 * @since 1.0.0
	 * @param array $settings_data Settings data to sanitize.
	 * @return array Sanitized settings data.
	 */
	public function sanitize_settings_data( $settings_data ) {
		if ( ! is_array( $settings_data ) ) {
			return array();
		}

		$sanitized = array();
		$all_settings = $this->get_settings();

		foreach ( $settings_data as $field_id => $value ) {
			// Find the field configuration
			$field_config = $this->find_field_config( $field_id, $all_settings );
			
			if ( $field_config ) {
				$sanitized[ $field_id ] = Field_Registry::sanitize_field_value( $field_config, $value, $this );
			} else {
				// Fallback sanitization
				$sanitized[ $field_id ] = sanitize_text_field( $value );
			}
		}

		return $sanitized;
	}

	/**
	 * Find field configuration by ID
	 *
	 * @since 1.0.0
	 * @param string $field_id Field ID to find.
	 * @param array  $all_settings All settings configuration.
	 * @return array|null Field configuration or null if not found.
	 */
	private function find_field_config( $field_id, $all_settings ) {
		foreach ( $all_settings as $tab_settings ) {
			if ( ! is_array( $tab_settings ) ) {
				continue;
			}
			
			foreach ( $tab_settings as $field ) {
				if ( isset( $field['id'] ) && $field['id'] === $field_id ) {
					return $field;
				}
			}
		}
		
		return null;
	}

	/**
	 * AJAX save settings handler
	 *
	 * @since 1.0.0
	 */
	public function ajax_save_settings() {
		// Verify nonce
		$nonce = isset( $_POST['nonce'] ) ? $_POST['nonce'] : '';
		$nonce_action = 'orbi_admin_' . $this->slug;
		
		if ( ! wp_verify_nonce( $nonce, $nonce_action ) ) {
			wp_send_json_error( 'Invalid nonce' );
		}

		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}

		// Process and save settings
		$settings_json = isset( $_POST['settings'] ) ? $_POST['settings'] : '{}';
		// Fix double-escaped quotes
		$settings_json = stripslashes( $settings_json );
		$settings_data = json_decode( $settings_json, true );
		
		// Fallback to empty array if JSON decode fails
		if ( ! is_array( $settings_data ) ) {
			$settings_data = array();
		}
		
		$result = $this->save_settings( $settings_data );

		if ( $result ) {
			wp_send_json_success( 'Settings saved successfully' );
		} else {
			wp_send_json_error( 'Failed to save settings' );
		}
	}

	/**
	 * Save settings to database
	 *
	 * @since 1.0.0
	 * @param array $settings_data Settings data to save.
	 * @return bool Success status.
	 */
	private function save_settings( $settings_data ) {
		// Sanitize settings using field registry
		$sanitized_data = $this->sanitize_settings_data( $settings_data );

		// Hook for pre-save processing
		$sanitized_data = apply_filters( $this->func_slug . '_pre_save_settings', $sanitized_data );

		// Save settings
		$result = update_option( $this->slug . '_settings', $sanitized_data );

		// Hook for post-save processing
		do_action( $this->func_slug . '_post_save_settings', $sanitized_data, $result );

		return $result;
	}

	/**
	 * Render the complete admin page
	 *
	 * @since 1.0.0
	 */
	public function render_admin_page() {
		// Start output buffering to capture and move admin notices
		ob_start();
		?>
		<main class="orbi-admin" id="orbi-admin-<?php echo esc_attr( $this->slug ); ?>" role="main" aria-labelledby="orbi-admin-title">
			
			<?php
			// HOOK: Before header
			do_action( $this->func_slug . '_before_header' );
			?>
			
			<header class="orbi-admin__header">
				<?php $this->render_header(); ?>
			</header>
			
			<?php
			// HOOK: After header / Before nav
			do_action( $this->func_slug . '_after_header' );
			?>
			
			<nav class="orbi-admin__nav" role="navigation" aria-label="<?php esc_attr_e( 'Admin page navigation', 'orbi-admin-kit' ); ?>">
				<?php $this->render_navigation(); ?>
			</nav>
			
			<?php
			// HOOK: After nav / Before notices
			do_action( $this->func_slug . '_after_nav' );
			?>
			
			<div class="orbi-admin__notices" id="orbi-notices-container" role="alert" aria-live="polite">
				<?php $this->render_notices(); ?>
			</div>
			
			<?php
			// HOOK: After notices / Before tabs
			do_action( $this->func_slug . '_after_notices' );
			?>
			
			<div class="orbi-admin__tabs" role="tablist" aria-label="<?php esc_attr_e( 'Settings sections', 'orbi-admin-kit' ); ?>">
				<?php $this->render_tabs(); ?>
			</div>
			
			<?php
			// HOOK: After tabs / Before content
			do_action( $this->func_slug . '_after_tabs' );
			?>
			
			<section class="orbi-admin__content" role="tabpanel" aria-labelledby="orbi-active-tab">
				<?php $this->render_tab_content(); ?>
			</section>
			
			<?php
			// HOOK: After content / Before footer
			do_action( $this->func_slug . '_after_content' );
			?>
			
			<footer class="orbi-admin__footer">
				<?php $this->render_footer(); ?>
			</footer>
			
			<?php
			// HOOK: After footer
			do_action( $this->func_slug . '_after_footer' );
			?>
			
		</main>
		<?php
	}

	/**
	 * Render header section
	 *
	 * @since 1.0.0
	 */
	private function render_header() {
		?>
		<div class="orbi-admin__header-content">
			<h1 class="orbi-admin__title" id="orbi-admin-title"><?php echo esc_html( $this->page_title ); ?></h1>
			<?php if ( $this->page_description ) : ?>
				<p class="orbi-admin__description"><?php echo esc_html( $this->page_description ); ?></p>
			<?php endif; ?>
		</div>
		
		<?php
		// Hook for additional header content
		do_action( $this->func_slug . '_render_header' );
		?>
		<?php
	}

	/**
	 * Render navigation section (BEM + semantic)
	 *
	 * @since 1.0.0
	 */
	private function render_navigation() {
		?>
		<div class="orbi-admin__nav-content">
			<?php $this->render_breadcrumbs(); ?>
			<?php $this->render_nav_actions(); ?>
		</div>
		<?php
		
		// Hook for custom navigation
		do_action( $this->func_slug . '_render_navigation' );
	}
	
	/**
	 * Render breadcrumbs
	 *
	 * @since 1.0.0
	 */
	private function render_breadcrumbs() {
		$current_tab = $this->get_current_tab();
		$current_section = $this->get_current_section();
		$tabs = $this->get_tabs();
		
		if ( empty( $tabs ) ) {
			return;
		}
		
		?>
		<nav class="orbi-admin__breadcrumbs" aria-label="<?php esc_attr_e( 'Breadcrumb navigation', 'orbi-admin-kit' ); ?>">
			<ol class="orbi-admin__breadcrumb-list">
				<li class="orbi-admin__breadcrumb-item">
					<span class="orbi-admin__breadcrumb-text"><?php echo esc_html( $this->page_title ); ?></span>
				</li>
				
				<?php if ( $current_tab && isset( $tabs[ $current_tab ] ) ) : ?>
					<li class="orbi-admin__breadcrumb-item">
						<span class="orbi-admin__breadcrumb-separator" aria-hidden="true">›</span>
						<span class="orbi-admin__breadcrumb-text orbi-admin__breadcrumb-text--current">
							<?php echo esc_html( $tabs[ $current_tab ] ); ?>
						</span>
					</li>
				<?php endif; ?>
				
				<?php if ( $current_section ) : ?>
					<?php
					$structure = $this->get_admin_structure();
					$sections = isset( $structure[ $current_tab ]['sections'] ) ? $structure[ $current_tab ]['sections'] : array();
					?>
					<?php if ( isset( $sections[ $current_section ] ) ) : ?>
						<li class="orbi-admin__breadcrumb-item">
							<span class="orbi-admin__breadcrumb-separator" aria-hidden="true">›</span>
							<span class="orbi-admin__breadcrumb-text orbi-admin__breadcrumb-text--current">
								<?php echo esc_html( $sections[ $current_section ] ); ?>
							</span>
						</li>
					<?php endif; ?>
				<?php endif; ?>
			</ol>
		</nav>
		<?php
	}
	
	/**
	 * Render navigation actions
	 *
	 * @since 1.0.0
	 */
	private function render_nav_actions() {
		?>
		<div class="orbi-admin__nav-actions">
			<?php
			// Hook for navigation actions (save buttons, etc.)
			do_action( $this->func_slug . '_render_nav_actions' );
			
			// Default save button (if no custom actions provided)
			if ( ! has_action( $this->func_slug . '_render_nav_actions' ) ) {
				$this->render_default_nav_actions();
			}
			?>
		</div>
		<?php
	}
	
	/**
	 * Render default navigation actions
	 *
	 * @since 1.0.0
	 */
	private function render_default_nav_actions() {
		?>
		<button type="submit" 
		        class="orbi-admin__save-btn button button-primary" 
		        form="orbi-settings-form"
		        aria-describedby="orbi-save-btn-desc">
			<span class="orbi-admin__save-btn-text"><?php esc_html_e( 'Save Settings', 'orbi-admin-kit' ); ?></span>
		</button>
		<span id="orbi-save-btn-desc" class="screen-reader-text">
			<?php esc_html_e( 'Save all settings changes', 'orbi-admin-kit' ); ?>
		</span>
		<?php
	}

	/**
	 * Render notices section (BEM + custom notice system)
	 *
	 * @since 1.0.0
	 */
	private function render_notices() {
		// Render any stored framework notices
		$notices = $this->get_framework_notices();
		
		if ( ! empty( $notices ) ) {
			foreach ( $notices as $notice ) {
				$this->render_single_notice( $notice );
			}
		}
		
		// Hook for additional notices
		do_action( $this->func_slug . '_render_notices' );
	}
	
	/**
	 * Get stored framework notices
	 *
	 * @since 1.0.0
	 * @return array Array of notices.
	 */
	private function get_framework_notices() {
		$notices_key = 'orbi_framework_notices_' . $this->slug;
		$notices = get_transient( $notices_key );
		
		// Clear notices after displaying them
		if ( ! empty( $notices ) ) {
			delete_transient( $notices_key );
		}
		
		return is_array( $notices ) ? $notices : array();
	}
	
	/**
	 * Add a framework notice
	 *
	 * @since 1.0.0
	 * @param string $message Notice message.
	 * @param string $type Notice type (success, error, warning, info).
	 * @param bool   $dismissible Whether notice is dismissible.
	 */
	public function add_notice( $message, $type = 'info', $dismissible = true ) {
		$notices_key = 'orbi_framework_notices_' . $this->slug;
		$notices = get_transient( $notices_key );
		
		if ( ! is_array( $notices ) ) {
			$notices = array();
		}
		
		// Check if this exact notice already exists to prevent duplicates
		$notice_exists = false;
		foreach ( $notices as $existing_notice ) {
			if ( $existing_notice['message'] === $message && $existing_notice['type'] === $type ) {
				$notice_exists = true;
				break;
			}
		}
		
		// Only add the notice if it doesn't already exist
		if ( ! $notice_exists ) {
			$notices[] = array(
				'message'     => $message,
				'type'        => $type,
				'dismissible' => $dismissible,
				'id'          => uniqid( 'orbi-notice-' ),
			);
			
			// Store for 5 minutes
			set_transient( $notices_key, $notices, 300 );
		}
	}
	
	/**
	 * Render a single notice (BEM + accessible)
	 *
	 * @since 1.0.0
	 * @param array $notice Notice data.
	 */
	private function render_single_notice( $notice ) {
		$type = isset( $notice['type'] ) ? $notice['type'] : 'info';
		$dismissible = isset( $notice['dismissible'] ) ? $notice['dismissible'] : true;
		$id = isset( $notice['id'] ) ? $notice['id'] : uniqid( 'orbi-notice-' );
		
		$classes = array(
			'orbi-notice',
			'orbi-notice--' . $type
		);
		
		if ( $dismissible ) {
			$classes[] = 'orbi-notice--dismissible';
		}
		
		?>
		<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" 
		     id="<?php echo esc_attr( $id ); ?>"
		     role="alert"
		     aria-live="polite">
			
			<div class="orbi-notice__icon" aria-hidden="true">
				<?php echo $this->get_notice_icon( $type ); ?>
			</div>
			
			<div class="orbi-notice__content">
				<p class="orbi-notice__message"><?php echo wp_kses_post( $notice['message'] ); ?></p>
			</div>
			
			<?php if ( $dismissible ) : ?>
				<button type="button" 
				        class="orbi-notice__dismiss" 
				        aria-label="<?php esc_attr_e( 'Dismiss notice', 'orbi-admin-kit' ); ?>"
				        onclick="this.parentElement.style.display='none';">
					<span class="orbi-notice__dismiss-icon" aria-hidden="true">&times;</span>
				</button>
			<?php endif; ?>
		</div>
		<?php
	}
	
	/**
	 * Get notice icon by type
	 *
	 * @since 1.0.0
	 * @param string $type Notice type.
	 * @return string Icon HTML.
	 */
	private function get_notice_icon( $type ) {
		$icons = array(
			'success' => '✓',
			'error'   => '✕',
			'warning' => '⚠',
			'info'    => 'ℹ',
		);
		
		return isset( $icons[ $type ] ) ? $icons[ $type ] : $icons['info'];
	}

	/**
	 * Render tabs section
	 *
	 * @since 1.0.0
	 */
	private function render_tabs() {
		$tabs = $this->get_tabs();
		$active_tab = $this->get_active_tab();
		
		if ( empty( $tabs ) ) {
			return;
		}
		?>
		<div class="orbi-admin__tabs-wrapper">
			<nav class="orbi-admin__tabs-nav">
				<?php foreach ( $tabs as $tab_key => $tab_label ) : ?>
					<a href="<?php echo esc_url( $this->get_tab_url( $tab_key ) ); ?>" 
					   class="orbi-admin__tab-link <?php echo $active_tab === $tab_key ? 'orbi-admin__tab-link--active' : ''; ?>"
					   data-tab="<?php echo esc_attr( $tab_key ); ?>"
					   role="tab"
					   aria-selected="<?php echo $active_tab === $tab_key ? 'true' : 'false'; ?>"
					   id="orbi-tab-<?php echo esc_attr( $tab_key ); ?>">
						<?php echo esc_html( $tab_label ); ?>
					</a>
				<?php endforeach; ?>
			</nav>
		</div>
		
		<?php
		// Hook for additional tab content
		do_action( $this->func_slug . '_render_tabs', $tabs, $active_tab );
	}

	/**
	 * Render tab content section
	 *
	 * @since 1.0.0
	 */
	private function render_tab_content() {
		$active_tab = $this->get_active_tab();
		$settings = $this->get_settings();
		$tabs = $this->get_tabs();
		
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" class="orbi-admin__settings-form" id="orbi-settings-form">
			<?php wp_nonce_field( 'orbi_admin_' . $this->slug, 'orbi_nonce' ); ?>
			<input type="hidden" name="action" value="orbi_admin_save_settings_<?php echo esc_attr( $this->slug ); ?>">
			<input type="hidden" name="slug" value="<?php echo esc_attr( $this->slug ); ?>">
			
			<?php foreach ( $tabs as $tab_key => $tab_title ) : ?>
				<div class="orbi-admin__tab-content" 
				     data-tab="<?php echo esc_attr( $tab_key ); ?>"
				     aria-labelledby="orbi-tab-<?php echo esc_attr( $tab_key ); ?>"
				     style="<?php echo $active_tab === $tab_key ? 'display: block;' : 'display: none;'; ?>">
					
					<?php 
					$sections = $this->get_sections( $tab_key );
					$display_mode = $this->get_section_display_mode( $tab_key );
					
					if ( ! empty( $sections ) ) : 
						if ( $display_mode === 'tabs' ) : 
						?>
							<!-- Sub-tabs for sections -->
							<div class="orbi-admin__subtabs-wrapper">
								<nav class="orbi-admin__subtabs-nav">
									<?php 
									$active_section = $this->get_active_section( $tab_key );
									foreach ( $sections as $section_key => $section_title ) : 
									?>
										<a href="#" 
										   class="orbi-admin__subtab-link <?php echo ( $active_tab === $tab_key && $active_section === $section_key ) ? 'orbi-admin__subtab-link--active' : ''; ?>"
										   data-section="<?php echo esc_attr( $section_key ); ?>"
										   role="tab"
										   aria-selected="<?php echo ( $active_tab === $tab_key && $active_section === $section_key ) ? 'true' : 'false'; ?>"
										   id="orbi-subtab-<?php echo esc_attr( $section_key ); ?>">
											<?php echo esc_html( $section_title ); ?>
										</a>
									<?php endforeach; ?>
								</nav>
							</div>
							
							<!-- Section content for tabs mode -->
							<?php foreach ( $sections as $section_key => $section_title ) : ?>
								<div class="orbi-admin__section-content" 
								     data-section="<?php echo esc_attr( $section_key ); ?>"
								     aria-labelledby="orbi-subtab-<?php echo esc_attr( $section_key ); ?>"
								     style="<?php echo ( $active_tab === $tab_key && $active_section === $section_key ) ? 'display: block;' : 'display: none;'; ?>">
									<?php
									if ( isset( $settings[ $tab_key ] ) ) {
										$section_fields = $this->get_section_fields( $settings[ $tab_key ], $section_key );
										if ( ! empty( $section_fields ) ) {
											?>
											<div class="orbi-admin__section-fields">
												<?php
												foreach ( $section_fields as $field ) {
													$this->render_field( $field );
												}
												?>
											</div>
											<?php
										} else {
											$this->render_no_fields_message( $section_title );
										}
									} else {
										$this->render_no_fields_message( $section_title );
									}
									?>
								</div>
							<?php endforeach; ?>
						<?php else : ?>
							<!-- Cards mode: sections as stacked cards -->
							<?php foreach ( $sections as $section_key => $section_title ) : ?>
								<div class="orbi-admin__section-card" data-section="<?php echo esc_attr( $section_key ); ?>">
									<h3 class="orbi-admin__section-title"><?php echo esc_html( $section_title ); ?></h3>
									<?php
									if ( isset( $settings[ $tab_key ] ) ) {
										$section_fields = $this->get_section_fields( $settings[ $tab_key ], $section_key );
										if ( ! empty( $section_fields ) ) {
											?>
											<div class="orbi-admin__section-fields">
												<?php
												foreach ( $section_fields as $field ) {
													$this->render_field( $field );
												}
												?>
											</div>
											<?php
										} else {
											$this->render_no_fields_message( $section_title );
										}
									} else {
										$this->render_no_fields_message( $section_title );
									}
									?>
								</div>
							<?php endforeach; ?>
						<?php endif; ?>
					<?php else : ?>
						<!-- No sections, render all fields directly -->
						<?php
						if ( isset( $settings[ $tab_key ] ) ) {
							?>
							<div class="orbi-admin__section-fields">
								<?php
								foreach ( $settings[ $tab_key ] as $field ) {
									$this->render_field( $field );
								}
								?>
							</div>
							<?php
						}
						?>
					<?php endif; ?>
					
					<?php
					// Hook for custom tab content
					do_action( $this->func_slug . '_render_tab_content', $tab_key );
					?>
				</div>
			<?php endforeach; ?>
			
			<?php submit_button( 'Save Settings' ); ?>
		</form>
		<?php
	}

	/**
	 * Render footer section
	 *
	 * @since 1.0.0
	 */
	private function render_footer() {
		?>
		<p class="orbi-admin__footer-text">
			<?php echo esc_html( sprintf( 'Powered by Orbital Admin Framework v%s', self::VERSION ) ); ?>
		</p>
		
		<?php
		// Hook for additional footer content
		do_action( $this->func_slug . '_render_footer' );
	}

	/**
	 * Render fields for a specific tab
	 *
	 * @since 1.0.0
	 * @param array  $fields Tab fields array.
	 * @param string $tab_key Current tab key.
	 */
	private function render_tab_fields( $fields, $tab_key ) {
		$sections = $this->get_sections( $tab_key );
		
		foreach ( $sections as $section_key => $section_title ) {
			$section_fields = $this->get_section_fields( $fields, $section_key );
			
			if ( empty( $section_fields ) ) {
				continue;
			}
			
			?>
			<div class="orbital-section" data-section="<?php echo esc_attr( $section_key ); ?>">
				<h3 class="orbital-section-title"><?php echo esc_html( $section_title ); ?></h3>
				<div class="orbital-section-fields">
					<?php
					foreach ( $section_fields as $field ) {
						$this->render_field( $field );
					}
					?>
				</div>
			</div>
			<?php
		}
	}

	/**
	 * Get section fields
	 *
	 * @since 1.0.0
	 * @param array  $fields All fields.
	 * @param string $section_key Section key.
	 * @return array Section fields.
	 */
	private function get_section_fields( $fields, $section_key ) {
		return array_filter( $fields, function( $field ) use ( $section_key ) {
			return isset( $field['section'] ) && $field['section'] === $section_key;
		});
	}

	/**
	 * Get fields without sections (for tabs that don't use sub-tabs)
	 *
	 * @since 1.0.0
	 * @param array $fields All fields.
	 * @return array Fields without sections.
	 */
	private function get_fields_without_sections( $fields ) {
		return array_filter( $fields, function( $field ) {
			return ! isset( $field['section'] ) || empty( $field['section'] );
		});
	}

	/**
	 * Render "no fields" message
	 *
	 * @since 1.0.0
	 * @param string $section_title Section title for context.
	 */
	private function render_no_fields_message( $section_title = '' ) {
		?>
		<div class="orbi-admin__no-fields-message">
			<div class="orbi-admin__no-fields-icon">
				<span class="dashicons dashicons-admin-settings"></span>
			</div>
			<h4>No fields configured</h4>
			<p>
				<?php if ( $section_title ) : ?>
					No fields have been added to the "<?php echo esc_html( $section_title ); ?>" section yet.
				<?php else : ?>
					No fields have been configured for this section yet.
				<?php endif; ?>
			</p>
			<p class="orbi-admin__no-fields-help">
				<strong>For developers:</strong> Add fields using the <code><?php echo esc_html( $this->func_slug ); ?>_settings</code> filter.
				<?php if ( $section_title ) : ?>
					Make sure to set <code>'section' => '<?php echo esc_attr( strtolower( str_replace( ' ', '_', $section_title ) ) ); ?>'</code> on your field definitions.
				<?php endif; ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Render individual field
	 *
	 * @since 1.0.0
	 * @param array $field Field configuration.
	 */
	private function render_field( $field ) {
		if ( ! isset( $field['type'] ) || ! isset( $field['id'] ) ) {
			return;
		}

		// Get field value
		$value = $this->get_field_value( $field['id'], $field );

		// Create field instance using registry
		$field_instance = Field_Registry::create_field( $field, $value, $this );

		// Build CSS classes using BEM methodology
		$css_classes = array(
			'field',
			'field--' . esc_attr( $field['type'] )
		);
		
		// Add custom classes if specified
		if ( isset( $field['class'] ) && ! empty( $field['class'] ) ) {
			if ( is_array( $field['class'] ) ) {
				$css_classes = array_merge( $css_classes, $field['class'] );
			} else {
				$custom_classes = explode( ' ', $field['class'] );
				$css_classes = array_merge( $css_classes, $custom_classes );
			}
		}
		
		// Add state modifiers
		if ( isset( $field['required'] ) && $field['required'] ) {
			$css_classes[] = 'field--required';
		}
		
		if ( isset( $field['disabled'] ) && $field['disabled'] ) {
			$css_classes[] = 'field--disabled';
		}
		
		?>
		<div class="<?php echo esc_attr( implode( ' ', array_filter( $css_classes ) ) ); ?>" data-field-id="<?php echo esc_attr( $field['id'] ); ?>" data-field-type="<?php echo esc_attr( $field['type'] ); ?>">
			<?php
			if ( $field_instance ) {
				// Enqueue field-specific assets
				Field_Registry::enqueue_field_assets( $field_instance );
				
				?>
				<div class="field__wrapper">
					<?php
					// For simple fields (text, etc.), render label then input
					if ( ! isset( $field['options'] ) || ! is_array( $field['options'] ) ) {
						$field_instance->render_label();
						echo '<div class="field__input-wrapper">';
						$field_instance->render();
						echo '</div>';
					} else {
						// For grouped fields (checkboxes, radios), the field handles its own structure
						$field_instance->render();
					}
					
					// Always render description at the end
					$field_instance->render_description();
					?>
				</div>
				<?php
			} else {
				// Fallback for unregistered field types
				echo '<p class="field__error">Unknown field type: ' . esc_html( $field['type'] ) . '</p>';
			}
			?>
		</div>
		<?php
	}


	/**
	 * Get field value from database
	 *
	 * @since 1.0.0
	 * @param string $field_id Field ID.
	 * @param array  $field Field configuration.
	 * @return mixed Field value.
	 */
	private function get_field_value( $field_id, $field ) {
		$settings = get_option( $this->slug . '_settings', array() );
		$default = isset( $field['std'] ) ? $field['std'] : '';
		
		return isset( $settings[ $field_id ] ) ? $settings[ $field_id ] : $default;
	}

	/**
	 * Get admin structure
	 *
	 * @since 1.0.0
	 * @return array Admin structure with tabs, sections, and display modes.
	 */
	private function get_admin_structure() {
		return apply_filters( $this->func_slug . '_admin_structure', array() );
	}

	/**
	 * Validate field IDs for uniqueness
	 *
	 * @since 1.0.0
	 * @param array $all_settings All settings configuration.
	 * @return bool True if all IDs are unique, false if duplicates found.
	 */
	private function validate_field_ids( $all_settings ) {
		$field_ids = array();
		$duplicates = array();
		$missing_ids = array();
		
		foreach ( $all_settings as $tab_key => $tab_settings ) {
			if ( ! is_array( $tab_settings ) ) {
				continue;
			}
			
			foreach ( $tab_settings as $field ) {
				if ( ! isset( $field['id'] ) ) {
					$missing_ids[] = $tab_key;
					if ( WP_DEBUG ) {
						error_log( sprintf( 
							'Orbital Framework Warning: Field missing ID in tab "%s". Field: %s', 
							$tab_key,
							print_r( $field, true )
						) );
					}
					continue;
				}
				
				$field_id = $field['id'];
				
				if ( in_array( $field_id, $field_ids ) ) {
					$duplicates[] = $field_id;
				} else {
					$field_ids[] = $field_id;
				}
			}
		}
		
		// Show framework notice for duplicates
		if ( ! empty( $duplicates ) ) {
			$duplicate_list = implode( ', ', array_unique( $duplicates ) );
			$message = sprintf(
				'<strong>Duplicate field IDs detected:</strong> <code>%s</code><br>Each field must have a unique <code>id</code> parameter. Duplicate IDs will cause data conflicts!',
				esc_html( $duplicate_list )
			);
			
			$this->add_notice( $message, 'error', false );
			
			if ( WP_DEBUG ) {
				error_log( sprintf( 
					'Orbital Framework Error: Duplicate field IDs detected in "%s": %s. This will cause data conflicts!', 
					$this->slug,
					$duplicate_list
				) );
			}
		}
		
		return empty( $duplicates );
	}

	/**
	 * Get tabs from admin structure
	 *
	 * @since 1.0.0
	 * @return array Tabs array.
	 */
	private function get_tabs() {
		$structure = $this->get_admin_structure();
		$tabs = array();
		
		foreach ( $structure as $tab_key => $tab_config ) {
			$tabs[ $tab_key ] = isset( $tab_config['title'] ) ? $tab_config['title'] : ucfirst( str_replace( '_', ' ', $tab_key ) );
		}
		
		return $tabs;
	}

	/**
	 * Get sections for a tab
	 *
	 * @since 1.0.0
	 * @param string $tab_key Tab key.
	 * @return array Sections array.
	 */
	private function get_sections( $tab_key ) {
		$structure = $this->get_admin_structure();
		return isset( $structure[ $tab_key ]['sections'] ) ? $structure[ $tab_key ]['sections'] : array();
	}

	/**
	 * Get section display mode for a tab
	 *
	 * @since 1.0.0
	 * @param string $tab_key Tab key.
	 * @return string Display mode: 'tabs' or 'cards'.
	 */
	private function get_section_display_mode( $tab_key ) {
		$structure = $this->get_admin_structure();
		return isset( $structure[ $tab_key ]['display_mode'] ) ? $structure[ $tab_key ]['display_mode'] : 'cards';
	}

	/**
	 * Get settings via filter
	 *
	 * @since 1.0.0
	 * @return array Settings array.
	 */
	private function get_settings() {
		$settings = apply_filters( $this->func_slug . '_settings', array() );
		
		// Validate field IDs for uniqueness only once per request (always validate for demo, or when WP_DEBUG is enabled)
		if ( ! $this->field_ids_validated && ( WP_DEBUG || strpos( $this->slug, 'demo' ) !== false ) ) {
			$this->validate_field_ids( $settings );
			$this->field_ids_validated = true;
		}
		
		return $settings;
	}

	/**
	 * Get currently active tab
	 *
	 * @since 1.0.0
	 * @return string Active tab key.
	 */
	private function get_active_tab() {
		$tabs = $this->get_tabs();
		$active = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : '';
		
		if ( $active && array_key_exists( $active, $tabs ) ) {
			return $active;
		}
		
		// Return first tab as default
		return empty( $tabs ) ? '' : key( $tabs );
	}

	/**
	 * Get current tab (alias for breadcrumbs)
	 *
	 * @since 1.0.0
	 * @return string Current tab key.
	 */
	private function get_current_tab() {
		return $this->get_active_tab();
	}

	/**
	 * Get current section (alias for breadcrumbs)
	 *
	 * @since 1.0.0
	 * @return string Current section key.
	 */
	private function get_current_section() {
		$current_tab = $this->get_current_tab();
		return $this->get_active_section( $current_tab );
	}

	/**
	 * Get currently active section within a tab
	 *
	 * @since 1.0.0
	 * @param string $tab_key Current tab key.
	 * @return string Active section key.
	 */
	private function get_active_section( $tab_key ) {
		$sections = $this->get_sections( $tab_key );
		$active = isset( $_GET['section'] ) ? sanitize_key( $_GET['section'] ) : '';
		
		if ( $active && array_key_exists( $active, $sections ) ) {
			return $active;
		}
		
		// Return first section as default
		return empty( $sections ) ? '' : key( $sections );
	}

	/**
	 * Get tab URL
	 *
	 * @since 1.0.0
	 * @param string $tab_key Tab key.
	 * @return string Tab URL.
	 */
	private function get_tab_url( $tab_key ) {
		return add_query_arg( 
			array( 
				'page' => $this->slug,
				'tab'  => $tab_key,
			),
			admin_url( 'admin.php' )
		);
	}

	/**
	 * Get framework labels
	 *
	 * @since 1.0.0
	 * @return array Labels array.
	 */
	private function get_labels() {
		$defaults = array(
			'save_success' => 'Settings saved successfully!',
			'save_error'   => 'Error saving settings. Please try again.',
			'loading'      => 'Saving...',
		);
		
		return apply_filters( $this->func_slug . '_labels', $defaults );
	}

	/**
	 * Get framework directory URL
	 *
	 * @since 1.0.0
	 * @return string Framework URL.
	 */
	private function get_framework_url() {
		return plugin_dir_url( __FILE__ );
	}
}