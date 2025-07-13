<?php
/**
 * Field Registry Class
 *
 * Manages registration and instantiation of field types in the Orbital Admin Framework.
 * Follows the modular approach similar to Kirki's field system.
 *
 * @package    Orbital_Admin_Framework
 * @subpackage Fields
 * @since      1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Field Registry Class
 *
 * @since 1.0.0
 */
class Orbital_Field_Registry {

	/**
	 * Registered field types
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private static $field_types = array();

	/**
	 * Assets that have been enqueued
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private static $enqueued_assets = array();

	/**
	 * Initialize the field registry
	 *
	 * @since 1.0.0
	 */
	public static function init() {
		self::register_core_fields();
		do_action( 'orbital_register_fields' );
	}

	/**
	 * Register core field types
	 *
	 * @since 1.0.0
	 */
	private static function register_core_fields() {
		$framework_path = dirname( __FILE__ );
		
		// Register core fields
		self::register_field_type( 'text', $framework_path . '/fields/text/class-orbital-field-text.php', 'Orbital_Field_Text' );
		self::register_field_type( 'checkbox', $framework_path . '/fields/checkbox/class-orbital-field-checkbox.php', 'Orbital_Field_Checkbox' );
		self::register_field_type( 'radio', $framework_path . '/fields/radio/class-orbital-field-radio.php', 'Orbital_Field_Radio' );
		self::register_field_type( 'select', $framework_path . '/fields/select/class-orbital-field-select.php', 'Orbital_Field_Select' );
		self::register_field_type( 'textarea', $framework_path . '/fields/textarea/class-orbital-field-textarea.php', 'Orbital_Field_Textarea' );
		self::register_field_type( 'number', $framework_path . '/fields/number/class-orbital-field-number.php', 'Orbital_Field_Number' );
	}

	/**
	 * Register a field type
	 *
	 * @since 1.0.0
	 * @param string $type Field type identifier.
	 * @param string $file_path Path to the field class file.
	 * @param string $class_name Field class name.
	 * @param array  $args Additional arguments.
	 */
	public static function register_field_type( $type, $file_path, $class_name, $args = array() ) {
		self::$field_types[ $type ] = array(
			'file_path'  => $file_path,
			'class_name' => $class_name,
			'args'       => $args,
		);
	}

	/**
	 * Create a field instance
	 *
	 * @since 1.0.0
	 * @param array $field Field configuration.
	 * @param mixed $value Field value.
	 * @param Orbital_Admin_Framework $framework Framework instance.
	 * @return Orbital_Field_Base|null Field instance or null if type not found.
	 */
	public static function create_field( $field, $value, $framework ) {
		if ( ! isset( $field['type'] ) ) {
			return null;
		}

		$type = $field['type'];

		// Check if field type is registered
		if ( ! isset( self::$field_types[ $type ] ) ) {
			return null;
		}

		$field_info = self::$field_types[ $type ];

		// Load the field class file
		if ( ! class_exists( $field_info['class_name'] ) ) {
			if ( file_exists( $field_info['file_path'] ) ) {
				require_once $field_info['file_path'];
			} else {
				return null;
			}
		}

		// Check if class exists after loading
		if ( ! class_exists( $field_info['class_name'] ) ) {
			return null;
		}

		// Create and return field instance
		return new $field_info['class_name']( $field, $value, $framework );
	}

	/**
	 * Get all registered field types
	 *
	 * @since 1.0.0
	 * @return array Registered field types.
	 */
	public static function get_field_types() {
		return self::$field_types;
	}

	/**
	 * Check if a field type is registered
	 *
	 * @since 1.0.0
	 * @param string $type Field type to check.
	 * @return bool True if registered, false otherwise.
	 */
	public static function is_field_type_registered( $type ) {
		return isset( self::$field_types[ $type ] );
	}

	/**
	 * Enqueue field assets
	 *
	 * @since 1.0.0
	 * @param Orbital_Field_Base $field Field instance.
	 */
	public static function enqueue_field_assets( $field ) {
		$assets = $field->get_assets();
		$field_type = get_class( $field );

		// Avoid enqueueing same assets multiple times
		if ( in_array( $field_type, self::$enqueued_assets ) ) {
			return;
		}

		foreach ( $assets as $asset ) {
			if ( isset( $asset['type'] ) && isset( $asset['handle'] ) && isset( $asset['src'] ) ) {
				if ( $asset['type'] === 'css' ) {
					wp_enqueue_style(
						$asset['handle'],
						$asset['src'],
						isset( $asset['deps'] ) ? $asset['deps'] : array(),
						isset( $asset['version'] ) ? $asset['version'] : '1.0.0'
					);
				} elseif ( $asset['type'] === 'js' ) {
					wp_enqueue_script(
						$asset['handle'],
						$asset['src'],
						isset( $asset['deps'] ) ? $asset['deps'] : array(),
						isset( $asset['version'] ) ? $asset['version'] : '1.0.0',
						isset( $asset['in_footer'] ) ? $asset['in_footer'] : true
					);
				}
			}
		}

		self::$enqueued_assets[] = $field_type;
	}

	/**
	 * Sanitize field value using the appropriate field type
	 *
	 * @since 1.0.0
	 * @param array $field Field configuration.
	 * @param mixed $value Value to sanitize.
	 * @param Orbital_Admin_Framework $framework Framework instance.
	 * @return mixed Sanitized value.
	 */
	public static function sanitize_field_value( $field, $value, $framework ) {
		$field_instance = self::create_field( $field, $value, $framework );
		
		if ( $field_instance ) {
			return $field_instance->sanitize( $value );
		}

		// Fallback sanitization
		return sanitize_text_field( $value );
	}

	/**
	 * Validate field value using the appropriate field type
	 *
	 * @since 1.0.0
	 * @param array $field Field configuration.
	 * @param mixed $value Value to validate.
	 * @param Orbital_Admin_Framework $framework Framework instance.
	 * @return bool|string True if valid, error message if invalid.
	 */
	public static function validate_field_value( $field, $value, $framework ) {
		$field_instance = self::create_field( $field, $value, $framework );
		
		if ( $field_instance ) {
			return $field_instance->validate( $value );
		}

		return true;
	}
}