<?php
/**
 * Base Field Class
 *
 * Abstract base class for all field types in the Orbital Admin Framework.
 * Provides common functionality and structure for all field implementations.
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
 * Abstract base class for all field types
 *
 * @since 1.0.0
 */
abstract class Orbital_Field_Base {

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
	 * @var Orbital_Admin_Framework
	 */
	protected $framework;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 * @param array $field Field configuration.
	 * @param mixed $value Field value.
	 * @param Orbital_Admin_Framework $framework Framework instance.
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
	 * Get field classes
	 *
	 * @since 1.0.0
	 * @return string CSS classes.
	 */
	protected function get_field_classes() {
		$classes = array( 'orbital-field' );
		
		if ( isset( $this->field['class'] ) ) {
			$classes[] = $this->field['class'];
		}
		
		return implode( ' ', $classes );
	}

	/**
	 * Render field label
	 *
	 * @since 1.0.0
	 */
	protected function render_label() {
		if ( ! $this->get_field_name() ) {
			return;
		}
		?>
		<label for="<?php echo esc_attr( $this->get_field_id() ); ?>">
			<span class="orbital-field-label"><?php echo esc_html( $this->get_field_name() ); ?></span>
		</label>
		<?php
	}

	/**
	 * Render field description
	 *
	 * @since 1.0.0
	 */
	protected function render_description() {
		if ( ! $this->get_field_description() ) {
			return;
		}
		?>
		<p class="description"><?php echo esc_html( $this->get_field_description() ); ?></p>
		<?php
	}

	/**
	 * Get field attributes
	 *
	 * @since 1.0.0
	 * @return array Field attributes.
	 */
	protected function get_field_attributes() {
		$attributes = array(
			'id'   => $this->get_field_id(),
			'name' => $this->get_input_name(),
		);

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