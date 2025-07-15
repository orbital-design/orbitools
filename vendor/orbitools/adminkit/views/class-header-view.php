<?php
/**
 * Header View Class
 *
 * Handles rendering of header components for AdminKit pages.
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
 * Header View Class
 *
 * Responsible for rendering header content including title, description,
 * header image, and global header positioning.
 *
 * @since 1.0.0
 */
class Header_View {

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
	 * Render header section
	 *
	 * @since 1.0.0
	 */
	public function render_header() {
		// Determine if title and description should be hidden visually
		$text_class = $this->admin_kit->get_hide_title_description() ? 'adminkit-header__text screen-reader-text' : 'adminkit-header__text';
		?>
		<div class="adminkit-header__content">
			<?php if ( $this->admin_kit->get_page_header_image() ) : ?>
				<div class="adminkit-header__image">
					<img src="<?php echo esc_url( $this->admin_kit->get_page_header_image() ); ?>" alt="<?php echo esc_attr( $this->admin_kit->get_page_title() ); ?>" class="adminkit-header__img" />
				</div>
			<?php endif; ?>
			<div class="<?php echo esc_attr( str_replace('adminKit__header-text', 'adminkit-header__text', $text_class) ); ?>">
				<h1 class="adminkit-header__title" id="orbi-admin-title"><?php echo esc_html( $this->admin_kit->get_page_title() ); ?></h1>
				<?php if ( $this->admin_kit->get_page_description() ) : ?>
					<p class="adminkit-header__description"><?php echo esc_html( $this->admin_kit->get_page_description() ); ?></p>
				<?php endif; ?>
			</div>
			<?php $this->render_header_tabs(); ?>
		</div>
		
		<?php
		// Hook for additional header content
		do_action( $this->admin_kit->get_func_slug() . '_render_header' );
		?>
		<?php
	}

	/**
	 * Render global header (public method for use outside admin pages)
	 *
	 * @since 1.0.0
	 */
	public function render_global_header() {
		// Only show on AdminKit pages
		$screen = get_current_screen();
		if ( ! $screen || strpos( $screen->id, $this->admin_kit->get_slug() ) === false ) {
			return;
		}

		// Add inline styles for header background color if set
		if ( $this->admin_kit->get_page_header_bg_color() ) {
			$header_style = ' style="background-color: ' . esc_attr( $this->admin_kit->get_page_header_bg_color() ) . '"';
		} else {
			$header_style = '';
		}
		?>
		<div class="orbi-global-header adminkit-header"<?php echo $header_style; ?>>
			<?php $this->render_header(); ?>
		</div>
		<div class="adminkit-toolbar">
			<?php $this->render_breadcrumbs(); ?>
			<?php $this->render_toolbar_actions(); ?>
		</div>
		<?php
	}

	/**
	 * Render toolbar actions (nav actions)
	 *
	 * @since 1.0.0
	 */
	private function render_toolbar_actions() {
		// Get the navigation view instance from the page builder
		$navigation_view = new \Orbitools\AdminKit\Views\Navigation_View( $this->admin_kit );
		?>
		<div class="adminkit-toolbar__actions">
			<?php $navigation_view->render_nav_actions(); ?>
		</div>
		<?php
	}

	/**
	 * Render breadcrumbs underneath the header
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
		<nav class="orbi-admin__breadcrumbs" aria-label="<?php esc_attr_e( 'Breadcrumb navigation', 'orbitools-adminkit' ); ?>">
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
	 * Render tabs in the header as navigation
	 *
	 * @since 1.0.0
	 */
	private function render_header_tabs() {
		$tabs = $this->admin_kit->get_tabs();
		$active_tab = $this->admin_kit->get_active_tab();

		if ( empty( $tabs ) ) {
			return;
		}
		?>
		<div class="orbi-admin__header-tabs">
			<nav class="orbi-admin__tabs-nav" role="navigation" aria-label="<?php esc_attr_e( 'Main navigation', 'orbitools-adminkit' ); ?>">
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
	}
}