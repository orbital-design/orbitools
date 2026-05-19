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

use Orbitools\Core\Admin\Module_Admin_Base;
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

        // Note: Module metadata is registered in the main Analytics class constructor
        // so it's always available regardless of enabled state
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
}