<?php
/**
 * Navigation View Class
 *
 * Handles rendering of navigation components for AdminKit pages.
 *
 * @package    Orbitools\AdminKit
 * @version    1.0.0
 * @author     OrbiTools
 * @since      1.0.0
 */

namespace Orbitools\AdminKit\Views;

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Navigation View Class
 *
 * Responsible for rendering navigation content including breadcrumbs,
 * navigation actions, and default save buttons.
 *
 * @since 1.0.0
 */
class Navigation_View {

	/**
	 * Reference to the main AdminKit instance
	 *
	 * @since 1.0.0
	 * @var \Orbitools\AdminKit\Admin_Kit
	 */
	private $admin_kit;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 * @param \Orbitools\AdminKit\Admin_Kit $admin_kit AdminKit instance
	 */
	public function __construct( $admin_kit ) {
		$this->admin_kit = $admin_kit;
	}

	/**
	 * Render navigation section (BEM + semantic)
	 *
	 * @since 1.0.0
	 */
	public function render_navigation() {
		?>
		<div class="orbi-admin__nav-content">
			<?php $this->render_breadcrumbs(); ?>
			<?php $this->render_nav_actions(); ?>
		</div>
		<?php
		
		// Hook for custom navigation
		do_action( $this->admin_kit->get_func_slug() . '_render_navigation' );
	}
	
	/**
	 * Render breadcrumbs
	 *
	 * @since 1.0.0
	 */
	private function render_breadcrumbs() {
		$current_tab = $this->admin_kit->get_current_tab();
		$current_section = $this->admin_kit->get_current_section();
		$tabs = $this->admin_kit->get_tabs();
		
		if ( empty( $tabs ) ) {
			return;
		}
		
		?>
		<nav class="orbi-admin__breadcrumbs" aria-label="<?php esc_attr_e( 'Breadcrumb navigation', 'orbi-admin-kit' ); ?>">
			<ol class="orbi-admin__breadcrumb-list">
				<li class="orbi-admin__breadcrumb-item">
					<span class="orbi-admin__breadcrumb-text"><?php echo esc_html( $this->admin_kit->get_page_title() ); ?></span>
				</li>
				
				<?php if ( $current_tab && isset( $tabs[ $current_tab ] ) ) : ?>
					<li class="orbi-admin__breadcrumb-item">
						<span class="orbi-admin__breadcrumb-separator" aria-hidden="true">›</span>
						<span class="orbi-admin__breadcrumb-text orbi-admin__breadcrumb-text--current">
							<?php echo esc_html( $tabs[ $current_tab ] ); ?>
						</span>
					</li>
				<?php endif; ?>
				
				<?php if ( $current_section ) : ?>
					<?php
					$structure = $this->admin_kit->get_content_structure();
					$sections = isset( $structure[ $current_tab ]['sections'] ) ? $structure[ $current_tab ]['sections'] : array();
					?>
					<?php if ( isset( $sections[ $current_section ] ) ) : ?>
						<li class="orbi-admin__breadcrumb-item">
							<span class="orbi-admin__breadcrumb-separator" aria-hidden="true">›</span>
							<span class="orbi-admin__breadcrumb-text orbi-admin__breadcrumb-text--current">
								<?php echo esc_html( $sections[ $current_section ] ); ?>
							</span>
						</li>
					<?php endif; ?>
				<?php endif; ?>
			</ol>
		</nav>
		<?php
	}
	
	/**
	 * Render navigation actions
	 *
	 * @since 1.0.0
	 */
	private function render_nav_actions() {
		?>
		<div class="orbi-admin__nav-actions">
			<?php
			// Hook for navigation actions (save buttons, etc.)
			do_action( $this->admin_kit->get_func_slug() . '_render_nav_actions' );
			
			// Default save button (if no custom actions provided)
			if ( ! has_action( $this->admin_kit->get_func_slug() . '_render_nav_actions' ) ) {
				$this->render_default_nav_actions();
			}
			?>
		</div>
		<?php
	}
	
	/**
	 * Render default navigation actions
	 *
	 * @since 1.0.0
	 */
	private function render_default_nav_actions() {
		?>
		<button type="submit" 
		        class="orbi-admin__save-btn button button-primary" 
		        form="orbi-settings-form"
		        aria-describedby="orbi-save-btn-desc">
			<span class="orbi-admin__save-btn-text"><?php esc_html_e( 'Save Settings', 'orbi-admin-kit' ); ?></span>
		</button>
		<span id="orbi-save-btn-desc" class="screen-reader-text">
			<?php esc_html_e( 'Save all settings changes', 'orbi-admin-kit' ); ?>
		</span>
		<?php
	}
}