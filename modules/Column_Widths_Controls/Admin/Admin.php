<?php

/**
 * Column Widths Controls Admin Handler
 *
 * Handles all admin-related functionality for the Column Widths Controls module,
 * including module registration, settings integration, and admin notices.
 *
 * @package    Orbitools
 * @subpackage Modules/Column_Widths_Controls/Admin
 * @since      1.0.0
 */

namespace Orbitools\Modules\Column_Widths_Controls\Admin;

use Orbitools\Admin\Module_Admin_Base;
use Orbitools\Modules\Column_Widths_Controls\Admin\Settings;
use Orbitools\Modules\Column_Widths_Controls\Admin\Settings_Helper;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Column Widths Controls Admin Class
 *
 * Manages admin interface integration for the Column Widths Controls module.
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
    const MODULE_SLUG = 'column-widths-controls';

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
     * Check if the Column Widths Controls module is enabled
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
     * @return array Modified modules array with Column Widths Controls metadata.
     */
    public function register_module_metadata(array $modules): array
    {
        $modules['column_widths_controls'] = array(
            'name'        => __('Column Widths Controls', 'orbitools'),
            'subtitle'    => __('Responsive 12-column grid system', 'orbitools'),
            'description' => __('Add responsive column width controls to WordPress blocks using a 12-column grid system with breakpoint support.', 'orbitools'),
            'icon'        => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path fill="#32a3e2" d="M0 80C0 53.5 21.5 32 48 32h96c26.5 0 48 21.5 48 48v352c0 26.5-21.5 48-48 48H48c-26.5 0-48-21.5-48-48V80zM208 80c0-26.5 21.5-48 48-48h96c26.5 0 48 21.5 48 48v352c0 26.5-21.5 48-48 48h-96c-26.5 0-48-21.5-48-48V80zM416 80c0-26.5 21.5-48 48-48h96c26.5 0 48 21.5 48 48v352c0 26.5-21.5 48-48 48h-96c-26.5 0-48-21.5-48-48V80z"/></svg>',
            'configure_url' => admin_url('admin.php?page=orbitools&tab=modules&section=column-widths'),
        );

        return $modules;
    }
}