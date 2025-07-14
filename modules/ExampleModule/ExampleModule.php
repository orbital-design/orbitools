<?php

namespace Orbitools\Modules\ExampleModule;

/**
 * Class ExampleModule
 *
 * Example module for Orbitools.
 */
class ExampleModule
{
    /**
     * ExampleModule constructor.
     *
     * @return void
     */
    public function __construct()
    {
        add_action('init', [$this, 'register_custom_post_type']);
    }

    /**
     * Registers a custom post type for the example module.
     *
     * @return void
     */
    public function register_custom_post_type()
    {
        register_post_type('orbitools_example', [
            'label' => __('Example', 'orbitools'),
            'public' => true,
            'supports' => ['title', 'editor'],
        ]);
    }
}