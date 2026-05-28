<?php

namespace Orbitools\Modules\Orbital_Colour_Scheme;

use Orbitools\Core\Abstracts\Module_Base;
use Orbitools\Core\Helpers\Settings_Manager;

/**
 * Orbital Colour Scheme module.
 *
 * Registers a custom WP admin colour scheme called "Orbital" using
 * the Orbital brand palette:
 *
 *   #1d303a — navy base (chrome background)
 *   #ffffff — text + icon on the navy
 *   #32a3e2 — highlight + notification (links, hover, active item)
 *
 * The CSS asset is a pre-compiled, minified stylesheet originally
 * authored in the dream-and-leap theme. We ship it as a static
 * asset (wrapped in a .scss file so webpack re-emits it through the
 * usual pipeline) rather than rebuilding from source, because the
 * source SCSS depends on theme-level abstracts (`clr()`, theme-vars)
 * that have no equivalent here.
 *
 * Two settings control behaviour:
 *   - force_for_all_users → override every user's admin_color choice
 *   - hide_picker         → strip the colour scheme picker off the
 *                           user profile page (only relevant when
 *                           the scheme is forced; otherwise users
 *                           need the picker to opt in)
 *
 * @package Orbitools
 * @since 3.2.0
 */
final class Orbital_Colour_Scheme extends Module_Base
{
    public function get_slug(): string
    {
        return 'orbital-colour-scheme';
    }

    public function get_name(): string
    {
        return \__('Orbital Colour Scheme', 'orbitools');
    }

    public function get_description(): string
    {
        return \__('Register an Orbital admin colour scheme and apply it to all users.', 'orbitools');
    }

    public function init(): void
    {
        \add_action('admin_init', [$this, 'register_colour_scheme']);

        $settings = new Settings_Manager();
        if ($settings->get_module_setting($this->get_slug(), 'force_for_all_users', true)) {
            \add_filter('get_user_option_admin_color', [$this, 'force_orbital_scheme'], 5);
        }
        if ($settings->get_module_setting($this->get_slug(), 'hide_picker', true)) {
            \add_action('admin_head-profile.php', [$this, 'hide_colour_scheme_picker']);
        }
    }

    /**
     * Register the Orbital admin colour scheme. The four-colour
     * array shown to users in the picker matches the dominant
     * colours of the compiled stylesheet (navy / white / blue / blue).
     */
    public function register_colour_scheme(): void
    {
        \wp_admin_css_color(
            'orbital',
            \__('Orbital', 'orbitools'),
            ORBITOOLS_URL . 'build/admin/css/modules/orbital-colour-scheme/orbital.css',
            ['#1d303a', '#ffffff', '#32a3e2', '#32a3e2']
        );
    }

    /**
     * Force every user onto the Orbital scheme regardless of their
     * stored preference.
     */
    public function force_orbital_scheme($colour_scheme): string
    {
        return 'orbital';
    }

    /**
     * Remove the "Admin Color Scheme" chooser from Users → Profile
     * so the forced scheme can't be switched away.
     */
    public function hide_colour_scheme_picker(): void
    {
        \remove_action('admin_color_scheme_picker', 'admin_color_scheme_picker');
    }
}
