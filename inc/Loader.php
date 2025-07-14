<?php

namespace Orbitools;

use Orbitools\Admin\Admin;
use Orbitools\Updater\Updater;
use Orbitools\Modules\ExampleModule\ExampleModule;

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
        // Initialize core classes.
        $this->admin = new Admin();
        $this->updater = new Updater();

        // Initialize modules.
        $this->modules[] = new ExampleModule();
    }
}