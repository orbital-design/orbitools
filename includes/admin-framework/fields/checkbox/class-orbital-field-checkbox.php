<?php
/**
 * Checkbox Field Class
 *
 * Handles rendering and functionality for checkbox input fields.
 * Supports both single checkbox and multiple checkbox options.
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
		// Check if this is a multi-checkbox (has options)
		if ( isset( $this->field['options'] ) && is_array( $this->field['options'] ) ) {
			$this->render_multi_checkbox();
		} else {
			$this->render_single_checkbox();
		}
		
		$this->render_description();
	}

	/**
	 * Render single checkbox
	 *
	 * @since 1.0.0
	 */
	private function render_single_checkbox() {
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
	}

	/**
	 * Render multiple checkboxes
	 *
	 * @since 1.0.0
	 */
	private function render_multi_checkbox() {
		$this->render_label();
		
		// Ensure value is an array
		$values = is_array( $this->value ) ? $this->value : array();
		
		?>
		<div class="orbital-multicheckbox-wrapper">
			<?php foreach ( $this->field['options'] as $option_value => $option_label ) : ?>
				<label class="orbital-multicheckbox-option">
					<input type="checkbox" 
					       name="<?php echo esc_attr( $this->get_input_name() ); ?>[]" 
					       value="<?php echo esc_attr( $option_value ); ?>"
					       <?php checked( in_array( $option_value, $values ) ); ?>>
					<span class="orbital-checkbox-label"><?php echo esc_html( $option_label ); ?></span>
				</label>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Sanitize checkbox field value
	 *
	 * @since 1.0.0
	 * @param mixed $value Value to sanitize.
	 * @return string|array Sanitized value.
	 */
	public function sanitize( $value ) {
		// Multi-checkbox (has options)
		if ( isset( $this->field['options'] ) && is_array( $this->field['options'] ) ) {
			if ( ! is_array( $value ) ) {
				return array();
			}

			$sanitized = array();
			$valid_options = array_keys( $this->field['options'] );

			foreach ( $value as $item ) {
				$sanitized_item = sanitize_text_field( $item );
				// Only include valid options
				if ( in_array( $sanitized_item, $valid_options ) ) {
					$sanitized[] = $sanitized_item;
				}
			}

			return $sanitized;
		}

		// Single checkbox
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
		// Multi-checkbox validation
		if ( isset( $this->field['options'] ) && is_array( $this->field['options'] ) ) {
			// Ensure value is an array
			if ( ! is_array( $value ) ) {
				$value = array();
			}

			// Check for required field
			if ( isset( $this->field['required'] ) && $this->field['required'] && empty( $value ) ) {
				return sprintf( 'At least one option must be selected for %s.', $this->get_field_name() );
			}

			// Check minimum selections
			if ( isset( $this->field['min_selections'] ) && count( $value ) < $this->field['min_selections'] ) {
				return sprintf( 
					'At least %d option(s) must be selected for %s.', 
					$this->field['min_selections'], 
					$this->get_field_name() 
				);
			}

			// Check maximum selections
			if ( isset( $this->field['max_selections'] ) && count( $value ) > $this->field['max_selections'] ) {
				return sprintf( 
					'No more than %d option(s) can be selected for %s.', 
					$this->field['max_selections'], 
					$this->get_field_name() 
				);
			}

			// Validate that all values are in allowed options
			$valid_options = array_keys( $this->field['options'] );
			foreach ( $value as $item ) {
				if ( ! in_array( $item, $valid_options ) ) {
					return sprintf( 'Invalid option selected for %s field.', $this->get_field_name() );
				}
			}

			return true;
		}

		// Single checkbox validation
		if ( isset( $this->field['required'] ) && $this->field['required'] && empty( $value ) ) {
			return sprintf( 'The %s field must be checked.', $this->get_field_name() );
		}

		return true;
	}
}