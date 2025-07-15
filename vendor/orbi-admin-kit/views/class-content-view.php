<?php
/**
 * Content View Class
 *
 * Handles rendering of content components for AdminKit pages.
 *
 * @package    Orbi\AdminKit
 * @version    1.0.0
 * @author     OrbiTools
 * @since      1.0.0
 */

namespace Orbi\AdminKit\Views;

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Content View Class
 *
 * Responsible for rendering content including tabs, sections, fields,
 * and form elements.
 *
 * @since 1.0.0
 */
class Content_View {

	/**
	 * Reference to the main AdminKit instance
	 *
	 * @since 1.0.0
	 * @var \Orbi\AdminKit\Admin_Kit
	 */
	private $admin_kit;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 * @param \Orbi\AdminKit\Admin_Kit $admin_kit AdminKit instance
	 */
	public function __construct( $admin_kit ) {
		$this->admin_kit = $admin_kit;
	}

	/**
	 * Render tabs section
	 *
	 * @since 1.0.0
	 */
	public function render_tabs() {
		$tabs = $this->admin_kit->get_tabs();
		$active_tab = $this->admin_kit->get_active_tab();
		
		if ( empty( $tabs ) ) {
			return;
		}
		?>
		<div class="orbi-admin__tabs-wrapper">
			<nav class="orbi-admin__tabs-nav">
				<?php foreach ( $tabs as $tab_key => $tab_label ) : ?>
					<a href="<?php echo esc_url( $this->admin_kit->get_tab_url( $tab_key ) ); ?>" 
					   class="orbi-admin__tab-link <?php echo $active_tab === $tab_key ? 'orbi-admin__tab-link--active' : ''; ?>"
					   data-tab="<?php echo esc_attr( $tab_key ); ?>"
					   role="tab"
					   aria-selected="<?php echo $active_tab === $tab_key ? 'true' : 'false'; ?>"
					   id="orbi-tab-<?php echo esc_attr( $tab_key ); ?>">
						<?php echo esc_html( $tab_label ); ?>
					</a>
				<?php endforeach; ?>
			</nav>
		</div>
		
		<?php
		// Hook for additional tab content
		do_action( $this->admin_kit->get_func_slug() . '_render_tabs', $tabs, $active_tab );
	}

	/**
	 * Render tab content section
	 *
	 * @since 1.0.0
	 */
	public function render_tab_content() {
		$active_tab = $this->admin_kit->get_active_tab();
		$settings = $this->admin_kit->get_settings();
		$tabs = $this->admin_kit->get_tabs();
		
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" class="orbi-admin__settings-form" id="orbi-settings-form">
			<?php wp_nonce_field( 'orbi_admin_' . $this->admin_kit->get_slug(), 'orbi_nonce' ); ?>
			<input type="hidden" name="action" value="orbi_admin_save_settings_<?php echo esc_attr( $this->admin_kit->get_slug() ); ?>">
			<input type="hidden" name="slug" value="<?php echo esc_attr( $this->admin_kit->get_slug() ); ?>">
			
			<?php foreach ( $tabs as $tab_key => $tab_title ) : ?>
				<div class="orbi-admin__tab-content" 
				     data-tab="<?php echo esc_attr( $tab_key ); ?>"
				     aria-labelledby="orbi-tab-<?php echo esc_attr( $tab_key ); ?>"
				     style="<?php echo $active_tab === $tab_key ? 'display: block;' : 'display: none;'; ?>">
					
					<?php 
					$sections = $this->admin_kit->get_sections( $tab_key );
					$display_mode = $this->admin_kit->get_section_display_mode( $tab_key );
					
					if ( ! empty( $sections ) ) : 
						if ( $display_mode === 'tabs' ) : 
						?>
							<!-- Sub-tabs for sections -->
							<div class="orbi-admin__subtabs-wrapper">
								<nav class="orbi-admin__subtabs-nav">
									<?php 
									$active_section = $this->admin_kit->get_active_section( $tab_key );
									foreach ( $sections as $section_key => $section_title ) : 
									?>
										<a href="#" 
										   class="orbi-admin__subtab-link <?php echo ( $active_tab === $tab_key && $active_section === $section_key ) ? 'orbi-admin__subtab-link--active' : ''; ?>"
										   data-section="<?php echo esc_attr( $section_key ); ?>"
										   role="tab"
										   aria-selected="<?php echo ( $active_tab === $tab_key && $active_section === $section_key ) ? 'true' : 'false'; ?>"
										   id="orbi-subtab-<?php echo esc_attr( $section_key ); ?>">
											<?php echo esc_html( $section_title ); ?>
										</a>
									<?php endforeach; ?>
								</nav>
							</div>
							
							<!-- Section content for tabs mode -->
							<?php foreach ( $sections as $section_key => $section_title ) : ?>
								<div class="orbi-admin__section-content" 
								     data-section="<?php echo esc_attr( $section_key ); ?>"
								     aria-labelledby="orbi-subtab-<?php echo esc_attr( $section_key ); ?>"
								     style="<?php echo ( $active_tab === $tab_key && $active_section === $section_key ) ? 'display: block;' : 'display: none;'; ?>">
									<?php
									if ( isset( $settings[ $tab_key ] ) ) {
										$section_fields = $this->get_section_fields( $settings[ $tab_key ], $section_key );
										if ( ! empty( $section_fields ) ) {
											?>
											<div class="orbi-admin__section-fields">
												<?php
												foreach ( $section_fields as $field ) {
													$this->admin_kit->render_field( $field );
												}
												?>
											</div>
											<?php
										} else {
											$this->render_no_fields_message( $section_title );
										}
									} else {
										$this->render_no_fields_message( $section_title );
									}
									?>
								</div>
							<?php endforeach; ?>
						<?php else : ?>
							<!-- Cards mode: sections as stacked cards -->
							<?php foreach ( $sections as $section_key => $section_title ) : ?>
								<div class="orbi-admin__section-card" data-section="<?php echo esc_attr( $section_key ); ?>">
									<h3 class="orbi-admin__section-title"><?php echo esc_html( $section_title ); ?></h3>
									<?php
									if ( isset( $settings[ $tab_key ] ) ) {
										$section_fields = $this->get_section_fields( $settings[ $tab_key ], $section_key );
										if ( ! empty( $section_fields ) ) {
											?>
											<div class="orbi-admin__section-fields">
												<?php
												foreach ( $section_fields as $field ) {
													$this->admin_kit->render_field( $field );
												}
												?>
											</div>
											<?php
										} else {
											$this->render_no_fields_message( $section_title );
										}
									} else {
										$this->render_no_fields_message( $section_title );
									}
									?>
								</div>
							<?php endforeach; ?>
						<?php endif; ?>
					<?php else : ?>
						<!-- No sections, render all fields directly -->
						<?php
						if ( isset( $settings[ $tab_key ] ) ) {
							?>
							<div class="orbi-admin__section-fields">
								<?php
								foreach ( $settings[ $tab_key ] as $field ) {
									$this->admin_kit->render_field( $field );
								}
								?>
							</div>
							<?php
						}
						?>
					<?php endif; ?>
					
					<?php
					// Hook for custom tab content
					do_action( $this->admin_kit->get_func_slug() . '_render_tab_content', $tab_key );
					?>
				</div>
			<?php endforeach; ?>
			
			<?php submit_button( 'Save Settings' ); ?>
		</form>
		<?php
	}

	/**
	 * Render fields for a specific tab
	 *
	 * @since 1.0.0
	 * @param array  $fields Tab fields array.
	 * @param string $tab_key Current tab key.
	 */
	public function render_tab_fields( $fields, $tab_key ) {
		$sections = $this->admin_kit->get_sections( $tab_key );
		
		foreach ( $sections as $section_key => $section_title ) {
			$section_fields = $this->get_section_fields( $fields, $section_key );
			
			if ( empty( $section_fields ) ) {
				continue;
			}
			
			?>
			<div class="orbital-section" data-section="<?php echo esc_attr( $section_key ); ?>">
				<h3 class="orbital-section-title"><?php echo esc_html( $section_title ); ?></h3>
				<div class="orbital-section-fields">
					<?php
					foreach ( $section_fields as $field ) {
						$this->admin_kit->render_field( $field );
					}
					?>
				</div>
			</div>
			<?php
		}
	}

	/**
	 * Get section fields
	 *
	 * @since 1.0.0
	 * @param array  $fields All fields.
	 * @param string $section_key Section key.
	 * @return array Section fields.
	 */
	private function get_section_fields( $fields, $section_key ) {
		return array_filter( $fields, function( $field ) use ( $section_key ) {
			return isset( $field['section'] ) && $field['section'] === $section_key;
		});
	}

	/**
	 * Get fields without sections (for tabs that don't use sub-tabs)
	 *
	 * @since 1.0.0
	 * @param array $fields All fields.
	 * @return array Fields without sections.
	 */
	private function get_fields_without_sections( $fields ) {
		return array_filter( $fields, function( $field ) {
			return ! isset( $field['section'] ) || empty( $field['section'] );
		});
	}

	/**
	 * Render "no fields" message
	 *
	 * @since 1.0.0
	 * @param string $section_title Section title for context.
	 */
	private function render_no_fields_message( $section_title = '' ) {
		?>
		<div class="orbi-admin__no-fields-message">
			<div class="orbi-admin__no-fields-icon">
				<span class="dashicons dashicons-admin-settings"></span>
			</div>
			<h4>No fields configured</h4>
			<p>
				<?php if ( $section_title ) : ?>
					No fields have been added to the "<?php echo esc_html( $section_title ); ?>" section yet.
				<?php else : ?>
					No fields have been configured for this section yet.
				<?php endif; ?>
			</p>
			<p class="orbi-admin__no-fields-help">
				<strong>For developers:</strong> Add fields using the <code><?php echo esc_html( $this->admin_kit->get_func_slug() ); ?>_settings</code> filter.
				<?php if ( $section_title ) : ?>
					Make sure to set <code>'section' => '<?php echo esc_attr( strtolower( str_replace( ' ', '_', $section_title ) ) ); ?>'</code> on your field definitions.
				<?php endif; ?>
			</p>
		</div>
		<?php
	}
}