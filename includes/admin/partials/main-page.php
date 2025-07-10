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
    $new_settings['enable_plugin'] = !empty($_POST['enable_plugin']);
    $new_settings['enable_search'] = !empty($_POST['enable_search']);
    $new_settings['allowed_blocks'] = isset($_POST['allowed_blocks']) ? 
        array_map('sanitize_text_field', $_POST['allowed_blocks']) : array();
    $new_settings['utility_categories'] = isset($_POST['utility_categories']) ? 
        array_map('sanitize_text_field', $_POST['utility_categories']) : array();
    $new_settings['custom_css'] = isset($_POST['custom_css']) ? 
        wp_kses_post($_POST['custom_css']) : '';
    $new_settings['load_custom_css'] = !empty($_POST['load_custom_css']);
    
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
                            <input type="checkbox" name="enable_plugin" value="1" <?php checked(!empty($settings['enable_plugin']), true); ?>>
                            <span class="orbital-slider"></span>
                            <span class="orbital-label"><?php _e('Enable Plugin', 'orbital-editor-suite'); ?></span>
                        </label>
                        <p class="orbital-help-text"><?php _e('Enable or disable the Typography Utility Controls plugin functionality.', 'orbital-editor-suite'); ?></p>
                    </div>
                    
                    <div class="orbital-field">
                        <label class="orbital-toggle-switch">
                            <input type="checkbox" name="enable_search" value="1" <?php checked(!empty($settings['enable_search']), true); ?>>
                            <span class="orbital-slider"></span>
                            <span class="orbital-label"><?php _e('Enable Search', 'orbital-editor-suite'); ?></span>
                        </label>
                        <p class="orbital-help-text"><?php _e('Allow users to search through utility classes in the block editor.', 'orbital-editor-suite'); ?></p>
                    </div>
                </div>
                
                <!-- Block Settings -->
                <div class="orbital-settings-card">
                    <h3><span class="dashicons dashicons-editor-table"></span> <?php _e('Block Settings', 'orbital-editor-suite'); ?></h3>
                    
                    <div class="orbital-field">
                        <label><strong><?php _e('Allowed Blocks', 'orbital-editor-suite'); ?></strong></label>
                        <p class="orbital-help-text"><?php _e('Select which blocks should have typography utility controls.', 'orbital-editor-suite'); ?></p>
                        
                        <div class="orbital-checkbox-grid">
                            <?php
                            $blocks = array(
                                'core/paragraph' => __('Paragraph', 'orbital-editor-suite'),
                                'core/heading' => __('Heading', 'orbital-editor-suite'),
                                'core/list' => __('List', 'orbital-editor-suite'),
                                'core/list-item' => __('List Item', 'orbital-editor-suite'),
                                'core/quote' => __('Quote', 'orbital-editor-suite'),
                                'core/pullquote' => __('Pullquote', 'orbital-editor-suite'),
                                'core/button' => __('Button', 'orbital-editor-suite'),
                                'core/group' => __('Group', 'orbital-editor-suite'),
                                'core/column' => __('Column', 'orbital-editor-suite'),
                                'core/columns' => __('Columns', 'orbital-editor-suite'),
                                'core/cover' => __('Cover', 'orbital-editor-suite'),
                                'core/image' => __('Image', 'orbital-editor-suite')
                            );
                            
                            $allowed_blocks = isset($settings['allowed_blocks']) ? $settings['allowed_blocks'] : array();
                            
                            foreach ($blocks as $block => $label) {
                                $checked = in_array($block, $allowed_blocks);
                                ?>
                                <label class="orbital-checkbox-item">
                                    <input type="checkbox" name="allowed_blocks[]" value="<?php echo esc_attr($block); ?>" <?php checked($checked, true); ?>>
                                    <span class="orbital-checkmark"></span>
                                    <?php echo esc_html($label); ?>
                                </label>
                                <?php
                            }
                            ?>
                        </div>
                    </div>
                </div>
                
                <!-- Utility Categories -->
                <div class="orbital-settings-card">
                    <h3><span class="dashicons dashicons-admin-appearance"></span> <?php _e('Utility Categories', 'orbital-editor-suite'); ?></h3>
                    
                    <div class="orbital-field">
                        <label><strong><?php _e('Available Categories', 'orbital-editor-suite'); ?></strong></label>
                        <p class="orbital-help-text"><?php _e('Select which categories of typography utilities should be available.', 'orbital-editor-suite'); ?></p>
                        
                        <div class="orbital-checkbox-grid">
                            <?php
                            $categories = array(
                                'font_family' => __('Font Family', 'orbital-editor-suite'),
                                'font_size' => __('Font Size', 'orbital-editor-suite'),
                                'font_weight' => __('Font Weight', 'orbital-editor-suite'),
                                'font_style' => __('Font Style', 'orbital-editor-suite'),
                                'text_color' => __('Text Color', 'orbital-editor-suite'),
                                'text_align' => __('Text Alignment', 'orbital-editor-suite'),
                                'text_decoration' => __('Text Decoration', 'orbital-editor-suite'),
                                'text_transform' => __('Text Transform', 'orbital-editor-suite'),
                                'line_height' => __('Line Height', 'orbital-editor-suite'),
                                'letter_spacing' => __('Letter Spacing', 'orbital-editor-suite'),
                                'text_indent' => __('Text Indent', 'orbital-editor-suite')
                            );
                            
                            $utility_categories = isset($settings['utility_categories']) ? $settings['utility_categories'] : array();
                            
                            foreach ($categories as $category => $label) {
                                $checked = in_array($category, $utility_categories);
                                ?>
                                <label class="orbital-checkbox-item">
                                    <input type="checkbox" name="utility_categories[]" value="<?php echo esc_attr($category); ?>" <?php checked($checked, true); ?>>
                                    <span class="orbital-checkmark"></span>
                                    <?php echo esc_html($label); ?>
                                </label>
                                <?php
                            }
                            ?>
                        </div>
                    </div>
                </div>
                
                <!-- Custom CSS -->
                <div class="orbital-settings-card">
                    <h3><span class="dashicons dashicons-editor-code"></span> <?php _e('Custom CSS', 'orbital-editor-suite'); ?></h3>
                    
                    <div class="orbital-field">
                        <label for="custom_css"><strong><?php _e('Custom Utility Classes', 'orbital-editor-suite'); ?></strong></label>
                        <p class="orbital-help-text"><?php _e('Add your own custom utility classes in CSS format.', 'orbital-editor-suite'); ?></p>
                        <textarea 
                            id="custom_css" 
                            name="custom_css" 
                            rows="8" 
                            class="orbital-textarea"
                            placeholder="<?php _e('/* Add your custom utility classes here */', 'orbital-editor-suite'); ?>"><?php echo esc_textarea($settings['custom_css'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="orbital-field">
                        <label class="orbital-toggle-switch">
                            <input type="checkbox" name="load_custom_css" value="1" <?php checked(!empty($settings['load_custom_css']), true); ?>>
                            <span class="orbital-slider"></span>
                            <span class="orbital-label"><?php _e('Load Custom CSS', 'orbital-editor-suite'); ?></span>
                        </label>
                        <p class="orbital-help-text"><?php _e('Enable to load the custom CSS in the block editor.', 'orbital-editor-suite'); ?></p>
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
                    <strong><?php _e('1. Enable Plugin', 'orbital-editor-suite'); ?></strong>
                    <p><?php _e('Toggle the plugin on in General Settings', 'orbital-editor-suite'); ?></p>
                </div>
                <div class="orbital-help-item">
                    <strong><?php _e('2. Select Blocks', 'orbital-editor-suite'); ?></strong>
                    <p><?php _e('Choose which blocks get typography controls', 'orbital-editor-suite'); ?></p>
                </div>
                <div class="orbital-help-item">
                    <strong><?php _e('3. Choose Categories', 'orbital-editor-suite'); ?></strong>
                    <p><?php _e('Enable utility categories you want to use', 'orbital-editor-suite'); ?></p>
                </div>
                <div class="orbital-help-item">
                    <strong><?php _e('4. Edit Blocks', 'orbital-editor-suite'); ?></strong>
                    <p><?php _e('Find "Typography Utilities" in the block inspector', 'orbital-editor-suite'); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>