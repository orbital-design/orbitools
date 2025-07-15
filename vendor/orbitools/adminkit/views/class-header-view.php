<?php

namespace Orbitools\AdminKit\Views;

if (!defined('ABSPATH')) {
    exit;
}

class Header_View
{
    private $admin_kit;

    public function __construct($admin_kit)
    {
        $this->admin_kit = $admin_kit;
    }

    public function render_header()
    {
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, $this->admin_kit->get_slug()) === false) {
            return;
        }

        $header_style = $this->admin_kit->get_page_header_bg_color() 
            ? ' style="background-color: ' . esc_attr($this->admin_kit->get_page_header_bg_color()) . '"' 
            : '';
        ?>
        <div class="adminkit-header"<?php echo $header_style; ?>>
            <div class="adminkit-header__content">
                <?php if ($this->admin_kit->get_page_header_image()) : ?>
                    <div class="adminkit-header__image">
                        <img src="<?php echo esc_url($this->admin_kit->get_page_header_image()); ?>" 
                             alt="<?php echo esc_attr($this->admin_kit->get_page_title()); ?>" 
                             class="adminkit-header__img" />
                    </div>
                <?php endif; ?>

                <div class="<?php echo $this->admin_kit->get_hide_title_description() ? 'adminkit-header__text screen-reader-text' : 'adminkit-header__text'; ?>">
                    <h1 class="adminkit-header__title"><?php echo esc_html($this->admin_kit->get_page_title()); ?></h1>
                    <?php if ($this->admin_kit->get_page_description()) : ?>
                        <p class="adminkit-header__description"><?php echo esc_html($this->admin_kit->get_page_description()); ?></p>
                    <?php endif; ?>
                </div>

                <?php $this->render_tabs(); ?>
            </div>
        </div>

        <div class="adminkit-toolbar">
            <?php $this->render_breadcrumbs(); ?>
        </div>
        <?php
    }

    private function render_tabs()
    {
        $tabs = $this->admin_kit->get_tabs();
        $active_tab = $this->admin_kit->get_active_tab();

        if (empty($tabs)) return;
        ?>
        <div class="orbi-admin__header-tabs">
            <nav class="orbi-admin__tabs-nav">
                <?php foreach ($tabs as $tab_key => $tab_label) : ?>
                    <a href="<?php echo esc_url($this->admin_kit->get_tab_url($tab_key)); ?>"
                       class="orbi-admin__tab-link <?php echo $active_tab === $tab_key ? 'orbi-admin__tab-link--active' : ''; ?>"
                       data-tab="<?php echo esc_attr($tab_key); ?>">
                        <?php echo esc_html($tab_label); ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        </div>
        <?php
    }

    private function render_breadcrumbs()
    {
        $current_tab = $this->admin_kit->get_current_tab();
        $current_section = $this->admin_kit->get_current_section();
        $tabs = $this->admin_kit->get_tabs();

        if (empty($tabs)) return;
        ?>
        <nav class="orbi-admin__breadcrumbs">
            <ol class="orbi-admin__breadcrumb-list">
                <li class="orbi-admin__breadcrumb-item">
                    <span class="orbi-admin__breadcrumb-text"><?php echo esc_html($this->admin_kit->get_page_title()); ?></span>
                </li>
                <?php if ($current_tab && isset($tabs[$current_tab])) : ?>
                    <li class="orbi-admin__breadcrumb-item">
                        <span class="orbi-admin__breadcrumb-separator">›</span>
                        <span class="orbi-admin__breadcrumb-text orbi-admin__breadcrumb-text--current">
                            <?php echo esc_html($tabs[$current_tab]); ?>
                        </span>
                    </li>
                <?php endif; ?>
                <?php if ($current_section) : ?>
                    <?php
                    $structure = $this->admin_kit->get_content_structure();
                    $sections = isset($structure[$current_tab]['sections']) ? $structure[$current_tab]['sections'] : array();
                    if (isset($sections[$current_section])) :
                    ?>
                        <li class="orbi-admin__breadcrumb-item">
                            <span class="orbi-admin__breadcrumb-separator">›</span>
                            <span class="orbi-admin__breadcrumb-text orbi-admin__breadcrumb-text--current">
                                <?php echo esc_html($sections[$current_section]); ?>
                            </span>
                        </li>
                    <?php endif; ?>
                <?php endif; ?>
            </ol>
        </nav>
        <?php
    }
}