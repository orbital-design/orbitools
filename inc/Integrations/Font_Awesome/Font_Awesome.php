<?php

declare(strict_types=1);

namespace Orbitools\Integrations\Font_Awesome;

use Orbitools\Core\Abstracts\Module_Base;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Font Awesome integration.
 *
 * - Loads the kit script on the frontend (`<head>`, deferred + crossorigin)
 *   when a kit token is available.
 * - Optionally emits a `<link rel=preconnect>` so the kit's CDN handshake
 *   completes before the script tag is encountered.
 * - Forwards an API key / kit token to the ACF Font Awesome plugin via its
 *   `ACFFA_fa_api_key` / `ACFFA_fa_kit_token` filters; works around a long-
 *   standing PHP 8+ `json_decode` fatal that fires when a Font Awesome
 *   field is rendered inside an ACF repeater.
 *
 * Credentials can be supplied two ways:
 *   1. Settings on this module's page (saved to `orbitools_settings`).
 *   2. `wp-config.php` constants — `FONT_AWESOME_KIT_TOKEN`,
 *      `FONT_AWESOME_API_KEY`, `FONT_AWESOME_PSEUDO_ELEMENTS`.
 *
 * A non-empty setting wins over the constant, so admins can override on a
 * per-site basis without redeploying.
 *
 * Ported from the dream-and-leap theme's `Logger\Font_Awesome\Component`.
 *
 * @package Orbitools
 * @since   1.0.0
 */
final class Font_Awesome extends Module_Base
{
    protected const VERSION = '1.0.0';

    public function get_slug(): string
    {
        return 'font-awesome';
    }

    public function get_name(): string
    {
        return \__('Font Awesome', 'orbitools');
    }

    public function get_description(): string
    {
        return \__('Load a Font Awesome kit on the frontend and bridge credentials to the ACF Font Awesome plugin.', 'orbitools');
    }

    public function init(): void
    {
        // ACF Font Awesome bridge filters are cheap — they no-op when
        // the plugin isn't installed, so there's no point gating their
        // registration on plugin detection.
        \add_filter('ACFFA_fa_api_key',   [$this, 'filter_acffa_api_key']);
        \add_filter('ACFFA_fa_kit_token', [$this, 'filter_acffa_kit_token']);

        if ($this->is_setting_on('fix_acf_repeater_value', true)) {
            \add_filter('acf/load_value/type=font-awesome', [$this, 'fix_repeater_value'], 10, 3);
        }

        $kit_token = $this->resolved_kit_token();
        if ($kit_token === '') {
            // No kit configured — leave the frontend alone.
            return;
        }

        \add_action('wp_enqueue_scripts', [$this, 'enqueue_kit']);
        \add_filter('script_loader_tag',  [$this, 'add_kit_attributes'], 10, 2);

        if ($this->is_setting_on('preconnect', true)) {
            \add_action('wp_head', [$this, 'emit_preconnect'], 1);
        }
    }

    public function get_default_settings(): array
    {
        return [
            'kit_token'              => '',
            'api_key'                => '',
            'preconnect'             => true,
            'pseudo_elements'        => false,
            'fix_acf_repeater_value' => true,
        ];
    }

    // =========================================================================
    // Frontend kit
    // =========================================================================

    public function enqueue_kit(): void
    {
        if (\is_admin()) {
            return;
        }

        $token = $this->resolved_kit_token();
        if ($token === '') {
            return;
        }

        \wp_enqueue_script(
            'font-awesome-kit',
            'https://kit.fontawesome.com/' . $token . '.js',
            [],
            null,
            false
        );
    }

    /**
     * Tag the kit script with `defer crossorigin="anonymous"`, plus the
     * `data-search-pseudo-elements` opt-in when the setting (or the
     * `FONT_AWESOME_PSEUDO_ELEMENTS` constant) is on.
     *
     * `defer` lets the kit download in parallel with parsing without
     * blocking render, but executes before DOMContentLoaded — so the
     * kit's MutationObserver activates in time for any pseudo-element
     * scanning to find icons before they paint.
     */
    public function add_kit_attributes(string $tag, string $handle): string
    {
        if ($handle !== 'font-awesome-kit') {
            return $tag;
        }

        $attrs = 'defer crossorigin="anonymous"';

        if ($this->resolved_pseudo_elements()) {
            $attrs .= ' data-search-pseudo-elements';
        }

        return (string) str_replace(' src=', ' ' . $attrs . ' src=', $tag);
    }

    public function emit_preconnect(): void
    {
        if (\is_admin()) {
            return;
        }
        echo '<link rel="preconnect" href="https://kit.fontawesome.com" crossorigin>' . "\n";
    }

    // =========================================================================
    // ACF Font Awesome bridge
    // =========================================================================

    /**
     * Fix ACF Font Awesome field values rendered inside a repeater.
     *
     * The plugin's render_field calls `json_decode($value)` — fine when
     * the value is the stored JSON string, but ACF passes an already-
     * decoded array when the field sits inside a repeater. PHP 8+ then
     * fatals on the non-string arg. Re-encode so the plugin's own
     * decode round-trips cleanly.
     *
     * @param mixed $value
     * @param mixed $post_id
     * @param mixed $field
     * @return mixed
     */
    public function fix_repeater_value($value, $post_id, $field)
    {
        if (is_array($value)) {
            return \wp_json_encode($value);
        }
        return $value;
    }

    /**
     * @param mixed $key Existing value (plugin passes false as the default).
     * @return string|false
     */
    public function filter_acffa_api_key($key = false)
    {
        $resolved = $this->resolved_api_key();
        return $resolved !== '' ? $resolved : $key;
    }

    /**
     * @param mixed $token Existing value (plugin passes false as the default).
     * @return string|false
     */
    public function filter_acffa_kit_token($token = false)
    {
        $resolved = $this->resolved_kit_token();
        return $resolved !== '' ? $resolved : $token;
    }

    // =========================================================================
    // Setting resolution
    // =========================================================================

    /**
     * Non-empty saved setting wins; otherwise fall back to the
     * corresponding `wp-config.php` constant.
     */
    private function resolved_kit_token(): string
    {
        $setting = (string) $this->get_setting('kit_token', '');
        if ($setting !== '') {
            return $setting;
        }
        return defined('FONT_AWESOME_KIT_TOKEN') ? (string) constant('FONT_AWESOME_KIT_TOKEN') : '';
    }

    private function resolved_api_key(): string
    {
        $setting = (string) $this->get_setting('api_key', '');
        if ($setting !== '') {
            return $setting;
        }
        return defined('FONT_AWESOME_API_KEY') ? (string) constant('FONT_AWESOME_API_KEY') : '';
    }

    private function resolved_pseudo_elements(): bool
    {
        // Setting wins; FONT_AWESOME_PSEUDO_ELEMENTS is a fallback for
        // installs already using the theme version's constant.
        if ($this->is_setting_on('pseudo_elements', false)) {
            return true;
        }
        return defined('FONT_AWESOME_PSEUDO_ELEMENTS') && (bool) constant('FONT_AWESOME_PSEUDO_ELEMENTS');
    }

    /**
     * Tiny helper around Module_Base::get_setting that coerces the
     * stored value to a bool with a sane per-call default — same
     * shape Core Cleanup uses for its toggle reads.
     */
    private function is_setting_on(string $key, bool $default = false): bool
    {
        $value = $this->get_setting($key, $default);
        if (is_bool($value)) {
            return $value;
        }
        if (is_string($value)) {
            return $value !== '' && $value !== '0' && strtolower($value) !== 'false';
        }
        return (bool) $value;
    }
}
