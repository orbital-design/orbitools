<?php

/**
 * Content View Class (Simplified)
 *
 * Handles rendering of content components for AdminKit pages.
 * Clean, focused implementation with minimal complexity.
 *
 * @package    Orbitools\AdminKit\Views
 * @since      1.0.0
 */

namespace Orbitools\AdminKit\Views;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Content View Class
 *
 * @since 1.0.0
 */
class Content_View
{
    /**
     * AdminKit instance
     *
     * @since 1.0.0
     * @var \Orbitools\AdminKit\Admin_Kit
     */
    private $admin_kit;

    /**
     * Constructor
     *
     * @since 1.0.0
     * @param \Orbitools\AdminKit\Admin_Kit $admin_kit AdminKit instance
     */
    public function __construct($admin_kit)
    {
        $this->admin_kit = $admin_kit;
    }

    /**
     * Render complete tab content with form wrapper
     *
     * @since 1.0.0
     */
    public function render_tab_content()
    {
        $active_tab = $this->admin_kit->get_active_tab();
        $tabs = $this->admin_kit->get_tabs();
        $settings = $this->admin_kit->get_content_fields();

?>
        <form method="post" action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>"
            class="adminkit__form adminkit__form--settings" id="adminkit-settings-form">
            <?php $this->render_form_fields(); ?>
            <?php $this->render_tabs($tabs, $active_tab, $settings); ?>
            <?php submit_button('Save Settings'); ?>
        </form>
    <?php
    }

    /**
     * Render hidden form fields
     *
     * @since 1.0.0
     */
    private function render_form_fields()
    {
        wp_nonce_field('orbitools_adminkit_' . $this->admin_kit->get_slug(), 'adminkit_nonce');
    ?>
        <input type="hidden" name="action"
            value="orbitools_adminkit_save_settings_<?php echo esc_attr($this->admin_kit->get_slug()); ?>">
        <input type="hidden" name="slug" value="<?php echo esc_attr($this->admin_kit->get_slug()); ?>">
        <?php
    }

    /**
     * Render all tab content containers
     *
     * @since 1.0.0
     * @param array $tabs All tabs
     * @param string $active_tab Active tab key
     * @param array $settings All settings
     */
    private function render_tabs($tabs, $active_tab, $settings)
    {
        foreach ($tabs as $tab_key => $tab_title) {
            $is_active = $active_tab === $tab_key;
        ?>
            <div class="adminkit-content__page" data-page="<?php echo esc_attr($tab_key); ?>"
                aria-labelledby="<?php echo esc_attr('adminkit-nav-' . $tab_key); ?>"
                style="<?php echo $is_active ? 'display: block;' : 'display: none;'; ?>">

                <?php $this->render_tab_content_sections($tab_key, $settings); ?>
                <?php do_action($this->admin_kit->get_func_slug() . '_render_tab_content', $tab_key); ?>
            </div>
        <?php
        }
    }

    /**
     * Render sections for a tab
     *
     * @since 1.0.0
     * @param string $tab_key Tab key
     * @param array $settings All settings
     */
    private function render_tab_content_sections($tab_key, $settings)
    {
        $sections = $this->admin_kit->get_sections($tab_key);

        if (empty($sections)) {
            $this->render_tab_fields($tab_key, $settings);
            return;
        }

        $display_mode = $this->admin_kit->get_section_display_mode($tab_key);
        $active_section = $this->admin_kit->get_active_section($tab_key);

        if ($display_mode === 'tabs') {
            $this->render_sections_with_navigation($tab_key, $sections, $active_section, $settings);
        } else {
            $this->render_sections_as_cards($tab_key, $sections, $settings);
        }
    }

    /**
     * Render tab content without sections
     *
     * @since 1.0.0
     * @param string $tab_key Tab key
     * @param array $settings All settings
     */
    private function render_tab_fields($tab_key, $settings)
    {
        if (!isset($settings[$tab_key])) {
            return;
        }
        ?>
        <div class="adminkit-content__fields">
            <?php $this->render_fields($settings[$tab_key]); ?>
        </div>
    <?php
    }

    /**
     * Render sections with navigation (tabs mode)
     *
     * @since 1.0.0
     * @param string $tab_key Tab key
     * @param array $sections Sections
     * @param string $active_section Active section
     * @param array $settings All settings
     */
    private function render_sections_with_navigation($tab_key, $sections, $active_section, $settings)
    {
    ?>
        <nav class="adminkit-content__sub-tabs">
            <?php foreach ($sections as $section_key => $section_title): ?>
                <?php
                $is_active = $active_section === $section_key;
                $classes = array('adminkit-content__sub-link');
                if ($is_active) $classes[] = 'adminkit-content__sub-link--active';
                ?>
                <a href="#" class="<?php echo esc_attr(implode(' ', $classes)); ?>"
                    data-section="<?php echo esc_attr($section_key); ?>" role="tab"
                    aria-selected="<?php echo $is_active ? 'true' : 'false'; ?>"
                    id="adminkit-subtab-<?php echo esc_attr($section_key); ?>">
                    <?php echo esc_html($section_title); ?>
                </a>
            <?php endforeach; ?>
        </nav>


        <?php foreach ($sections as $section_key => $section_title): ?>
            <?php $is_active = $active_section === $section_key; ?>
            <div class="adminkit-content__sub-content" data-section="<?php echo esc_attr($section_key); ?>"
                aria-labelledby="adminkit-subtab-<?php echo esc_attr($section_key); ?>"
                style="<?php echo $is_active ? 'display: block;' : 'display: none;'; ?>">
                <?php $this->render_section_content($tab_key, $section_key, $section_title, $settings); ?>
            </div>
        <?php endforeach; ?>
        <?php
    }

    /**
     * Render sections as cards (stacked layout)
     *
     * @since 1.0.0
     * @param string $tab_key Tab key
     * @param array $sections Sections
     * @param array $settings All settings
     */
    private function render_sections_as_cards($tab_key, $sections, $settings)
    {
        foreach ($sections as $section_key => $section_title) {
        ?>
            <div class="adminkit-content__section" data-section="<?php echo esc_attr($section_key); ?>">
                <h3 class="adminkit-content__section-title"><?php echo esc_html($section_title); ?></h3>
                <?php $this->render_section_content($tab_key, $section_key, $section_title, $settings); ?>
            </div>
        <?php
        }
    }

    /**
     * Render content for a section
     *
     * @since 1.0.0
     * @param string $tab_key Tab key
     * @param string $section_key Section key
     * @param string $section_title Section title
     * @param array $settings All settings
     */
    private function render_section_content($tab_key, $section_key, $section_title, $settings)
    {
        if (!isset($settings[$tab_key])) {
            $this->render_no_fields_message($section_title);
            return;
        }

        $section_fields = array_filter($settings[$tab_key], function ($field) use ($section_key) {
            return isset($field['section']) && $field['section'] === $section_key;
        });

        if (empty($section_fields)) {
            $this->render_no_fields_message($section_title);
            return;
        }
        ?>
        <div class="adminkit-content__fields">
            <?php $this->render_fields($section_fields); ?>
        </div>
    <?php
    }

    /**
     * Render array of fields
     *
     * @since 1.0.0
     * @param array $fields Fields to render
     */
    private function render_fields($fields)
    {
        foreach ($fields as $field) {
            $this->admin_kit->render_field($field);
        }
    }


    /**
     * Render "no fields" message
     *
     * @since 1.0.0
     * @param string $section_title Section title for context
     */
    private function render_no_fields_message($section_title = '')
    {
    ?>
        <div class="orbi-admin__no-fields-message">
            <div class="orbi-admin__no-fields-icon">
                <span class="dashicons dashicons-admin-settings"></span>
            </div>
            <h4>No fields configured</h4>
            <p>
                <?php if ($section_title): ?>
                    No fields have been added to the "<?php echo esc_html($section_title); ?>" section yet.
                <?php else: ?>
                    No fields have been configured for this section yet.
                <?php endif; ?>
            </p>
            <p class="orbi-admin__no-fields-help">
                <strong>For developers:</strong> Add fields using the
                <code><?php echo esc_html($this->admin_kit->get_func_slug()); ?>_adminkit_fields</code> filter.
                <?php if ($section_title): ?>
                    Make sure to set
                    <code>'section' => '<?php echo esc_attr(strtolower(str_replace(' ', '_', $section_title))); ?>'</code> on your
                    field definitions.
                <?php endif; ?>
            </p>
        </div>
<?php
    }
}
