<?php

namespace Orbitools\Core;

use Orbitools\Core\Admin\React_Admin;
use Orbitools\Core\Rest\Rest_Server;
use Orbitools\Core\Updater\Updater;

/**
 * Loader
 *
 * Orchestrates plugin boot: data migrations, the React admin, the
 * Updater, the block CSS loader, the REST API, and the module
 * registry. AdminKit and its orchestrator were retired in v3 Phase 7.
 *
 * @package Orbitools
 * @since 2.0.0
 */
class Loader
{
    private Module_Manager $module_manager;
    private React_Admin $react_admin;
    private Updater $updater;
    private Rest_Server $rest_server;

    public function init(): void
    {
        // Run pending data migrations before anything reads settings —
        // keeps Settings_Manager's request-scoped cache from priming on
        // stale data.
        Migrations::run();

        // Initialize core classes.
        $this->react_admin = new React_Admin();
        $this->updater = new Updater();

        // Wire up non-render-blocking block CSS loading.
        Block_Style_Loader::init();

        // Bootstrap the v3 REST API. The instance registers its own
        // routes on rest_api_init; Loader just keeps it alive.
        $this->rest_server = new Rest_Server();

        // Initialize modules via the registry. Disabled modules are never
        // autoloaded; their constructors and asset registrations are skipped.
        $this->module_manager = new Module_Manager();
        $this->module_manager->register_built_in();
        $this->module_manager->boot();
    }
}
