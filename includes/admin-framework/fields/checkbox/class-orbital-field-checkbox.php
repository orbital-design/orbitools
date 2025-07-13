<?php
/**
 * Checkbox Field Class
 *
 * Handles rendering and functionality for checkbox input fields.
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
 * Checkbox field implementation
 *
 * @since 1.0.0
 */
class Orbital_Field_Checkbox extends Orbital_Field_Base {

	/**
	 * Render the checkbox field
	 *
	 * @since 1.0.0
	 */
	public function render() {
		$checked = ! empty( $this->value );
		?>
		<label for="<?php echo esc_attr( $this->get_field_id() ); ?>" class="orbital-checkbox-label">
			<input type="checkbox"<?php echo $this->render_attributes( array( 
				'value' => '1',
				'checked' => $checked
			) ); ?>>
			<span class="orbital-field-label"><?php echo esc_html( $this->get_field_name() ); ?></span>
		</label>
		<?php
		$this->render_description();
	}

	/**
	 * Sanitize checkbox field value
	 *
	 * @since 1.0.0
	 * @param mixed $value Value to sanitize.
	 * @return string Sanitized value.
	 */
	public function sanitize( $value ) {
		return ! empty( $value ) ? '1' : '';
	}

	/**
	 * Validate checkbox field value
	 *
	 * @since 1.0.0
	 * @param mixed $value Value to validate.
	 * @return bool|string True if valid, error message if invalid.
	 */
	public function validate( $value ) {
		// Check if checkbox is required to be checked
		if ( isset( $this->field['required'] ) && $this->field['required'] && empty( $value ) ) {
			return sprintf( 'The %s field must be checked.', $this->get_field_name() );
		}

		return true;
	}
}