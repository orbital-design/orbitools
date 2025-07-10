<?php
/**
 * Typography Presets Admin Class
 *
 * Extends the base Module_Admin class to provide clean admin interface
 * for the Typography Presets module.
 *
 * @package    Orbital_Editor_Suite
 * @subpackage Orbital_Editor_Suite/includes/modules/typography-presets
 */

namespace Orbital\Editor_Suite\Modules\Typography_Presets;

use Orbital\Editor_Suite\Admin\Module_Admin;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Typography Presets Admin Class
 *
 * Handles admin interface for Typography Presets module.
 */
class Typography_Presets_Admin extends Module_Admin {

    /**
     * Initialize admin properties.
     */
    protected function init_admin_properties() {
        $this->page_slug = 'orbital-editor-suite-typography';
        $this->page_title = __('Typography Presets', 'orbital-editor-suite');
        $this->menu_title = __('Typography Presets', 'orbital-editor-suite');
        
        // Add hook to refresh module settings when options are updated
        add_action('update_option_orbital_editor_suite_options', array($this, 'refresh_module_settings'), 10, 2);
    }

    /**
     * Refresh module settings after options update.
     */
    public function refresh_module_settings($old_value, $new_value) {
        $this->module->refresh_settings();
        $this->settings = $this->module->get_settings();
    }

    /**
     * Get admin page fields and sections.
     */
    protected function get_admin_fields() {
        return array(
            'settings' => array(
                'title' => __('Module Settings', 'orbital-editor-suite'),
                'icon' => 'admin-generic',
                'description' => __('Configure how the Typography Presets module behaves.', 'orbital-editor-suite'),
                'fields' => array(
                    'replace_core_controls' => array(
                        'type' => 'checkbox',
                        'label' => __('Replace Core Typography Controls', 'orbital-editor-suite'),
                        'description' => __('Remove WordPress core typography controls and replace with preset system.', 'orbital-editor-suite'),
                        'default' => true
                    ),
                    'show_groups' => array(
                        'type' => 'checkbox',
                        'label' => __('Show Groups in Dropdown', 'orbital-editor-suite'),
                        'description' => __('Group presets by group in the block editor dropdown.', 'orbital-editor-suite'),
                        'default' => true
                    ),
                    'custom_css_output' => array(
                        'type' => 'checkbox',
                        'label' => __('Output CSS for Presets', 'orbital-editor-suite'),
                        'description' => __('Automatically include CSS for presets on frontend and in editor.', 'orbital-editor-suite'),
                        'default' => true
                    ),
                    'allowed_blocks' => array(
                        'type' => 'multi_checkbox',
                        'label' => __('Allowed Blocks', 'orbital-editor-suite'),
                        'description' => __('Select which blocks should have typography preset controls.', 'orbital-editor-suite'),
                        'options' => array(
                            'core/paragraph' => __('Paragraph', 'orbital-editor-suite'),
                            'core/heading' => __('Heading', 'orbital-editor-suite'),
                            'core/list' => __('List', 'orbital-editor-suite'),
                            'core/quote' => __('Quote', 'orbital-editor-suite'),
                            'core/button' => __('Button', 'orbital-editor-suite'),
                            'core/pullquote' => __('Pullquote', 'orbital-editor-suite'),
                            'core/group' => __('Group', 'orbital-editor-suite'),
                            'core/column' => __('Column', 'orbital-editor-suite')
                        ),
                        'default' => array('core/paragraph', 'core/heading', 'core/list', 'core/quote', 'core/button')
                    )
                )
            ),
            'preset_management' => array(
                'title' => __('Preset Management', 'orbital-editor-suite'),
                'icon' => 'admin-appearance',
                'description' => __('Create and manage your typography presets.', 'orbital-editor-suite'),
                'callback' => array($this, 'render_preset_management')
            ),
            'css_output' => array(
                'title' => __('Generated CSS', 'orbital-editor-suite'),
                'icon' => 'editor-code',
                'description' => __('View and copy the CSS generated for your presets.', 'orbital-editor-suite'),
                'callback' => array($this, 'render_css_output')
            )
        );
    }

    /**
     * Render preset management section.
     */
    public function render_preset_management() {
        $presets = $this->module->get_presets();
        ?>
        <div class="orbital-preset-management">
            <!-- Create New Preset Form -->
            <div class="orbital-form-card">
                <h4>
                    <span class="dashicons dashicons-plus-alt"></span>
                    <?php _e('Create New Preset', 'orbital-editor-suite'); ?>
                </h4>
                <?php $this->render_preset_form(); ?>
            </div>

            <!-- Existing Presets -->
            <div class="orbital-form-card">
                <h4>
                    <span class="dashicons dashicons-list-view"></span>
                    <?php _e('Existing Presets', 'orbital-editor-suite'); ?>
                </h4>
                <?php $this->render_presets_list($presets); ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render preset creation form.
     */
    private function render_preset_form() {
        ?>
        <form id="orbital-new-preset-form">
            <div class="orbital-field">
                <label for="preset-id"><?php _e('Preset ID', 'orbital-editor-suite'); ?></label>
                <input type="text" id="preset-id" name="id" placeholder="e.g., custom-heading" required>
                <p class="orbital-field-description"><?php _e('Unique identifier (lowercase, hyphens only).', 'orbital-editor-suite'); ?></p>
            </div>

            <div class="orbital-field">
                <label for="preset-label"><?php _e('Display Name', 'orbital-editor-suite'); ?></label>
                <input type="text" id="preset-label" name="label" placeholder="e.g., Custom Heading" required>
            </div>

            <div class="orbital-field">
                <label for="preset-description"><?php _e('Description', 'orbital-editor-suite'); ?></label>
                <textarea id="preset-description" name="description" rows="2" 
                    placeholder="<?php _e('Brief description...', 'orbital-editor-suite'); ?>"></textarea>
            </div>

            <div class="orbital-field">
                <label for="preset-group"><?php _e('Group', 'orbital-editor-suite'); ?></label>
                <select id="preset-group" name="group">
                    <option value="headings"><?php _e('Headings', 'orbital-editor-suite'); ?></option>
                    <option value="body"><?php _e('Body Text', 'orbital-editor-suite'); ?></option>
                    <option value="utility"><?php _e('Utility', 'orbital-editor-suite'); ?></option>
                    <option value="custom"><?php _e('Custom', 'orbital-editor-suite'); ?></option>
                </select>
            </div>

            <div class="orbital-field">
                <label><?php _e('Typography Properties', 'orbital-editor-suite'); ?></label>
                <div class="orbital-properties-grid">
                    <?php $this->render_typography_property_fields(); ?>
                </div>
            </div>

            <button type="submit" class="button button-primary">
                <?php _e('Create Preset', 'orbital-editor-suite'); ?>
            </button>
        </form>
        <?php
    }

    /**
     * Render typography property fields.
     */
    private function render_typography_property_fields() {
        $properties = array(
            'font-size' => array('label' => __('Font Size', 'orbital-editor-suite'), 'placeholder' => '1rem'),
            'line-height' => array('label' => __('Line Height', 'orbital-editor-suite'), 'placeholder' => '1.5'),
            'font-weight' => array('label' => __('Font Weight', 'orbital-editor-suite'), 'type' => 'select', 'options' => array(
                '' => __('Default', 'orbital-editor-suite'),
                '300' => __('Light (300)', 'orbital-editor-suite'),
                '400' => __('Normal (400)', 'orbital-editor-suite'),
                '500' => __('Medium (500)', 'orbital-editor-suite'),
                '600' => __('Semi Bold (600)', 'orbital-editor-suite'),
                '700' => __('Bold (700)', 'orbital-editor-suite')
            )),
            'letter-spacing' => array('label' => __('Letter Spacing', 'orbital-editor-suite'), 'placeholder' => '0'),
            'text-transform' => array('label' => __('Text Transform', 'orbital-editor-suite'), 'type' => 'select', 'options' => array(
                '' => __('None', 'orbital-editor-suite'),
                'uppercase' => __('Uppercase', 'orbital-editor-suite'),
                'lowercase' => __('Lowercase', 'orbital-editor-suite'),
                'capitalize' => __('Capitalize', 'orbital-editor-suite')
            )),
            'margin-bottom' => array('label' => __('Margin Bottom', 'orbital-editor-suite'), 'placeholder' => '1rem')
        );

        foreach ($properties as $prop_id => $prop) {
            echo '<div class="orbital-property-field">';
            echo '<label>' . esc_html($prop['label']) . '</label>';
            
            if (isset($prop['type']) && $prop['type'] === 'select') {
                echo '<select name="properties[' . esc_attr($prop_id) . ']">';
                foreach ($prop['options'] as $value => $label) {
                    echo '<option value="' . esc_attr($value) . '">' . esc_html($label) . '</option>';
                }
                echo '</select>';
            } else {
                echo '<input type="text" name="properties[' . esc_attr($prop_id) . ']" placeholder="' . esc_attr($prop['placeholder'] ?? '') . '">';
            }
            
            echo '</div>';
        }
    }

    /**
     * Render presets list.
     */
    private function render_presets_list($presets) {
        if (empty($presets)) {
            echo '<p>' . __('No presets found.', 'orbital-editor-suite') . '</p>';
            return;
        }
        ?>
        <div class="orbital-cards-grid">
            <?php foreach ($presets as $id => $preset) : ?>
                <div class="orbital-card">
                    <h5>
                        <?php echo esc_html($preset['label']); ?>
                        <?php if (!empty($preset['is_default'])) : ?>
                            <span class="orbital-card-badge"><?php _e('Default', 'orbital-editor-suite'); ?></span>
                        <?php endif; ?>
                    </h5>
                    
                    <?php if (!empty($preset['description'])) : ?>
                        <p style="color: #6c757d; font-size: 14px;"><?php echo esc_html($preset['description']); ?></p>
                    <?php endif; ?>
                    
                    <div class="orbital-card-sample orbital-preset-<?php echo esc_attr($id); ?>">
                        <?php _e('Sample text with this preset', 'orbital-editor-suite'); ?>
                    </div>
                    
                    <?php if (!empty($preset['properties'])) : ?>
                        <div style="font-size: 12px; color: #6c757d; margin: 8px 0;">
                            <?php 
                            $props = array_slice($preset['properties'], 0, 3);
                            echo esc_html(implode(' • ', array_map(function($k, $v) { 
                                return $k . ': ' . $v; 
                            }, array_keys($props), $props)));
                            ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (empty($preset['is_default'])) : ?>
                        <button type="button" class="button button-small orbital-delete-preset" 
                            data-preset-id="<?php echo esc_attr($id); ?>" 
                            style="margin-top: 10px; background: #dc3545; color: #fff; border: none; border-radius: 4px;">
                            <?php _e('Delete', 'orbital-editor-suite'); ?>
                        </button>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }

    /**
     * Render CSS output section.
     */
    public function render_css_output() {
        $css = $this->module->generate_css();
        ?>
        <div class="orbital-css-output-section">
            <p><?php _e('This CSS is automatically included when the module is enabled:', 'orbital-editor-suite'); ?></p>
            <textarea class="orbital-css-textarea" readonly><?php echo esc_textarea($css); ?></textarea>
            <button type="button" class="button button-secondary" onclick="navigator.clipboard.writeText(this.previousElementSibling.value)">
                <?php _e('Copy CSS', 'orbital-editor-suite'); ?>
            </button>
        </div>

        <style>
        .orbital-css-textarea {
            width: 100%;
            height: 300px;
            font-family: monospace;
            font-size: 12px;
            background: #2b2b2b;
            color: #f8f8f2;
            padding: 15px;
            border: none;
            border-radius: 4px;
        }
        </style>
        <?php
    }
}