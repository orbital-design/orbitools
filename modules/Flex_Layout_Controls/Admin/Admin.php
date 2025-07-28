<?php

/**
 * Flex Layout Controls Admin Handler
 *
 * Handles all admin-related functionality for the Flex Layout Controls module,
 * including module registration, settings integration, and admin notices.
 *
 * @package    Orbitools
 * @subpackage Modules/Flex_Layout_Controls/Admin
 * @since      1.0.0
 */

namespace Orbitools\Modules\Flex_Layout_Controls\Admin;

use Orbitools\Admin\Module_Admin_Base;
use Orbitools\Modules\Flex_Layout_Controls\Admin\Settings;
use Orbitools\Modules\Flex_Layout_Controls\Admin\Settings_Helper;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Flex Layout Controls Admin Class
 *
 * Manages admin interface integration for the Flex Layout Controls module.
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
    const MODULE_SLUG = 'flex-layout-controls';

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
     * Check if the Flex Layout Controls module is enabled
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
     * @return array Modified modules array with Flex Layout Controls metadata.
     */
    public function register_module_metadata(array $modules): array
    {
        $modules['flex_layout_controls'] = array(
            'name'        => __('Flex Layout Controls', 'orbitools'),
            'subtitle'    => __('Advanced flexbox layout system', 'orbitools'),
            'description' => __('Add comprehensive flexbox layout controls to WordPress blocks for precise container and item positioning.', 'orbitools'),
            'icon'        => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><path fill="#32a3e2" d="M0 32C0 14.3 14.3 0 32 0H288c17.7 0 32 14.3 32 32V96H160c-17.7 0-32 14.3-32 32s14.3 32 32 32H320v64H160c-17.7 0-32 14.3-32 32s14.3 32 32 32H320v64H160c-17.7 0-32 14.3-32 32s14.3 32 32 32H320v64H32c-17.7 0-32-14.3-32-32V32zM352 96v64H608c17.7 0 32-14.3 32-32s-14.3-32-32-32H352zm0 128v64H608c17.7 0 32-14.3 32-32s-14.3-32-32-32H352zm0 128v64H608c17.7 0 32-14.3 32-32s-14.3-32-32-32H352z"/></svg>',
            'configure_url' => admin_url('admin.php?page=orbitools&tab=modules&section=flex-layout'),
        );

        return $modules;
    }
}