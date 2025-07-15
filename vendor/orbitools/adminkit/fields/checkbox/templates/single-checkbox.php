<?php
/**
 * Single Checkbox Template
 *
 * Template for rendering a single checkbox field.
 * 
 * Available variables:
 * @var array  $field       - Field configuration array
 * @var mixed  $value       - Current field value
 * @var string $field_id    - Generated field ID
 * @var string $field_name  - Field display name
 * @var string $input_name  - Input name attribute
 * @var bool   $checked     - Whether checkbox is checked
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
<label for="<?php echo esc_attr( $field_id ); ?>" class="field__checkbox-label">
	<input type="checkbox"<?php echo $attributes; ?>>
	<span class="field__checkbox-custom" aria-hidden="true">
		<span class="field__checkbox-indicator">
			<svg class="field__checkbox-check" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path d="M13.5 4.5L6 12L2.5 8.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
			</svg>
		</span>
	</span>
	<span class="field__checkbox-text"><?php echo esc_html( $field_name ); ?></span>
</label>