<?php

namespace Orbitools\Core;

use Orbitools\Core\Admin\Admin;
use Orbitools\Core\Updater\Updater;
use Orbitools\Core\Toolbar_FAB;
use Orbitools\Core\SpacingConfig;
use Orbitools\Core\Helpers\Gaps_CSS_Generator;
use Orbitools\Controls\Typography_Presets\Typography_Presets;
use Orbitools\Modules\Layout_Guides\Layout_Guides;
use Orbitools\Modules\Menu_Groups\Menu_Groups;
use Orbitools\Modules\Menu_Dividers\Menu_Dividers;
use Orbitools\Modules\Analytics\Analytics;
use Orbitools\Blocks\Collection\Collection;
use Orbitools\Blocks\Entry\Entry;
use Orbitools\Blocks\Query_Loop\Query_Loop;
use Orbitools\Blocks\Spacer\Spacer;
use Orbitools\Blocks\Read_More\Read_More;
use Orbitools\Blocks\Marquee\Marquee;
use Orbitools\Blocks\Group\Group;
use Orbitools\Modules\User_Avatars\User_Avatars;
use Orbitools\Controls\Spacings_Controls\Spacings_Controls;

/**
 * Class Loader
 *
 * Loads core classes and modules for Orbitools.
 */
class Loader
{
    /**
     * Holds the loaded modules.
     *
     * @var array
     */
    private $modules = [];

    /**
     * Admin instance.
     *
     * @var Admin
     */
    private $admin;

    /**
     * Updater instance.
     *
     * @var Updater
     */
    private $updater;

    /**
     * Block slugs that have frontend CSS.
     *
     * @var string[]
     */
    private const STYLED_BLOCKS = [
        'collection',
        'entry',
        'marquee',
        'group',
        'query-loop',
        'read-more',
    ];

    /**
     * Register block frontend styles (without enqueuing).
     *
     * Styles are registered here and enqueued per-block during render_block,
     * so they output in the footer via print_late_styles() (non-render-blocking).
     *
     * @return void
     */
    public function register_block_styles(): void
    {
        foreach (self::STYLED_BLOCKS as $block) {
            $css_file = ORBITOOLS_DIR . "build/blocks/{$block}/index.css";

            if (!file_exists($css_file)) {
                continue;
            }

            wp_register_style(
                "orb-{$block}-style",
                plugins_url("build/blocks/{$block}/index.css", ORBITOOLS_FILE),
                [],
                (string) filemtime($css_file)
            );
        }
    }

    /**
     * Enqueue a block's frontend style when that block is rendered.
     *
     * Fires on render_block — after wp_head has already printed, so styles
     * output via print_late_styles() in wp_footer (non-render-blocking).
     *
     * @param string $content      The block HTML content.
     * @param array  $parsed_block The parsed block data.
     * @return string Unmodified block content.
     */
    public function enqueue_rendered_block_style(string $content, array $parsed_block): string
    {
        $block_name = $parsed_block['blockName'] ?? '';

        if (strpos($block_name, 'orb/') === 0) {
            $slug   = substr($block_name, 4);
            $handle = "orb-{$slug}-style";

            if (wp_style_is($handle, 'registered') && !wp_style_is($handle, 'enqueued')) {
                wp_enqueue_style($handle);
            }
        }

        return $content;
    }

    /**
     * Make block <link> tags non-render-blocking by applying
     * the media="print" onload async pattern.
     *
     * @param string $tag    The <link> tag HTML.
     * @param string $handle The style handle.
     * @return string Modified HTML.
     */
    public function async_block_styles(string $tag, string $handle): string
    {
        if (strpos($handle, 'orb-') === 0 && substr($handle, -6) === '-style') {
            $tag = preg_replace(
                '/(?<=\s)media=[\'"]all[\'"]/',
                'media="print" onload="this.media=\'all\'"',
                $tag
            );
        }

        return $tag;
    }

    /**
     * Initializes core classes and modules.
     *
     * @return void
     */
    public function init()
    {
        // Load the OrbiTools AdminKit
        if (file_exists(ORBITOOLS_DIR . 'vendor/orbitools/adminkit/adminkit.php')) {
            require_once ORBITOOLS_DIR . 'vendor/orbitools/adminkit/adminkit.php';
        }

        // Initialize core classes.
        $this->admin = new Admin();
        $this->updater = new Updater();

        // Initialize Spacing Configuration system
        SpacingConfig::init();

        // Initialize Gaps CSS generation
        Gaps_CSS_Generator::init();

        // Initialize Toolbar FAB
        new Toolbar_FAB();

        // Register block frontend styles (enqueued per-block via render_block → footer).
        add_action('wp_enqueue_scripts', [$this, 'register_block_styles']);
        add_filter('render_block', [$this, 'enqueue_rendered_block_style'], 10, 2);
        add_filter('style_loader_tag', [$this, 'async_block_styles'], 10, 2);

        // Initialize modules.
        $this->modules[] = new Typography_Presets();
        $this->modules[] = new Layout_Guides();
        $this->modules[] = new Menu_Groups();
        $this->modules[] = new Menu_Dividers();
        $this->modules[] = new Analytics();
        // Initialize individual blocks
        $this->modules[] = new Collection();
        $this->modules[] = new Entry();
        $this->modules[] = new Query_Loop();
        $this->modules[] = new Spacer();
        $this->modules[] = new Read_More();
        $this->modules[] = new Marquee();
        // $this->modules[] = new Group();
        $this->modules[] = new Spacings_Controls();
        $this->modules[] = new User_Avatars();
    }
}
