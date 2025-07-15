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
		$text_class = $this->admin_kit->get_hide_title_description() ? 'orbi-admin__header-text screen-reader-text' : 'orbi-admin__header-text';
		?>
		<div class="orbi-admin__header-content">
			<?php if ( $this->admin_kit->get_page_header_image() ) : ?>
				<div class="orbi-admin__header-image">
					<img src="<?php echo esc_url( $this->admin_kit->get_page_header_image() ); ?>" alt="<?php echo esc_attr( $this->admin_kit->get_page_title() ); ?>" class="orbi-admin__header-img" />
				</div>
			<?php endif; ?>
			<div class="<?php echo esc_attr( $text_class ); ?>">
				<h1 class="orbi-admin__title" id="orbi-admin-title"><?php echo esc_html( $this->admin_kit->get_page_title() ); ?></h1>
				<?php if ( $this->admin_kit->get_page_description() ) : ?>
					<p class="orbi-admin__description"><?php echo esc_html( $this->admin_kit->get_page_description() ); ?></p>
				<?php endif; ?>
			</div>
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
		<div class="orbi-global-header orbi-admin__header"<?php echo $header_style; ?>>
			<?php $this->render_header(); ?>
		</div>
		<?php
	}
}