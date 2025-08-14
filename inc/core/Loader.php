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
        $this->modules[] = new Spacings_Controls();
        $this->modules[] = new User_Avatars();
    }
}
