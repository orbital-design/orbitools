<?php
/**
 * HTML Field Class
 *
 * Handles rendering HTML content fields (for informational displays, 
 * dynamic content, etc.). This field type doesn't save data.
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
 * HTML field implementation
 *
 * @since 1.0.0
 */
class Field_Html extends Field_Base {

	/**
	 * Render the HTML field
	 *
	 * @since 1.0.0
	 */
	public function render() {
		// Get the HTML content from field config
		$html_content = isset( $this->field['std'] ) ? $this->field['std'] : '';
		
		// Allow filtering of the HTML content
		$html_content = apply_filters( 'orbital_html_field_content', $html_content, $this->field );
		
		// Render the HTML content
		?>
		<div class="field__html-content">
			<?php echo wp_kses_post( $html_content ); ?>
		</div>
		<?php
	}

	/**
	 * Sanitize HTML field value
	 *
	 * HTML fields don't save data, so return empty string
	 *
	 * @since 1.0.0
	 * @param mixed $value Value to sanitize.
	 * @return string Empty string (HTML fields don't save data).
	 */
	public function sanitize( $value ) {
		return '';
	}

	/**
	 * Validate HTML field value
	 *
	 * HTML fields don't save data, so always valid
	 *
	 * @since 1.0.0
	 * @param mixed $value Value to validate.
	 * @return bool Always true (HTML fields don't save data).
	 */
	public function validate( $value ) {
		return true;
	}
}