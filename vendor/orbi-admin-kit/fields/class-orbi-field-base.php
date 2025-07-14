<?php
/**
 * Base Field Class
 *
 * Abstract base class for all field types in the OrbiTools AdminKit.
 * Provides common functionality and structure for all field implementations.
 *
 * @package    Orbi\AdminKit
 * @subpackage Fields
 * @since      1.0.0
 */

namespace Orbi\AdminKit;

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Abstract base class for all field types
 *
 * @since 1.0.0
 */
abstract class Field_Base {

	/**
	 * Field configuration
	 *
	 * @since 1.0.0
	 * @var array
	 */
	protected $field;

	/**
	 * Field value
	 *
	 * @since 1.0.0
	 * @var mixed
	 */
	protected $value;

	/**
	 * Framework instance
	 *
	 * @since 1.0.0
	 * @var Admin_Kit
	 */
	protected $framework;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 * @param array     $field Field configuration.
	 * @param mixed     $value Field value.
	 * @param Admin_Kit $framework Framework instance.
	 */
	public function __construct( $field, $value, $framework ) {
		$this->field = $field;
		$this->value = $value;
		$this->framework = $framework;
	}

	/**
	 * Render the field
	 *
	 * Must be implemented by each field type.
	 *
	 * @since 1.0.0
	 */
	abstract public function render();

	/**
	 * Sanitize field value
	 *
	 * Can be overridden by individual field types for specific sanitization.
	 *
	 * @since 1.0.0
	 * @param mixed $value Value to sanitize.
	 * @return mixed Sanitized value.
	 */
	public function sanitize( $value ) {
		return sanitize_text_field( $value );
	}

	/**
	 * Validate field value
	 *
	 * Can be overridden by individual field types for specific validation.
	 *
	 * @since 1.0.0
	 * @param mixed $value Value to validate.
	 * @return bool|string True if valid, error message if invalid.
	 */
	public function validate( $value ) {
		// Check required fields
		if ( isset( $this->field['required'] ) && $this->field['required'] ) {
			if ( empty( $value ) && '0' !== $value ) {
				return sprintf( 
					__( '%s is required.', 'orbi-admin-kit' ), 
					isset( $this->field['name'] ) ? $this->field['name'] : $this->field['id'] 
				);
			}
		}

		return true;
	}

	/**
	 * Get field assets (CSS/JS)
	 *
	 * Can be overridden by field types that need custom assets.
	 *
	 * @since 1.0.0
	 * @return array Assets array.
	 */
	public function get_assets() {
		return array();
	}

	/**
	 * Get field ID for HTML attributes
	 *
	 * @since 1.0.0
	 * @return string Field ID.
	 */
	public function get_field_id() {
		return isset( $this->field['id'] ) ? $this->field['id'] : '';
	}

	/**
	 * Get field name for HTML form element
	 *
	 * @since 1.0.0
	 * @return string Field name.
	 */
	public function get_field_name() {
		return $this->get_field_id();
	}

	/**
	 * Get field value
	 *
	 * @since 1.0.0
	 * @return mixed Field value.
	 */
	public function get_value() {
		return $this->value;
	}

	/**
	 * Get field configuration
	 *
	 * @since 1.0.0
	 * @return array Field configuration.
	 */
	public function get_field_config() {
		return $this->field;
	}

	/**
	 * Generate HTML attributes string
	 *
	 * @since 1.0.0
	 * @param array $attributes Attributes array.
	 * @return string HTML attributes string.
	 */
	protected function build_attributes( $attributes ) {
		$output = '';
		
		foreach ( $attributes as $key => $value ) {
			if ( null === $value || false === $value ) {
				continue;
			}
			
			if ( true === $value ) {
				$output .= ' ' . esc_attr( $key );
			} else {
				$output .= ' ' . esc_attr( $key ) . '="' . esc_attr( $value ) . '"';
			}
		}
		
		return $output;
	}

	/**
	 * Get common field attributes
	 *
	 * @since 1.0.0
	 * @return array Common attributes array.
	 */
	protected function get_common_attributes() {
		$attributes = array(
			'id'   => $this->get_field_id(),
			'name' => $this->get_field_name(),
		);

		// Add placeholder if specified
		if ( isset( $this->field['placeholder'] ) ) {
			$attributes['placeholder'] = $this->field['placeholder'];
		}

		// Add required attribute if specified
		if ( isset( $this->field['required'] ) && $this->field['required'] ) {
			$attributes['required'] = true;
			$attributes['aria-required'] = 'true';
		}

		return $attributes;
	}

	/**
	 * Escape field value for output
	 *
	 * @since 1.0.0
	 * @param mixed $value Value to escape.
	 * @return string Escaped value.
	 */
	protected function esc_field_value( $value ) {
		if ( is_array( $value ) ) {
			return array_map( 'esc_attr', $value );
		}
		
		return esc_attr( $value );
	}

	/**
	 * Render field label
	 *
	 * @since 1.0.0
	 */
	public function render_label() {
		if ( empty( $this->field['name'] ) ) {
			return;
		}

		$label_for = $this->get_field_id();
		$required = isset( $this->field['required'] ) && $this->field['required'];
		
		?>
		<label for="<?php echo esc_attr( $label_for ); ?>" class="field__label">
			<?php echo esc_html( $this->field['name'] ); ?>
			<?php if ( $required ) : ?>
				<span class="field__required" aria-label="<?php esc_attr_e( 'Required', 'orbi-admin-kit' ); ?>">*</span>
			<?php endif; ?>
		</label>
		<?php if ( ! empty( $this->field['desc'] ) ) : ?>
			<p class="field__description"><?php echo esc_html( $this->field['desc'] ); ?></p>
		<?php endif;
	}
}