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
 * Settings:
 *   - logo_url            → URL the login logo links to (default Orbital site)
 *   - logo_title          → title attribute on the login logo
 *   - remember_last_user  → drop a per-device cookie after successful login
 *                           so the next wp-login.php render greets the user
 *                           by name and only asks for the password
 *
 * @package Orbitools
 * @since 3.2.0
 */
final class Orbital_Login extends Module_Base
{
    /**
     * Cookie name prefix for the "remember last user" feature. The
     * full name appends COOKIEHASH so the cookie is multisite-safe.
     */
    private const REMEMBER_COOKIE_PREFIX = 'orbitools_last_user_';

    /**
     * Lifetime for the remember-last-user cookie, in seconds. 30
     * days mirrors the WP "Remember Me" auth cookie lifetime so the
     * two features decay at the same pace. Spelled out as a literal
     * because class constants can't reference WP's DAY_IN_SECONDS
     * (only resolved at runtime via the global namespace).
     */
    private const REMEMBER_COOKIE_LIFETIME = 30 * 24 * 60 * 60;

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

        // Filters fire only when the user has filled in a value;
        // empty fields leave WP's defaults (wordpress.org link,
        // "Powered by WordPress" title) untouched.
        $settings = new Settings_Manager();
        if ($settings->get_module_setting($this->get_slug(), 'logo_url', 'https://orbital.co.uk') !== '') {
            \add_filter('login_headerurl', [$this, 'filter_login_headerurl']);
        }
        if ($settings->get_module_setting($this->get_slug(), 'logo_title', 'Orbital') !== '') {
            \add_filter('login_headertext', [$this, 'filter_login_headertext']);
        }

        if ($settings->get_module_setting($this->get_slug(), 'remember_last_user', false)) {
            // Drop the cookie after a successful login.
            \add_action('wp_login', [$this, 'remember_last_user'], 10, 2);
            // Inject welcome-back markup + the small script that
            // pre-fills the username and wires the "Not you?" link.
            // login_footer fires after the form, so the DOM the
            // script targets already exists.
            \add_action('login_footer', [$this, 'render_welcome_back']);
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
        $settings = new Settings_Manager();
        $url = (string) $settings->get_module_setting($this->get_slug(), 'logo_url', 'https://orbital.co.uk');
        return \esc_url_raw($url);
    }

    public function filter_login_headertext(): string
    {
        $settings = new Settings_Manager();
        return (string) $settings->get_module_setting($this->get_slug(), 'logo_title', 'Orbital');
    }

    /**
     * Store the just-logged-in user's login name in a per-device
     * cookie so the next wp-login.php render can greet them by
     * name. The cookie holds only `user_login`; no auth material,
     * no display name (we look that up server-side from the user
     * record so we don't widen the recon surface).
     *
     * @param string   $user_login The user's login name.
     * @param \WP_User $user       The WP_User object of the logged-in user.
     */
    public function remember_last_user(string $user_login, $user): void
    {
        if (\headers_sent()) {
            return;
        }

        \setcookie(
            $this->get_remember_cookie_name(),
            $user_login,
            [
                'expires'  => \time() + self::REMEMBER_COOKIE_LIFETIME,
                'path'     => \SITECOOKIEPATH,
                'domain'   => \COOKIE_DOMAIN,
                'secure'   => \is_ssl(),
                // HttpOnly off — JS needs to clear it from the
                // "Not you?" link without a server round-trip.
                'httponly' => false,
                'samesite' => 'Lax',
            ]
        );
    }

    /**
     * Render the welcome-back UI on wp-login.php. Only shows on the
     * default login action (not lost-password / register), only
     * when the cookie names a user who still exists, and only on
     * GET (so a failed POST keeps the normal form with its error).
     */
    public function render_welcome_back(): void
    {
        // Skip on non-default actions (lostpassword, register, etc.).
        $action = isset($_REQUEST['action']) ? \sanitize_key((string) $_REQUEST['action']) : 'login';
        if ($action !== 'login') {
            return;
        }

        // Skip on form submissions — let WP show its own error
        // alongside the user's typed value.
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
            return;
        }

        $cookie_name = $this->get_remember_cookie_name();
        if (!isset($_COOKIE[$cookie_name]) || $_COOKIE[$cookie_name] === '') {
            return;
        }

        $login = \sanitize_user(\wp_unslash((string) $_COOKIE[$cookie_name]), true);
        if ($login === '') {
            return;
        }

        $user = \get_user_by('login', $login);
        if (!$user) {
            // Cookie names a user who no longer exists — clear it.
            \setcookie(
                $cookie_name,
                '',
                [
                    'expires'  => \time() - 3600,
                    'path'     => \SITECOOKIEPATH,
                    'domain'   => \COOKIE_DOMAIN,
                    'secure'   => \is_ssl(),
                    'httponly' => false,
                    'samesite' => 'Lax',
                ]
            );
            return;
        }

        $display    = $user->display_name !== '' ? $user->display_name : $user->user_login;
        $first_name = \trim((string) \get_user_meta($user->ID, 'first_name', true));
        $greeting   = $first_name !== '' ? $first_name : $display;

        $payload = [
            'login'   => $user->user_login,
            'name'    => $greeting,
            'cookie'  => $cookie_name,
            'path'    => \SITECOOKIEPATH,
            'domain'  => \COOKIE_DOMAIN,
            'secure'  => \is_ssl(),
        ];
        ?>
<script>
(function () {
    var data = <?php echo \wp_json_encode($payload); ?>;
    var userField = document.getElementById('user_login');
    var passField = document.getElementById('user_pass');
    var form      = document.getElementById('loginform');
    if (!userField || !passField || !form) {
        return;
    }

    // Pre-fill the username and hide its row.
    userField.value = data.login;
    userField.setAttribute('readonly', 'readonly');
    var userRow = userField.closest('p') || userField.parentNode;
    if (userRow) {
        userRow.style.display = 'none';
    }
    form.classList.add('orbital-login--welcome-back');

    // Prepend the welcome banner.
    var banner = document.createElement('div');
    banner.className = 'orbital-login__welcome';
    var hi = document.createElement('p');
    hi.className = 'orbital-login__welcome-greeting';
    hi.appendChild(document.createTextNode('Welcome back, '));
    var strong = document.createElement('strong');
    strong.textContent = data.name;
    hi.appendChild(strong);
    banner.appendChild(hi);

    var notYou = document.createElement('button');
    notYou.type = 'button';
    notYou.className = 'orbital-login__not-you';
    notYou.textContent = 'Not you? Sign in as someone else';
    notYou.addEventListener('click', function () {
        var parts = [
            data.cookie + '=',
            'path=' + data.path,
            'max-age=0',
            'SameSite=Lax'
        ];
        if (data.domain) {
            parts.push('domain=' + data.domain);
        }
        if (data.secure) {
            parts.push('Secure');
        }
        document.cookie = parts.join('; ');
        // Reload without query string so we re-enter the default
        // login flow with an empty form.
        window.location.href = window.location.pathname;
    });
    banner.appendChild(notYou);

    form.parentNode.insertBefore(banner, form);

    // Focus password so the user can type and submit straight away.
    passField.focus();
}());
</script>
        <?php
    }

    private function get_remember_cookie_name(): string
    {
        return self::REMEMBER_COOKIE_PREFIX . \COOKIEHASH;
    }
}
