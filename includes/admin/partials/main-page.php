<?php
/**
 * Main admin page template (LEGACY - NO LONGER USED).
 * 
 * This file is kept for backward compatibility but is no longer used.
 * The main admin interface is now handled by the Vue.js Main_Vue_Admin class.
 *
 * @package    Orbital_Editor_Suite
 * @subpackage Orbital_Editor_Suite/includes/admin/partials
 * @deprecated Use Main_Vue_Admin class instead
 */

if (!defined('ABSPATH')) {
    exit;
}

// DEPRECATED: This file is no longer used
// All functionality has been moved to the Vue.js Main_Vue_Admin class
// This file is kept for backward compatibility only

echo '<div class="wrap">';
echo '<h1>Legacy Main Page (Deprecated)</h1>';
echo '<div class="notice notice-error">';
echo '<p><strong>This page has been replaced.</strong> Please use the new Vue.js interface.</p>';
echo '<p><a href="' . admin_url('admin.php?page=orbital-editor-suite') . '" class="button button-primary">Go to New Interface</a></p>';
echo '</div>';
echo '</div>';

return; // Stop execution

// Legacy code below (no longer executed)
$options = get_option('orbital_editor_suite_options', array());
$settings = isset($options['settings']) ? $options['settings'] : array();

// Show success message if redirected after module changes
if (isset($_GET['settings-updated']) && $_GET['settings-updated'] === '1') {
    echo '<div class="notice notice-success"><p>' . __('Settings saved successfully! Admin menu updated.', 'orbital-editor-suite') . '</p></div>';
}

// Handle form submission
if (isset($_POST['submit']) && check_admin_referer('orbital_editor_suite_settings')) {
    $new_settings = array();
    
    // Get old enabled modules for comparison
    $old_enabled_modules = isset($settings['enabled_modules']) ? $settings['enabled_modules'] : array();
    
    // Process form data
    $new_settings['enable_debug'] = !empty($_POST['enable_debug']);
    $new_settings['enabled_modules'] = isset($_POST['enabled_modules']) ? 
        array_map('sanitize_text_field', $_POST['enabled_modules']) : array();
    
    // Check if enabled modules changed
    $modules_changed = (
        count(array_diff($old_enabled_modules, $new_settings['enabled_modules'])) > 0 ||
        count(array_diff($new_settings['enabled_modules'], $old_enabled_modules)) > 0
    );
    
    // Update options
    $options['settings'] = $new_settings;
    $options['version'] = ORBITAL_EDITOR_SUITE_VERSION;
    update_option('orbital_editor_suite_options', $options);
    
    // If modules changed, redirect to refresh admin menu
    if ($modules_changed) {
        wp_redirect(admin_url('admin.php?page=orbital-editor-suite&settings-updated=1'));
        exit;
    }
    
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
                <!-- Available Features -->
                <div class="orbital-settings-card">
                    <h3><span class="dashicons dashicons-admin-plugins"></span> <?php _e('Available Features', 'orbital-editor-suite'); ?></h3>
                    
                    <div class="orbital-field">
                        <label><strong><?php _e('Choose Features', 'orbital-editor-suite'); ?></strong></label>
                        <p class="orbital-help-text"><?php _e('Select which features you want to use. Only checked features will be available in your editor and admin menu.', 'orbital-editor-suite'); ?></p>
                        
                        <div class="orbital-checkbox-grid">
                            <?php
                            $available_modules = array(
                                'typography-presets' => array(
                                    'name' => __('Typography Presets', 'orbital-editor-suite'),
                                    'description' => __('Preset-based typography system with CSS classes', 'orbital-editor-suite')
                                )
                            );
                            
                            $enabled_modules = isset($settings['enabled_modules']) ? $settings['enabled_modules'] : array();
                            
                            foreach ($available_modules as $module_id => $module_info) {
                                $checked = in_array($module_id, $enabled_modules);
                                ?>
                                <label class="orbital-checkbox-item">
                                    <input type="checkbox" name="enabled_modules[]" value="<?php echo esc_attr($module_id); ?>" <?php checked($checked, true); ?>>
                                    <span class="orbital-checkmark"></span>
                                    <div class="orbital-checkbox-text">
                                        <strong><?php echo esc_html($module_info['name']); ?></strong>
                                        <small style="display: block; color: #666; font-weight: normal;"><?php echo esc_html($module_info['description']); ?></small>
                                    </div>
                                </label>
                                <?php
                            }
                            ?>
                        </div>
                    </div>
                </div>
                
                <style>
                /* Enhanced prominence for checked features */
                .orbital-settings-card .orbital-checkbox-item:has(input[type="checkbox"]:checked) {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
                    border: 3px solid #4f46e5 !important;
                    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4) !important;
                    transform: scale(1.02) !important;
                    position: relative;
                }
                
                .orbital-settings-card .orbital-checkbox-item:has(input[type="checkbox"]:checked)::after {
                    content: "ACTIVE";
                    position: absolute;
                    top: -8px;
                    right: -8px;
                    background: #10b981;
                    color: white;
                    padding: 2px 8px;
                    border-radius: 12px;
                    font-size: 10px;
                    font-weight: bold;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
                }
                
                .orbital-settings-card .orbital-checkbox-item:has(input[type="checkbox"]:checked) .orbital-checkbox-text {
                    color: #fff !important;
                    text-shadow: 0 1px 2px rgba(0,0,0,0.2);
                }
                
                .orbital-settings-card .orbital-checkbox-item:has(input[type="checkbox"]:checked) small {
                    color: rgba(255,255,255,0.9) !important;
                    text-shadow: 0 1px 2px rgba(0,0,0,0.2);
                }
                
                .orbital-settings-card .orbital-checkbox-item:has(input[type="checkbox"]:checked):hover {
                    transform: scale(1.03) translateY(-2px) !important;
                    box-shadow: 0 12px 35px rgba(102, 126, 234, 0.5) !important;
                }
                
                /* Pulse animation for active features */
                .orbital-settings-card .orbital-checkbox-item:has(input[type="checkbox"]:checked) {
                    animation: feature-glow 2s ease-in-out infinite alternate;
                }
                
                @keyframes feature-glow {
                    from { box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4); }
                    to { box-shadow: 0 8px 25px rgba(102, 126, 234, 0.6); }
                }
                </style>
                
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