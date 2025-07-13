<?php
/**
 * Text Field Class
 *
 * Handles rendering and functionality for text input fields.
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
 * Text field implementation
 *
 * @since 1.0.0
 */
class Orbital_Field_Text extends Orbital_Field_Base {

	/**
	 * Render the text field
	 *
	 * @since 1.0.0
	 */
	public function render() {
		$this->render_label();
		?>
		<input type="text"<?php echo $this->render_attributes( array( 
			'value' => esc_attr( $this->value ),
			'class' => 'regular-text'
		) ); ?>>
		<?php
		$this->render_description();
	}

	/**
	 * Sanitize text field value
	 *
	 * @since 1.0.0
	 * @param mixed $value Value to sanitize.
	 * @return string Sanitized value.
	 */
	public function sanitize( $value ) {
		return sanitize_text_field( $value );
	}

	/**
	 * Validate text field value
	 *
	 * @since 1.0.0
	 * @param mixed $value Value to validate.
	 * @return bool|string True if valid, error message if invalid.
	 */
	public function validate( $value ) {
		// Check for required field
		if ( isset( $this->field['required'] ) && $this->field['required'] && empty( $value ) ) {
			return sprintf( 'The %s field is required.', $this->get_field_name() );
		}

		// Check minimum length
		if ( isset( $this->field['min_length'] ) && strlen( $value ) < $this->field['min_length'] ) {
			return sprintf( 
				'The %s field must be at least %d characters long.', 
				$this->get_field_name(), 
				$this->field['min_length'] 
			);
		}

		// Check maximum length
		if ( isset( $this->field['max_length'] ) && strlen( $value ) > $this->field['max_length'] ) {
			return sprintf( 
				'The %s field must be no more than %d characters long.', 
				$this->get_field_name(), 
				$this->field['max_length'] 
			);
		}

		return true;
	}
}