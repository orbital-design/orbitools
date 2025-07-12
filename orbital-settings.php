<?php

/**
 * Orbital Editor Suite Settings
 */

return array(
    // Dashboard Tab - Plugin overview and module controls
    'dashboard' => array(
        array(
            'id'      => 'plugin_status',
            'name'    => 'Plugin Status',
            'type'    => 'html',
            'std'     => '<p><strong>Status:</strong> Active</p><p><strong>Version:</strong> ' . ORBITAL_EDITOR_SUITE_VERSION . '</p>',
            'section' => 'overview',
        ),
        array(
            'id'      => 'active_modules_count',
            'name'    => 'Active Modules',
            'type'    => 'html',
            'std'     => orbital_get_active_modules_html(),
            'section' => 'status',
        ),
        array(
            'id'      => 'typography_presets_enabled',
            'name'    => 'Typography Presets',
            'desc'    => 'Enable typography presets module',
            'type'    => 'checkbox',
            'std'     => '1',
            'section' => 'status',
        ),
    ),

    // Modules Tab - Settings for enabled modules (populated dynamically by modules)
    'modules' => array(
        array(
            'id'      => 'modules_info',
            'name'    => 'Module Settings',
            'type'    => 'html',
            'std'     => '<p>Settings for enabled modules will appear below.</p>',
            // 'section' => 'settings',
        ),
        array(
            'id'      => 'test_checkbox',
            'name'    => 'Test Checkbox',
            'desc'    => 'This is a test checkbox',
            'type'    => 'checkbox',
            'std'     => false,
            // 'section' => 'settings',
        ),
        // Module settings are added here by individual modules via filters
    ),

    // Settings Tab
    'settings' => array(
        array(
            'id'      => 'debug_mode',
            'name'    => 'Debug Mode',
            'desc'    => 'Enable debug logging for troubleshooting',
            'type'    => 'checkbox',
            'std'     => false,
            'section' => 'general',
        ),
        array(
            'id'      => 'cache_css',
            'name'    => 'Cache Generated CSS',
            'desc'    => 'Cache CSS output for better performance',
            'type'    => 'checkbox',
            'std'     => true,
            'section' => 'performance',
        ),
        array(
            'id'      => 'reset_on_deactivation',
            'name'    => 'Reset Data on Deactivation',
            'desc'    => 'Remove all plugin data when deactivating (cannot be undone)',
            'type'    => 'checkbox',
            'std'     => false,
            'section' => 'cleanup',
        ),
    ),

    // Updates Tab
    'updates' => array(
        array(
            'id'      => 'current_version',
            'name'    => 'Current Version',
            'type'    => 'html',
            'std'     => '<p>Version: ' . ORBITAL_EDITOR_SUITE_VERSION . '</p>',
            'section' => 'version',
        ),
        array(
            'id'      => 'auto_updates',
            'name'    => 'Automatic Updates',
            'desc'    => 'Enable automatic updates for this plugin',
            'type'    => 'checkbox',
            'std'     => false,
            'section' => 'auto',
        ),
        array(
            'id'      => 'update_channel',
            'name'    => 'Update Channel',
            'desc'    => 'Choose which updates to receive',
            'type'    => 'select',
            'options' => array(
                'stable' => 'Stable releases only',
                'beta'   => 'Include beta releases',
            ),
            'std'     => 'stable',
            'section' => 'auto',
        ),
    ),
);