<?php
/**
 * Page Builder Class
 *
 * Coordinates all view components to build admin pages for AdminKit.
 *
 * @package    Orbi\AdminKit
 * @version    1.0.0
 * @author     OrbiTools
 * @since      1.0.0
 */

namespace Orbi\AdminKit\Classes;

use Orbi\AdminKit\Views\Header_View;
use Orbi\AdminKit\Views\Navigation_View;
use Orbi\AdminKit\Views\Notice_Manager;
use Orbi\AdminKit\Views\Content_View;
use Orbi\AdminKit\Views\Footer_View;

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Page Builder Class
 *
 * Responsible for coordinating all view components to render
 * complete admin pages with proper structure and hooks.
 *
 * @since 1.0.0
 */
class Page_Builder {

	/**
	 * Reference to the main AdminKit instance
	 *
	 * @since 1.0.0
	 * @var \Orbi\AdminKit\Admin_Kit
	 */
	private $admin_kit;

	/**
	 * Header view instance
	 *
	 * @since 1.0.0
	 * @var Header_View
	 */
	private $header_view;

	/**
	 * Navigation view instance
	 *
	 * @since 1.0.0
	 * @var Navigation_View
	 */
	private $navigation_view;

	/**
	 * Notice manager instance
	 *
	 * @since 1.0.0
	 * @var Notice_Manager
	 */
	private $notice_manager;

	/**
	 * Content view instance
	 *
	 * @since 1.0.0
	 * @var Content_View
	 */
	private $content_view;

	/**
	 * Footer view instance
	 *
	 * @since 1.0.0
	 * @var Footer_View
	 */
	private $footer_view;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 * @param \Orbi\AdminKit\Admin_Kit $admin_kit AdminKit instance
	 */
	public function __construct( $admin_kit ) {
		$this->admin_kit = $admin_kit;
		$this->init_view_components();
	}

	/**
	 * Initialize view components
	 *
	 * @since 1.0.0
	 */
	private function init_view_components() {
		$this->header_view = new Header_View( $this->admin_kit );
		$this->navigation_view = new Navigation_View( $this->admin_kit );
		$this->notice_manager = new Notice_Manager( $this->admin_kit );
		$this->content_view = new Content_View( $this->admin_kit );
		$this->footer_view = new Footer_View( $this->admin_kit );
	}

	/**
	 * Render complete admin page
	 *
	 * @since 1.0.0
	 */
	public function render_admin_page() {
		$header_style = '';
		if ( $this->admin_kit->get_page_header_bg_color() ) {
			$header_style = ' style="background-color: ' . esc_attr( $this->admin_kit->get_page_header_bg_color() ) . '"';
		}
		?>
		<div class="orbi-admin wrap" id="orbi-admin-<?php echo esc_attr( $this->admin_kit->get_slug() ); ?>">
			
			<?php do_action( $this->admin_kit->get_func_slug() . '_before_header' ); ?>
			
			<nav class="orbi-admin__nav">
				<?php $this->navigation_view->render_navigation(); ?>
			</nav>
			
			<?php do_action( $this->admin_kit->get_func_slug() . '_after_nav' ); ?>
			
			<div class="orbi-admin__notices">
				<?php $this->notice_manager->render_notices(); ?>
			</div>
			
			<?php do_action( $this->admin_kit->get_func_slug() . '_after_notices' ); ?>
			
			<div class="orbi-admin__tabs">
				<?php $this->content_view->render_tabs(); ?>
			</div>
			
			<?php do_action( $this->admin_kit->get_func_slug() . '_after_tabs' ); ?>
			
			<div class="orbi-admin__content">
				<?php $this->content_view->render_tab_content(); ?>
			</div>
			
			<?php do_action( $this->admin_kit->get_func_slug() . '_after_content' ); ?>
			
			<div class="orbi-admin__footer">
				<?php $this->footer_view->render_footer(); ?>
			</div>
			
			<?php do_action( $this->admin_kit->get_func_slug() . '_after_footer' ); ?>
			
		</div>
		<?php
	}

	/**
	 * Render global header (public method for use outside admin pages)
	 *
	 * @since 1.0.0
	 */
	public function render_global_header() {
		$this->header_view->render_global_header();
	}

	/**
	 * Get header view instance
	 *
	 * @since 1.0.0
	 * @return Header_View
	 */
	public function get_header_view() {
		return $this->header_view;
	}

	/**
	 * Get navigation view instance
	 *
	 * @since 1.0.0
	 * @return Navigation_View
	 */
	public function get_navigation_view() {
		return $this->navigation_view;
	}

	/**
	 * Get notice manager instance
	 *
	 * @since 1.0.0
	 * @return Notice_Manager
	 */
	public function get_notice_manager() {
		return $this->notice_manager;
	}

	/**
	 * Get content view instance
	 *
	 * @since 1.0.0
	 * @return Content_View
	 */
	public function get_content_view() {
		return $this->content_view;
	}

	/**
	 * Get footer view instance
	 *
	 * @since 1.0.0
	 * @return Footer_View
	 */
	public function get_footer_view() {
		return $this->footer_view;
	}

	/**
	 * Add a notice through the notice manager
	 *
	 * @since 1.0.0
	 * @param string $message Notice message.
	 * @param string $type Notice type (success, error, warning, info).
	 * @param bool   $dismissible Whether notice is dismissible.
	 */
	public function add_notice( $message, $type = 'info', $dismissible = true ) {
		$this->notice_manager->add_notice( $message, $type, $dismissible );
	}
}