<?php
/**
 * Select Field Class
 *
 * Handles rendering and functionality for select dropdown fields.
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
 * Select field implementation
 *
 * @since 1.0.0
 */
class Field_Select extends Field_Base {

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

		$attributes = array();
		
		// Add multiple attribute if specified
		if ( isset( $this->field['multiple'] ) && $this->field['multiple'] ) {
			$attributes['multiple'] = true;
			// Multiple selects use the same name - JavaScript will handle array collection
			$attributes['name'] = $this->get_input_name();
			// Add size attribute for better UX if not specified
			if ( ! isset( $this->field['size'] ) ) {
				$attributes['size'] = min( count( $this->field['options'] ), 8 );
			}
		}
		
		// Add size attribute if specified
		if ( isset( $this->field['size'] ) ) {
			$attributes['size'] = $this->field['size'];
		}

		?>
		<select<?php echo $this->render_attributes( $attributes ); ?>>
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
		$is_multiple = isset( $this->field['multiple'] ) && $this->field['multiple'];
		$selected_values = $is_multiple && is_array( $this->value ) ? $this->value : array( $this->value );
		
		// Add empty option if placeholder is set and not multiple
		if ( isset( $this->field['placeholder'] ) && ! $is_multiple ) {
			?>
			<option value=""><?php echo esc_html( $this->field['placeholder'] ); ?></option>
			<?php
		}

		foreach ( $this->field['options'] as $option_value => $option_label ) {
			$is_selected = in_array( $option_value, $selected_values );
			?>
			<option value="<?php echo esc_attr( $option_value ); ?>" 
			        <?php selected( $is_selected, true ); ?>>
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
	 * @return string|array Sanitized value.
	 */
	public function sanitize( $value ) {
		$is_multiple = isset( $this->field['multiple'] ) && $this->field['multiple'];
		
		// Multi-select handling
		if ( $is_multiple ) {
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
		
		// Single select handling
		if ( isset( $this->field['options'] ) && is_array( $this->field['options'] ) ) {
			return array_key_exists( $value, $this->field['options'] ) ? sanitize_text_field( $value ) : '';
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
		$is_multiple = isset( $this->field['multiple'] ) && $this->field['multiple'];
		
		// Multi-select validation
		if ( $is_multiple ) {
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
		
		// Single select validation
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