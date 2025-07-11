<?php
/**
 * Debug Status Page
 * 
 * Shows debug information about the plugin and WordPress environment
 */

add_action('admin_menu', 'debug_status_menu');

function debug_status_menu() {
    add_submenu_page(
        'orbital-editor-suite',
        'Debug Status',
        'Debug Status',
        'manage_options',
        'orbital-debug-status',
        'debug_status_page'
    );
}

function debug_status_page() {
    ?>
    <div class="wrap">
        <h1>Orbital Editor Suite - Debug Status</h1>
        
        <div style="background: #f0f0f1; padding: 20px; border-radius: 8px; margin: 20px 0;">
            <h2>WordPress Environment</h2>
            <table class="wp-list-table widefat fixed striped">
                <tbody>
                    <tr>
                        <td><strong>WP_DEBUG</strong></td>
                        <td><?php echo defined('WP_DEBUG') && WP_DEBUG ? '✅ Enabled' : '❌ Disabled'; ?></td>
                    </tr>
                    <tr>
                        <td><strong>WP_DEBUG_LOG</strong></td>
                        <td><?php echo defined('WP_DEBUG_LOG') && WP_DEBUG_LOG ? '✅ Enabled' : '❌ Disabled'; ?></td>
                    </tr>
                    <tr>
                        <td><strong>WP_DEBUG_DISPLAY</strong></td>
                        <td><?php echo defined('WP_DEBUG_DISPLAY') && WP_DEBUG_DISPLAY ? '✅ Enabled' : '❌ Disabled'; ?></td>
                    </tr>
                    <tr>
                        <td><strong>WordPress Version</strong></td>
                        <td><?php echo get_bloginfo('version'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>PHP Version</strong></td>
                        <td><?php echo PHP_VERSION; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Current User Can Manage Options</strong></td>
                        <td><?php echo current_user_can('manage_options') ? '✅ Yes' : '❌ No'; ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div style="background: #f0f0f1; padding: 20px; border-radius: 8px; margin: 20px 0;">
            <h2>Plugin Files</h2>
            <table class="wp-list-table widefat fixed striped">
                <tbody>
                    <?php
                    $files_to_check = array(
                        'Vue.js Admin PHP' => ORBITAL_EDITOR_SUITE_PATH . 'includes/modules/typography-presets/class-typography-presets-vue-admin.php',
                        'Vue.js App JS' => ORBITAL_EDITOR_SUITE_PATH . 'assets/js/typography-presets-vue-app.js',
                        'Vue.js App CSS' => ORBITAL_EDITOR_SUITE_PATH . 'assets/css/typography-presets-vue-styles.css',
                        'Debug Vue Admin' => ORBITAL_EDITOR_SUITE_PATH . 'debug-vue-admin.php',
                        'Main Plugin File' => ORBITAL_EDITOR_SUITE_PATH . 'orbital-editor-suite.php'
                    );
                    
                    foreach ($files_to_check as $name => $file) {
                        echo '<tr>';
                        echo '<td><strong>' . $name . '</strong></td>';
                        echo '<td>' . (file_exists($file) ? '✅ Exists' : '❌ Missing') . '</td>';
                        echo '</tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
        
        <div style="background: #f0f0f1; padding: 20px; border-radius: 8px; margin: 20px 0;">
            <h2>Module Status</h2>
            <table class="wp-list-table widefat fixed striped">
                <tbody>
                    <?php
                    // Check if Typography Presets module is enabled
                    $global_options = get_option('orbital_editor_suite_options', array());
                    $global_settings = isset($global_options['settings']) ? $global_options['settings'] : array();
                    $enabled_modules = isset($global_settings['enabled_modules']) ? $global_settings['enabled_modules'] : array();
                    $is_typography_enabled = in_array('typography-presets', $enabled_modules);
                    
                    // Check if classes exist
                    $vue_class_exists = class_exists('\Orbital\Editor_Suite\Modules\Typography_Presets\Typography_Presets_Vue_Admin');
                    $original_class_exists = class_exists('\Orbital\Editor_Suite\Modules\Typography_Presets\Typography_Presets_Admin');
                    ?>
                    <tr>
                        <td><strong>Typography Presets Module Enabled</strong></td>
                        <td><?php echo $is_typography_enabled ? '✅ Yes' : '❌ No'; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Vue Admin Class Exists</strong></td>
                        <td><?php echo $vue_class_exists ? '✅ Yes' : '❌ No'; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Original Admin Class Exists</strong></td>
                        <td><?php echo $original_class_exists ? '✅ Yes' : '❌ No'; ?></td>
                    </tr>
                </tbody>
            </table>
            
            <?php if (!$is_typography_enabled) : ?>
                <div style="background: #fff3cd; padding: 15px; border-radius: 6px; margin: 15px 0; border: 1px solid #ffeaa7;">
                    <strong>⚠️ Typography Presets Module is Disabled!</strong><br>
                    Go to <strong>Orbital Editor Suite → Settings</strong> and enable the Typography Presets module.
                </div>
            <?php endif; ?>
        </div>
        
        <div style="background: #f0f0f1; padding: 20px; border-radius: 8px; margin: 20px 0;">
            <h2>Expected Menu Items</h2>
            <p>You should see these menu items:</p>
            <ul>
                <li>Orbital Editor Suite → Typography Presets (original)</li>
                <li>Orbital Editor Suite → Typography Presets (Vue.js) <em>← New Vue.js version</em></li>
                <li>Orbital Editor Suite → Simplified Example</li>
                <li>Orbital Editor Suite → WP Options Kit (Vue.js)</li>
                <li>Orbital Editor Suite → Vue Debug Test</li>
                <li>Orbital Editor Suite → Debug Status (this page)</li>
            </ul>
        </div>
        
        <div style="background: #fff3cd; padding: 20px; border-radius: 8px; margin: 20px 0; border: 1px solid #ffeaa7;">
            <h2>⚠️ Action Required</h2>
            <p>If WP_DEBUG is disabled, add this to your <code>wp-config.php</code> file:</p>
            <pre style="background: #2d3748; color: #e2e8f0; padding: 15px; border-radius: 6px; overflow-x: auto;">define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);</pre>
            <p>Then refresh this page and check the menu items.</p>
        </div>
    </div>
    <?php
}
?>