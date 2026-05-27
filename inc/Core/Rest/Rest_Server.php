<?php

namespace Orbitools\Core\Rest;

/**
 * REST Server
 *
 * Single entry point for the Orbitools REST API. Instantiated once
 * from {@see \Orbitools\Core\Loader::init()}; defers actual route
 * registration to `rest_api_init` so the WP REST infrastructure is
 * ready when controllers run.
 *
 * Namespace: /orbitools/v1/
 *
 * @package Orbitools
 * @since 3.0.0
 */
final class Rest_Server
{
    public const REST_NAMESPACE = 'orbitools/v1';

    public function __construct()
    {
        \add_action('rest_api_init', [$this, 'register']);
    }

    /**
     * Instantiate each resource controller and let it register its
     * own routes. Controllers are self-contained; this method's only
     * job is enumerating them.
     */
    public function register(): void
    {
        (new Modules_Controller())->register_routes();
        (new Settings_Controller())->register_routes();
        (new Field_Types_Controller())->register_routes();
        (new Theme_Pages_Controller())->register_routes();
    }
}
