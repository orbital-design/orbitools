<?php

namespace Orbitools\Modules\Orbital_Login;

use Orbitools\Core\Abstracts\Module_Base;
use Orbitools\Core\Helpers\Settings_Manager;

/**
 * Orbital Login module.
 *
 * Restyles wp-login.php with the Orbital brand: dark navy backdrop,
 * white card form, blue primary action, Orbital logo in place of
 * the default WordPress mark. The styling is a single static CSS
 * file enqueued only on the login screen (`login_enqueue_scripts`),
 * so nothing else in the admin or frontend is affected.
 *
 * Two settings opt in / out of the lighter-touch behaviour:
 *   - logo_links_home              → swap the WP.org link for home_url()
 *   - use_site_name_as_logo_text   → swap "Powered by WordPress" for site name
 *
 * @package Orbitools
 * @since 3.2.0
 */
final class Orbital_Login extends Module_Base
{
    public function get_slug(): string
    {
        return 'orbital-login';
    }

    public function get_name(): string
    {
        return \__('Orbital Login', 'orbitools');
    }

    public function get_description(): string
    {
        return \__('Restyle the wp-login.php screen with the Orbital brand.', 'orbitools');
    }

    public function init(): void
    {
        \add_action('login_enqueue_scripts', [$this, 'enqueue_login_styles']);

        $settings = new Settings_Manager();
        if ($settings->get_module_setting($this->get_slug(), 'logo_links_home', true)) {
            \add_filter('login_headerurl', [$this, 'filter_login_headerurl']);
        }
        if ($settings->get_module_setting($this->get_slug(), 'use_site_name_as_logo_text', true)) {
            \add_filter('login_headertext', [$this, 'filter_login_headertext']);
        }
    }

    public function enqueue_login_styles(): void
    {
        \wp_enqueue_style(
            'orbitools-orbital-login',
            ORBITOOLS_URL . 'build/admin/css/modules/orbital-login/login.css',
            [],
            ORBITOOLS_VERSION
        );
    }

    public function filter_login_headerurl(): string
    {
        return \home_url('/');
    }

    public function filter_login_headertext(): string
    {
        return \get_bloginfo('name');
    }
}
