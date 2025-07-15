<?php

/**
 * Notice Manager Class
 *
 * Handles notice management and rendering for AdminKit pages.
 *
 * @package    Orbitools\AdminKit
 * @version    1.0.0
 * @author     OrbiTools
 * @since      1.0.0
 */

namespace Orbitools\AdminKit\Views;

// Prevent direct access
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Notice Manager Class
 *
 * Responsible for managing and rendering notices including adding,
 * storing, and displaying various types of notices.
 *
 * @since 1.0.0
 */
class Notice_Manager
{

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
    public function __construct($admin_kit)
    {
        $this->admin_kit = $admin_kit;
    }

    /**
     * Render notices section (BEM + custom notice system)
     *
     * @since 1.0.0
     */
    public function render_notices()
    {
        // Render any stored framework notices
        $notices = $this->get_framework_notices();

        if (! empty($notices)) {
            foreach ($notices as $notice) {
                $this->render_single_notice($notice);
            }
        }

        // Hook for additional notices
        do_action($this->admin_kit->get_func_slug() . '_render_notices');
    }

    /**
     * Get stored framework notices
     *
     * @since 1.0.0
     * @return array Array of notices.
     */
    private function get_framework_notices()
    {
        $notices_key = 'orbi_framework_notices_' . $this->admin_kit->get_slug();
        $notices = get_transient($notices_key);

        // Clear notices after displaying them
        if (! empty($notices)) {
            delete_transient($notices_key);
        }

        return is_array($notices) ? $notices : array();
    }

    /**
     * Add a framework notice
     *
     * @since 1.0.0
     * @param string $message Notice message.
     * @param string $type Notice type (success, error, warning, info).
     * @param bool   $dismissible Whether notice is dismissible.
     */
    public function add_notice($message, $type = 'info', $dismissible = true)
    {
        $notices_key = 'orbi_framework_notices_' . $this->admin_kit->get_slug();
        $notices = get_transient($notices_key);

        if (! is_array($notices)) {
            $notices = array();
        }

        // Check if this exact notice already exists to prevent duplicates
        $notice_exists = false;
        foreach ($notices as $existing_notice) {
            if ($existing_notice['message'] === $message && $existing_notice['type'] === $type) {
                $notice_exists = true;
                break;
            }
        }

        // Only add the notice if it doesn't already exist
        if (! $notice_exists) {
            $notices[] = array(
                'message'     => $message,
                'type'        => $type,
                'dismissible' => $dismissible,
                'id'          => uniqid('orbi-notice-'),
            );

            // Store for 5 minutes
            set_transient($notices_key, $notices, 300);
        }
    }

    /**
     * Render a single notice (BEM + accessible)
     *
     * @since 1.0.0
     * @param array $notice Notice data.
     */
    private function render_single_notice($notice)
    {
        $type = isset($notice['type']) ? $notice['type'] : 'info';
        $dismissible = isset($notice['dismissible']) ? $notice['dismissible'] : true;
        $id = isset($notice['id']) ? $notice['id'] : uniqid('orbi-notice-');

        $classes = array(
            'orbi-notice',
            'orbi-notice--' . $type
        );

        if ($dismissible) {
            $classes[] = 'orbi-notice--dismissible';
        }

?>
        <div class="<?php echo esc_attr(implode(' ', $classes)); ?>"
            id="<?php echo esc_attr($id); ?>"
            role="alert"
            aria-live="polite">

            <div class="orbi-notice__icon" aria-hidden="true">
                <?php echo $this->get_notice_icon($type); ?>
            </div>

            <div class="orbi-notice__content">
                <p class="orbi-notice__message"><?php echo wp_kses_post($notice['message']); ?></p>
            </div>

            <?php if ($dismissible) : ?>
                <button type="button"
                    class="orbi-notice__dismiss"
                    aria-label="<?php esc_attr_e('Dismiss notice', 'orbitools-adminkit'); ?>"
                    onclick="this.parentElement.style.display='none';">
                    <span class="orbi-notice__dismiss-icon" aria-hidden="true">&times;</span>
                </button>
            <?php endif; ?>
        </div>
<?php
    }

    /**
     * Get notice icon by type
     *
     * @since 1.0.0
     * @param string $type Notice type.
     * @return string Icon HTML.
     */
    private function get_notice_icon($type)
    {
        $icons = array(
            'success' => '✓',
            'error'   => '✕',
            'warning' => '⚠',
            'info'    => 'ℹ',
        );

        return isset($icons[$type]) ? $icons[$type] : $icons['info'];
    }
}
