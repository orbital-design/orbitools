<?php

namespace Orbitools\Admin;

/**
 * Class Admin
 *
 * Handles admin functionality for Orbitools.
 */
class Admin
{
    /**
     * Admin constructor.
     *
     * @return void
     */
    public function __construct()
    {
        add_action('admin_menu', [$this, 'register_menu']);
    }

    /**
     * Registers the Orbitools admin menu.
     *
     * @return void
     */
    public function register_menu()
    {
        add_menu_page(
            __('Orbitools', 'orbitools'),
            __('Orbitools', 'orbitools'),
            'manage_options',
            'orbitools',
            [$this, 'render_page']
        );
    }

    /**
     * Renders the admin page.
     *
     * @return void
     */
    public function render_page()
    {
        echo '<h1>' . esc_html__('Orbitools Admin', 'orbitools') . '</h1>';
    }
}