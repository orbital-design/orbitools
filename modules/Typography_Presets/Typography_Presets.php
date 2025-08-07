<?php

/**
 * Typography Presets Module
 *
 * Main coordinator class for the Typography Presets module. This class acts as
 * the primary entry point and orchestrates the various components of the module.
 *
 * @package    Orbitools
 * @subpackage Modules/Typography_Presets
 * @since      1.0.0
 */

namespace Orbitools\Modules\Typography_Presets;

use Orbitools\Abstracts\Module_Base;
use Orbitools\Modules\Typography_Presets\Admin\Admin;
use Orbitools\Modules\Typography_Presets\Admin\Settings;
use Orbitools\Modules\Typography_Presets\Core\Preset_Manager;
use Orbitools\Modules\Typography_Presets\Core\CSS_Generator;
use Orbitools\Modules\Typography_Presets\Frontend\Block_Editor;
use Orbitools\Modules\Typography_Presets\Frontend\Assets;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Typography Presets Module Class
 *
 * Coordinates all aspects of the Typography Presets functionality by managing
 * the interaction between admin, core, and frontend components.
 *
 * @since 1.0.0
 */
class Typography_Presets extends Module_Base
{
    /**
     * Module version
     */
    protected const VERSION = '1.0.0';

    /**
     * Default allowed blocks for typography presets
     *
     * @since 1.0.0
     */
    public const DEFAULT_ALLOWED_BLOCKS = [
        'core/paragraph',
        'core/heading',
        'core/post-title',
        'core/list',
        'core/list-item',
        'core/quote',
        'core/button',
        'core/pullquote'
    ];

    /**
     * Admin handler instance
     *
     * @since 1.0.0
     * @var Admin
     */
    private $admin;

    /**
     * Preset manager instance
     *
     * @since 1.0.0
     * @var Preset_Manager
     */
    private $preset_manager;

    /**
     * Block editor integration instance
     *
     * @since 1.0.0
     * @var Block_Editor
     */
    private $block_editor;

    /**
     * CSS generator instance
     *
     * @since 1.0.0
     * @var CSS_Generator
     */
    private $css_generator;

    /**
     * Assets manager instance
     *
     * @since 1.0.0
     * @var Assets
     */
    private $assets;

    /**
     * Whether the module has been initialized
     *
     * @since 1.0.0
     * @var bool
     */
    private static $initialized = false;

    /**
     * Initialize the Typography Presets module
     *
     * Sets up the module by calling the parent constructor which handles
     * the initialization logic via the Module_Base system.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        // Call parent constructor which handles initialization
        parent::__construct();
    }

    /**
     * Get the module's unique slug
     *
     * @return string
     */
    public function get_slug(): string
    {
        return 'typography-presets';
    }

    /**
     * Get the module's display name
     *
     * @return string
     */
    public function get_name(): string
    {
        return __('Typography Presets', 'orbitools');
    }

    /**
     * Get the module's description
     *
     * @return string
     */
    public function get_description(): string
    {
        return __('Predefined typography styles and custom font management for consistent design.', 'orbitools');
    }

    /**
     * Get module's default settings
     *
     * @return array
     */
    public function get_default_settings(): array
    {
        return [
            'typography-presets_enabled' => true,
            'typography-presets_disable_core_controls' => false,
            'typography-presets_custom_fonts_enabled' => true,
            'typography-presets_google_fonts_enabled' => true,
            'typography-presets_presets' => [],
            'typography-presets_allowed_blocks' => self::DEFAULT_ALLOWED_BLOCKS
        ];
    }

    /**
     * Initialize the module
     * Called by Module_Base when module should be initialized
     *
     * @return void
     */
    public function init(): void
    {
        // Always initialize admin functionality for module registration
        $this->admin = new Admin();

        // Initialize Settings class for AJAX handlers
        Settings::init();

        // Always initialize preset manager and CSS generator for CSS output
        $this->preset_manager = new Preset_Manager();
        $this->css_generator = new CSS_Generator($this->preset_manager);

        // Initialize frontend functionality
        $this->init_frontend_functionality();
    }

    /**
     * Initialize frontend module functionality
     *
     * Sets up frontend integration when the module is enabled.
     *
     * @since 1.0.0
     */
    private function init_frontend_functionality(): void
    {
        // Initialize asset management
        $this->assets = new Assets();

        // Initialize block editor integration
        $this->block_editor = new Block_Editor($this->preset_manager);

        // Set up additional hooks
        $this->setup_hooks();
    }

    /**
     * Set up WordPress hooks
     *
     * @since 1.0.0
     */
    private function setup_hooks(): void
    {
        // Add dynamic block filters for server-rendered blocks
        $this->setup_dynamic_block_filters();
    }

    /**
     * Set up filters for dynamic blocks that are rendered server-side
     *
     * @since 1.0.0
     */
    private function setup_dynamic_block_filters(): void
    {
        // Get all registered block types to find which ones are dynamic
        $registered_blocks = \WP_Block_Type_Registry::get_instance()->get_all_registered();

        foreach ($registered_blocks as $block_name => $block_type) {
            // Check if this block type is supported and is dynamic (has render_callback)
            if (
                $this->is_supported_block($block_name) &&
                ($block_type->render_callback !== null || $block_type->is_dynamic())
            ) {

                // Add specific filter for this dynamic block
                $filter_name = 'render_block_' . $block_name;
                add_filter($filter_name, [$this, 'apply_typography_classes_to_dynamic_block'], 10, 2);
            }
        }
    }

    /**
     * Apply typography preset classes to dynamic blocks only
     *
     * @param string $block_content The block content about to be appended.
     * @param array  $block The full block, including name and attributes.
     * @return string Modified block content.
     * @since 1.0.0
     */
    public function apply_typography_classes_to_dynamic_block($block_content, $block): string
    {
        // Check if block has typography preset attribute
        if (
            !isset($block['attrs']['orbitoolsTypographyPreset']) ||
            empty($block['attrs']['orbitoolsTypographyPreset'])
        ) {
            return $block_content;
        }

        // Block is already validated as supported in setup_dynamic_block_filters()

        // Get the preset class name
        $preset_slug = \sanitize_html_class($block['attrs']['orbitoolsTypographyPreset']);
        $preset_class = 'has-type-preset-' . $preset_slug;

        // Skip if class is already applied (avoid double-processing)
        if (strpos($block_content, $preset_class) !== false) {
            return $block_content;
        }

        // Find the main block wrapper and add the class
        // This handles most block structures
        $block_content = preg_replace_callback(
            '/^(\s*)(<[a-zA-Z0-9]+)(\s+[^>]*class="([^"]*)"[^>]*)(>)/i',
            function ($matches) use ($preset_class) {
                $leading_space = $matches[1];
                $tag_start = $matches[2];
                $attributes = $matches[3];
                $existing_classes = $matches[4];
                $tag_end = $matches[5];

                // Add our preset class to existing classes
                $new_classes = trim($existing_classes . ' ' . $preset_class);
                $new_attributes = str_replace('class="' . $existing_classes . '"', 'class="' . $new_classes . '"', $attributes);

                return $leading_space . $tag_start . $new_attributes . $tag_end;
            },
            $block_content,
            1
        );

        // Fallback: if no class attribute exists, add one
        if (strpos($block_content, $preset_class) === false) {
            $block_content = preg_replace(
                '/^(\s*)(<[a-zA-Z0-9]+)([^>]*)(>)/i',
                '$1$2$3 class="' . $preset_class . '"$4',
                $block_content,
                1
            );
        }

        return $block_content;
    }

    /**
     * Check if block supports typography presets
     *
     * @param string $block_name Block name (e.g., 'core/paragraph')
     * @return bool Whether block supports typography presets
     * @since 1.0.0
     */
    private function is_supported_block($block_name): bool
    {
        // Get allowed blocks from database settings
        $allowed_blocks = $this->settings_manager->get_module_setting(
            'typography-presets',
            'typography_allowed_blocks',
            []
        );

        // If setting is empty (not configured yet), use sensible defaults
        if (empty($allowed_blocks)) {
            $allowed_blocks = self::DEFAULT_ALLOWED_BLOCKS;
        }

        // Allow filtering of supported blocks
        $allowed_blocks = \apply_filters('orbitools_typography_supported_blocks', $allowed_blocks);

        $is_supported = in_array($block_name, $allowed_blocks);

        return $is_supported;
    }

    /**
     * Get the preset manager instance
     *
     * @since 1.0.0
     * @return Preset_Manager|null Preset manager instance or null if not initialized.
     */
    public function get_preset_manager(): ?Preset_Manager
    {
        return $this->preset_manager;
    }

    /**
     * Get the block editor integration instance
     *
     * @since 1.0.0
     * @return Block_Editor|null Block editor instance or null if not initialized.
     */
    public function get_block_editor(): ?Block_Editor
    {
        return $this->block_editor;
    }

    /**
     * Get the admin handler instance
     *
     * @since 1.0.0
     * @return Admin Admin instance.
     */
    public function get_admin(): Admin
    {
        return $this->admin;
    }

    /**
     * Get the CSS generator instance
     *
     * @since 1.0.0
     * @return CSS_Generator|null CSS generator instance or null if not initialized.
     */
    public function get_css_generator(): ?CSS_Generator
    {
        return $this->css_generator;
    }

    /**
     * Get the assets manager instance
     *
     * @since 1.0.0
     * @return Assets|null Assets manager instance or null if not initialized.
     */
    public function get_assets(): ?Assets
    {
        return $this->assets;
    }

    /**
     * Check if the module is fully initialized
     *
     * @since 1.0.0
     * @return bool True if core functionality is loaded, false otherwise.
     */
    public function is_fully_initialized(): bool
    {
        return $this->preset_manager !== null && $this->css_generator !== null;
    }

    /**
     * Clear all preset-related caches
     *
     * @since 1.0.0
     */
    public function clear_preset_cache(): void
    {
        // Use CSS generator's cache clearing if available
        if ($this->css_generator) {
            $this->css_generator->clear_cache();
        } else {
            // Fallback for when CSS generator not initialized
            global $wpdb;
            $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_orbitools_typography_css_%'");
            $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_orbitools_typography_css_%'");
        }

        // Clear WordPress object cache
        \wp_cache_delete('orbitools_typography_presets', 'theme_json');

        // Clear theme.json related transients
        \delete_transient('theme_json_data_user');
        \delete_transient('theme_json_data_theme');
    }
}
