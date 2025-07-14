<?php
/**
 * Multi Checkbox Template
 *
 * Template for rendering multiple checkbox options.
 * 
 * Available variables:
 * @var array  $field       - Field configuration array
 * @var array  $values      - Current field values (array)
 * @var string $field_id    - Generated field ID
 * @var string $field_name  - Field display name
 * @var string $input_name  - Input name attribute
 * @var array  $options     - Available options array
 * 
 * @package    Orbital_Admin_Framework
 * @subpackage Field_Templates
 * @since      1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<fieldset class="field__fieldset" aria-describedby="<?php echo esc_attr( $field_id ); ?>-description">
	<legend class="field__legend"><?php echo esc_html( $field_name ); ?></legend>
	<div class="field__checkbox-group">
		<?php foreach ( $options as $option_value => $option_label ) : ?>
			<label class="field__checkbox-option" for="<?php echo esc_attr( $field_id . '_' . $option_value ); ?>">
				<input type="checkbox" 
				       class="field__input field__input--checkbox"
				       name="<?php echo esc_attr( $input_name ); ?>[]" 
				       id="<?php echo esc_attr( $field_id . '_' . $option_value ); ?>"
				       value="<?php echo esc_attr( $option_value ); ?>"
				       <?php checked( in_array( $option_value, $values ) ); ?>
				       <?php if ( isset( $field['required'] ) && $field['required'] ) echo 'required aria-required="true"'; ?>>
				<span class="field__checkbox-custom" aria-hidden="true">
					<span class="field__checkbox-indicator">
						<svg class="field__checkbox-check" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path d="M13.5 4.5L6 12L2.5 8.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
						</svg>
					</span>
				</span>
				<span class="field__checkbox-text"><?php echo esc_html( $option_label ); ?></span>
			</label>
		<?php endforeach; ?>
	</div>
</fieldset>