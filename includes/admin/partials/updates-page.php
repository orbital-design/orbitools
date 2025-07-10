<?php
/**
 * Updates admin page template.
 *
 * @package    Orbital_Editor_Suite
 * @subpackage Orbital_Editor_Suite/includes/admin/partials
 */

if (!defined('ABSPATH')) {
    exit;
}

// Initialize updater for this page
$updater = new \Orbital\Editor_Suite\Updater\GitHub_Updater(
    plugin_dir_path(dirname(dirname(dirname(__FILE__)))) . 'orbital-editor-suite.php',
    ORBITAL_EDITOR_SUITE_VERSION
);

$update_info = $updater->get_update_info();
$checked_message = isset($_GET['checked']) ? __('Update check completed!', 'orbital-editor-suite') : '';
?>

<div class="orbital-admin-wrap">
    <div class="orbital-admin-header">
        <span class="dashicons dashicons-update"></span>
        <h1><?php _e('Plugin Updates', 'orbital-editor-suite'); ?></h1>
    </div>
    
    <div class="orbital-admin-content">
        <?php if ($checked_message): ?>
            <div class="notice notice-success is-dismissible">
                <p><?php echo esc_html($checked_message); ?></p>
            </div>
        <?php endif; ?>
        
        <div class="orbital-settings-grid">
            <!-- Current Version -->
            <div class="orbital-settings-card">
                <h3><span class="dashicons dashicons-info"></span> <?php _e('Current Version', 'orbital-editor-suite'); ?></h3>
                <div class="orbital-version-info">
                    <div class="orbital-version-current">
                        <strong><?php _e('Installed Version:', 'orbital-editor-suite'); ?></strong> 
                        <span class="version-number"><?php echo esc_html($update_info['current_version']); ?></span>
                    </div>
                    
                    <div class="orbital-version-remote">
                        <strong><?php _e('Latest Version:', 'orbital-editor-suite'); ?></strong> 
                        <span class="version-number"><?php echo esc_html($update_info['remote_version']); ?></span>
                    </div>
                    
                    <div class="orbital-version-status">
                        <?php if ($update_info['has_update']): ?>
                            <span class="update-available">
                                <span class="dashicons dashicons-warning"></span>
                                <?php _e('Update Available', 'orbital-editor-suite'); ?>
                            </span>
                        <?php else: ?>
                            <span class="update-current">
                                <span class="dashicons dashicons-yes"></span>
                                <?php _e("You're up to date!", 'orbital-editor-suite'); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Update Actions -->
            <div class="orbital-settings-card">
                <h3><span class="dashicons dashicons-update"></span> <?php _e('Update Actions', 'orbital-editor-suite'); ?></h3>
                <div class="orbital-update-actions">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=orbital-editor-suite-updates&orbital_check_update=1')); ?>" class="button button-secondary">
                        <span class="dashicons dashicons-update"></span>
                        <?php _e('Check for Updates', 'orbital-editor-suite'); ?>
                    </a>
                    
                    <?php if ($update_info['has_update']): ?>
                        <div class="orbital-update-notice">
                            <p><strong><?php _e('New version available!', 'orbital-editor-suite'); ?></strong></p>
                            <p>
                                <?php 
                                printf(
                                    __('Go to <a href="%s">Plugins</a> page to update.', 'orbital-editor-suite'),
                                    admin_url('plugins.php')
                                ); 
                                ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Repository Information -->
            <div class="orbital-settings-card">
                <h3><span class="dashicons dashicons-admin-links"></span> <?php _e('Repository Information', 'orbital-editor-suite'); ?></h3>
                <div class="orbital-repo-info">
                    <p>
                        <strong><?php _e('Repository:', 'orbital-editor-suite'); ?></strong> 
                        <a href="<?php echo esc_url($update_info['github_url']); ?>" target="_blank">
                            <?php echo esc_url($update_info['github_url']); ?>
                        </a>
                    </p>
                    <p>
                        <strong><?php _e('Last Checked:', 'orbital-editor-suite'); ?></strong> 
                        <?php echo esc_html($update_info['last_checked']); ?>
                    </p>
                    <p>
                        <strong><?php _e('Repository Type:', 'orbital-editor-suite'); ?></strong> 
                        <?php echo esc_html($update_info['repository_type']); ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>