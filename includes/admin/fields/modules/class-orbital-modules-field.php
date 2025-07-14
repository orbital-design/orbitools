<?php
/**
 * Orbital Editor Suite - Modules Field Class
 *
 * Plugin-specific field for displaying and managing Orbital Editor Suite modules.
 * This is registered as a custom field type for this plugin only.
 *
 * @package    Orbital_Editor_Suite
 * @subpackage Admin\Fields
 * @since      1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Orbital Editor Suite modules field implementation
 *
 * @since 1.0.0
 */
class Orbital_Editor_Suite_Modules_Field extends Orbital_Field_Base {

	/**
	 * Render the modules field
	 *
	 * @since 1.0.0
	 */
	public function render() {
		$modules = $this->get_available_modules();
		$field_id = $this->get_field_id();
		$field_name = $this->get_field_name();
		
		?>
		<div class="orbi-modules-grid">
			<?php foreach ( $modules as $module_id => $module ) : ?>
				<?php
				$is_enabled = $this->is_module_enabled( $module_id );
				$card_classes = 'orbi-module-card';
				if ( $is_enabled ) {
					$card_classes .= ' orbi-module-card--enabled';
				}
				?>
				<div class="<?php echo esc_attr( $card_classes ); ?>">
					<div class="orbi-module-card__header">
						<div class="orbi-module-card__icon">
							<span class="dashicons <?php echo esc_attr( $module['icon'] ); ?>"></span>
						</div>
						<div class="orbi-module-card__title-area">
							<h4 class="orbi-module-card__title"><?php echo esc_html( $module['name'] ); ?></h4>
							<?php if ( ! empty( $module['subtitle'] ) ) : ?>
								<p class="orbi-module-card__subtitle"><?php echo esc_html( $module['subtitle'] ); ?></p>
							<?php endif; ?>
						</div>
						<div class="orbi-module-card__toggle">
							<label class="orbi-toggle">
								<input type="checkbox" 
								       name="<?php echo esc_attr( $field_name ); ?>[<?php echo esc_attr( $module_id ); ?>]" 
								       value="1"
								       <?php checked( $is_enabled ); ?>
								       class="orbi-toggle__input">
								<span class="orbi-toggle__slider"></span>
							</label>
						</div>
					</div>
					
					<div class="orbi-module-card__content">
						<p class="orbi-module-card__description"><?php echo esc_html( $module['description'] ); ?></p>
						
						<div class="orbi-module-card__meta">
							<span class="orbi-module-card__version">v<?php echo esc_html( $module['version'] ); ?></span>
							<span class="orbi-module-card__category"><?php echo esc_html( $module['category'] ); ?></span>
						</div>
					</div>
					
					<?php if ( $is_enabled && ! empty( $module['config_url'] ) ) : ?>
						<div class="orbi-module-card__actions">
							<a href="<?php echo esc_url( $module['config_url'] ); ?>" class="orbi-button orbi-button--secondary">
								<span class="dashicons dashicons-admin-generic"></span>
								Configure
							</a>
						</div>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Get available modules with their metadata
	 *
	 * @since 1.0.0
	 * @return array Available modules
	 */
	private function get_available_modules() {
		// Start with empty array - modules register themselves via filter
		$modules = array();

		// Allow modules to register their metadata
		$modules = apply_filters( 'orbital_available_modules', $modules );
		
		// Add config URLs to each module
		foreach ( $modules as $module_id => &$module ) {
			$module['config_url'] = $this->get_module_config_url( $module_id );
		}

		return $modules;
	}

	/**
	 * Check if a module is enabled
	 *
	 * @since 1.0.0
	 * @param string $module_id Module identifier.
	 * @return bool True if enabled, false otherwise.
	 */
	private function is_module_enabled( $module_id ) {
		$framework_settings = get_option( 'orbital_editor_suite_new', array() );
		$setting_key = $module_id . '_enabled';
		
		if ( isset( $framework_settings[ $setting_key ] ) ) {
			return '1' === $framework_settings[ $setting_key ] || 1 === $framework_settings[ $setting_key ];
		}
		
		return false;
	}

	/**
	 * Get module configuration URL
	 *
	 * @since 1.0.0
	 * @param string $module_id Module identifier.
	 * @return string Configuration URL.
	 */
	private function get_module_config_url( $module_id ) {
		$base_url = admin_url( 'options-general.php?page=orbital-editor-suite-new' );
		
		// Map module IDs to their tab sections
		$module_tabs = array(
			'typography_presets' => 'modules&section=typography',
		);
		
		if ( isset( $module_tabs[ $module_id ] ) ) {
			return $base_url . '#' . $module_tabs[ $module_id ];
		}
		
		return $base_url . '#modules';
	}

	/**
	 * Sanitize modules field value
	 *
	 * This field doesn't save data directly - individual module enable/disable
	 * states are handled by their respective hidden fields or direct option updates.
	 *
	 * @since 1.0.0
	 * @param mixed $value Value to sanitize.
	 * @return string Empty string (this field doesn't save data).
	 */
	public function sanitize( $value ) {
		// Handle module toggle updates directly
		if ( is_array( $value ) ) {
			$available_modules = $this->get_available_modules();
			$current_settings = get_option( 'orbital_editor_suite_new', array() );
			
			foreach ( $available_modules as $module_id => $module_data ) {
				$setting_key = $module_id . '_enabled';
				$current_settings[ $setting_key ] = isset( $value[ $module_id ] ) ? '1' : '0';
			}
			
			update_option( 'orbital_editor_suite_new', $current_settings );
		}
		
		return '';
	}

	/**
	 * Validate modules field value
	 *
	 * @since 1.0.0
	 * @param mixed $value Value to validate.
	 * @return bool Always true (modules field values are always valid).
	 */
	public function validate( $value ) {
		return true;
	}

	/**
	 * Get field assets
	 *
	 * @since 1.0.0
	 * @return array Field assets.
	 */
	public function get_assets() {
		return array(
			array(
				'type'    => 'css',
				'handle'  => 'orbital-modules-field',
				'src'     => plugin_dir_url( __FILE__ ) . 'modules-field.css',
				'version' => '1.0.0',
			),
		);
	}
}