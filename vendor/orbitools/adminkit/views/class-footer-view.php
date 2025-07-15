<?php
/**
 * Footer View Class
 *
 * Handles rendering of footer components for AdminKit pages.
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
 * Footer View Class
 *
 * Responsible for rendering footer content including version info
 * and hook points for additional footer content.
 *
 * @since 1.0.0
 */
class Footer_View {

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
	 * Render footer section
	 *
	 * @since 1.0.0
	 */
	public function render_footer() {
		?>
		<p class="orbi-admin__footer-text">
			<?php echo esc_html( sprintf( 'Powered by Orbital Admin Framework v%s', $this->admin_kit->get_version() ) ); ?>
		</p>
		
		<?php
		// Hook for additional footer content
		do_action( $this->admin_kit->get_func_slug() . '_render_footer' );
	}
}