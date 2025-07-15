<?php
/**
 * Single Radio Template
 *
 * Template for rendering a single radio button field.
 * 
 * Available variables:
 * @var array  $field       - Field configuration array
 * @var mixed  $value       - Current field value
 * @var string $field_id    - Generated field ID
 * @var string $field_name  - Field display name
 * @var string $input_name  - Input name attribute
 * @var bool   $checked     - Whether radio is checked
 * @var string $attributes  - Rendered input attributes
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
<label for="<?php echo esc_attr( $field_id ); ?>" class="field__radio-label">
	<input type="radio"<?php echo $attributes; ?>>
	<span class="field__radio-custom" aria-hidden="true">
		<span class="field__radio-indicator">
			<span class="field__radio-dot"></span>
		</span>
	</span>
	<span class="field__radio-text"><?php echo esc_html( $field_name ); ?></span>
</label>