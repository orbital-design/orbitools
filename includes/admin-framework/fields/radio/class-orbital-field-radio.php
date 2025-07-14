<?php
/**
 * Radio Field Class
 *
 * Handles rendering and functionality for radio input fields.
 * Supports both single radio button and radio set options.
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
 * Radio field implementation
 *
 * @since 1.0.0
 */
class Orbital_Field_Radio extends Orbital_Field_Base {

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
		?>
		<label for="<?php echo esc_attr( $this->get_field_id() ); ?>" class="field__radio-label">
			<input type="radio"<?php echo $this->render_attributes( array( 
				'value' => '1',
				'checked' => $checked
			) ); ?>>
			<span class="field__radio-custom" aria-hidden="true">
				<span class="field__radio-indicator">
					<span class="field__radio-dot"></span>
				</span>
			</span>
			<span class="field__radio-text"><?php echo esc_html( $this->get_field_name() ); ?></span>
		</label>
		<?php
	}

	/**
	 * Render radio set
	 *
	 * @since 1.0.0
	 */
	private function render_radio_set() {
		?>
		<fieldset class="field__fieldset" aria-describedby="<?php echo esc_attr( $this->get_field_id() ); ?>-description">
			<legend class="field__legend"><?php echo esc_html( $this->get_field_name() ); ?></legend>
			<div class="field__radio-group">
				<?php foreach ( $this->field['options'] as $option_value => $option_label ) : ?>
					<label class="field__radio-option" for="<?php echo esc_attr( $this->get_field_id() . '_' . $option_value ); ?>">
						<input type="radio" 
						       class="field__input field__input--radio"
						       name="<?php echo esc_attr( $this->get_input_name() ); ?>" 
						       id="<?php echo esc_attr( $this->get_field_id() . '_' . $option_value ); ?>"
						       value="<?php echo esc_attr( $option_value ); ?>"
						       <?php checked( $this->value, $option_value ); ?>
						       <?php if ( isset( $this->field['required'] ) && $this->field['required'] ) echo 'required aria-required="true"'; ?>>
						<span class="field__radio-custom" aria-hidden="true">
							<span class="field__radio-indicator">
								<span class="field__radio-dot"></span>
							</span>
						</span>
						<span class="field__radio-text"><?php echo esc_html( $option_label ); ?></span>
					</label>
				<?php endforeach; ?>
			</div>
		</fieldset>
		<?php
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