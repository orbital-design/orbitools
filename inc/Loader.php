<?php

namespace Orbitools;

use Orbitools\Admin\Admin;
use Orbitools\Updater\Updater;
use Orbitools\Toolbar_FAB;
use Orbitools\SpacingConfig;
use Orbitools\Modules\Typography_Presets\Typography_Presets;
use Orbitools\Modules\Layout_Guides\Layout_Guides;
use Orbitools\Modules\Menu_Groups\Menu_Groups;
use Orbitools\Modules\Menu_Dividers\Menu_Dividers;
use Orbitools\Modules\Analytics\Analytics;
use Orbitools\Modules\Layout_Blocks\Layout_Blocks;
use Orbitools\Modules\User_Avatars\User_Avatars;
use Orbitools\Modules\Dimensions_Controls\Dimensions_Controls;

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

        // Initialize Toolbar FAB
        new Toolbar_FAB();

        // Initialize modules.
        $this->modules[] = new Typography_Presets();
        $this->modules[] = new Layout_Guides();
        $this->modules[] = new Menu_Groups();
        $this->modules[] = new Menu_Dividers();
        $this->modules[] = new Analytics();
        $this->modules[] = new Layout_Blocks();
        $this->modules[] = new Dimensions_Controls();
        $this->modules[] = new User_Avatars();
    }
}
