<?php

/**
 * User Avatars Admin Class
 *
 * Handles admin interface and settings for the User Avatars module.
 *
 * @package    Orbitools
 * @subpackage Modules/User_Avatars/Admin
 * @since      1.0.0
 */

namespace Orbitools\Modules\User_Avatars\Admin;

use Orbitools\Admin\Module_Admin_Base;
use Orbitools\Modules\User_Avatars\Admin\Settings;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * User Avatars Admin Class
 *
 * Manages admin functionality for the User Avatars module.
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
    const MODULE_SLUG = 'user-avatars';

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        // Initialize parent with module slug and settings class
        parent::__construct(self::MODULE_SLUG, Settings::class);

        // Register module metadata for admin interface
        \add_filter('orbitools_available_modules', array($this, 'register_module_metadata'));
    }

    /**
     * Register module metadata for the admin interface
     *
     * @since 1.0.0
     * @param array $modules Existing modules array.
     * @return array Modified modules array with User Avatars metadata.
     */
    public function register_module_metadata(array $modules): array
    {
        $modules['user_avatars'] = array(
            'name'        => \__('User Avatars', 'orbitools'),
            'subtitle'    => \__('Local avatar uploads & Gravatar management', 'orbitools'),
            'description' => \__('Enable local avatar uploads for users and manage Gravatar settings. Control file types, sizes, and disable Gravatar entirely if needed.', 'orbitools'),
            'icon'        => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="#32a3e2" d="M224 256A128 128 0 1 0 224 0a128 128 0 1 0 0 256zm-45.7 48C79.8 304 0 383.8 0 482.3C0 498.7 13.3 512 29.7 512l388.6 0c16.4 0 29.7-13.3 29.7-29.7C448 383.8 368.2 304 269.7 304l-91.4 0z"/></svg>',
            'configure_url' => \admin_url('admin.php?page=orbitools&tab=modules&section=user_avatars'),
        );

        return $modules;
    }

    /**
     * Check if the User Avatars module is enabled
     *
     * @since 1.0.0
     * @return bool True if module is enabled, false otherwise.
     */
    public function is_module_enabled(): bool
    {
        $settings = get_option('orbitools_settings', array());
        return !empty($settings['user_avatars_enabled']) && $settings['user_avatars_enabled'] !== '0';
    }
}