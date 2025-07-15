<?php

/**
 * Content View Class
 *
 * Handles rendering of content components for AdminKit pages including
 * tabs, sections, fields, and form elements.
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
        $content_data = $this->get_content_data();
        
        $this->render_form_wrapper($content_data);
    }

    /**
     * Get content data for rendering
     *
     * @since 1.0.0
     * @return array
     */
    private function get_content_data()
    {
        return array(
            'active_tab' => $this->admin_kit->get_active_tab(),
            'tabs' => $this->admin_kit->get_tabs(),
            'settings' => $this->admin_kit->get_content_fields()
        );
    }

    /**
     * Render form wrapper with all tabs
     *
     * @since 1.0.0
     * @param array $data Content data
     */
    private function render_form_wrapper($data)
    {
        ?>
        <form method="post" action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>" 
              class="orbi-admin__settings-form" id="orbi-settings-form">
            <?php $this->render_form_fields(); ?>
            <?php $this->render_all_tabs($data); ?>
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
        wp_nonce_field('orbitools_adminkit_' . $this->admin_kit->get_slug(), 'orbi_nonce');
        ?>
        <input type="hidden" name="action" value="orbitools_adminkit_save_settings_<?php echo esc_attr($this->admin_kit->get_slug()); ?>">
        <input type="hidden" name="slug" value="<?php echo esc_attr($this->admin_kit->get_slug()); ?>">
        <?php
    }

    /**
     * Render all tab content containers
     *
     * @since 1.0.0
     * @param array $data Content data
     */
    private function render_all_tabs($data)
    {
        foreach ($data['tabs'] as $tab_key => $tab_title) {
            $this->render_single_tab($tab_key, $data);
        }
    }

    /**
     * Render individual tab content
     *
     * @since 1.0.0
     * @param string $tab_key Tab key
     * @param array $data Content data
     */
    private function render_single_tab($tab_key, $data)
    {
        $is_active = $data['active_tab'] === $tab_key;
        ?>
        <div class="orbi-admin__tab-content"
             data-tab="<?php echo esc_attr($tab_key); ?>"
             aria-labelledby="orbi-tab-<?php echo esc_attr($tab_key); ?>"
             style="<?php echo $is_active ? 'display: block;' : 'display: none;'; ?>">
            
            <?php $this->render_tab_sections($tab_key, $data); ?>
            <?php do_action($this->admin_kit->get_func_slug() . '_render_tab_content', $tab_key); ?>
        </div>
        <?php
    }

    /**
     * Render sections for a tab
     *
     * @since 1.0.0
     * @param string $tab_key Tab key
     * @param array $data Content data
     */
    private function render_tab_sections($tab_key, $data)
    {
        $tab_data = $this->get_tab_data($tab_key);
        
        if (empty($tab_data['sections'])) {
            $this->render_tab_without_sections($tab_key, $data['settings']);
            return;
        }

        if ($tab_data['display_mode'] === 'tabs') {
            $this->render_sections_as_tabs($tab_key, $tab_data, $data);
        } else {
            $this->render_sections_as_cards($tab_key, $tab_data, $data);
        }
    }

    /**
     * Get tab-specific data
     *
     * @since 1.0.0
     * @param string $tab_key Tab key
     * @return array
     */
    private function get_tab_data($tab_key)
    {
        return array(
            'sections' => $this->admin_kit->get_sections($tab_key),
            'display_mode' => $this->admin_kit->get_section_display_mode($tab_key),
            'active_section' => $this->admin_kit->get_active_section($tab_key)
        );
    }

    /**
     * Render tab content without sections
     *
     * @since 1.0.0
     * @param string $tab_key Tab key
     * @param array $settings All settings
     */
    private function render_tab_without_sections($tab_key, $settings)
    {
        if (!isset($settings[$tab_key])) {
            return;
        }
        ?>
        <div class="orbi-admin__section-fields">
            <?php $this->render_fields($settings[$tab_key]); ?>
        </div>
        <?php
    }

    /**
     * Render sections as tabs (with sub-navigation)
     *
     * @since 1.0.0
     * @param string $tab_key Tab key
     * @param array $tab_data Tab data
     * @param array $data Content data
     */
    private function render_sections_as_tabs($tab_key, $tab_data, $data)
    {
        ?>
        <div class="orbi-admin__subtabs-wrapper">
            <?php $this->render_subtab_navigation($tab_key, $tab_data); ?>
        </div>
        <?php $this->render_subtab_content($tab_key, $tab_data, $data); ?>
        <?php
    }

    /**
     * Render subtab navigation
     *
     * @since 1.0.0
     * @param string $tab_key Tab key
     * @param array $tab_data Tab data
     */
    private function render_subtab_navigation($tab_key, $tab_data)
    {
        ?>
        <nav class="orbi-admin__subtabs-nav">
            <?php foreach ($tab_data['sections'] as $section_key => $section_title) : ?>
                <?php $this->render_subtab_link($section_key, $section_title, $tab_key, $tab_data['active_section']); ?>
            <?php endforeach; ?>
        </nav>
        <?php
    }

    /**
     * Render individual subtab link
     *
     * @since 1.0.0
     * @param string $section_key Section key
     * @param string $section_title Section title
     * @param string $tab_key Tab key
     * @param string $active_section Active section
     */
    private function render_subtab_link($section_key, $section_title, $tab_key, $active_section)
    {
        $is_active = $active_section === $section_key;
        $link_class = 'orbi-admin__subtab-link';
        if ($is_active) {
            $link_class .= ' orbi-admin__subtab-link--active';
        }
        ?>
        <a href="#"
           class="<?php echo esc_attr($link_class); ?>"
           data-section="<?php echo esc_attr($section_key); ?>"
           role="tab"
           aria-selected="<?php echo $is_active ? 'true' : 'false'; ?>"
           id="orbi-subtab-<?php echo esc_attr($section_key); ?>">
            <?php echo esc_html($section_title); ?>
        </a>
        <?php
    }

    /**
     * Render subtab content sections
     *
     * @since 1.0.0
     * @param string $tab_key Tab key
     * @param array $tab_data Tab data
     * @param array $data Content data
     */
    private function render_subtab_content($tab_key, $tab_data, $data)
    {
        foreach ($tab_data['sections'] as $section_key => $section_title) {
            $this->render_subtab_section($section_key, $section_title, $tab_key, $tab_data['active_section'], $data['settings']);
        }
    }

    /**
     * Render individual subtab section
     *
     * @since 1.0.0
     * @param string $section_key Section key
     * @param string $section_title Section title
     * @param string $tab_key Tab key
     * @param string $active_section Active section
     * @param array $settings All settings
     */
    private function render_subtab_section($section_key, $section_title, $tab_key, $active_section, $settings)
    {
        $is_active = $active_section === $section_key;
        ?>
        <div class="orbi-admin__section-content"
             data-section="<?php echo esc_attr($section_key); ?>"
             aria-labelledby="orbi-subtab-<?php echo esc_attr($section_key); ?>"
             style="<?php echo $is_active ? 'display: block;' : 'display: none;'; ?>">
            <?php $this->render_section_fields($tab_key, $section_key, $section_title, $settings); ?>
        </div>
        <?php
    }

    /**
     * Render sections as cards (stacked layout)
     *
     * @since 1.0.0
     * @param string $tab_key Tab key
     * @param array $tab_data Tab data
     * @param array $data Content data
     */
    private function render_sections_as_cards($tab_key, $tab_data, $data)
    {
        foreach ($tab_data['sections'] as $section_key => $section_title) {
            $this->render_section_card($tab_key, $section_key, $section_title, $data['settings']);
        }
    }

    /**
     * Render individual section card
     *
     * @since 1.0.0
     * @param string $tab_key Tab key
     * @param string $section_key Section key
     * @param string $section_title Section title
     * @param array $settings All settings
     */
    private function render_section_card($tab_key, $section_key, $section_title, $settings)
    {
        ?>
        <div class="orbi-admin__section-card" data-section="<?php echo esc_attr($section_key); ?>">
            <h3 class="orbi-admin__section-title"><?php echo esc_html($section_title); ?></h3>
            <?php $this->render_section_fields($tab_key, $section_key, $section_title, $settings); ?>
        </div>
        <?php
    }

    /**
     * Render fields for a section
     *
     * @since 1.0.0
     * @param string $tab_key Tab key
     * @param string $section_key Section key
     * @param string $section_title Section title
     * @param array $settings All settings
     */
    private function render_section_fields($tab_key, $section_key, $section_title, $settings)
    {
        if (!isset($settings[$tab_key])) {
            $this->render_no_fields_message($section_title);
            return;
        }

        $section_fields = $this->get_section_fields($settings[$tab_key], $section_key);
        
        if (empty($section_fields)) {
            $this->render_no_fields_message($section_title);
            return;
        }
        ?>
        <div class="orbi-admin__section-fields">
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
     * Render fields for a specific tab (legacy method - kept for compatibility)
     *
     * @since 1.0.0
     * @param array  $fields Tab fields array
     * @param string $tab_key Current tab key
     */
    public function render_tab_fields($fields, $tab_key)
    {
        $sections = $this->admin_kit->get_sections($tab_key);

        foreach ($sections as $section_key => $section_title) {
            $section_fields = $this->get_section_fields($fields, $section_key);

            if (empty($section_fields)) {
                continue;
            }
            ?>
            <div class="orbital-section" data-section="<?php echo esc_attr($section_key); ?>">
                <h3 class="orbital-section-title"><?php echo esc_html($section_title); ?></h3>
                <div class="orbital-section-fields">
                    <?php $this->render_fields($section_fields); ?>
                </div>
            </div>
            <?php
        }
    }

    /**
     * Get section fields
     *
     * @since 1.0.0
     * @param array  $fields All fields
     * @param string $section_key Section key
     * @return array Section fields
     */
    private function get_section_fields($fields, $section_key)
    {
        return array_filter($fields, function ($field) use ($section_key) {
            return isset($field['section']) && $field['section'] === $section_key;
        });
    }

    /**
     * Get fields without sections (for tabs that don't use sub-tabs)
     *
     * @since 1.0.0
     * @param array $fields All fields
     * @return array Fields without sections
     */
    private function get_fields_without_sections($fields)
    {
        return array_filter($fields, function ($field) {
            return !isset($field['section']) || empty($field['section']);
        });
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
                <?php if ($section_title) : ?>
                    No fields have been added to the "<?php echo esc_html($section_title); ?>" section yet.
                <?php else : ?>
                    No fields have been configured for this section yet.
                <?php endif; ?>
            </p>
            <p class="orbi-admin__no-fields-help">
                <strong>For developers:</strong> Add fields using the <code><?php echo esc_html($this->admin_kit->get_func_slug()); ?>_adminkit_fields</code> filter.
                <?php if ($section_title) : ?>
                    Make sure to set <code>'section' => '<?php echo esc_attr(strtolower(str_replace(' ', '_', $section_title))); ?>'</code> on your field definitions.
                <?php endif; ?>
            </p>
        </div>
        <?php
    }
}