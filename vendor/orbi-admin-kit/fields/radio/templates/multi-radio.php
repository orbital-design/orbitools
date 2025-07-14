<?php
/**
 * Multi Radio Template
 *
 * Template for rendering radio button group (radio set).
 * 
 * Available variables:
 * @var array  $field       - Field configuration array
 * @var mixed  $value       - Current field value
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
	<div class="field__radio-group">
		<?php foreach ( $options as $option_value => $option_label ) : ?>
			<label class="field__radio-option" for="<?php echo esc_attr( $field_id . '_' . $option_value ); ?>">
				<input type="radio" 
				       class="field__input field__input--radio"
				       name="<?php echo esc_attr( $input_name ); ?>" 
				       id="<?php echo esc_attr( $field_id . '_' . $option_value ); ?>"
				       value="<?php echo esc_attr( $option_value ); ?>"
				       <?php checked( $value, $option_value ); ?>
				       <?php if ( isset( $field['required'] ) && $field['required'] ) echo 'required aria-required="true"'; ?>>
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