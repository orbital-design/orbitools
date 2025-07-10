<?php
/**
 * Typography Presets Admin Interface
 *
 * @package    Orbital_Editor_Suite
 * @subpackage Orbital_Editor_Suite/includes/modules/typography-presets/admin
 */

namespace Orbital\Editor_Suite\Modules\Typography_Presets\Admin;

use Orbital\Editor_Suite\Modules\Typography_Presets\Typography_Presets;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Typography Presets Admin Interface Class
 *
 * Handles the admin interface for managing typography presets.
 */
class Admin_Interface {

    /**
     * Typography Presets instance.
     */
    private $typography_presets;

    /**
     * Initialize the admin interface.
     */
    public function __construct(Typography_Presets $typography_presets) {
        $this->typography_presets = $typography_presets;
    }

    /**
     * Render the typography presets admin section.
     */
    public function render_admin_section() {
        $presets = $this->typography_presets->get_presets();
        $categories = $this->typography_presets->get_categories();
        ?>
        <div class="orbital-module-section orbital-typography-presets-section">
            <div class="orbital-module-header">
                <h3>
                    <span class="dashicons dashicons-admin-appearance"></span>
                    <?php _e('Typography Presets', 'orbital-editor-suite'); ?>
                </h3>
                <p class="orbital-module-description">
                    <?php _e('Create and manage typography presets that combine multiple style properties into reusable utility classes. Replace core typography controls with a streamlined preset dropdown.', 'orbital-editor-suite'); ?>
                </p>
            </div>

            <div class="orbital-settings-grid">
                <!-- Module Settings -->
                <div class="orbital-settings-card">
                    <h4><span class="dashicons dashicons-admin-settings"></span> <?php _e('Module Settings', 'orbital-editor-suite'); ?></h4>
                    
                    <div class="orbital-field">
                        <label class="orbital-toggle-switch">
                            <input type="checkbox" name="modules[typography-presets][enabled]" value="1" 
                                <?php checked($this->typography_presets->is_enabled(), true); ?>>
                            <span class="orbital-slider"></span>
                            <span class="orbital-label"><?php _e('Enable Typography Presets', 'orbital-editor-suite'); ?></span>
                        </label>
                        <p class="orbital-help-text"><?php _e('Enable the typography presets system throughout the editor.', 'orbital-editor-suite'); ?></p>
                    </div>

                    <div class="orbital-field">
                        <label class="orbital-toggle-switch">
                            <input type="checkbox" name="modules[typography-presets][replace_core_controls]" value="1" 
                                <?php checked($this->typography_presets->should_replace_core_controls(), true); ?>>
                            <span class="orbital-slider"></span>
                            <span class="orbital-label"><?php _e('Replace Core Typography Controls', 'orbital-editor-suite'); ?></span>
                        </label>
                        <p class="orbital-help-text"><?php _e('Replace WordPress core typography controls with preset dropdown.', 'orbital-editor-suite'); ?></p>
                    </div>

                    <div class="orbital-field">
                        <label><strong><?php _e('Allowed Blocks', 'orbital-editor-suite'); ?></strong></label>
                        <p class="orbital-help-text"><?php _e('Select which blocks should have typography preset controls.', 'orbital-editor-suite'); ?></p>
                        
                        <div class="orbital-checkbox-grid">
                            <?php
                            $blocks = array(
                                'core/paragraph' => __('Paragraph', 'orbital-editor-suite'),
                                'core/heading' => __('Heading', 'orbital-editor-suite'),
                                'core/list' => __('List', 'orbital-editor-suite'),
                                'core/quote' => __('Quote', 'orbital-editor-suite'),
                                'core/pullquote' => __('Pullquote', 'orbital-editor-suite'),
                                'core/button' => __('Button', 'orbital-editor-suite'),
                                'core/group' => __('Group', 'orbital-editor-suite'),
                                'core/cover' => __('Cover', 'orbital-editor-suite')
                            );
                            
                            $allowed_blocks = $this->typography_presets->get_allowed_blocks();
                            
                            foreach ($blocks as $block => $label) {
                                $checked = in_array($block, $allowed_blocks);
                                ?>
                                <label class="orbital-checkbox-item">
                                    <input type="checkbox" name="modules[typography-presets][allowed_blocks][]" 
                                        value="<?php echo esc_attr($block); ?>" <?php checked($checked, true); ?>>
                                    <span class="orbital-checkmark"></span>
                                    <?php echo esc_html($label); ?>
                                </label>
                                <?php
                            }
                            ?>
                        </div>
                    </div>
                </div>

                <!-- Preset Management -->
                <div class="orbital-settings-card">
                    <h4><span class="dashicons dashicons-plus-alt"></span> <?php _e('Create New Preset', 'orbital-editor-suite'); ?></h4>
                    
                    <form id="orbital-new-preset-form" class="orbital-preset-form">
                        <div class="orbital-field">
                            <label for="preset-id"><strong><?php _e('Preset ID', 'orbital-editor-suite'); ?></strong></label>
                            <input type="text" id="preset-id" name="id" class="regular-text" 
                                placeholder="e.g., custom-heading" required>
                            <p class="orbital-help-text"><?php _e('Unique identifier for the preset (lowercase, hyphens only).', 'orbital-editor-suite'); ?></p>
                        </div>

                        <div class="orbital-field">
                            <label for="preset-label"><strong><?php _e('Display Name', 'orbital-editor-suite'); ?></strong></label>
                            <input type="text" id="preset-label" name="label" class="regular-text" 
                                placeholder="e.g., Custom Heading" required>
                        </div>

                        <div class="orbital-field">
                            <label for="preset-description"><strong><?php _e('Description', 'orbital-editor-suite'); ?></strong></label>
                            <textarea id="preset-description" name="description" rows="2" class="orbital-textarea"
                                placeholder="<?php _e('Brief description of when to use this preset...', 'orbital-editor-suite'); ?>"></textarea>
                        </div>

                        <div class="orbital-field">
                            <label for="preset-category"><strong><?php _e('Category', 'orbital-editor-suite'); ?></strong></label>
                            <select id="preset-category" name="category" class="regular-text">
                                <option value="headings"><?php _e('Headings', 'orbital-editor-suite'); ?></option>
                                <option value="body"><?php _e('Body Text', 'orbital-editor-suite'); ?></option>
                                <option value="utility"><?php _e('Utility', 'orbital-editor-suite'); ?></option>
                                <option value="custom"><?php _e('Custom', 'orbital-editor-suite'); ?></option>
                            </select>
                        </div>

                        <div class="orbital-field">
                            <label><strong><?php _e('Typography Properties', 'orbital-editor-suite'); ?></strong></label>
                            <div class="orbital-preset-properties">
                                <div class="orbital-property-row">
                                    <label><?php _e('Font Size', 'orbital-editor-suite'); ?></label>
                                    <input type="text" name="properties[font-size]" placeholder="1rem">
                                </div>
                                <div class="orbital-property-row">
                                    <label><?php _e('Line Height', 'orbital-editor-suite'); ?></label>
                                    <input type="text" name="properties[line-height]" placeholder="1.5">
                                </div>
                                <div class="orbital-property-row">
                                    <label><?php _e('Font Weight', 'orbital-editor-suite'); ?></label>
                                    <select name="properties[font-weight]">
                                        <option value=""><?php _e('Default', 'orbital-editor-suite'); ?></option>
                                        <option value="300"><?php _e('Light (300)', 'orbital-editor-suite'); ?></option>
                                        <option value="400"><?php _e('Normal (400)', 'orbital-editor-suite'); ?></option>
                                        <option value="500"><?php _e('Medium (500)', 'orbital-editor-suite'); ?></option>
                                        <option value="600"><?php _e('Semi Bold (600)', 'orbital-editor-suite'); ?></option>
                                        <option value="700"><?php _e('Bold (700)', 'orbital-editor-suite'); ?></option>
                                    </select>
                                </div>
                                <div class="orbital-property-row">
                                    <label><?php _e('Letter Spacing', 'orbital-editor-suite'); ?></label>
                                    <input type="text" name="properties[letter-spacing]" placeholder="0">
                                </div>
                                <div class="orbital-property-row">
                                    <label><?php _e('Text Transform', 'orbital-editor-suite'); ?></label>
                                    <select name="properties[text-transform]">
                                        <option value=""><?php _e('None', 'orbital-editor-suite'); ?></option>
                                        <option value="uppercase"><?php _e('Uppercase', 'orbital-editor-suite'); ?></option>
                                        <option value="lowercase"><?php _e('Lowercase', 'orbital-editor-suite'); ?></option>
                                        <option value="capitalize"><?php _e('Capitalize', 'orbital-editor-suite'); ?></option>
                                    </select>
                                </div>
                                <div class="orbital-property-row">
                                    <label><?php _e('Margin Bottom', 'orbital-editor-suite'); ?></label>
                                    <input type="text" name="properties[margin-bottom]" placeholder="1rem">
                                </div>
                            </div>
                        </div>

                        <div class="orbital-field">
                            <button type="submit" class="button button-primary">
                                <span class="dashicons dashicons-plus"></span>
                                <?php _e('Create Preset', 'orbital-editor-suite'); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Existing Presets -->
            <div class="orbital-presets-list">
                <h4><?php _e('Existing Presets', 'orbital-editor-suite'); ?></h4>
                
                <div class="orbital-presets-grid">
                    <?php foreach ($presets as $id => $preset): ?>
                    <div class="orbital-preset-card" data-preset-id="<?php echo esc_attr($id); ?>">
                        <div class="orbital-preset-header">
                            <h5><?php echo esc_html($preset['label']); ?></h5>
                            <?php if (empty($preset['is_default'])): ?>
                                <button class="orbital-delete-preset" data-preset-id="<?php echo esc_attr($id); ?>">
                                    <span class="dashicons dashicons-trash"></span>
                                </button>
                            <?php endif; ?>
                        </div>
                        
                        <div class="orbital-preset-meta">
                            <span class="orbital-preset-category"><?php echo esc_html($preset['category']); ?></span>
                            <?php if (!empty($preset['is_default'])): ?>
                                <span class="orbital-preset-badge"><?php _e('Default', 'orbital-editor-suite'); ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (!empty($preset['description'])): ?>
                            <p class="orbital-preset-description"><?php echo esc_html($preset['description']); ?></p>
                        <?php endif; ?>
                        
                        <div class="orbital-preset-preview">
                            <div class="orbital-preset-sample orbital-preset-<?php echo esc_attr($id); ?>">
                                <?php _e('Sample Text', 'orbital-editor-suite'); ?>
                            </div>
                        </div>
                        
                        <div class="orbital-preset-properties">
                            <?php foreach ($preset['properties'] as $property => $value): ?>
                                <span class="orbital-property-tag">
                                    <?php echo esc_html($property); ?>: <?php echo esc_html($value); ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- CSS Output -->
            <div class="orbital-settings-card">
                <h4><span class="dashicons dashicons-editor-code"></span> <?php _e('Generated CSS', 'orbital-editor-suite'); ?></h4>
                <p class="orbital-help-text"><?php _e('This CSS is automatically generated and applied to your site.', 'orbital-editor-suite'); ?></p>
                
                <textarea class="orbital-textarea orbital-css-output" readonly rows="10"><?php echo esc_textarea($this->typography_presets->generate_css()); ?></textarea>
                
                <div class="orbital-field">
                    <label class="orbital-toggle-switch">
                        <input type="checkbox" name="modules[typography-presets][custom_css_output]" value="1" checked>
                        <span class="orbital-slider"></span>
                        <span class="orbital-label"><?php _e('Output CSS to Frontend', 'orbital-editor-suite'); ?></span>
                    </label>
                    <p class="orbital-help-text"><?php _e('Automatically include generated CSS on your website.', 'orbital-editor-suite'); ?></p>
                </div>
            </div>
        </div>

        <style>
        .orbital-preset-properties {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin: 15px 0;
        }
        
        .orbital-property-row {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .orbital-property-row label {
            font-weight: 500;
            font-size: 13px;
            color: #2c3e50;
        }
        
        .orbital-property-row input,
        .orbital-property-row select {
            padding: 6px 8px;
            border: 1px solid #e9ecef;
            border-radius: 4px;
            font-size: 13px;
        }
        
        .orbital-presets-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .orbital-preset-card {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            transition: all 0.3s ease;
        }
        
        .orbital-preset-card:hover {
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }
        
        .orbital-preset-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .orbital-preset-header h5 {
            margin: 0;
            color: #2c3e50;
            font-size: 16px;
            font-weight: 600;
        }
        
        .orbital-delete-preset {
            background: transparent;
            border: none;
            color: #dc3545;
            cursor: pointer;
            padding: 4px;
            border-radius: 4px;
            transition: background 0.2s ease;
        }
        
        .orbital-delete-preset:hover {
            background: #ffeaea;
        }
        
        .orbital-preset-meta {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
        }
        
        .orbital-preset-category {
            background: #e3f2fd;
            color: #1976d2;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
            text-transform: uppercase;
        }
        
        .orbital-preset-badge {
            background: #e8f5e8;
            color: #2e7d32;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
        }
        
        .orbital-preset-description {
            color: #6c757d;
            font-size: 13px;
            line-height: 1.4;
            margin: 10px 0;
        }
        
        .orbital-preset-preview {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
        }
        
        .orbital-preset-sample {
            margin: 0;
        }
        
        .orbital-preset-properties {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-top: 15px;
        }
        
        .orbital-property-tag {
            background: #f1f3f4;
            color: #5f6368;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 11px;
            font-family: monospace;
        }
        
        .orbital-css-output {
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
            font-size: 12px;
            background: #f8f9fa;
            border: 1px solid #e9ecef;
        }
        </style>
        <?php
    }
}