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

namespace Orbitools\Controls\Typography_Presets\Admin;

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
            'typography_allowed_blocks' => \Orbitools\Controls\Typography_Presets\Typography_Presets::DEFAULT_ALLOWED_BLOCKS,
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
                'id'      => 'typography_allowed_blocks',
                'name'    => __('Allowed Blocks', 'orbitools'),
                'desc'    => __('Select which block types can use typography presets.', 'orbitools'),
                'type'    => 'checkbox',
                'options' => array(
                    'core/paragraph' => __('Paragraph', 'orbitools'),
                    'core/heading' => __('Heading', 'orbitools'),
                    'core/details' => __('Details', 'orbitools'),
                    'core/post-title' => __('Title', 'orbitools'),
                    'core/list' => __('List', 'orbitools'),
                    'core/list-item' => __('List Item', 'orbitools'),
                    'core/quote' => __('Quote', 'orbitools'),
                    'core/button' => __('Button', 'orbitools'),
                    'core/group' => __('Group', 'orbitools'),
                    'core/column' => __('Column', 'orbitools'),
                    'core/cover' => __('Cover', 'orbitools'),
                    'core/pullquote' => __('Pullquote', 'orbitools')
                ),
                'std'     => \Orbitools\Controls\Typography_Presets\Typography_Presets::DEFAULT_ALLOWED_BLOCKS,
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
                'typography' => array(
                    'title' => __('Typography Presets', 'orbitools'),
                    'icon' => array(
                        'type' => 'svg',
                        'value' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><path fill="#32a3e2" d="M64 128V96h96v320h-32c-17.7 0-32 14.3-32 32s14.3 32 32 32h128c17.7 0 32-14.3 32-32s-14.3-32-32-32h-32V96h96v32c0 17.7 14.3 32 32 32s32-14.3 32-32V80c0-26.5-21.5-48-48-48H48C21.5 32 0 53.5 0 80v48c0 17.7 14.3 32 32 32s32-14.3 32-32zm320 176v-16h64v128h-16c-17.7 0-32 14.3-32 32s14.3 32 32 32h96c17.7 0 32-14.3 32-32s-14.3-32-32-32h-16V288h64v16c0 17.7 14.3 32 32 32s32-14.3 32-32v-32c0-26.5-21.5-48-48-48H368c-26.5 0-48 21.5-48 48v32c0 17.7 14.3 32 32 32s32-14.3 32-32z"/></svg>'
                    )
                ),
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

        // Get user preference for accordion state (default to open)
        $user_id = get_current_user_id();
        $is_expanded = get_user_meta($user_id, 'orbitools_presets_accordion_expanded', true);
        if ($is_expanded === '') {
            $is_expanded = 'true'; // Default to open
        }

        // Try to get preset manager
        try {
            $preset_manager = new \Orbitools\Controls\Typography_Presets\Core\Preset_Manager();
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
                <p class="presets-empty__text">' . __('Add typography presets to your config/orbitools.json file to see them here.', 'orbitools') . '</p>
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
        $html .= '<div class="preset-card__sample ' . esc_attr($class_name) . '">';
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
            ORBITOOLS_URL . 'build/admin/css/controls/typography-presets/admin.css',
            array('dashicons'),
            '1.0.0'
        );

        // Load theme CSS variables for font families
        self::enqueue_theme_css_vars();

        wp_enqueue_script(
            'orbitools-typography-presets-admin',
            ORBITOOLS_URL . 'build/admin/js/controls/typography-presets/admin-handle-module-dashboard.js',
            array(),
            '1.0.0',
            true
        );

        // Localize script with nonce for AJAX requests
        wp_localize_script(
            'orbitools-typography-presets-admin',
            'orbitoolsAjax',
            array(
                'nonce' => wp_create_nonce('orbitools_ajax_nonce'),
                'url'   => admin_url('admin-ajax.php')
            )
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
        $security_logger = \Orbitools\Core\Helpers\Security_Logger::instance();

        // Basic security check
        if (!current_user_can('manage_options')) {
            $security_logger->log_event('PERMISSION_DENIED', array(
                'action' => 'save_accordion_state',
                'required_capability' => 'manage_options'
            ), 'high');
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'orbitools_ajax_nonce')) {
            $security_logger->log_nonce_failure('save_accordion_state', 'orbitools_ajax_nonce', $_POST['nonce'] ?? '');
            wp_send_json_error(array('message' => __('Security check failed.', 'orbitools')));
            return;
        }

        $expanded = sanitize_text_field($_POST['expanded'] ?? 'false');
        $user_id = get_current_user_id();

        if ($user_id && in_array($expanded, array('true', 'false'))) {
            update_user_meta($user_id, 'orbitools_presets_accordion_expanded', $expanded);
            $security_logger->log_event('SETTINGS_CHANGED', array(
                'action' => 'accordion_state_changed',
                'expanded' => $expanded
            ), 'low');
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
        $security_logger = \Orbitools\Core\Helpers\Security_Logger::instance();

        // Basic security check
        if (!current_user_can('manage_options')) {
            $security_logger->log_event('PERMISSION_DENIED', array(
                'action' => 'clear_typography_cache',
                'required_capability' => 'manage_options'
            ), 'high');
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'orbitools_ajax_nonce')) {
            $security_logger->log_nonce_failure('clear_typography_cache', 'orbitools_ajax_nonce', $_POST['nonce'] ?? '');
            wp_send_json_error(array('message' => __('Security check failed.', 'orbitools')));
            return;
        }

        try {
            // Get the CSS generator instance and clear cache
            $preset_manager = new \Orbitools\Controls\Typography_Presets\Core\Preset_Manager();
            $css_generator = new \Orbitools\Controls\Typography_Presets\Core\CSS_Generator($preset_manager);
            $css_generator->clear_cache();

            $security_logger->log_event('CACHE_CLEARED', array(
                'cache_type' => 'typography_css'
            ), 'medium');

            wp_send_json_success(array('message' => __('Cache cleared successfully!', 'orbitools')));
        } catch (Exception $e) {
            $security_logger->log_event('CACHE_CLEARED', array(
                'cache_type' => 'typography_css',
                'error' => $e->getMessage(),
                'status' => 'failed'
            ), 'medium');
            wp_send_json_error(array('message' => __('Failed to clear cache: ', 'orbitools') . $e->getMessage()));
        }
    }
}
