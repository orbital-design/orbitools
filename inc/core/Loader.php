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
     * Remove path metadata from block style handles so WordPress
     * serves them as external <link> tags instead of inlining.
     *
     * @return void
     */
    public function disable_block_css_inlining(): void
    {
        $wp_styles = wp_styles();

        foreach ($wp_styles->registered as $handle => $style) {
            if (strpos($handle, 'orb-') === 0 && isset($style->extra['path'])) {
                // Skip editor-only styles — inlining is fine in the admin
                if (substr($handle, -13) === '-editor-style') {
                    continue;
                }
                $wp_styles->add_data($handle, 'path', '');
            }
        }
    }

    /**
     * Make block stylesheets non-render-blocking by swapping to
     * media="print" with an onload handler that flips to "all".
     *
     * @param string $html   The <link> tag HTML.
     * @param string $handle The style handle.
     * @return string Modified tag HTML.
     */
    public function async_block_styles(string $html, string $handle): string
    {
        if (strpos($handle, 'orb-') !== 0 || substr($handle, -13) === '-editor-style') {
            return $html;
        }

        // Replace media="all" with media="print" onload="this.media='all'"
        $html = str_replace(
            "media='all'",
            "media='print' onload=\"this.media='all'\"",
            $html
        );

        // Also handle double-quoted variant
        $html = str_replace(
            'media="all"',
            'media="print" onload="this.media=\'all\'"',
            $html
        );

        // Add noscript fallback after the link tag
        if (strpos($html, "media='print'") !== false || strpos($html, 'media="print"') !== false) {
            $noscript = '<noscript>' . str_replace(
                ["media='print' onload=\"this.media='all'\"", 'media="print" onload="this.media=\'all\'"'],
                ["media='all'", 'media="all"'],
                $html
            ) . '</noscript>';
            $html .= $noscript;
        }

        return $html;
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

        // Prevent WordPress from inlining block CSS — serve as cacheable <link> tags
        add_action('wp_enqueue_scripts', [$this, 'disable_block_css_inlining']);

        // Make block CSS non-render-blocking on the frontend
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
