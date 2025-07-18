<?php

/**
 * Orbitools - Modules Field Class
 *
 * Plugin-specific field for displaying and managing Orbitools modules.
 * This is registered as a custom field type for this plugin only.
 *
 * @package    Orbitools
 * @subpackage Admin\Fields
 * @since      1.0.0
 */

// Prevent direct access
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Orbitools modules field implementation
 *
 * @since 1.0.0
 */
class Orbitools_Modules_Field extends Orbitools\AdminKit\Field_Base
{

    /**
     * Render the modules field
     *
     * @since 1.0.0
     */
    public function render()
    {
        $modules = $this->get_available_modules();
        $field_id = $this->get_field_id();
        $field_name = $this->get_field_name();

?>
        <div class="orbitools-modules-grid">
            <?php foreach ($modules as $module_id => $module) : ?>
                <?php
                $is_enabled = $this->is_module_enabled($module_id);
                $card_classes = 'orbitools-mod-card';
                if ($is_enabled) {
                    $card_classes .= ' orbitools-mod-card--enabled';
                }
                ?>
                <div class="<?php echo esc_attr($card_classes); ?>">
                    <div class="orbitools-mod-card__header">
                        <div class="orbitools-mod-card__icon">
                            <?php if (!empty($module['icon'])) : ?>
                                <?php echo $module['icon']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                ?>
                            <?php else : ?>
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                                    <path fill="#32a3e2" d="M192 104.8c0-9.2-5.8-17.3-13.2-22.8-11.6-8.7-18.8-20.7-18.8-34 0-26.5 28.7-48 64-48s64 21.5 64 48c0 13.3-7.2 25.3-18.8 34-7.4 5.5-13.2 13.6-13.2 22.8 0 12.8 10.4 23.2 23.2 23.2H336c26.5 0 48 21.5 48 48v56.8c0 12.8 10.4 23.2 23.2 23.2 9.2 0 17.3-5.8 22.8-13.2 8.7-11.6 20.7-18.8 34-18.8 26.5 0 48 28.7 48 64s-21.5 64-48 64c-13.3 0-25.3-7.2-34-18.8-5.5-7.4-13.6-13.2-22.8-13.2-12.8 0-23.2 10.4-23.2 23.2V464c0 26.5-21.5 48-48 48h-56.8c-12.8 0-23.2-10.4-23.2-23.2 0-9.2 5.8-17.3 13.2-22.8 11.6-8.7 18.8-20.7 18.8-34 0-26.5-28.7-48-64-48s-64 21.5-64 48c0 13.3 7.2 25.3 18.8 34 7.4 5.5 13.2 13.6 13.2 22.8 0 12.8-10.4 23.2-23.2 23.2H48c-26.5 0-48-21.5-48-48V343.2C0 330.4 10.4 320 23.2 320c9.2 0 17.3 5.8 22.8 13.2 8.7 11.6 20.7 18.8 34 18.8 26.5 0 48-28.7 48-64s-21.5-64-48-64c-13.3 0-25.3 7.2-34 18.8-5.5 7.4-13.6 13.2-22.8 13.2C10.4 256 0 245.6 0 232.8V176c0-26.5 21.5-48 48-48h120.8c12.8 0 23.2-10.4 23.2-23.2z" />
                                </svg>
                            <?php endif; ?>
                        </div>
                        <div class="orbitools-mod-card__title-area">
                            <h4 class="orbitools-mod-card__title"><?php echo esc_html($module['name']); ?></h4>
                            <?php if (! empty($module['subtitle'])) : ?>
                                <p class="orbitools-mod-card__subtitle"><?php echo esc_html($module['subtitle']); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="orbitools-mod-card__controls">
                            <?php if ($is_enabled && ! empty($module['config_url'])) : ?>
                                <a href="<?php echo esc_url($module['config_url']); ?>" class="orbitools-mod-card__button orbitools-mod-card__button--icon"
                                    title="Configure">
                                    <span class="dashicons dashicons-admin-generic"></span>
                                </a>
                            <?php endif; ?>
                            <label class="orbitools-mod-card__toggle">
                                <input type="checkbox" name="settings[<?php echo esc_attr($module_id . '_enabled'); ?>]" value="1"
                                    <?php checked($is_enabled); ?> class="orbitools-mod-card__toggle__input">
                                <span class="orbitools-mod-card__toggle__slider"></span>
                            </label>
                        </div>
                    </div>

                    <div class="orbitools-mod-card__content">
                        <p class="orbitools-mod-card__description"><?php echo esc_html($module['description']); ?></p>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>
<?php
    }

    /**
     * Get available modules with their metadata
     *
     * @since 1.0.0
     * @return array Available modules
     */
    private function get_available_modules()
    {
        // Start with empty array - modules register themselves via filter
        $modules = array();

        // Allow modules to register their metadata
        $modules = apply_filters('orbitools_available_modules', $modules);

        // Add config URLs to each module (only if not already provided)
        foreach ($modules as $module_id => &$module) {
            if (array_key_exists('configure_url', $module)) {
                // Use the configure_url provided by the module (even if null or empty)
                $module['config_url'] = $module['configure_url'];
            } elseif (empty($module['config_url'])) {
                // Generate default config URL only if no configure_url key is set
                $module['config_url'] = $this->get_module_config_url($module_id);
            }
        }

        return $modules;
    }

    /**
     * Check if a module is enabled
     *
     * @since 1.0.0
     * @param string $module_id Module identifier.
     * @return bool True if enabled, false otherwise.
     */
    private function is_module_enabled($module_id)
    {
        $settings = get_option('orbitools_settings', array());
        $setting_key = $module_id . '_enabled';

        if (isset($settings[$setting_key])) {
            return '1' === $settings[$setting_key] || 1 === $settings[$setting_key];
        }

        return false;
    }

    /**
     * Get module configuration URL
     *
     * @since 1.0.0
     * @param string $module_id Module identifier.
     * @return string Configuration URL.
     */
    private function get_module_config_url($module_id)
    {
        $base_url = admin_url('options-general.php?page=orbitools');

        // Map module IDs to their tab sections
        $module_tabs = array(
            'typography_presets' => 'modules&section=typography',
        );

        if (isset($module_tabs[$module_id])) {
            return $base_url . '#' . $module_tabs[$module_id];
        }

        return $base_url . '#modules';
    }

    /**
     * Sanitize modules field value
     *
     * This field doesn't save data directly - individual module enable/disable
     * states are handled by their respective hidden fields or direct option updates.
     *
     * @since 1.0.0
     * @param mixed $value Value to sanitize.
     * @return string Empty string (this field doesn't save data).
     */
    public function sanitize($value)
    {
        // The modules field doesn't save data itself - individual module toggles are handled
        // by their respective field names (module_management_module_id)
        return '';
    }

    /**
     * Validate modules field value
     *
     * @since 1.0.0
     * @param mixed $value Value to validate.
     * @return bool Always true (modules field values are always valid).
     */
    public function validate($value)
    {
        return true;
    }

    /**
     * Get field assets
     *
     * @since 1.0.0
     * @return array Field assets.
     */
    public function get_assets()
    {
        return array(
            array(
                'type'    => 'css',
                'handle'  => 'orbitools-modules-field',
                'src'     => ORBITOOLS_URL . 'inc/Admin/adminkit/fields/modules/modules-field.css',
                'version' => ORBITOOLS_VERSION,
            ),
        );
    }
}
