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
     * Initialize the Settings class
     *
     * @since 1.0.0
     */
    public static function init(): void
    {
        // Add AJAX handler for saving accordion state
        add_action('wp_ajax_orbitools_save_accordion_state', array(self::class, 'save_accordion_state'));
        
        // Add AJAX handler for clearing cache
        add_action('wp_ajax_orbitools_clear_typography_cache', array(self::class, 'clear_typography_cache'));
    }

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
            'typography_theme_json_path' => 'settings.custom.orbitools',
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
                'id'      => 'typography_presets_preview',
                'name'    => '',
                'desc'    => '',
                'type'    => 'html',
                'std'     => self::get_presets_preview_html(),
                'section' => 'typography',
            ),
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
                'id'      => 'typography_clear_cache',
                'name'    => __('Clear CSS Cache', 'orbitools'),
                'desc'    => __('Clear the cached CSS to force regeneration of typography preset styles.', 'orbitools'),
                'type'    => 'html',
                'std'     => '<button type="button" class="button button-secondary" id="orbitools-clear-typography-cache" data-nonce="' . wp_create_nonce('orbitools_clear_cache') . '">' . __('Clear Cache', 'orbitools') . '</button><div id="orbitools-clear-cache-result" style="margin-top: 10px;"></div>',
                'section' => 'typography',
            ),
            array(
                'id'      => 'typography_theme_json_path',
                'name'    => __('Theme.json Path', 'orbitools'),
                'desc'    => __('Specify the full path to your presets in theme.json. Use dot notation for nested paths.<br><br><strong>Examples:</strong><br>• <code>settings.custom.orbitools</code> → <code>settings.custom.orbitools.Typography_Presets</code><br>• <code>settings.custom.mytheme.components</code> → <code>settings.custom.mytheme.components.Typography_Presets</code><br>• <code>orbital.plugins.orbitools</code> → <code>orbital.plugins.orbitools.Typography_Presets</code><br><br><strong>Note:</strong> Path automatically ends with "Typography_Presets"', 'orbitools'),
                'type'    => 'text',
                'std'     => 'settings.custom.orbitools',
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
     * Get theme.json path array based on user settings
     *
     * @since 1.0.0
     * @return array Path array for navigating theme.json structure.
     */
    public static function get_theme_json_path(): array
    {
        $settings = self::get_current_settings();
        $path_setting = $settings['typography_theme_json_path'] ?? 'settings.custom.orbitools';

        // Parse the path setting and add segments
        $segments = explode('.', trim($path_setting, '.'));
        $path = array();
        foreach ($segments as $segment) {
            if (!empty($segment)) {
                $path[] = $segment;
            }
        }

        // Always end with Typography_Presets
        $path[] = 'Typography_Presets';

        return $path;
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

        // Get user preference for accordion state (default to open)
        $user_id = get_current_user_id();
        $is_expanded = get_user_meta($user_id, 'orbitools_presets_accordion_expanded', true);
        if ($is_expanded === '') {
            $is_expanded = 'true'; // Default to open
        }

        // Try to get preset manager
        try {
            $preset_manager = new \Orbitools\Modules\Typography_Presets\Core\Preset_Manager();
            $presets = $preset_manager->get_presets();
        } catch (Exception $e) {
            return '<p>' . __('Unable to load presets. Please check your theme.json configuration.', 'orbitools') . '</p>';
        }

        $expanded_class = $is_expanded === 'true' ? 'presets-accordion--expanded' : '';
        $expanded_attr = $is_expanded === 'true' ? 'true' : 'false';

        $html = '<div class="presets-accordion ' . $expanded_class . '">';
        $html .= '<button class="presets-accordion__toggle" type="button" aria-expanded="' . $expanded_attr . '" data-toggle="presets-accordion">';
        $html .= '<span class="presets-accordion__icon"></span>';
        $html .= '<span class="presets-accordion__label">' . __('Preview Typography Presets', 'orbitools') . '</span>';
        $html .= '</button>';
        $html .= '<div class="presets-accordion__content">';

        if (empty($presets)) {
            $html .= '<div class="presets-empty">
                <p class="presets-empty__text"><strong>' . __('No presets found.', 'orbitools') . '</strong></p>
                <p class="presets-empty__text">' . __('Add typography presets to your theme.json file to see them here.', 'orbitools') . '</p>
                <p class="presets-empty__text presets-empty__text--last"><a href="https://github.com/orbital-design/orbitools/blob/main/modules/Typography_Presets/README.md" target="_blank">' . __('View Documentation', 'orbitools') . '</a></p>
            </div>';
        } else {
            $html .= '<div class="presets-grid">';
        }

        if (!empty($presets)) {
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

            // Close presets-grid (only if we have presets)
            $html .= '</div>';
        }

        // Close accordion content and container
        $html .= '</div>'; // Close presets-accordion__content
        $html .= '</div>'; // Close presets-accordion

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
                $clean_property = self::clean_property_name($property);
                $clean_value = self::clean_property_value($value);
                $html .= esc_html($clean_property) . ': ' . esc_html($clean_value) . '<br>';
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

        // Load theme CSS variables for font families
        self::enqueue_theme_css_vars();

        wp_enqueue_script(
            'orbitools-typography-presets-admin',
            ORBITOOLS_URL . 'modules/Typography_Presets/js/admin-presets.js',
            array(),
            '1.0.0',
            true
        );
    }

    /**
     * Enqueue theme CSS variables for font families
     *
     * @since 1.0.0
     */
    private static function enqueue_theme_css_vars(): void
    {
        // Get theme.json data
        $theme_json_path = get_template_directory() . '/theme.json';

        if (!file_exists($theme_json_path)) {
            return;
        }

        $theme_json_content = file_get_contents($theme_json_path);
        $theme_json = json_decode($theme_json_content, true);

        if (!$theme_json || JSON_ERROR_NONE !== json_last_error()) {
            return;
        }

        // Extract font family variables
        $font_families = $theme_json['settings']['typography']['fontFamilies'] ?? array();

        if (empty($font_families)) {
            return;
        }

        // Generate CSS variables
        $css_vars = ':root {';

        foreach ($font_families as $font_family) {
            if (isset($font_family['slug']) && isset($font_family['fontFamily'])) {
                $slug = $font_family['slug'];
                $family = $font_family['fontFamily'];
                $css_vars .= "--wp--preset--font-family--{$slug}: {$family};";
            }
        }

        $css_vars .= '}';

        // Add inline CSS
        wp_add_inline_style('orbitools-typography-presets-admin', $css_vars);
    }

    /**
     * Clean property name for display
     *
     * @since 1.0.0
     * @param string $property CSS property name.
     * @return string Cleaned property name.
     */
    private static function clean_property_name(string $property): string
    {
        // Convert camelCase to kebab-case for CSS property format
        $property = strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $property));

        return $property;
    }

    /**
     * Clean property value for display
     *
     * @since 1.0.0
     * @param string $value CSS property value.
     * @return string Cleaned property value.
     */
    private static function clean_property_value(string $value): string
    {
        // Convert CSS variables to readable names
        if (strpos($value, 'var(--wp--preset--font-family--') === 0) {
            // Extract the slug from var(--wp--preset--font-family--slug)
            preg_match('/var\(--wp--preset--font-family--([^)]+)\)/', $value, $matches);
            if (!empty($matches[1])) {
                return ucwords(str_replace('-', ' ', $matches[1]));
            }
        }

        // Convert other CSS variables to readable names
        if (strpos($value, 'var(--wp--preset--') === 0) {
            preg_match('/var\(--wp--preset--[^-]+--([^)]+)\)/', $value, $matches);
            if (!empty($matches[1])) {
                return ucwords(str_replace('-', ' ', $matches[1]));
            }
        }

        return $value;
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

    /**
     * AJAX handler for saving accordion state
     *
     * @since 1.0.0
     */
    public static function save_accordion_state(): void
    {
        // Basic security check
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        $expanded = sanitize_text_field($_POST['expanded'] ?? 'false');
        $user_id = get_current_user_id();

        if ($user_id && in_array($expanded, array('true', 'false'))) {
            update_user_meta($user_id, 'orbitools_presets_accordion_expanded', $expanded);
            wp_send_json_success();
        } else {
            wp_send_json_error();
        }
    }

    /**
     * Clear typography cache via AJAX
     *
     * @since 1.0.0
     */
    public static function clear_typography_cache(): void
    {
        // Basic security check
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'orbitools_clear_cache')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'orbitools')));
            return;
        }

        try {
            // Get the CSS generator instance and clear cache
            $preset_manager = new \Orbitools\Modules\Typography_Presets\Core\Preset_Manager();
            $css_generator = new \Orbitools\Modules\Typography_Presets\Core\CSS_Generator($preset_manager);
            $css_generator->clear_cache();

            wp_send_json_success(array('message' => __('Cache cleared successfully!', 'orbitools')));
        } catch (Exception $e) {
            wp_send_json_error(array('message' => __('Failed to clear cache: ', 'orbitools') . $e->getMessage()));
        }
    }
}