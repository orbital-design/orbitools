<?php
/**
 * Number Field Class
 *
 * Handles rendering and functionality for number input fields.
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
 * Number field implementation
 *
 * @since 1.0.0
 */
class Field_Number extends Field_Base {

	/**
	 * Render the number field input only
	 *
	 * @since 1.0.0
	 */
	public function render() {
		$attributes = array(
			'value' => esc_attr( $this->value )
		);

		// Add min/max/step attributes if defined
		if ( isset( $this->field['min'] ) ) {
			$attributes['min'] = $this->field['min'];
		}
		if ( isset( $this->field['max'] ) ) {
			$attributes['max'] = $this->field['max'];
		}
		if ( isset( $this->field['step'] ) ) {
			$attributes['step'] = $this->field['step'];
		}

		?>
		<input type="number"<?php echo $this->render_attributes( $attributes ); ?>>
		<?php
	}

	/**
	 * Sanitize number field value
	 *
	 * @since 1.0.0
	 * @param mixed $value Value to sanitize.
	 * @return int|float Sanitized value.
	 */
	public function sanitize( $value ) {
		// Check if step indicates decimal numbers
		$step = isset( $this->field['step'] ) ? $this->field['step'] : 1;
		
		if ( $step != 1 && strpos( $step, '.' ) !== false ) {
			return (float) $value;
		}
		
		return (int) $value;
	}

	/**
	 * Validate number field value
	 *
	 * @since 1.0.0
	 * @param mixed $value Value to validate.
	 * @return bool|string True if valid, error message if invalid.
	 */
	public function validate( $value ) {
		// Check for required field
		if ( isset( $this->field['required'] ) && $this->field['required'] && ( $value === '' || $value === null ) ) {
			return sprintf( 'The %s field is required.', $this->get_field_name() );
		}

		// Skip validation if empty and not required
		if ( $value === '' || $value === null ) {
			return true;
		}

		// Check if value is numeric
		if ( ! is_numeric( $value ) ) {
			return sprintf( 'The %s field must be a number.', $this->get_field_name() );
		}

		// Check minimum value
		if ( isset( $this->field['min'] ) && $value < $this->field['min'] ) {
			return sprintf( 
				'The %s field must be at least %s.', 
				$this->get_field_name(), 
				$this->field['min'] 
			);
		}

		// Check maximum value
		if ( isset( $this->field['max'] ) && $value > $this->field['max'] ) {
			return sprintf( 
				'The %s field must be no more than %s.', 
				$this->get_field_name(), 
				$this->field['max'] 
			);
		}

		return true;
	}
}