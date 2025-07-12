<?php
/**
 * Shared Admin Components
 *
 * Provides reusable UI components for consistent admin interface design
 * across all plugin admin pages.
 *
 * @package    Orbital_Editor_Suite
 * @subpackage Orbital_Editor_Suite/includes/admin
 */

namespace Orbital\Editor_Suite\Admin;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin Components Class
 *
 * Contains methods for rendering shared UI components like headers,
 * tabs, and common layout elements used across admin pages.
 */
class Admin_Components {

    /**
     * Render the standard admin header
     *
     * @param array $config Header configuration options
     *                     - title: Page title (default: plugin name)
     *                     - icon: Dashicon class (default: dashicons-admin-customizer)
     *                     - show_version: Show version badge (default: true)
     *                     - actions: Array of action buttons (default: empty)
     */
    public static function render_header($config = array()) {
        $config = wp_parse_args($config, array(
            'title' => 'Orbital Editor Suite',
            'icon' => 'dashicons-admin-customizer',
            'show_version' => true,
            'actions' => array()
        ));

        ?>
        <div class="orbital-header">
            <div class="header-content">
                <div class="header-title">
                    <h1>
                        <span class="dashicons <?php echo esc_attr($config['icon']); ?>"></span>
                        <?php echo esc_html($config['title']); ?>
                    </h1>
                    <?php if ($config['show_version']): ?>
                        <span class="version-badge">v{{ pluginInfo.version }}</span>
                    <?php endif; ?>
                </div>
                <?php if (!empty($config['actions'])): ?>
                    <div class="header-actions">
                        <?php foreach ($config['actions'] as $action): ?>
                            <button 
                                <?php if (isset($action['click'])): ?>@click="<?php echo esc_attr($action['click']); ?>"<?php endif; ?>
                                <?php if (isset($action['disabled'])): ?>:disabled="<?php echo esc_attr($action['disabled']); ?>"<?php endif; ?>
                                class="button <?php echo esc_attr($action['class'] ?? 'button-secondary'); ?>"
                            >
                                <?php if (isset($action['dynamic_text'])): ?>
                                    {{ <?php echo esc_attr($action['dynamic_text']); ?> }}
                                <?php else: ?>
                                    <?php echo esc_html($action['text']); ?>
                                <?php endif; ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render admin notices container
     */
    public static function render_notices_container() {
        ?>
        <div class="orbital-notices-container">
            <!-- WordPress admin notices will be moved here -->
            <div v-if="message" :class="['orbital-message', messageType]">
                {{ message }}
            </div>
        </div>
        <?php
    }

    /**
     * Render the standard tab navigation
     *
     * @param array $tabs Array of tab configurations
     *                   Each tab should have: id, title, icon
     */
    public static function render_tabs($tabs) {
        ?>
        <div class="orbital-tabs">
            <?php foreach ($tabs as $tab): ?>
                <button 
                    @click="activeTab = '<?php echo esc_attr($tab['id']); ?>'"
                    :class="['orbital-tab', { active: activeTab === '<?php echo esc_attr($tab['id']); ?>' }]"
                >
                    <span class="dashicons <?php echo esc_attr($tab['icon']); ?>"></span>
                    <?php echo esc_html($tab['title']); ?>
                </button>
            <?php endforeach; ?>
        </div>
        <?php
    }

    /**
     * Render tab content wrapper start
     */
    public static function render_tab_content_start() {
        ?>
        <div class="orbital-tab-content">
        <?php
    }

    /**
     * Render tab content wrapper end
     */
    public static function render_tab_content_end() {
        ?>
        </div>
        <?php
    }

    /**
     * Render a single tab panel start
     *
     * @param string $tab_id The tab identifier
     * @param string $title Tab panel title
     * @param string $description Optional description text
     */
    public static function render_tab_panel_start($tab_id, $title, $description = '') {
        ?>
        <div v-if="activeTab === '<?php echo esc_attr($tab_id); ?>'" class="orbital-section">
            <h2><?php echo esc_html($title); ?></h2>
            <?php if ($description): ?>
                <p><?php echo esc_html($description); ?></p>
            <?php endif; ?>
        <?php
    }

    /**
     * Render a single tab panel end
     */
    public static function render_tab_panel_end() {
        ?>
        </div>
        <?php
    }

    /**
     * Get standard CSS classes for admin components
     */
    public static function get_css_classes() {
        return array(
            'container' => 'orbital-admin-container',
            'header' => 'orbital-header',
            'notices' => 'orbital-notices-container',
            'tabs' => 'orbital-tabs',
            'tab_content' => 'orbital-tab-content',
            'section' => 'orbital-section',
            'message' => 'orbital-message'
        );
    }
}