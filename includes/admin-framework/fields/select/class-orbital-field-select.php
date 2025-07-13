<?php
/**
 * Select Field Class
 *
 * Handles rendering and functionality for select dropdown fields.
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
 * Select field implementation
 *
 * @since 1.0.0
 */
class Orbital_Field_Select extends Orbital_Field_Base {

	/**
	 * Render the select field input only
	 *
	 * @since 1.0.0
	 */
	public function render() {
		if ( ! isset( $this->field['options'] ) || ! is_array( $this->field['options'] ) ) {
			echo '<p class="field__error">Select field requires options array.</p>';
			return;
		}

		?>
		<select<?php echo $this->render_attributes(); ?>>
			<?php $this->render_options(); ?>
		</select>
		<?php
	}

	/**
	 * Render select options
	 *
	 * @since 1.0.0
	 */
	private function render_options() {
		// Add empty option if placeholder is set
		if ( isset( $this->field['placeholder'] ) ) {
			?>
			<option value=""><?php echo esc_html( $this->field['placeholder'] ); ?></option>
			<?php
		}

		foreach ( $this->field['options'] as $option_value => $option_label ) {
			?>
			<option value="<?php echo esc_attr( $option_value ); ?>" 
			        <?php selected( $this->value, $option_value ); ?>>
				<?php echo esc_html( $option_label ); ?>
			</option>
			<?php
		}
	}

	/**
	 * Sanitize select field value
	 *
	 * @since 1.0.0
	 * @param mixed $value Value to sanitize.
	 * @return string Sanitized value.
	 */
	public function sanitize( $value ) {
		// Ensure the value is one of the allowed options
		if ( isset( $this->field['options'] ) && is_array( $this->field['options'] ) ) {
			return array_key_exists( $value, $this->field['options'] ) ? $value : '';
		}
		
		return sanitize_text_field( $value );
	}

	/**
	 * Validate select field value
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

		// Validate that value is in allowed options
		if ( ! empty( $value ) && isset( $this->field['options'] ) && is_array( $this->field['options'] ) ) {
			if ( ! array_key_exists( $value, $this->field['options'] ) ) {
				return sprintf( 'Invalid value selected for %s field.', $this->get_field_name() );
			}
		}

		return true;
	}
}