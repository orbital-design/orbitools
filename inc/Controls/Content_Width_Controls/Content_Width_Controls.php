<?php
namespace Orbitools\Controls\Content_Width_Controls;

use Orbitools\Core\Abstracts\Module_Base;

/**
 * Content Width Controls Module
 *
 * Adds a content-width control (Full / Wide / Standard) to any block that
 * declares `orbitools.contentWidth` support and is aligned full. The block
 * keeps its full-bleed background while its inner content is constrained to
 * the site content ("standard") or wide width.
 *
 * The control is global — attribute registration, the inspector UI, the value
 * resolver and the constraint CSS all live here. The nested wrapper that does
 * the actual constraining is composed per-block (Collection / Grid render
 * callbacks + edit components), because constraining a flex / grid container
 * needs a real outer/inner div pair, not just a class.
 *
 * @package Orbitools
 * @since 1.0.0
 */
class Content_Width_Controls extends Module_Base
{
    public function __construct()
    {
        parent::__construct();
    }

    public function get_slug(): string
    {
        return 'content-width-controls';
    }

    public function get_name(): string
    {
        return __('Content Width Controls', 'orbitools');
    }

    public function get_description(): string
    {
        return __('Content width control (Full / Wide / Standard) for full-aligned blocks with orbitools.contentWidth support.', 'orbitools');
    }

    public function get_version(): string
    {
        return '1.0.0';
    }

    public function init(): void
    {
        add_action('enqueue_block_editor_assets', [$this, 'enqueue_editor_assets']);

        // The constraint CSS is generic ([data-constrain="standard|wide"]), so
        // it ships once for both contexts. enqueue_block_assets fires on the
        // frontend AND inside the editor canvas iframe (where blocks render),
        // so a single hook covers both without an is_admin() split.
        add_action('enqueue_block_assets', [$this, 'enqueue_constraint_css']);
    }

    public function enqueue_editor_assets(): void
    {
        $asset_url = ORBITOOLS_URL . 'build/admin/js/controls/content-width/';

        // Attribute registration (must load first).
        \wp_enqueue_script(
            'orbitools-content-width-attributes',
            $asset_url . 'editor-content-width-attribute-registration.js',
            ['wp-hooks', 'wp-blocks'],
            $this->get_version(),
            true
        );

        // Inspector control (loads after attributes).
        \wp_enqueue_script(
            'orbitools-content-width-controls',
            $asset_url . 'editor-content-width-register-controls.js',
            ['wp-hooks', 'wp-compose', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-blocks'],
            $this->get_version(),
            true
        );
    }

    /**
     * Ship the generic constraint CSS (frontend + editor canvas iframe).
     *
     * Any element carrying data-constrain="standard|wide" is capped at the
     * matching theme width and centred. Blocks emit that attribute on their
     * inner (constrained) wrapper; the outer wrapper bleeds full-width.
     */
    public function enqueue_constraint_css(): void
    {
        // Resolve the theme's real content / wide sizes. WordPress only exposes
        // --wp--style--global--content-size as a usable custom property inside
        // the editor — on the frontend it inlines the value into its
        // constrained-layout rules instead — so we read theme.json directly and
        // inline it as the fallback. The var still wins where it exists (editor).
        $content_size = '1200px';
        $wide_size    = '1280px';
        if (\function_exists('wp_get_global_settings')) {
            $layout = \wp_get_global_settings(['layout']);
            if (!empty($layout['contentSize'])) {
                $content_size = $layout['contentSize'];
                $wide_size    = $layout['contentSize'];
            }
            if (!empty($layout['wideSize'])) {
                $wide_size = $layout['wideSize'];
            }
        }

        // theme.json sizes are trusted but may be clamp()/calc()/var(); strip
        // only the characters that could break out of the CSS value.
        $clean = static function ($value, string $fallback): string {
            $value = trim((string) $value);
            if ($value === '') {
                return $fallback;
            }
            return preg_replace('/[;{}<>"\\\\]/', '', $value);
        };
        $content_size = $clean($content_size, '1200px');
        $wide_size    = $clean($wide_size, '1280px');

        $css = '[data-constrain="standard"]{max-width:var(--wp--style--global--content-size,' . $content_size . ');margin-left:auto;margin-right:auto}'
             . '[data-constrain="wide"]{max-width:var(--wp--style--global--wide-size,' . $wide_size . ');margin-left:auto;margin-right:auto}';

        \wp_register_style('orbitools-content-width', false);
        \wp_enqueue_style('orbitools-content-width');
        \wp_add_inline_style('orbitools-content-width', $css);
    }

    public function get_default_settings(): array
    {
        return [];
    }
}
