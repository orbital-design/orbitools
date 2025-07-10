<?php
/**
 * Modern Admin Panel for Typography Utility Controls
 * No external dependencies required
 */

if (!defined('ABSPATH')) {
    exit;
}

class TUC_Admin_Panel {
    
    private $options;
    
    public function __construct() {
        add_action('admin_init', array($this, 'page_init'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        $this->options = get_option('tuc_options');
    }
    
    public function render_admin_page() {
        $this->options = get_option('tuc_options');
        
        if (isset($_POST['submit'])) {
            $this->save_settings();
        }
        
        ?>
        <div class="tuc-admin-wrap">
            <div class="tuc-admin-header">
                <span class="dashicons dashicons-editor-textcolor"></span>
                <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            </div>
            
            <div class="tuc-admin-content">
                <form method="post" action="">
                    <?php wp_nonce_field('tuc_save_settings', 'tuc_nonce'); ?>
                    
                    <div class="tuc-settings-grid">
                        <!-- General Settings -->
                        <div class="tuc-settings-card">
                            <h3><span class="dashicons dashicons-admin-settings"></span> General Settings</h3>
                            
                            <div class="tuc-field">
                                <label class="tuc-toggle-switch">
                                    <input type="checkbox" name="tuc_options[enable_plugin]" value="1" <?php checked(isset($this->options['enable_plugin']) ? $this->options['enable_plugin'] : 0, 1); ?>>
                                    <span class="tuc-slider"></span>
                                    <span class="tuc-label">Enable Plugin</span>
                                </label>
                                <p class="tuc-help-text">Enable or disable the Typography Utility Controls plugin functionality.</p>
                            </div>
                            
                            <div class="tuc-field">
                                <label class="tuc-toggle-switch">
                                    <input type="checkbox" name="tuc_options[enable_search]" value="1" <?php checked(isset($this->options['enable_search']) ? $this->options['enable_search'] : 1, 1); ?>>
                                    <span class="tuc-slider"></span>
                                    <span class="tuc-label">Enable Search</span>
                                </label>
                                <p class="tuc-help-text">Allow users to search through utility classes in the block editor.</p>
                            </div>
                        </div>
                        
                        <!-- Block Settings -->
                        <div class="tuc-settings-card">
                            <h3><span class="dashicons dashicons-editor-table"></span> Block Settings</h3>
                            
                            <div class="tuc-field">
                                <label><strong>Allowed Blocks</strong></label>
                                <p class="tuc-help-text">Select which blocks should have typography utility controls.</p>
                                
                                <div class="tuc-checkbox-grid">
                                    <?php
                                    $blocks = array(
                                        'core/paragraph' => 'Paragraph',
                                        'core/heading' => 'Heading',
                                        'core/list' => 'List',
                                        'core/list-item' => 'List Item',
                                        'core/quote' => 'Quote',
                                        'core/pullquote' => 'Pullquote',
                                        'core/button' => 'Button',
                                        'core/group' => 'Group',
                                        'core/column' => 'Column',
                                        'core/columns' => 'Columns',
                                        'core/cover' => 'Cover',
                                        'core/image' => 'Image'
                                    );
                                    
                                    foreach ($blocks as $block => $label) {
                                        $checked = isset($this->options['allowed_blocks']) && in_array($block, $this->options['allowed_blocks']);
                                        ?>
                                        <label class="tuc-checkbox-item">
                                            <input type="checkbox" name="tuc_options[allowed_blocks][]" value="<?php echo esc_attr($block); ?>" <?php checked($checked, true); ?>>
                                            <span class="tuc-checkmark"></span>
                                            <?php echo esc_html($label); ?>
                                        </label>
                                        <?php
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Utility Categories -->
                        <div class="tuc-settings-card">
                            <h3><span class="dashicons dashicons-admin-appearance"></span> Utility Categories</h3>
                            
                            <div class="tuc-field">
                                <label><strong>Available Categories</strong></label>
                                <p class="tuc-help-text">Select which categories of typography utilities should be available.</p>
                                
                                <div class="tuc-checkbox-grid">
                                    <?php
                                    $categories = array(
                                        'font_family' => 'Font Family',
                                        'font_size' => 'Font Size',
                                        'font_weight' => 'Font Weight',
                                        'font_style' => 'Font Style',
                                        'text_color' => 'Text Color',
                                        'text_align' => 'Text Alignment',
                                        'text_decoration' => 'Text Decoration',
                                        'text_transform' => 'Text Transform',
                                        'line_height' => 'Line Height',
                                        'letter_spacing' => 'Letter Spacing',
                                        'text_indent' => 'Text Indent'
                                    );
                                    
                                    foreach ($categories as $category => $label) {
                                        $checked = isset($this->options['utility_categories']) && in_array($category, $this->options['utility_categories']);
                                        ?>
                                        <label class="tuc-checkbox-item">
                                            <input type="checkbox" name="tuc_options[utility_categories][]" value="<?php echo esc_attr($category); ?>" <?php checked($checked, true); ?>>
                                            <span class="tuc-checkmark"></span>
                                            <?php echo esc_html($label); ?>
                                        </label>
                                        <?php
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Custom CSS -->
                        <div class="tuc-settings-card">
                            <h3><span class="dashicons dashicons-editor-code"></span> Custom CSS</h3>
                            
                            <div class="tuc-field">
                                <label for="custom_css"><strong>Custom Utility Classes</strong></label>
                                <p class="tuc-help-text">Add your own custom utility classes in CSS format.</p>
                                <textarea 
                                    id="custom_css" 
                                    name="tuc_options[custom_css]" 
                                    rows="8" 
                                    class="tuc-textarea"
                                    placeholder="/* Add your custom utility classes here */
.text-brand {
    color: #your-color;
}

.font-custom {
    font-family: 'Your Custom Font', sans-serif;
}"><?php echo esc_textarea(isset($this->options['custom_css']) ? $this->options['custom_css'] : ''); ?></textarea>
                            </div>
                            
                            <div class="tuc-field">
                                <label class="tuc-toggle-switch">
                                    <input type="checkbox" name="tuc_options[load_custom_css]" value="1" <?php checked(isset($this->options['load_custom_css']) ? $this->options['load_custom_css'] : 0, 1); ?>>
                                    <span class="tuc-slider"></span>
                                    <span class="tuc-label">Load Custom CSS</span>
                                </label>
                                <p class="tuc-help-text">Enable to load the custom CSS on the frontend.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="tuc-section-divider"></div>
                    
                    <!-- Preview Section -->
                    <div class="tuc-utility-preview">
                        <h4><span class="dashicons dashicons-visibility"></span> Live Preview</h4>
                        <div class="tuc-preview-grid">
                            <div class="tuc-preview-item">
                                <div class="tuc-preview-text font-sans text-lg font-bold text-center">
                                    Sample Text - Font Sans, Large, Bold, Centered
                                </div>
                                <code>.font-sans .text-lg .font-bold .text-center</code>
                            </div>
                            <div class="tuc-preview-item">
                                <div class="tuc-preview-text font-serif text-xl italic text-left">
                                    Sample Text - Font Serif, Extra Large, Italic, Left
                                </div>
                                <code>.font-serif .text-xl .italic .text-left</code>
                            </div>
                        </div>
                    </div>
                    
                    <div class="tuc-section-divider"></div>
                    
                    <!-- Submit Button -->
                    <div class="tuc-submit-section">
                        <?php submit_button('Save Settings', 'primary tuc-save-button', 'submit', false); ?>
                        <button type="button" class="button tuc-reset-button" onclick="if(confirm('Are you sure you want to reset all settings?')) { window.location.href = '?page=typography-utility-controls&reset=true'; }">
                            Reset All Settings
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Help Section -->
            <div class="tuc-help-section">
                <h3><span class="dashicons dashicons-editor-help"></span> How to Use</h3>
                <div class="tuc-help-grid">
                    <div class="tuc-help-item">
                        <strong>1. Enable Plugin</strong>
                        <p>Toggle the plugin on in General Settings</p>
                    </div>
                    <div class="tuc-help-item">
                        <strong>2. Select Blocks</strong>
                        <p>Choose which blocks get typography controls</p>
                    </div>
                    <div class="tuc-help-item">
                        <strong>3. Choose Categories</strong>
                        <p>Enable utility categories you want to use</p>
                    </div>
                    <div class="tuc-help-item">
                        <strong>4. Edit Blocks</strong>
                        <p>Find "Typography Utilities" in the block inspector</p>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    public function page_init() {
        // Handle reset
        if (isset($_GET['reset']) && $_GET['reset'] === 'true') {
            delete_option('tuc_options');
            $this->set_default_options();
            wp_redirect(admin_url('admin.php?page=typography-utility-controls&reset-success=true'));
            exit;
        }
        
        // Show reset success message
        if (isset($_GET['reset-success'])) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible"><p>Settings have been reset to defaults.</p></div>';
            });
        }
    }
    
    private function save_settings() {
        if (!isset($_POST['tuc_nonce']) || !wp_verify_nonce($_POST['tuc_nonce'], 'tuc_save_settings')) {
            wp_die('Security check failed');
        }
        
        $options = array();
        
        if (isset($_POST['tuc_options'])) {
            $options = array_map('sanitize_text_field', $_POST['tuc_options']);
            
            // Handle arrays
            if (isset($_POST['tuc_options']['allowed_blocks'])) {
                $options['allowed_blocks'] = array_map('sanitize_text_field', $_POST['tuc_options']['allowed_blocks']);
            }
            
            if (isset($_POST['tuc_options']['utility_categories'])) {
                $options['utility_categories'] = array_map('sanitize_text_field', $_POST['tuc_options']['utility_categories']);
            }
            
            // Handle custom CSS
            if (isset($_POST['tuc_options']['custom_css'])) {
                $options['custom_css'] = wp_kses_post($_POST['tuc_options']['custom_css']);
            }
        }
        
        update_option('tuc_options', $options);
        
        add_action('admin_notices', function() {
            echo '<div class="notice notice-success is-dismissible"><p>Settings saved successfully!</p></div>';
        });
    }
    
    private function set_default_options() {
        $default_options = array(
            'enable_plugin' => 1,
            'enable_search' => 1,
            'allowed_blocks' => array(
                'core/paragraph',
                'core/heading',
                'core/list',
                'core/button'
            ),
            'utility_categories' => array(
                'font_family',
                'font_size',
                'font_weight',
                'text_color',
                'text_align'
            ),
            'custom_css' => '',
            'load_custom_css' => 0
        );
        
        update_option('tuc_options', $default_options);
    }
    
    public function enqueue_admin_assets($hook) {
        if ('toplevel_page_typography-utility-controls' !== $hook) {
            return;
        }
        
        wp_enqueue_style(
            'tuc-admin-styles',
            TUC_PLUGIN_URL . 'inc/modern-admin.css',
            array(),
            '1.0.0'
        );
        
        wp_enqueue_script(
            'tuc-admin-script',
            TUC_PLUGIN_URL . 'inc/admin-script.js',
            array('jquery'),
            '1.0.0',
            true
        );
    }
}

new TUC_Admin_Panel();