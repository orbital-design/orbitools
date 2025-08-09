<?php

/**
 * User Avatars Settings
 *
 * Manages settings definitions for the User Avatars module.
 *
 * @package    Orbitools
 * @subpackage Modules/User_Avatars/Admin
 * @since      1.0.0
 */

namespace Orbitools\Modules\User_Avatars\Admin;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * User Avatars Settings Class
 *
 * Defines settings structure and field definitions for the User Avatars module.
 *
 * @since 1.0.0
 */
class Settings
{
    /**
     * Module slug
     *
     * @since 1.0.0
     * @var string
     */
    const MODULE_SLUG = 'user-avatars';

    /**
     * Get admin structure for the User Avatars module
     *
     * @since 1.0.0
     * @return array Admin structure array.
     */
    public static function get_admin_structure(): array
    {
        return array(
            'sections' => array(
                'user_avatars' => array(
                    'title'       => \__('User Avatars', 'orbitools'),
                    'description' => \__('Configure local avatar uploads and Gravatar settings for WordPress users.', 'orbitools'),
                    'icon'        => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="#32a3e2" d="M224 256A128 128 0 1 0 224 0a128 128 0 1 0 0 256zm-45.7 48C79.8 304 0 383.8 0 482.3C0 498.7 13.3 512 29.7 512l388.6 0c16.4 0 29.7-13.3 29.7-29.7C448 383.8 368.2 304 269.7 304l-91.4 0z"/></svg>',
                    'fields'      => array(),
                ),
            ),
        );
    }

    /**
     * Get default settings values
     *
     * @since 1.0.0
     * @return array Default settings array.
     */
    public static function get_defaults(): array
    {
        return array(
            'user_avatars_enabled' => true,
            'user_avatars_local_avatars_enabled' => true,
            'user_avatars_disable_gravatar' => false,
            'user_avatars_allowed_filetypes' => array(
                'jpg|jpeg|jpe' => 'image/jpeg',
                'gif' => 'image/gif',
                'png' => 'image/png'
            ),
            'user_avatars_max_file_size' => 2048 // 2MB in KB
        );
    }

    /**
     * Get settings field definitions for admin framework
     *
     * @since 1.0.0
     * @return array Settings fields array.
     */
    public static function get_field_definitions(): array
    {
        return array(
            // Enable local avatars
            array(
                'id'      => 'user_avatars_local_avatars_enabled',
                'name'    => \__('Enable Local Avatars', 'orbitools'),
                'desc'    => \__('Allow users to upload local avatar images instead of using Gravatar.', 'orbitools'),
                'type'    => 'checkbox',
                'std'     => true,
                'section' => 'user_avatars',
            ),
            // Disable Gravatar
            array(
                'id'      => 'user_avatars_disable_gravatar',
                'name'    => \__('Disable Gravatar', 'orbitools'),
                'desc'    => \__('Disable Gravatar integration completely. When enabled, all Gravatar functionality will be removed.', 'orbitools'),
                'type'    => 'checkbox',
                'std'     => false,
                'section' => 'user_avatars',
            ),
            // Allowed file types
            array(
                'id'      => 'user_avatars_allowed_filetypes',
                'name'    => \__('Allowed File Types', 'orbitools'),
                'desc'    => \__('Select which image file types are allowed for local avatar uploads.', 'orbitools'),
                'type'    => 'multicheck',
                'std'     => array(
                    'jpg|jpeg|jpe' => 'image/jpeg',
                    'gif' => 'image/gif',
                    'png' => 'image/png'
                ),
                'options' => array(
                    'jpg|jpeg|jpe' => \__('JPG/JPEG (image/jpeg)', 'orbitools'),
                    'gif'          => \__('GIF (image/gif)', 'orbitools'),
                    'png'          => \__('PNG (image/png)', 'orbitools'),
                ),
                'section' => 'user_avatars',
            ),
            // Max file size
            array(
                'id'      => 'user_avatars_max_file_size',
                'name'    => \__('Max File Size (KB)', 'orbitools'),
                'desc'    => \__('Maximum file size allowed for avatar uploads in KB. Recommended: 2048 KB (2 MB).', 'orbitools'),
                'type'    => 'number',
                'std'     => 2048,
                'min'     => 100,
                'max'     => 10240,
                'step'    => 1,
                'section' => 'user_avatars',
            ),
        );
    }
}