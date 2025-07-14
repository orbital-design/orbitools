<?php
/**
 * Radio Field Class
 *
 * Handles rendering and functionality for radio input fields.
 * Supports both single radio button and radio set options.
 *
 * @package    Orbi\AdminKit
 * @subpackage Fields
 * @since      1.0.0
 */

namespace Orbi\AdminKit;

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Radio field implementation
 *
 * @since 1.0.0
 */
class Field_Radio extends Field_Base {

	/**
	 * Render the radio field
	 *
	 * @since 1.0.0
	 */
	public function render() {
		// Check if this is a radio set (has options)
		if ( isset( $this->field['options'] ) && is_array( $this->field['options'] ) ) {
			$this->render_radio_set();
		} else {
			$this->render_single_radio();
		}
	}

	/**
	 * Render single radio button
	 *
	 * @since 1.0.0
	 */
	private function render_single_radio() {
		$checked = ! empty( $this->value );
		
		// Prepare template variables
		$template_vars = array(
			'field'      => $this->field,
			'value'      => $this->value,
			'field_id'   => $this->get_field_id(),
			'field_name' => $this->get_field_name(),
			'input_name' => $this->get_input_name(),
			'checked'    => $checked,
			'attributes' => $this->render_attributes( array( 
				'value' => '1',
				'checked' => $checked
			) ),
		);
		
		$this->render_template( 'single-radio', $template_vars );
	}

	/**
	 * Render radio set
	 *
	 * @since 1.0.0
	 */
	private function render_radio_set() {
		// Prepare template variables
		$template_vars = array(
			'field'      => $this->field,
			'value'      => $this->value,
			'field_id'   => $this->get_field_id(),
			'field_name' => $this->get_field_name(),
			'input_name' => $this->get_input_name(),
			'options'    => $this->field['options'],
		);
		
		$this->render_template( 'multi-radio', $template_vars );
	}

	/**
	 * Render template with variables
	 *
	 * @since 1.0.0
	 * @param string $template_name Template name (without .php extension)
	 * @param array  $template_vars Variables to extract into template scope
	 */
	private function render_template( $template_name, $template_vars = array() ) {
		// Check for custom template first
		if ( isset( $this->field['template'] ) ) {
			$custom_template = $this->field['template'];
			
			// Security: Only allow files within WordPress directories
			$allowed_paths = array(
				ABSPATH,
				WP_CONTENT_DIR,
				get_template_directory(),
				get_stylesheet_directory(),
			);

			$is_allowed = false;
			$template_path = '';
			
			// Handle relative paths by making them relative to the plugin directory
			if ( strpos( $custom_template, '/' ) !== 0 ) {
				$template_path = plugin_dir_path( __FILE__ ) . '../../../' . $custom_template;
			} else {
				$template_path = $custom_template;
			}
			
			$real_path = realpath( $template_path );
			if ( $real_path ) {
				foreach ( $allowed_paths as $allowed_path ) {
					if ( strpos( $real_path, realpath( $allowed_path ) ) === 0 ) {
						$is_allowed = true;
						break;
					}
				}
			}

			if ( $is_allowed && file_exists( $template_path ) ) {
				// Extract variables into template scope
				extract( $template_vars );
				include $template_path;
				return;
			}
		}
		
		// Use default template
		$default_template = plugin_dir_path( __FILE__ ) . 'templates/' . $template_name . '.php';
		if ( file_exists( $default_template ) ) {
			// Extract variables into template scope
			extract( $template_vars );
			include $default_template;
		}
	}

	/**
	 * Sanitize radio field value
	 *
	 * @since 1.0.0
	 * @param mixed $value Value to sanitize.
	 * @return string Sanitized value.
	 */
	public function sanitize( $value ) {
		// Radio set (has options)
		if ( isset( $this->field['options'] ) && is_array( $this->field['options'] ) ) {
			// Ensure the value is one of the allowed options
			return array_key_exists( $value, $this->field['options'] ) ? sanitize_text_field( $value ) : '';
		}

		// Single radio button
		return ! empty( $value ) ? '1' : '';
	}

	/**
	 * Validate radio field value
	 *
	 * @since 1.0.0
	 * @param mixed $value Value to validate.
	 * @return bool|string True if valid, error message if invalid.
	 */
	public function validate( $value ) {
		// Radio set validation
		if ( isset( $this->field['options'] ) && is_array( $this->field['options'] ) ) {
			// Check for required field
			if ( isset( $this->field['required'] ) && $this->field['required'] && empty( $value ) ) {
				return sprintf( 'An option must be selected for %s.', $this->get_field_name() );
			}

			// Validate that value is in allowed options
			if ( ! empty( $value ) && ! array_key_exists( $value, $this->field['options'] ) ) {
				return sprintf( 'Invalid option selected for %s field.', $this->get_field_name() );
			}

			return true;
		}

		// Single radio button validation
		if ( isset( $this->field['required'] ) && $this->field['required'] && empty( $value ) ) {
			return sprintf( 'The %s field must be selected.', $this->get_field_name() );
		}

		return true;
	}
}