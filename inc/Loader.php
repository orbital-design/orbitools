<?php

namespace Orbitools;

use Orbitools\Admin\Admin;
use Orbitools\Updater\Updater;
use Orbitools\Modules\ExampleModule\ExampleModule;
use Orbitools\Modules\Typography_Presets\Typography_Presets;

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
        $this->load_orbi_admin_kit();

        // Initialize core classes.
        $this->admin = new Admin();
        $this->updater = new Updater();

        // Initialize modules.
        $this->modules[] = new ExampleModule();
        $this->modules[] = new Typography_Presets();
    }

    /**
     * Load the OrbiTools AdminKit.
     *
     * @return void
     */
    private function load_orbi_admin_kit(): void
    {
        if (file_exists(ORBITOOLS_DIR . 'vendor/orbi-admin-kit/loader.php')) {
            require_once ORBITOOLS_DIR . 'vendor/orbi-admin-kit/loader.php';
        }
    }
}