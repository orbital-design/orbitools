<?php
/**
 * Main admin page template.
 *
 * @package    Orbital_Editor_Suite
 * @subpackage Orbital_Editor_Suite/includes/admin/partials
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get current options
$options = get_option('orbital_editor_suite_options', array());
$settings = isset($options['settings']) ? $options['settings'] : array();

// Handle form submission
if (isset($_POST['submit']) && check_admin_referer('orbital_editor_suite_settings')) {
    $new_settings = array();
    
    // Process form data
    $new_settings['enable_debug'] = !empty($_POST['enable_debug']);
    
    // Update options
    $options['settings'] = $new_settings;
    $options['version'] = ORBITAL_EDITOR_SUITE_VERSION;
    update_option('orbital_editor_suite_options', $options);
    
    echo '<div class="notice notice-success"><p>' . __('Settings saved successfully!', 'orbital-editor-suite') . '</p></div>';
    $settings = $new_settings; // Update local variable
}
?>

<div class="orbital-admin-wrap">
    <div class="orbital-admin-header">
        <span class="dashicons dashicons-admin-customizer"></span>
        <h1><?php _e('Orbital Editor Suite', 'orbital-editor-suite'); ?></h1>
    </div>
    
    <div class="orbital-admin-content">
        <form method="post" action="">
            <?php wp_nonce_field('orbital_editor_suite_settings'); ?>
            
            <div class="orbital-settings-grid">
                <!-- General Settings -->
                <div class="orbital-settings-card">
                    <h3><span class="dashicons dashicons-admin-settings"></span> <?php _e('General Settings', 'orbital-editor-suite'); ?></h3>
                    
                    
                    <div class="orbital-field">
                        <label class="orbital-toggle-switch">
                            <input type="checkbox" name="enable_debug" value="1" <?php checked(!empty($settings['enable_debug']), true); ?>>
                            <span class="orbital-slider"></span>
                            <span class="orbital-label"><?php _e('Enable Debug Logging', 'orbital-editor-suite'); ?></span>
                        </label>
                        <p class="orbital-help-text"><?php _e('Show detailed console logs in browser developer tools for troubleshooting.', 'orbital-editor-suite'); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="orbital-section-divider"></div>
            
            <!-- Submit Button -->
            <div class="orbital-submit-section">
                <?php submit_button(__('Save Settings', 'orbital-editor-suite'), 'primary orbital-save-button', 'submit', false); ?>
            </div>
        </form>
        
        <!-- Help Section -->
        <div class="orbital-help-section">
            <h3><span class="dashicons dashicons-editor-help"></span> <?php _e('How to Use', 'orbital-editor-suite'); ?></h3>
            <div class="orbital-help-grid">
                <div class="orbital-help-item">
                    <strong><?php _e('1. Configure Typography Presets', 'orbital-editor-suite'); ?></strong>
                    <p><?php _e('Go to Typography Presets to set up preset styles', 'orbital-editor-suite'); ?></p>
                </div>
                <div class="orbital-help-item">
                    <strong><?php _e('2. Edit Blocks', 'orbital-editor-suite'); ?></strong>
                    <p><?php _e('Find "Typography" section in the block inspector', 'orbital-editor-suite'); ?></p>
                </div>
                <div class="orbital-help-item">
                    <strong><?php _e('3. Choose Presets', 'orbital-editor-suite'); ?></strong>
                    <p><?php _e('Select from predefined typography presets', 'orbital-editor-suite'); ?></p>
                </div>
                <div class="orbital-help-item">
                    <strong><?php _e('4. Debug Issues', 'orbital-editor-suite'); ?></strong>
                    <p><?php _e('Enable debug logging to troubleshoot problems', 'orbital-editor-suite'); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>