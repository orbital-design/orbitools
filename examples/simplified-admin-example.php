<?php
/**
 * Simplified Admin Example
 *
 * This shows how to simplify our current Module_Admin approach
 * while keeping the benefits of custom styling and flexibility.
 *
 * @package Orbital_Editor_Suite
 * @subpackage Examples
 */

namespace Orbital\Editor_Suite\Examples;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Simplified Admin Class
 * 
 * A streamlined version of our Module_Admin that's easier to use
 * while maintaining the visual appeal and flexibility.
 */
class Simplified_Admin {

    private $page_slug;
    private $options_key;
    private $fields;

    public function __construct($page_slug, $options_key, $fields) {
        $this->page_slug = $page_slug;
        $this->options_key = $options_key;
        $this->fields = $fields;
        
        add_action('admin_menu', array($this, 'add_menu_page'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    public function add_menu_page() {
        add_submenu_page(
            'orbital-editor-suite',
            'Simplified Admin Example',
            'Simplified Example',
            'manage_options',
            $this->page_slug,
            array($this, 'render_page')
        );
    }

    public function register_settings() {
        register_setting($this->options_key, $this->options_key, array(
            'sanitize_callback' => array($this, 'sanitize_options')
        ));
    }

    public function render_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        if (isset($_POST['submit'])) {
            $this->handle_form_submission();
        }

        $options = get_option($this->options_key, array());
        ?>
        <div class="wrap">
            <h1>Simplified Admin Example</h1>
            
            <div class="simplified-admin">
                <form method="post" action="">
                    <?php wp_nonce_field('simplified_admin_nonce', 'simplified_nonce'); ?>
                    
                    <?php foreach ($this->fields as $section_id => $section) : ?>
                        <div class="admin-section">
                            <h2 class="section-title">
                                <?php if (!empty($section['icon'])) : ?>
                                    <span class="dashicons dashicons-<?php echo esc_attr($section['icon']); ?>"></span>
                                <?php endif; ?>
                                <?php echo esc_html($section['title']); ?>
                            </h2>
                            
                            <?php if (!empty($section['description'])) : ?>
                                <p class="section-description"><?php echo esc_html($section['description']); ?></p>
                            <?php endif; ?>
                            
                            <div class="section-fields">
                                <?php foreach ($section['fields'] as $field_id => $field) : ?>
                                    <div class="field-row">
                                        <?php $this->render_field($field_id, $field, $options); ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php submit_button('Save Settings', 'primary', 'submit', true, array('class' => 'save-button')); ?>
                </form>
            </div>
        </div>
        
        <?php $this->render_styles(); ?>
        <?php
    }

    private function render_field($field_id, $field, $options) {
        $value = isset($options[$field_id]) ? $options[$field_id] : ($field['default'] ?? '');
        $name = $this->options_key . '[' . $field_id . ']';
        
        echo '<div class="field-wrapper">';
        
        if (!empty($field['label'])) {
            echo '<label class="field-label">' . esc_html($field['label']) . '</label>';
        }
        
        switch ($field['type']) {
            case 'checkbox':
                echo '<input type="checkbox" name="' . esc_attr($name) . '" value="1" ' . checked($value, true, false) . '>';
                break;
                
            case 'select':
                echo '<select name="' . esc_attr($name) . '">';
                foreach ($field['options'] as $option_value => $option_label) {
                    echo '<option value="' . esc_attr($option_value) . '" ' . selected($value, $option_value, false) . '>';
                    echo esc_html($option_label);
                    echo '</option>';
                }
                echo '</select>';
                break;
                
            case 'textarea':
                echo '<textarea name="' . esc_attr($name) . '" rows="4">' . esc_textarea($value) . '</textarea>';
                break;
                
            case 'text':
            default:
                echo '<input type="text" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '">';
                break;
        }
        
        if (!empty($field['description'])) {
            echo '<p class="field-description">' . esc_html($field['description']) . '</p>';
        }
        
        echo '</div>';
    }

    private function handle_form_submission() {
        if (!wp_verify_nonce($_POST['simplified_nonce'], 'simplified_admin_nonce')) {
            return;
        }
        
        $options = isset($_POST[$this->options_key]) ? $_POST[$this->options_key] : array();
        $sanitized = $this->sanitize_options($options);
        
        update_option($this->options_key, $sanitized);
        
        add_settings_error(
            'simplified_admin',
            'settings_updated',
            'Settings saved successfully!',
            'updated'
        );
    }

    public function sanitize_options($input) {
        $sanitized = array();
        
        foreach ($this->fields as $section) {
            foreach ($section['fields'] as $field_id => $field) {
                if (isset($input[$field_id])) {
                    switch ($field['type']) {
                        case 'checkbox':
                            $sanitized[$field_id] = !empty($input[$field_id]);
                            break;
                        case 'textarea':
                            $sanitized[$field_id] = sanitize_textarea_field($input[$field_id]);
                            break;
                        case 'text':
                        default:
                            $sanitized[$field_id] = sanitize_text_field($input[$field_id]);
                            break;
                    }
                }
            }
        }
        
        return $sanitized;
    }

    private function render_styles() {
        ?>
        <style>
        .simplified-admin {
            max-width: 800px;
        }
        .admin-section {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 8px;
            margin: 20px 0;
            overflow: hidden;
        }
        .section-title {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            margin: 0;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 18px;
        }
        .section-description {
            padding: 0 20px;
            color: #666;
            font-style: italic;
        }
        .section-fields {
            padding: 20px;
        }
        .field-row {
            margin-bottom: 20px;
        }
        .field-wrapper {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .field-label {
            font-weight: 600;
            color: #333;
        }
        .field-wrapper input,
        .field-wrapper select,
        .field-wrapper textarea {
            max-width: 400px;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .field-description {
            font-size: 13px;
            color: #666;
            margin: 0;
        }
        .save-button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
            border: none !important;
            border-radius: 6px !important;
            font-weight: 600 !important;
        }
        </style>
        <?php
    }
}

/**
 * Usage Example
 * 
 * This shows how simple it is to create an admin page with the simplified approach.
 */
function create_simplified_admin_example() {
    $fields = array(
        'general' => array(
            'title' => 'General Settings',
            'icon' => 'admin-generic',
            'description' => 'Configure general module settings',
            'fields' => array(
                'enable_module' => array(
                    'type' => 'checkbox',
                    'label' => 'Enable Module',
                    'description' => 'Enable the typography presets module',
                    'default' => true
                ),
                'preset_method' => array(
                    'type' => 'select',
                    'label' => 'Preset Method',
                    'description' => 'Choose how presets are managed',
                    'options' => array(
                        'admin' => 'Admin Interface',
                        'theme_json' => 'Theme.json'
                    ),
                    'default' => 'admin'
                )
            )
        ),
        'typography' => array(
            'title' => 'Typography Settings',
            'icon' => 'editor-textcolor',
            'description' => 'Configure typography-specific settings',
            'fields' => array(
                'default_font_size' => array(
                    'type' => 'text',
                    'label' => 'Default Font Size',
                    'description' => 'Default font size for new presets',
                    'default' => '16px'
                ),
                'custom_css' => array(
                    'type' => 'textarea',
                    'label' => 'Custom CSS',
                    'description' => 'Add custom CSS for typography presets'
                )
            )
        )
    );
    
    new Simplified_Admin('orbital-simplified-example', 'orbital_simplified_options', $fields);
}

// Initialize the example (commented out - uncomment to test)
add_action('admin_init', 'Orbital\Editor_Suite\Examples\create_simplified_admin_example');