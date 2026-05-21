<?php

namespace Orbitools\Core\Interfaces;

/**
 * Module Interface
 * 
 * Defines the standard contract that all OrbiTools modules should follow.
 * This ensures consistency across modules while allowing flexibility in implementation.
 * 
 * @package Orbitools
 * @since 1.0.0
 */
interface Module_Interface
{
    /**
     * Get the module's unique slug identifier
     * 
     * @return string Module slug (e.g., 'analytics', 'menu-dividers')
     */
    public function get_slug(): string;

    /**
     * Get the module's display name
     * 
     * @return string Human-readable module name
     */
    public function get_name(): string;

    /**
     * Get the module's description
     * 
     * @return string Brief description of what the module does
     */
    public function get_description(): string;

    /**
     * Get the module's version
     * 
     * @return string Version number
     */
    public function get_version(): string;

    /**
     * Check if the module is currently enabled
     * 
     * @return bool True if enabled, false otherwise
     */
    public function is_enabled(): bool;

    /**
     * Initialize the module
     * Called when the module should set up its functionality
     * 
     * @return void
     */
    public function init(): void;

    /**
     * Get the module's default settings
     * 
     * @return array Default settings array
     */
    public function get_default_settings(): array;
}