<?php
/**
 * Textarea Field Class
 *
 * Handles rendering and functionality for textarea fields.
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
 * Textarea field implementation
 *
 * @since 1.0.0
 */
class Orbital_Field_Textarea extends Orbital_Field_Base {

	/**
	 * Render the textarea field
	 *
	 * @since 1.0.0
	 */
	public function render() {
		$this->render_label();
		
		$rows = isset( $this->field['rows'] ) ? $this->field['rows'] : 5;
		$cols = isset( $this->field['cols'] ) ? $this->field['cols'] : 50;
		
		?>
		<textarea<?php echo $this->render_attributes( array( 
			'rows' => $rows,
			'cols' => $cols,
			'class' => 'large-text'
		) ); ?>><?php echo esc_textarea( $this->value ); ?></textarea>
		<?php
		$this->render_description();
	}

	/**
	 * Sanitize textarea field value
	 *
	 * @since 1.0.0
	 * @param mixed $value Value to sanitize.
	 * @return string Sanitized value.
	 */
	public function sanitize( $value ) {
		// Allow HTML if specified
		if ( isset( $this->field['allow_html'] ) && $this->field['allow_html'] ) {
			return wp_kses_post( $value );
		}
		
		return sanitize_textarea_field( $value );
	}

	/**
	 * Validate textarea field value
	 *
	 * @since 1.0.0
	 * @param mixed $value Value to validate.
	 * @return bool|string True if valid, error message if invalid.
	 */
	public function validate( $value ) {
		// Check for required field
		if ( isset( $this->field['required'] ) && $this->field['required'] && empty( trim( $value ) ) ) {
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