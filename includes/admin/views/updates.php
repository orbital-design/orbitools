<?php
/**
 * Updates View
 *
 * @package    Orbital_Editor_Suite
 * @subpackage Orbital_Editor_Suite/includes/admin/views
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap orbital-admin-page">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="orbital-admin-content">
        <div class="orbital-admin-header">
            <h2>Plugin Updates</h2>
            <p>Manage updates for Orbital Editor Suite.</p>
        </div>

        <div class="orbital-card">
            <h3>Current Version</h3>
            <p>Version: <?php echo esc_html(ORBITAL_EDITOR_SUITE_VERSION); ?></p>
            
            <h3>Update Settings</h3>
            <form method="post" action="options.php">
                <?php
                settings_fields('orbital_editor_suite_updates');
                do_settings_sections('orbital_editor_suite_updates');
                ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Auto Updates</th>
                        <td>
                            <label>
                                <input type="checkbox" name="orbital_auto_updates" value="1" 
                                    <?php checked(get_option('orbital_auto_updates', 0)); ?> />
                                Enable automatic updates
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Update Channel</th>
                        <td>
                            <select name="orbital_update_channel">
                                <option value="stable" <?php selected(get_option('orbital_update_channel', 'stable'), 'stable'); ?>>Stable</option>
                                <option value="beta" <?php selected(get_option('orbital_update_channel', 'stable'), 'beta'); ?>>Beta</option>
                            </select>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
    </div>
</div>

<style>
.orbital-admin-page {
    max-width: 1200px;
}

.orbital-admin-content {
    margin-top: 20px;
}

.orbital-admin-header {
    margin-bottom: 30px;
    padding: 20px;
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
}

.orbital-card {
    padding: 20px;
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
}

.orbital-card h3 {
    margin-top: 0;
    color: #23282d;
}

.orbital-card p {
    color: #666;
}
</style>