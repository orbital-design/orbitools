<?php

/**
 * Base Module Admin Class
 *
 * Provides common functionality for module admin classes to reduce code duplication.
 * Handles standard module registration patterns for AdminKit integration.
 *
 * @package    Orbitools
 * @subpackage Admin
 * @since      1.0.0
 */

namespace Orbitools\Admin;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Module Admin Base Class
 *
 * Abstract base class that provides common AdminKit integration functionality
 * for OrbiTools modules, reducing code duplication across module admin classes.
 *
 * @since 1.0.0
 */
abstract class Module_Admin_Base
{
    /**
     * Module slug identifier
     *
     * @since 1.0.0
     * @var string
     */
    protected $module_slug;

    /**
     * Settings class name
     *
     * @since 1.0.0
     * @var string
     */
    protected $settings_class;

    /**
     * Constructor
     *
     * @since 1.0.0
     * @param string $module_slug Module identifier slug.
     * @param string $settings_class Full class name of the module's Settings class.
     */
    public function __construct(string $module_slug, string $settings_class)
    {
        $this->module_slug = $module_slug;
        $this->settings_class = $settings_class;

        // Register with admin framework
        add_filter('orbitools_adminkit_structure', array($this, 'register_adminkit_structure'));
        add_filter('orbitools_adminkit_fields', array($this, 'register_adminkit_fields'));
    }

    /**
     * Check if the module is enabled
     *
     * @since 1.0.0
     * @return bool True if module is enabled, false otherwise.
     */
    abstract public function is_module_enabled(): bool;

    /**
     * Register admin structure for AdminKit
     *
     * @since 1.0.0
     * @param array $structure Existing structure array.
     * @return array Modified structure array.
     */
    public function register_adminkit_structure(array $structure): array
    {
        // Only register structure if module is enabled
        if (!$this->is_module_enabled()) {
            return $structure;
        }

        if (!isset($structure['modules']['sections'])) {
            $structure['modules']['sections'] = array();
        }

        // Get structure from Settings class
        if (class_exists($this->settings_class) && method_exists($this->settings_class, 'get_admin_structure')) {
            $settings_structure = $this->settings_class::get_admin_structure();
            $structure['modules']['sections'] = array_merge(
                $structure['modules']['sections'],
                $settings_structure['sections']
            );
        }

        return $structure;
    }

    /**
     * Register settings fields for AdminKit
     *
     * @since 1.0.0
     * @param array $settings Existing settings array.
     * @return array Modified settings array.
     */
    public function register_adminkit_fields(array $settings): array
    {
        // Only register settings if module is enabled
        if (!$this->is_module_enabled()) {
            return $settings;
        }

        if (!isset($settings['modules'])) {
            $settings['modules'] = array();
        }

        // Get settings from Settings class
        if (class_exists($this->settings_class) && method_exists($this->settings_class, 'get_field_definitions')) {
            $module_settings = $this->settings_class::get_field_definitions();
            $settings['modules'] = array_merge($settings['modules'], $module_settings);
        }

        return $settings;
    }
}