<?php
/**
 * Base Module Admin Class
 *
 * Provides a clean, extensible base for module admin interfaces.
 * Modules extend this class and define their fields, settings, and pages.
 *
 * @package    Orbital_Editor_Suite
 * @subpackage Orbital_Editor_Suite/includes/admin
 */

namespace Orbital\Editor_Suite\Admin;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Base Module Admin Class
 *
 * Provides structure for module admin interfaces with field rendering,
 * settings management, and page registration.
 */
abstract class Module_Admin {

    /**
     * Module instance.
     */
    protected $module;

    /**
     * Module settings.
     */
    protected $settings;

    /**
     * Admin page slug.
     */
    protected $page_slug;

    /**
     * Page title.
     */
    protected $page_title;

    /**
     * Menu title.
     */
    protected $menu_title;

    /**
     * Initialize the admin interface.
     */
    public function __construct($module) {
        $this->module = $module;
        $this->settings = $module->get_settings();
        $this->init_admin_properties();
    }

    /**
     * Initialize admin properties (must be implemented by child classes).
     */
    abstract protected function init_admin_properties();

    /**
     * Get admin page fields (must be implemented by child classes).
     */
    abstract protected function get_admin_fields();

    /**
     * Register admin page.
     * Called via orbital_editor_suite_admin_pages hook.
     */
    public function register_admin_page() {
        add_submenu_page(
            'orbital-editor-suite',
            $this->page_title,
            $this->menu_title,
            'manage_options',
            $this->page_slug,
            array($this, 'render_admin_page')
        );
    }

    /**
     * Render the admin page.
     */
    public function render_admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        ?>
        <div class="wrap">
            <h1><?php echo esc_html($this->page_title); ?></h1>
            
            <div class="orbital-module-admin">
                <?php $this->render_admin_styles(); ?>
                <?php $this->render_admin_content(); ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render admin content.
     */
    protected function render_admin_content() {
        $fields = $this->get_admin_fields();
        
        foreach ($fields as $section_id => $section) {
            $this->render_section($section_id, $section);
        }
    }

    /**
     * Render a section.
     */
    protected function render_section($section_id, $section) {
        ?>
        <div class="orbital-admin-section orbital-admin-section-<?php echo esc_attr($section_id); ?>">
            <?php if (!empty($section['title'])) : ?>
                <div class="orbital-section-header">
                    <h3>
                        <?php if (!empty($section['icon'])) : ?>
                            <span class="dashicons dashicons-<?php echo esc_attr($section['icon']); ?>"></span>
                        <?php endif; ?>
                        <?php echo esc_html($section['title']); ?>
                    </h3>
                    <?php if (!empty($section['description'])) : ?>
                        <p class="orbital-section-description"><?php echo esc_html($section['description']); ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="orbital-section-content">
                <?php
                if (!empty($section['fields'])) {
                    $this->render_fields($section['fields']);
                }

                if (!empty($section['callback']) && is_callable($section['callback'])) {
                    call_user_func($section['callback']);
                }
                ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render fields.
     */
    protected function render_fields($fields) {
        ?>
        <form method="post" action="options.php" class="orbital-admin-form">
            <?php settings_fields('orbital_editor_suite_settings'); ?>
            
            <?php foreach ($fields as $field_id => $field) : ?>
                <div class="orbital-field orbital-field-<?php echo esc_attr($field['type']); ?>">
                    <?php $this->render_field($field_id, $field); ?>
                </div>
            <?php endforeach; ?>

            <?php submit_button(); ?>
        </form>
        <?php
    }

    /**
     * Render individual field.
     */
    protected function render_field($field_id, $field) {
        $field = wp_parse_args($field, array(
            'type' => 'text',
            'label' => '',
            'description' => '',
            'default' => '',
            'options' => array(),
            'placeholder' => ''
        ));

        $name = $this->get_field_name($field_id);
        $value = $this->get_field_value($field_id, $field['default']);

        switch ($field['type']) {
            case 'checkbox':
                $this->render_checkbox_field($field_id, $field, $name, $value);
                break;
            case 'multi_checkbox':
                $this->render_multi_checkbox_field($field_id, $field, $name, $value);
                break;
            case 'select':
                $this->render_select_field($field_id, $field, $name, $value);
                break;
            case 'textarea':
                $this->render_textarea_field($field_id, $field, $name, $value);
                break;
            case 'text':
            default:
                $this->render_text_field($field_id, $field, $name, $value);
                break;
        }

        if (!empty($field['description'])) {
            echo '<p class="orbital-field-description">' . esc_html($field['description']) . '</p>';
        }
    }

    /**
     * Render checkbox field.
     */
    protected function render_checkbox_field($field_id, $field, $name, $value) {
        ?>
        <!-- Hidden field to ensure unchecked checkboxes are submitted as empty value -->
        <input type="hidden" name="<?php echo esc_attr($name); ?>" value="">
        <label class="orbital-checkbox-label">
            <input type="checkbox" name="<?php echo esc_attr($name); ?>" value="1" <?php checked($value, true); ?>>
            <span class="orbital-checkbox-text"><?php echo esc_html($field['label']); ?></span>
        </label>
        <?php
    }

    /**
     * Render multi-checkbox field.
     */
    protected function render_multi_checkbox_field($field_id, $field, $name, $value) {
        if (!empty($field['label'])) {
            echo '<label>' . esc_html($field['label']) . '</label>';
        }
        
        // Ensure value is an array
        if (!is_array($value)) {
            $value = !empty($value) ? array($value) : array();
        }
        
        // Hidden field to ensure something is submitted even if no checkboxes are checked
        echo '<input type="hidden" name="' . esc_attr($name) . '[_dummy]" value="">';
        
        ?>
        <div class="orbital-multi-checkbox-grid">
            <?php foreach ($field['options'] as $option_value => $option_label) : ?>
                <label class="orbital-checkbox-item">
                    <input type="checkbox" 
                           name="<?php echo esc_attr($name); ?>[]" 
                           value="<?php echo esc_attr($option_value); ?>" 
                           <?php checked(in_array($option_value, $value)); ?>>
                    <span class="orbital-checkbox-text"><?php echo esc_html($option_label); ?></span>
                </label>
            <?php endforeach; ?>
        </div>
        
        <style>
        .orbital-multi-checkbox-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
            margin: 15px 0;
            padding: 20px;
            background: #fff;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .orbital-checkbox-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #fafbfc;
            position: relative;
        }
        .orbital-checkbox-item:hover {
            background-color: #f0f6ff;
            border-color: #0073aa;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0,115,170,0.15);
        }
        .orbital-checkbox-item:has(input[type="checkbox"]:checked) {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-color: #667eea;
            color: #fff;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }
        .orbital-checkbox-item:has(input[type="checkbox"]:checked)::before {
            content: "✓";
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #fff;
            font-weight: bold;
            font-size: 16px;
        }
        .orbital-checkbox-item input[type="checkbox"] {
            margin: 0;
            transform: scale(1.3);
            accent-color: #667eea;
            cursor: pointer;
        }
        .orbital-checkbox-item:has(input[type="checkbox"]:checked) input[type="checkbox"] {
            accent-color: #fff;
        }
        .orbital-checkbox-text {
            font-weight: 600;
            color: #1d2327;
            font-size: 14px;
            user-select: none;
        }
        .orbital-checkbox-item:has(input[type="checkbox"]:checked) .orbital-checkbox-text {
            color: #fff;
        }
        .orbital-multi-checkbox-grid .orbital-checkbox-item:has(input[type="checkbox"]:checked):hover {
            background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(102, 126, 234, 0.4);
        }
        </style>
        <?php
    }

    /**
     * Render select field.
     */
    protected function render_select_field($field_id, $field, $name, $value) {
        if (!empty($field['label'])) {
            echo '<label for="' . esc_attr($field_id) . '">' . esc_html($field['label']) . '</label>';
        }
        ?>
        <select name="<?php echo esc_attr($name); ?>" id="<?php echo esc_attr($field_id); ?>">
            <?php foreach ($field['options'] as $option_value => $option_label) : ?>
                <option value="<?php echo esc_attr($option_value); ?>" <?php selected($value, $option_value); ?>>
                    <?php echo esc_html($option_label); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    /**
     * Render textarea field.
     */
    protected function render_textarea_field($field_id, $field, $name, $value) {
        if (!empty($field['label'])) {
            echo '<label for="' . esc_attr($field_id) . '">' . esc_html($field['label']) . '</label>';
        }
        ?>
        <textarea name="<?php echo esc_attr($name); ?>" id="<?php echo esc_attr($field_id); ?>" 
            rows="<?php echo esc_attr($field['rows'] ?? 4); ?>"
            placeholder="<?php echo esc_attr($field['placeholder']); ?>"><?php echo esc_textarea($value); ?></textarea>
        <?php
    }

    /**
     * Render text field.
     */
    protected function render_text_field($field_id, $field, $name, $value) {
        if (!empty($field['label'])) {
            echo '<label for="' . esc_attr($field_id) . '">' . esc_html($field['label']) . '</label>';
        }
        ?>
        <input type="text" name="<?php echo esc_attr($name); ?>" id="<?php echo esc_attr($field_id); ?>" 
            value="<?php echo esc_attr($value); ?>" placeholder="<?php echo esc_attr($field['placeholder']); ?>">
        <?php
    }

    /**
     * Get field name for form submission.
     */
    protected function get_field_name($field_id) {
        return "orbital_editor_suite_options[modules][{$this->module->get_slug()}][{$field_id}]";
    }

    /**
     * Get field value from settings.
     */
    protected function get_field_value($field_id, $default = '') {
        return isset($this->settings[$field_id]) ? $this->settings[$field_id] : $default;
    }

    /**
     * Render admin styles.
     */
    protected function render_admin_styles() {
        ?>
        <style>
        .orbital-module-admin {
            max-width: 1200px;
        }
        .orbital-admin-section {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 8px;
            margin: 20px 0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .orbital-section-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
        }
        .orbital-section-header h3 {
            margin: 0 0 10px 0;
            display: flex;
            align-items: center;
            gap: 8px;
            color: #fff;
            font-size: 18px;
        }
        .orbital-section-header .dashicons {
            font-size: 20px;
            width: 20px;
            height: 20px;
        }
        .orbital-section-description {
            margin: 0;
            color: rgba(255,255,255,0.9);
            font-size: 14px;
        }
        .orbital-section-content {
            padding: 25px;
        }
        .orbital-admin-form {
            max-width: 600px;
        }
        .orbital-field {
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 6px;
            border: 1px solid #e9ecef;
        }
        .orbital-field label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #495057;
        }
        .orbital-checkbox-label {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            color: #495057;
            cursor: pointer;
            padding: 0;
        }
        .orbital-checkbox-label input[type="checkbox"] {
            transform: scale(1.2);
            accent-color: #667eea;
        }
        .orbital-field input[type="text"], 
        .orbital-field select, 
        .orbital-field textarea {
            width: 100%;
            max-width: 400px;
            padding: 10px 12px;
            border: 2px solid #dee2e6;
            border-radius: 6px;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        .orbital-field input[type="text"]:focus, 
        .orbital-field select:focus, 
        .orbital-field textarea:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            outline: none;
        }
        .orbital-field-description {
            font-size: 12px;
            color: #6c757d;
            margin: 8px 0 0 0;
            font-style: italic;
        }
        .button-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 6px;
            padding: 10px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .button-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        /* Card layouts for custom sections */
        .orbital-cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .orbital-card {
            background: #fff;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }
        .orbital-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .orbital-card h5 {
            margin: 0 0 10px 0;
            color: #495057;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .orbital-card-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 11px;
            text-transform: uppercase;
            font-weight: 600;
        }
        .orbital-card-sample {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 12px;
            border-radius: 6px;
            margin: 12px 0;
            border-left: 4px solid #667eea;
        }
        .orbital-form-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #fff 100%);
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 25px;
            margin: 20px 0;
        }
        .orbital-form-card h4 {
            margin: 0 0 20px 0;
            color: #495057;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .orbital-properties-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            background: #fff;
            padding: 20px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            margin: 15px 0;
        }
        .orbital-property-field label {
            font-size: 11px;
            text-transform: uppercase;
            color: #6c757d;
            margin-bottom: 5px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        .orbital-css-textarea {
            width: 100%;
            height: 300px;
            font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
            font-size: 13px;
            background: #2d3748;
            color: #e2e8f0;
            padding: 20px;
            border: none;
            border-radius: 8px;
            line-height: 1.5;
        }
        .orbital-css-output-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }
        </style>
        <?php
    }
}