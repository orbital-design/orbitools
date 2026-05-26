<?php

/**
 * User Avatars Settings
 *
 * Provides default values for the module. The AdminKit-era field-
 * definition helpers were removed in v3 Phase 7; the React admin
 * reads the field schema from module.json instead.
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

class Settings
{
    const MODULE_SLUG = 'user-avatars';

    /**
     * Get default settings values
     *
     * @return array Default settings array.
     */
    public static function get_defaults(): array
    {
        return array(
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
}
