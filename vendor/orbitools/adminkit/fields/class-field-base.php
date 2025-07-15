<?php
/**
 * Base Field Class
 *
 * Abstract base class for all field types in the OrbiTools AdminKit.
 * Provides common functionality and structure for all field implementations.
 *
 * IMPORTANT: Field IDs must be unique across the entire framework instance.
 * The 'id' parameter is used for:
 * - HTML input names (settings[field_id])
 * - Database storage keys
 * - Value retrieval
 * 
 * Duplicate IDs will cause fields to overwrite each other's values!
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
	 * @param array $field Field configuration.
	 * @param mixed $value Field value.
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
	 * @since 1.0.0
	 */
	abstract public function render();

	/**
	 * Get field assets (CSS/JS files)
	 *
	 * @since 1.0.0
	 * @return array Array of asset files.
	 */
	public function get_assets() {
		return array();
	}

	/**
	 * Sanitize field value
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
	 * @since 1.0.0
	 * @param mixed $value Value to validate.
	 * @return bool|string True if valid, error message if invalid.
	 */
	public function validate( $value ) {
		return true;
	}

	/**
	 * Get field ID
	 *
	 * @since 1.0.0
	 * @return string Field ID.
	 */
	protected function get_field_id() {
		return isset( $this->field['id'] ) ? $this->field['id'] : '';
	}

	/**
	 * Get field name
	 *
	 * @since 1.0.0
	 * @return string Field name.
	 */
	protected function get_field_name() {
		return isset( $this->field['name'] ) ? $this->field['name'] : '';
	}

	/**
	 * Get field description
	 *
	 * @since 1.0.0
	 * @return string Field description.
	 */
	protected function get_field_description() {
		return isset( $this->field['desc'] ) ? $this->field['desc'] : '';
	}

	/**
	 * Get field input name attribute
	 *
	 * @since 1.0.0
	 * @return string Input name attribute.
	 */
	protected function get_input_name() {
		return 'settings[' . $this->get_field_id() . ']';
	}

	/**
	 * Get field wrapper classes (BEM methodology)
	 *
	 * @since 1.0.0
	 * @return string CSS classes.
	 */
	protected function get_field_wrapper_classes() {
		$classes = array( 
			'field',
			'field--' . $this->field['type']
		);
		
		// Add custom classes if specified
		if ( isset( $this->field['class'] ) && ! empty( $this->field['class'] ) ) {
			if ( is_array( $this->field['class'] ) ) {
				$classes = array_merge( $classes, $this->field['class'] );
			} else {
				$custom_classes = explode( ' ', $this->field['class'] );
				$classes = array_merge( $classes, $custom_classes );
			}
		}
		
		// Add state modifiers
		if ( isset( $this->field['required'] ) && $this->field['required'] ) {
			$classes[] = 'field--required';
		}
		
		if ( isset( $this->field['disabled'] ) && $this->field['disabled'] ) {
			$classes[] = 'field--disabled';
		}
		
		return implode( ' ', array_filter( $classes ) );
	}

	/**
	 * Get input element classes (BEM methodology)
	 *
	 * @since 1.0.0
	 * @return string CSS classes.
	 */
	protected function get_input_classes() {
		$classes = array( 'field__input' );
		
		// Add type-specific class
		$classes[] = 'field__input--' . $this->field['type'];
		
		return implode( ' ', $classes );
	}

	/**
	 * Render field label (BEM + accessibility)
	 *
	 * @since 1.0.0
	 */
	public function render_label() {
		if ( ! $this->get_field_name() ) {
			return;
		}
		
		$required_indicator = '';
		if ( isset( $this->field['required'] ) && $this->field['required'] ) {
			$required_indicator = ' <span class="field__required" aria-label="required">*</span>';
		}
		
		?>
		<label for="<?php echo esc_attr( $this->get_field_id() ); ?>" class="field__label">
			<?php echo esc_html( $this->get_field_name() ); ?><?php echo $required_indicator; ?>
		</label>
		<?php
	}

	/**
	 * Render field description (BEM + accessibility)
	 *
	 * @since 1.0.0
	 */
	public function render_description() {
		if ( ! $this->get_field_description() ) {
			return;
		}
		?>
		<div class="field__description" id="<?php echo esc_attr( $this->get_field_id() ); ?>-description">
			<?php echo esc_html( $this->get_field_description() ); ?>
		</div>
		<?php
	}

	/**
	 * Get field attributes (with accessibility)
	 *
	 * @since 1.0.0
	 * @return array Field attributes.
	 */
	protected function get_field_attributes() {
		$attributes = array(
			'id'    => $this->get_field_id(),
			'name'  => $this->get_input_name(),
			'class' => $this->get_input_classes(),
		);

		// Add accessibility attributes
		if ( $this->get_field_description() ) {
			$attributes['aria-describedby'] = $this->get_field_id() . '-description';
		}

		if ( isset( $this->field['required'] ) && $this->field['required'] ) {
			$attributes['required'] = true;
			$attributes['aria-required'] = 'true';
		}

		if ( isset( $this->field['disabled'] ) && $this->field['disabled'] ) {
			$attributes['disabled'] = true;
			$attributes['aria-disabled'] = 'true';
		}

		// Add placeholder if specified
		if ( isset( $this->field['placeholder'] ) ) {
			$attributes['placeholder'] = $this->field['placeholder'];
		}

		// Add custom attributes if defined
		if ( isset( $this->field['attributes'] ) && is_array( $this->field['attributes'] ) ) {
			$attributes = array_merge( $attributes, $this->field['attributes'] );
		}

		return $attributes;
	}

	/**
	 * Render field attributes as HTML
	 *
	 * @since 1.0.0
	 * @param array $attributes Additional attributes.
	 * @return string HTML attributes.
	 */
	protected function render_attributes( $attributes = array() ) {
		$field_attributes = array_merge( $this->get_field_attributes(), $attributes );
		$html = '';

		foreach ( $field_attributes as $key => $value ) {
			if ( is_bool( $value ) ) {
				if ( $value ) {
					$html .= ' ' . esc_attr( $key );
				}
			} else {
				$html .= ' ' . esc_attr( $key ) . '="' . esc_attr( $value ) . '"';
			}
		}

		return $html;
	}
}