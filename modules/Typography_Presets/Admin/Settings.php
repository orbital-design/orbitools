<?php

/**
 * Typography Presets Settings Configuration
 *
 * Handles settings field definitions and configuration for the Typography Presets module.
 * This class centralizes all settings-related logic for better maintainability.
 *
 * @package    Orbitools
 * @subpackage Modules/Typography_Presets/Admin
 * @since      1.0.0
 */

namespace Orbitools\Modules\Typography_Presets\Admin;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Typography Presets Settings Class
 *
 * Manages settings configuration and validation for the Typography Presets module.
 *
 * @since 1.0.0
 */
class Settings
{
    /**
     * Get default settings configuration
     *
     * @since 1.0.0
     * @return array Default settings array.
     */
    public static function get_defaults(): array
    {
        return array(
            'typography_presets_enabled' => false,
            'typography_show_groups_in_dropdown' => false,
            'typography_output_preset_css' => true,
            'typography_allowed_blocks' => array(
                'core/paragraph',
                'core/heading',
                'core/list',
                'core/quote',
                'core/button',
            ),
        );
    }

    /**
     * Get settings field definitions for admin framework
     *
     * @since 1.0.0
     * @return array Settings fields array.
     */
    public static function get_field_definitions(): array
    {
        return array(
            array(
                'id'      => 'typography_show_groups_in_dropdown',
                'name'    => __('Show Groups in Dropdown', 'orbitools'),
                'desc'    => __('Display preset groups as separate dropdown options.', 'orbitools'),
                'type'    => 'checkbox',
                'std'     => false,
                'section' => 'typography',
            ),
            array(
                'id'      => 'typography_output_preset_css',
                'name'    => __('Output Preset CSS', 'orbitools'),
                'desc'    => __('Automatically output CSS for typography presets in the page head.', 'orbitools'),
                'type'    => 'checkbox',
                'std'     => true,
                'section' => 'typography',
            ),
            array(
                'id'      => 'typography_allowed_blocks',
                'name'    => __('Allowed Blocks', 'orbitools'),
                'desc'    => __('Select which block types can use typography presets.', 'orbitools'),
                'type'    => 'checkbox',
                'options' => array(
                    'core/paragraph' => __('Paragraph', 'orbitools'),
                    'core/heading' => __('Heading', 'orbitools'),
                    'core/list' => __('List', 'orbitools'),
                    'core/quote' => __('Quote', 'orbitools'),
                    'core/button' => __('Button', 'orbitools'),
                    'core/group' => __('Group', 'orbitools'),
                    'core/column' => __('Column', 'orbitools'),
                    'core/cover' => __('Cover', 'orbitools'),
                ),
                'std'     => array(
                    'core/paragraph',
                    'core/heading',
                    'core/list',
                    'core/quote',
                    'core/button'
                ),
                'section' => 'typography',
            ),
            array(
                'id'      => 'typography_presets_preview',
                'name'    => __('Available Presets', 'orbitools'),
                'desc'    => __('Preview of typography presets from your theme.json file.', 'orbitools'),
                'type'    => 'html',
                'std'     => self::get_presets_preview_html(),
                'section' => 'typography',
            ),
        );
    }

    /**
     * Get admin structure configuration
     *
     * @since 1.0.0
     * @return array Admin structure configuration.
     */
    public static function get_admin_structure(): array
    {
        return array(
            'sections' => array(
                'typography' => __('Typography Presets', 'orbitools'),
            ),
        );
    }


    /**
     * Get current settings with defaults
     *
     * @since 1.0.0
     * @return array Current settings merged with defaults.
     */
    public static function get_current_settings(): array
    {
        $saved_settings = get_option('orbitools_settings', array());
        return wp_parse_args($saved_settings, self::get_defaults());
    }

    /**
     * Get presets preview HTML
     *
     * @since 1.0.0
     * @return string HTML for presets preview.
     */
    public static function get_presets_preview_html(): string
    {
        // Enqueue the CSS
        self::enqueue_preset_styles();
        
        // Try to get preset manager
        try {
            $preset_manager = new \Orbitools\Modules\Typography_Presets\Core\Preset_Manager();
            $presets = $preset_manager->get_presets();
        } catch (Exception $e) {
            return '<p>' . __('Unable to load presets. Please check your theme.json configuration.', 'orbitools') . '</p>';
        }

        if (empty($presets)) {
            return '<div class="presets-empty">
                <p class="presets-empty__text"><strong>' . __('No presets found.', 'orbitools') . '</strong></p>
                <p class="presets-empty__text">' . __('Add typography presets to your theme.json file to see them here.', 'orbitools') . '</p>
                <p class="presets-empty__text presets-empty__text--last"><a href="https://github.com/orbital-design/orbitools/blob/main/modules/Typography_Presets/README.md" target="_blank">' . __('View Documentation', 'orbitools') . '</a></p>
            </div>';
        }

        $html = '<div class="presets-grid">';
        
        // Check if we should group presets
        $settings = self::get_current_settings();
        $show_groups = !empty($settings['typography_show_groups_in_dropdown']);
        
        if ($show_groups) {
            $grouped_presets = array();
            foreach ($presets as $id => $preset) {
                $group = $preset['group'] ?? 'ungrouped';
                if (!isset($grouped_presets[$group])) {
                    $grouped_presets[$group] = array();
                }
                $grouped_presets[$group][$id] = $preset;
            }
            
            foreach ($grouped_presets as $group => $group_presets) {
                $html .= '<div class="presets-group">';
                $html .= '<h3 class="presets-group__title">' . esc_html($group) . '</h3>';
                $html .= '<hr class="presets-group__separator">';
                $html .= '</div>';
                
                foreach ($group_presets as $id => $preset) {
                    $html .= self::get_preset_card_html($id, $preset);
                }
            }
        } else {
            foreach ($presets as $id => $preset) {
                $html .= self::get_preset_card_html($id, $preset);
            }
        }
        
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Get HTML for individual preset card
     *
     * @since 1.0.0
     * @param string $id Preset ID.
     * @param array $preset Preset data.
     * @return string HTML for preset card.
     */
    private static function get_preset_card_html(string $id, array $preset): string
    {
        $label = $preset['label'] ?? $id;
        $description = $preset['description'] ?? '';
        $properties = $preset['properties'] ?? array();
        
        // Build inline styles from properties for the sample text
        $inline_styles = array();
        foreach ($properties as $property => $value) {
            $css_property = self::sanitize_css_property($property);
            if ($css_property) {
                $inline_styles[] = $css_property . ': ' . esc_attr($value);
            }
        }
        $style_attr = !empty($inline_styles) ? ' style="' . implode('; ', $inline_styles) . '"' : '';
        
        $class_name = 'has-type-preset-' . $id;
        $html = '<div class="preset-card" data-copy-text="' . esc_attr($class_name) . '" title="Click to copy class name">';
        $html .= '<div class="preset-card__inner">';
        $html .= '<div class="preset-card__header">';
        $html .= '<div class="preset-card__content">';
        $html .= '<h4 class="preset-card__title">' . esc_html($label) . '</h4>';
        $html .= '<div class="preset-card__meta">';
        $html .= '.' . esc_html($class_name);
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        
        $html .= '<div class="preset-card__preview">';
        $html .= '<div class="preset-card__sample"' . $style_attr . '>';
        $html .= __('The quick brown fox jumps over the lazy dog', 'orbitools');
        $html .= '</div>';
        $html .= '</div>';
        
        $html .= '<div class="preset-card__meta">';
        if (!empty($properties)) {
            foreach ($properties as $property => $value) {
                $html .= '<strong>' . esc_html($property) . ':</strong> ' . esc_html($value) . '<br>';
            }
        } else {
            $html .= '<em>No properties defined</em>';
        }
        $html .= '</div>';
        $html .= '</div>';
        
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Enqueue preset preview styles
     *
     * @since 1.0.0
     */
    private static function enqueue_preset_styles(): void
    {
        wp_enqueue_style(
            'orbitools-typography-presets-admin',
            ORBITOOLS_URL . 'modules/Typography_Presets/css/admin-presets.css',
            array('dashicons'),
            '1.0.0'
        );
        
        wp_enqueue_script(
            'orbitools-typography-presets-admin',
            ORBITOOLS_URL . 'modules/Typography_Presets/js/admin-presets.js',
            array(),
            '1.0.0',
            true
        );
    }

    /**
     * Sanitize CSS property name
     *
     * @since 1.0.0
     * @param string $property CSS property name.
     * @return string|null Sanitized property or null if invalid.
     */
    private static function sanitize_css_property(string $property): ?string
    {
        // Convert camelCase to kebab-case
        $property = strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $property));
        
        // Basic validation - only allow letters, numbers, and hyphens
        if (preg_match('/^[a-z0-9-]+$/', $property)) {
            return $property;
        }

        return null;
    }
}