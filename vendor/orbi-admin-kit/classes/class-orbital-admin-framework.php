<?php
/**
 * OrbiTools AdminKit (Refactored)
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
	private $page_title = '';

	/**
	 * Page description
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $page_description = '';

	/**
	 * Page header image URL
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $page_header_image = '';

	/**
	 * Page header background color
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $page_header_bg_color = '';

	/**
	 * Hide title and description visually
	 *
	 * @since 1.0.0
	 * @var bool
	 */
	private $hide_title_description = false;

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

		// Set all default values
		$this->page_title = 'AdminKit';
		$this->page_description = 'Extensible modular admin framework by Orbital';
		$this->page_header_image = $this->get_framework_url() . 'assets/orbi-logo.svg';
		$this->page_header_bg_color = '#32A3E2';
		$this->hide_title_description = false;
		
		// Set default menu configuration
		$this->menu_config = array(
			'parent'     => 'options-general.php',
			'page_title' => 'Settings',
			'menu_title' => 'Settings',
			'capability' => 'manage_options',
		);

	}

	/**
	 * Initialize framework hooks
	 *
	 * @since 1.0.0
	 */
	private function init_hooks() {
		add_action( 'admin_menu', array( $this, 'add_admin_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'wp_ajax_orbi_admin_save_settings_' . $this->slug, array( $this, 'ajax_save_settings' ) );
		
		// Always add global header after admin bar but before #wpbody
		add_action( 'in_admin_header', array( $this, 'render_global_header' ) );
	}

	/**
	 * Initialize AdminKit with configuration array
	 *
	 * @since 1.0.0
	 * @param array $config Configuration array.
	 * @return Admin_Kit Returns self for method chaining.
	 */
	public function init( $config = array() ) {
		if ( isset( $config['title'] ) ) {
			$this->set_page_title( $config['title'] );
		}
		if ( isset( $config['description'] ) ) {
			$this->set_page_description( $config['description'] );
		}
		if ( isset( $config['header_image'] ) ) {
			$this->set_page_header_image( $config['header_image'] );
		}
		if ( isset( $config['header_bg_color'] ) ) {
			$this->set_page_header_bg_color( $config['header_bg_color'] );
		}
		if ( isset( $config['hide_title_description'] ) ) {
			$this->set_hide_title_description( $config['hide_title_description'] );
		}
		if ( isset( $config['menu'] ) ) {
			$this->set_menu_config( $config['menu'] );
		}

		$this->init_hooks();
		return $this;
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
	 * Set page header image
	 *
	 * @since 1.0.0
	 * @param string $image_url Header image URL.
	 */
	public function set_page_header_image( $image_url ) {
		$this->page_header_image = $image_url;
	}

	/**
	 * Set page header background color
	 *
	 * @since 1.0.0
	 * @param string $color Header background color (any valid CSS color value).
	 */
	public function set_page_header_bg_color( $color ) {
		$this->page_header_bg_color = $color;
	}

	/**
	 * Set whether to hide title and description visually
	 *
	 * @since 1.0.0
	 * @param bool $hide Whether to hide title and description visually.
	 */
	public function set_hide_title_description( $hide ) {
		$this->hide_title_description = (bool) $hide;
	}

	/**
	 * Set menu configuration
	 *
	 * @since 1.0.0
	 * @param array $config Menu configuration array.
	 */
	public function set_menu_config( $config ) {
		$this->menu_config = array_merge( $this->menu_config, $config );
	}

	/**
	 * Add a notice
	 *
	 * @since 1.0.0
	 * @param string $message Notice message.
	 * @param string $type Notice type (success, error, warning, info).
	 * @param bool   $dismissible Whether notice is dismissible.
	 */
	public function add_notice( $message, $type = 'info', $dismissible = true ) {
		$notice_manager = new \Orbi\AdminKit\Views\Notice_Manager( $this );
		$notice_manager->add_notice( $message, $type, $dismissible );
	}

	/**
	 * Render admin page using page builder
	 *
	 * @since 1.0.0
	 */
	public function render_admin_page() {
		$page_builder = new \Orbi\AdminKit\Classes\Page_Builder( $this );
		$page_builder->build_page();
	}

	/**
	 * Render global header using page builder
	 *
	 * @since 1.0.0
	 */
	public function render_global_header() {
		$page_builder = new \Orbi\AdminKit\Classes\Page_Builder( $this );
		$page_builder->build_global_header();
	}

	// Public getter methods for view components to access private properties

	/**
	 * Get framework slug
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_slug() {
		return $this->slug;
	}

	/**
	 * Get framework function slug
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_func_slug() {
		return $this->func_slug;
	}

	/**
	 * Get page title
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_page_title() {
		return $this->page_title;
	}

	/**
	 * Get page description
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_page_description() {
		return $this->page_description;
	}

	/**
	 * Get page header image
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_page_header_image() {
		return $this->page_header_image;
	}

	/**
	 * Get page header background color
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_page_header_bg_color() {
		return $this->page_header_bg_color;
	}

	/**
	 * Get hide title description setting
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function get_hide_title_description() {
		return $this->hide_title_description;
	}

	/**
	 * Get framework version
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_version() {
		return self::VERSION;
	}

	/**
	 * Get framework URL
	 *
	 * @since 1.0.0
	 * @return string Framework URL.
	 */
	public function get_framework_url() {
		return ORBI_ADMIN_KIT_URL;
	}


	// The following methods are preserved from the original implementation
	// to maintain full functionality. They handle WordPress admin integration,
	// settings management, and data processing.

	/**
	 * Add admin page to WordPress admin menu
	 *
	 * @since 1.0.0
	 */
	public function add_admin_page() {
		$parent = isset( $this->menu_config['parent'] ) ? $this->menu_config['parent'] : 'options-general.php';
		
		add_submenu_page(
			$parent,
			$this->page_title,
			$this->page_title,
			$this->menu_config['capability'],
			$this->slug,
			array( $this, 'render_admin_page' )
		);
	}

	/**
	 * Enqueue framework assets
	 *
	 * @since 1.0.0
	 * @param string $hook_suffix Current admin page hook suffix.
	 */
	public function enqueue_assets( $hook_suffix ) {
		// Only enqueue on our admin page
		if ( strpos( $hook_suffix, $this->slug ) === false ) {
			return;
		}

		// Enqueue styles
		wp_enqueue_style(
			'orbi-admin-kit',
			$this->get_framework_url() . 'assets/admin-framework.css',
			array(),
			self::VERSION
		);

		// Enqueue scripts
		wp_enqueue_script(
			'orbi-admin-kit',
			$this->get_framework_url() . 'assets/admin-framework.js',
			array( 'jquery' ),
			self::VERSION,
			true
		);

		// Localize script
		wp_localize_script( 'orbi-admin-kit', 'orbiAdminKit', array(
			'slug' => $this->slug,
			'nonce' => wp_create_nonce( 'orbi_admin_' . $this->slug ),
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'strings' => array(
				'saving' => __( 'Saving...', 'orbi-admin-kit' ),
				'saved' => __( 'Settings saved!', 'orbi-admin-kit' ),
				'error' => __( 'Error saving settings. Please try again.', 'orbi-admin-kit' ),
			)
		) );

		// Hook for additional assets
		do_action( $this->func_slug . '_enqueue_assets', $hook_suffix );
	}

	/**
	 * Register settings with WordPress
	 *
	 * @since 1.0.0
	 */
	public function register_settings() {
		register_setting( $this->slug . '_settings', $this->slug . '_settings', array(
			'sanitize_callback' => array( $this, 'sanitize_settings_data' ),
		) );
	}

	/**
	 * Sanitize settings data
	 *
	 * @since 1.0.0
	 * @param array $input Raw input data.
	 * @return array Sanitized data.
	 */
	public function sanitize_settings_data( $input ) {
		$sanitized = array();
		$settings = $this->get_settings();

		// Flatten settings array to get all field configurations
		$all_fields = array();
		foreach ( $settings as $tab_fields ) {
			$all_fields = array_merge( $all_fields, $tab_fields );
		}

		foreach ( $all_fields as $field ) {
			if ( ! isset( $field['id'] ) ) {
				continue;
			}

			$field_id = $field['id'];
			$field_value = isset( $input[ $field_id ] ) ? $input[ $field_id ] : '';

			// Apply field-specific sanitization
			$sanitized[ $field_id ] = $this->sanitize_setting( $field_value, $field );
		}

		return apply_filters( $this->func_slug . '_pre_save_settings', $sanitized );
	}

	/**
	 * Sanitize individual setting
	 *
	 * @since 1.0.0
	 * @param mixed $value Field value.
	 * @param array $field Field configuration.
	 * @return mixed Sanitized value.
	 */
	private function sanitize_setting( $value, $field ) {
		// Allow custom sanitization per field
		$sanitized = apply_filters( $this->func_slug . '_sanitize_setting', $value, $field );
		
		// If custom sanitization was applied, return it
		if ( $sanitized !== $value ) {
			return $sanitized;
		}

		// Default sanitization based on field type
		switch ( $field['type'] ) {
			case 'text':
			case 'textarea':
				return sanitize_text_field( $value );
			case 'email':
				return sanitize_email( $value );
			case 'url':
				return esc_url_raw( $value );
			case 'number':
				return intval( $value );
			case 'checkbox':
				return $value ? 1 : 0;
			case 'select':
			case 'radio':
				$options = isset( $field['options'] ) ? $field['options'] : array();
				return array_key_exists( $value, $options ) ? $value : '';
			default:
				return sanitize_text_field( $value );
		}
	}

	/**
	 * AJAX handler for saving settings
	 *
	 * @since 1.0.0
	 */
	public function ajax_save_settings() {
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['orbi_nonce'], 'orbi_admin_' . $this->slug ) ) {
			wp_send_json_error( 'Invalid nonce' );
		}

		// Check capabilities
		if ( ! current_user_can( $this->menu_config['capability'] ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}

		// Process settings
		$settings_data = isset( $_POST['settings'] ) ? json_decode( stripslashes( $_POST['settings'] ), true ) : array();
		
		if ( ! is_array( $settings_data ) ) {
			$settings_data = array();
		}

		// Save settings
		$result = $this->save_settings( $settings_data );

		if ( $result ) {
			wp_send_json_success( array(
				'message' => 'Settings saved successfully'
			) );
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
		// Sanitize data
		$sanitized_data = $this->sanitize_settings_data( $settings_data );
		
		// Save to database
		$result = update_option( $this->slug . '_settings', $sanitized_data );
		
		// Trigger post-save action
		do_action( $this->func_slug . '_post_save_settings', $sanitized_data, $result );
		
		return $result;
	}

	// Data access methods that are used by view components

	/**
	 * Get admin structure
	 *
	 * @since 1.0.0
	 * @return array Admin structure.
	 */
	public function get_admin_structure() {
		return apply_filters( $this->func_slug . '_admin_structure', array() );
	}

	/**
	 * Get tabs
	 *
	 * @since 1.0.0
	 * @return array Tabs array.
	 */
	public function get_tabs() {
		$structure = $this->get_admin_structure();
		$tabs = array();

		foreach ( $structure as $tab_key => $tab_config ) {
			$tabs[ $tab_key ] = isset( $tab_config['title'] ) ? $tab_config['title'] : ucfirst( $tab_key );
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
	public function get_sections( $tab_key ) {
		$structure = $this->get_admin_structure();
		return isset( $structure[ $tab_key ]['sections'] ) ? $structure[ $tab_key ]['sections'] : array();
	}

	/**
	 * Get section display mode
	 *
	 * @since 1.0.0
	 * @param string $tab_key Tab key.
	 * @return string Display mode (tabs or cards).
	 */
	public function get_section_display_mode( $tab_key ) {
		$structure = $this->get_admin_structure();
		return isset( $structure[ $tab_key ]['display_mode'] ) ? $structure[ $tab_key ]['display_mode'] : 'cards';
	}

	/**
	 * Get settings configuration
	 *
	 * @since 1.0.0
	 * @return array Settings configuration.
	 */
	public function get_settings() {
		return apply_filters( $this->func_slug . '_settings', array() );
	}

	/**
	 * Get active tab
	 *
	 * @since 1.0.0
	 * @return string Active tab key.
	 */
	public function get_active_tab() {
		$tabs = $this->get_tabs();
		$current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : '';
		
		// If no tab specified or invalid tab, use first tab
		if ( empty( $current_tab ) || ! array_key_exists( $current_tab, $tabs ) ) {
			$current_tab = ! empty( $tabs ) ? key( $tabs ) : '';
		}
		
		return $current_tab;
	}

	/**
	 * Get current tab
	 *
	 * @since 1.0.0
	 * @return string Current tab key.
	 */
	public function get_current_tab() {
		return $this->get_active_tab();
	}

	/**
	 * Get current section
	 *
	 * @since 1.0.0
	 * @return string Current section key.
	 */
	public function get_current_section() {
		$current_tab = $this->get_current_tab();
		return $this->get_active_section( $current_tab );
	}

	/**
	 * Get active section for a tab
	 *
	 * @since 1.0.0
	 * @param string $tab_key Tab key.
	 * @return string Active section key.
	 */
	public function get_active_section( $tab_key ) {
		$sections = $this->get_sections( $tab_key );
		$current_section = isset( $_GET['section'] ) ? sanitize_text_field( $_GET['section'] ) : '';
		
		// If no section specified or invalid section, use first section
		if ( empty( $current_section ) || ! array_key_exists( $current_section, $sections ) ) {
			$current_section = ! empty( $sections ) ? key( $sections ) : '';
		}
		
		return $current_section;
	}

	/**
	 * Get tab URL
	 *
	 * @since 1.0.0
	 * @param string $tab_key Tab key.
	 * @return string Tab URL.
	 */
	public function get_tab_url( $tab_key ) {
		$base_url = admin_url( 'admin.php' );
		$parent = isset( $this->menu_config['parent'] ) ? $this->menu_config['parent'] : 'options-general.php';
		
		if ( $parent === 'options-general.php' ) {
			$base_url = admin_url( 'options-general.php' );
		}
		
		return add_query_arg( array(
			'page' => $this->slug,
			'tab' => $tab_key,
		), $base_url );
	}

	/**
	 * Get field value
	 *
	 * @since 1.0.0
	 * @param string $field_id Field ID.
	 * @param mixed  $default Default value.
	 * @return mixed Field value.
	 */
	public function get_field_value( $field_id, $default = '' ) {
		$settings = get_option( $this->slug . '_settings', array() );
		return isset( $settings[ $field_id ] ) ? $settings[ $field_id ] : $default;
	}

	/**
	 * Render individual field (preserved for backward compatibility)
	 *
	 * @since 1.0.0
	 * @param array $field Field configuration.
	 */
	public function render_field( $field ) {
		if ( ! isset( $field['type'] ) || ! isset( $field['id'] ) ) {
			return;
		}

		// Get field value
		$default = isset( $field['std'] ) ? $field['std'] : '';
		$value = $this->get_field_value( $field['id'], $default );

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

}