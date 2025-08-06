<?php

/**
 * User Avatars Module
 *
 * Main coordinator class for the User Avatars module. This class acts as
 * the primary entry point and orchestrates avatar management functionality.
 *
 * @package    Orbitools
 * @subpackage Modules/User_Avatars
 * @since      1.0.0
 */

namespace Orbitools\Modules\User_Avatars;

use Orbitools\Abstracts\Module_Base;
use Orbitools\Modules\User_Avatars\Admin\Admin;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * User Avatars Module Class
 *
 * Manages local avatar uploads and Gravatar settings for WordPress users.
 *
 * @since 1.0.0
 */
class User_Avatars extends Module_Base
{
    /**
     * Module version
     */
    protected const VERSION = '1.0.0';

    /**
     * Admin handler instance
     *
     * @since 1.0.0
     * @var Admin
     */
    private $admin;

    /**
     * Initialize the User Avatars module
     *
     * Sets up the module by calling the parent constructor which handles
     * the initialization logic via the Module_Base system.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        // Call parent constructor which handles initialization
        parent::__construct();
    }

    /**
     * Get the module's unique slug
     * 
     * @return string
     */
    public function get_slug(): string
    {
        return 'user-avatars';
    }

    /**
     * Get the module's display name
     * 
     * @return string
     */
    public function get_name(): string
    {
        return __('User Avatars', 'orbitools');
    }

    /**
     * Get the module's description
     * 
     * @return string
     */
    public function get_description(): string
    {
        return __('Local avatar uploads and Gravatar management for WordPress users.', 'orbitools');
    }

    /**
     * Get module's default settings
     * 
     * @return array
     */
    public function get_default_settings(): array
    {
        return [
            'user-avatars_enabled' => true,
            'user-avatars_local_avatars_enabled' => true,
            'user-avatars_disable_gravatar' => false,
            'user-avatars_allowed_filetypes' => [
                'jpg|jpeg|jpe' => 'image/jpeg',
                'gif' => 'image/gif',
                'png' => 'image/png'
            ],
            'user-avatars_max_file_size' => 2048 // 2MB in KB
        ];
    }

    /**
     * Initialize the module
     * Called by Module_Base when module should be initialized
     * 
     * @return void
     */
    public function init(): void
    {
        // Always initialize admin functionality for module registration
        $this->admin = new Admin();

        // Initialize avatar functionality
        $this->init_avatar_functionality();
    }

    /**
     * Initialize avatar module functionality
     *
     * Sets up avatar handling when the module is enabled.
     *
     * @since 1.0.0
     */
    private function init_avatar_functionality(): void
    {
        // Handle Gravatar disabling
        if ($this->settings_manager->get_module_setting('user-avatars', 'disable_gravatar', false)) {
            $this->disable_gravatar();
        }

        // Set up avatar hooks for local avatars (when implemented)
        if ($this->settings_manager->get_module_setting('user-avatars', 'local_avatars_enabled', true)) {
            // TODO: Implement local avatar functionality
        }
    }

    /**
     * Disable Gravatar functionality
     *
     * @since 1.0.0
     */
    private function disable_gravatar(): void
    {
        // Remove default avatar options that use Gravatar
        add_filter('avatar_defaults', [$this, 'remove_gravatar_defaults']);
        
        // Replace Gravatar URLs with blank or default local avatar
        add_filter('get_avatar_url', [$this, 'replace_gravatar_url'], 10, 3);
        
        // Remove Gravatar from user profile
        add_filter('user_profile_picture_description', [$this, 'remove_gravatar_description']);
    }

    /**
     * Remove Gravatar from default avatar options
     *
     * @param array $avatar_defaults Default avatars
     * @return array Modified avatar defaults
     * @since 1.0.0
     */
    public function remove_gravatar_defaults($avatar_defaults): array
    {
        // Remove all Gravatar-related avatars, keep only 'blank'
        return [
            'blank' => __('Blank', 'orbitools')
        ];
    }

    /**
     * Replace Gravatar URLs with local alternatives
     *
     * @param string $url Avatar URL
     * @param mixed $id_or_email User ID or email
     * @param array $args Avatar arguments
     * @return string Modified avatar URL
     * @since 1.0.0
     */
    public function replace_gravatar_url($url, $id_or_email, $args): string
    {
        // If URL contains gravatar.com, replace with blank
        if (strpos($url, 'gravatar.com') !== false) {
            // Return blank avatar or local default
            return $this->get_blank_avatar_url($args['size'] ?? 96);
        }
        
        return $url;
    }

    /**
     * Get blank avatar URL
     *
     * @param int $size Avatar size
     * @return string Blank avatar URL
     * @since 1.0.0
     */
    private function get_blank_avatar_url($size): string
    {
        // Create a simple blank/transparent image data URL
        return 'data:image/svg+xml;base64,' . base64_encode(
            '<svg width="' . $size . '" height="' . $size . '" xmlns="http://www.w3.org/2000/svg"><rect width="100%" height="100%" fill="#f0f0f0"/></svg>'
        );
    }

    /**
     * Remove Gravatar description from user profile
     *
     * @param string $description Current description
     * @return string Modified description
     * @since 1.0.0
     */
    public function remove_gravatar_description($description): string
    {
        return __('Upload a local avatar or use the default avatar.', 'orbitools');
    }

    /**
     * Get the admin handler instance
     *
     * @since 1.0.0
     * @return Admin Admin instance.
     */
    public function get_admin(): Admin
    {
        return $this->admin;
    }
}