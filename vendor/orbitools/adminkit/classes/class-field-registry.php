<?php
/**
 * Field Registry Class
 *
 * Manages registration and instantiation of field types in the OrbiTools AdminKit.
 * Follows the modular approach allowing external field registration.
 *
 * @package    Orbitools\AdminKit
 * @subpackage Fields
 * @since      1.0.0
 */

namespace Orbitools\AdminKit;

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Field Registry Class
 *
 * @since 1.0.0
 */
class Field_Registry {

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
		do_action( 'orbi_register_fields' );
	}

	/**
	 * Register core field types
	 *
	 * @since 1.0.0
	 */
	private static function register_core_fields() {
		// Register core fields
		self::register_field_type( 'text', ORBITOOLS_ADMINKIT_PATH . 'fields/text/class-field-text.php', 'Orbitools\\AdminKit\\Field_Text' );
		self::register_field_type( 'checkbox', ORBITOOLS_ADMINKIT_PATH . 'fields/checkbox/class-field-checkbox.php', 'Orbitools\\AdminKit\\Field_Checkbox' );
		self::register_field_type( 'radio', ORBITOOLS_ADMINKIT_PATH . 'fields/radio/class-field-radio.php', 'Orbitools\\AdminKit\\Field_Radio' );
		self::register_field_type( 'select', ORBITOOLS_ADMINKIT_PATH . 'fields/select/class-field-select.php', 'Orbitools\\AdminKit\\Field_Select' );
		self::register_field_type( 'textarea', ORBITOOLS_ADMINKIT_PATH . 'fields/textarea/class-field-textarea.php', 'Orbitools\\AdminKit\\Field_Textarea' );
		self::register_field_type( 'number', ORBITOOLS_ADMINKIT_PATH . 'fields/number/class-field-number.php', 'Orbitools\\AdminKit\\Field_Number' );
		self::register_field_type( 'html', ORBITOOLS_ADMINKIT_PATH . 'fields/html/class-field-html.php', 'Orbitools\\AdminKit\\Field_Html' );
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
	 * @param Admin_Kit $framework Framework instance.
	 * @return Field_Base|null Field instance or null if type not found.
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