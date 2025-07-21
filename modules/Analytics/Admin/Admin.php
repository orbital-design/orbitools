<?php

/**
 * Analytics Admin Handler
 *
 * Handles all admin-related functionality for the Analytics module,
 * including module registration, settings integration, and admin notices.
 *
 * @package    Orbitools
 * @subpackage Modules/Analytics/Admin
 * @since      1.0.0
 */

namespace Orbitools\Modules\Analytics\Admin;

use Orbitools\Admin\Module_Admin_Base;
use Orbitools\Modules\Analytics\Admin\Settings;
use Orbitools\Modules\Analytics\Admin\Settings_Helper;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Analytics Admin Class
 *
 * Manages admin interface integration for the Analytics module.
 *
 * @since 1.0.0
 */
class Admin extends Module_Admin_Base
{
    /**
     * Module version
     *
     * @since 1.0.0
     * @var string
     */
    const VERSION = '1.0.0';

    /**
     * Module slug identifier
     *
     * @since 1.0.0
     * @var string
     */
    const MODULE_SLUG = 'analytics';

    /**
     * Initialize admin functionality
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        // Call parent constructor with module info
        parent::__construct(self::MODULE_SLUG, Settings::class);

        // Register module metadata
        add_filter('orbitools_available_modules', array($this, 'register_module_metadata'));
    }

    /**
     * Check if the Analytics module is enabled
     *
     * @since 1.0.0
     * @return bool True if module is enabled, false otherwise.
     */
    public function is_module_enabled(): bool
    {
        return Settings_Helper::is_module_enabled();
    }

    /**
     * Register module metadata for the admin interface
     *
     * @since 1.0.0
     * @param array $modules Existing modules array.
     * @return array Modified modules array.
     */
    public function register_module_metadata(array $modules): array
    {
        $modules['analytics'] = array(
            'name'        => __('Analytics', 'orbitools'),
            'subtitle'    => __('Google Analytics & Tag Manager', 'orbitools'),
            'description' => __('Comprehensive analytics tracking with support for GA4, Google Tag Manager, enhanced ecommerce, custom events, and privacy compliance features.', 'orbitools'),
            'icon'        => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="#32a3e2" d="M500 89c13.8-11 16-31.2 5-45s-31.2-16-45-5L319.4 151.5 211.2 70.4c-11.7-8.8-27.8-8.5-39.2.6L12 199c-13.8 11-16 31.2-5 45s31.2 16 45 5l140.6-112.5 108.2 81.1c11.7 8.8 27.8 8.5 39.2-.6L500 89zM160 256v192c0 17.7 14.3 32 32 32s32-14.3 32-32V256c0-17.7-14.3-32-32-32s-32 14.3-32 32zM32 352v96c0 17.7 14.3 32 32 32s32-14.3 32-32v-96c0-17.7-14.3-32-32-32s-32 14.3-32 32zm288-64c-17.7 0-32 14.3-32 32v128c0 17.7 14.3 32 32 32s32-14.3 32-32V320c0-17.7-14.3-32-32-32zm96-32v192c0 17.7 14.3 32 32 32s32-14.3 32-32V256c0-17.7-14.3-32-32-32s-32 14.3-32 32z"/></svg>',
            'configure_url' => admin_url('admin.php?page=orbitools&tab=modules&section=analytics'),
        );

        return $modules;
    }

}