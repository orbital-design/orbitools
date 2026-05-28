<?php

namespace Orbitools\Modules\Orbital_Login;

use Orbitools\Core\Abstracts\Module_Base;
use Orbitools\Core\Helpers\Settings_Manager;

/**
 * Orbital Login module.
 *
 * Restyles wp-login.php as a two-column Orbital-branded screen: the
 * bundled background photo on the left, white card with the Orbital
 * wordmark + form + headline + copyright on the right. The visual
 * treatment is fixed — every Orbital site looks the same — so none
 * of it is exposed as a setting.
 *
 * The one configurable bit is a UX shortcut:
 *   - remember_last_user → drop a per-device cookie after a
 *     successful login so the next wp-login.php render greets the
 *     user by name and only asks for the password.
 *
 * @package Orbitools
 * @since 3.2.0
 */
final class Orbital_Login extends Module_Base
{
    // Brand constants — hardcoded on purpose so every Orbital site
    // renders the same login screen. If you find yourself wanting
    // to vary one of these per-site, add a setting instead.
    private const LOGO_URL    = 'https://orbital.co.uk';
    private const LOGO_TITLE  = 'Orbital';
    private const HEADLINE    = 'Nice to see you again';
    private const FOOTER_TEXT = '© Orbital';

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
        // Brand chrome — fixed across all Orbital sites.
        \add_action('login_head', [$this, 'render_inline_vars']);
        \add_filter('login_message', [$this, 'prepend_headline']);
        \add_action('login_footer', [$this, 'render_static_chrome']);
        \add_filter('login_headerurl', [$this, 'filter_login_headerurl']);
        \add_filter('login_headertext', [$this, 'filter_login_headertext']);

        // Per-site UX shortcut.
        $settings = new Settings_Manager();
        if ($settings->get_module_setting($this->get_slug(), 'remember_last_user', false)) {
            \add_action('wp_login', [$this, 'remember_last_user'], 10, 2);
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

    /**
     * Emit `:root` CSS variables for the hero image and wordmark.
     * Kept inline (rather than in the static stylesheet) so the
     * plugin URL — which only PHP can resolve at runtime — drives
     * the asset paths without baking the host into login.css.
     */
    public function render_inline_vars(): void
    {
        $hero_url = ORBITOOLS_URL . 'build/media/orbital-login-background.jpg';
        $wordmark = ORBITOOLS_URL . 'build/media/orbital-wordmark.svg';

        $vars = [
            "--orb-login-wordmark: url('" . \esc_url($wordmark) . "');",
            "--orb-login-hero: url('" . \esc_url($hero_url) . "');",
        ];

        echo "<style id=\"orbitools-orbital-login-vars\">:root{" . implode(' ', $vars) . "}</style>\n";
    }

    /**
     * Prepend the brand headline above whatever login_message would
     * otherwise render (errors, action notices, etc.). The headline
     * is an h2 so it complements the existing h1 logo without
     * displacing assistive-tech landmarks.
     */
    public function prepend_headline(string $message): string
    {
        return '<h2 class="orbital-login__headline">' . \esc_html(self::HEADLINE) . '</h2>' . $message;
    }

    /**
     * Render the form-card footer (just the copyright, bottom-right).
     * Done via login_footer because the element sits outside #login
     * so it can hug the bottom edge of the right column.
     */
    public function render_static_chrome(): void
    {
        echo '<div class="orbital-login__card-footer">';
        echo '<span class="orbital-login__card-footer-left"></span>';
        echo '<span class="orbital-login__card-footer-right">' . \esc_html(self::FOOTER_TEXT) . '</span>';
        echo "</div>\n";
    }

    public function filter_login_headerurl(): string
    {
        return \esc_url_raw(self::LOGO_URL);
    }

    public function filter_login_headertext(): string
    {
        return self::LOGO_TITLE;
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
