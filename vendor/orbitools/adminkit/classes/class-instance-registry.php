<?php

/**
 * AdminKit Instance Registry
 *
 * Manages multiple AdminKit instances and provides detection methods
 * for determining which instance should handle the current page.
 *
 * @package AdminKit
 * @since   1.0.0
 */

namespace Orbitools\AdminKit;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Instance Registry Class
 *
 * Static registry for managing AdminKit instances and page ownership detection.
 * Follows the same pattern as Field_Registry for consistency.
 *
 * @since 1.0.0
 */
class Instance_Registry
{
    /**
     * Registered AdminKit instances
     *
     * @since 1.0.0
     * @var array
     */
    private static $instances = array();

    /**
     * Page ownership cache
     *
     * @since 1.0.0
     * @var array
     */
    private static $ownership_cache = array();

    /**
     * Register an AdminKit instance
     *
     * @since 1.0.0
     * @param string    $slug     The unique slug for this instance.
     * @param Admin_Kit $instance The AdminKit instance.
     * @return bool True on success, false if slug already exists.
     */
    public static function register_instance(string $slug, Admin_Kit $instance): bool
    {
        if (self::instance_exists($slug)) {
            return false;
        }

        self::$instances[$slug] = $instance;
        
        // Clear ownership cache when new instance is registered
        self::$ownership_cache = array();

        /**
         * Fires when an AdminKit instance is registered
         *
         * @since 1.0.0
         * @param string    $slug     The instance slug.
         * @param Admin_Kit $instance The instance object.
         */
        do_action('adminkit_instance_registered', $slug, $instance);

        return true;
    }

    /**
     * Get an AdminKit instance by slug
     *
     * @since 1.0.0
     * @param string $slug The instance slug.
     * @return Admin_Kit|null The instance or null if not found.
     */
    public static function get_instance(string $slug): ?Admin_Kit
    {
        return self::$instances[$slug] ?? null;
    }

    /**
     * Check if an instance exists
     *
     * @since 1.0.0
     * @param string $slug The instance slug.
     * @return bool True if instance exists, false otherwise.
     */
    public static function instance_exists(string $slug): bool
    {
        return isset(self::$instances[$slug]);
    }

    /**
     * Get all registered instances
     *
     * @since 1.0.0
     * @return array Array of slug => instance pairs.
     */
    public static function get_all_instances(): array
    {
        return self::$instances;
    }

    /**
     * Remove an instance from the registry
     *
     * @since 1.0.0
     * @param string $slug The instance slug.
     * @return bool True if removed, false if not found.
     */
    public static function remove_instance(string $slug): bool
    {
        if (!self::instance_exists($slug)) {
            return false;
        }

        unset(self::$instances[$slug]);
        
        // Clear ownership cache when instance is removed
        self::$ownership_cache = array();

        /**
         * Fires when an AdminKit instance is removed
         *
         * @since 1.0.0
         * @param string $slug The instance slug.
         */
        do_action('adminkit_instance_removed', $slug);

        return true;
    }

    /**
     * Get the slug of the AdminKit instance that owns the current page
     *
     * @since 1.0.0
     * @return string|null The owning instance slug or null if none.
     */
    public static function get_page_owner(): ?string
    {
        $screen = get_current_screen();
        if (!$screen) {
            return null;
        }

        // Check cache first
        if (isset(self::$ownership_cache[$screen->id])) {
            return self::$ownership_cache[$screen->id];
        }

        $owner = self::determine_page_owner($screen);
        
        // Cache the result
        self::$ownership_cache[$screen->id] = $owner;

        return $owner;
    }

    /**
     * Determine which AdminKit instance owns a screen
     *
     * @since 1.0.0
     * @param \WP_Screen $screen The screen object.
     * @return string|null The owning instance slug or null.
     */
    private static function determine_page_owner(\WP_Screen $screen): ?string
    {
        $screen_id = $screen->id;
        $registered_slugs = array_keys(self::$instances);

        // Sort by length (longest first) to match most specific slugs first
        usort($registered_slugs, function($a, $b) {
            return strlen($b) - strlen($a);
        });

        foreach ($registered_slugs as $slug) {
            // Check for exact matches first (highest priority)
            if ($screen_id === "toplevel_page_{$slug}" || 
                $screen_id === "{$slug}_page_{$slug}") {
                return $slug;
            }
        }

        foreach ($registered_slugs as $slug) {
            // Check for subpage matches
            if (strpos($screen_id, "{$slug}_page_") === 0) {
                return $slug;
            }
        }

        foreach ($registered_slugs as $slug) {
            // Fallback to partial matches (lowest priority)
            if (strpos($screen_id, $slug) !== false) {
                return $slug;
            }
        }

        return null;
    }

    /**
     * Check if current page is owned by a specific AdminKit instance
     *
     * @since 1.0.0
     * @param string $slug The instance slug to check.
     * @return bool True if the instance owns the current page.
     */
    public static function is_instance_page(string $slug): bool
    {
        return self::get_page_owner() === $slug;
    }

    /**
     * Check if current page is owned by any AdminKit instance
     *
     * @since 1.0.0
     * @return bool True if any AdminKit instance owns the current page.
     */
    public static function is_adminkit_page(): bool
    {
        return self::get_page_owner() !== null;
    }

    /**
     * Get the active AdminKit instance for the current page
     *
     * @since 1.0.0
     * @return Admin_Kit|null The active instance or null.
     */
    public static function get_active_instance(): ?Admin_Kit
    {
        $owner = self::get_page_owner();
        return $owner ? self::get_instance($owner) : null;
    }

    /**
     * Check if a page is a child of an AdminKit page
     *
     * @since 1.0.0
     * @param string|null $parent_slug Optional. Check for specific parent.
     * @return bool True if current page is a child AdminKit page.
     */
    public static function is_child_page(?string $parent_slug = null): bool
    {
        $screen = get_current_screen();
        if (!$screen) {
            return false;
        }

        // Check if the screen ID indicates a child page
        // Child pages have format: {parent_slug}_page_{child_slug}
        foreach (array_keys(self::$instances) as $slug) {
            // Check if this is a child page of this AdminKit instance
            if (preg_match('/^' . preg_quote($slug, '/') . '_page_(.+)$/', $screen->id, $matches)) {
                if ($parent_slug) {
                    return $slug === $parent_slug;
                }
                return true;
            }
        }

        return false;
    }

    /**
     * Get page hierarchy information
     *
     * @since 1.0.0
     * @return array Array with 'owner', 'is_child', 'parent' keys.
     */
    public static function get_page_info(): array
    {
        $owner = self::get_page_owner();
        $is_child = self::is_child_page();
        $screen = get_current_screen();
        
        return array(
            'owner' => $owner,
            'is_child' => $is_child,
            'parent' => $is_child && $screen ? $screen->parent_file : null,
            'screen_id' => $screen ? $screen->id : null,
        );
    }

    /**
     * Clear the ownership cache
     *
     * @since 1.0.0
     * @return void
     */
    public static function clear_cache(): void
    {
        self::$ownership_cache = array();
    }

    /**
     * Get debug information about registered instances and current page
     *
     * @since 1.0.0
     * @return array Debug information.
     */
    public static function get_debug_info(): array
    {
        return array(
            'registered_instances' => array_keys(self::$instances),
            'current_page_info' => self::get_page_info(),
            'ownership_cache' => self::$ownership_cache,
        );
    }
}