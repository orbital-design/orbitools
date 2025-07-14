<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @package    Orbital_Editor_Suite
 * @subpackage Orbital_Editor_Suite/includes/admin
 */

namespace Orbital\Editor_Suite\Admin;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and hooks for enqueuing
 * the admin-specific stylesheet and JavaScript.
 */
class Admin
{

    /**
     * The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     */
    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Check if we're on an Orbital admin page.
     */
    private function is_orbital_admin_page($hook)
    {
        return strpos($hook, 'orbital-editor-suite') !== false ||
            strpos($hook, 'orbital_editor_suite') !== false;
    }
}