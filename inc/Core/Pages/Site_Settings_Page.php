<?php

namespace Orbitools\Core\Pages;

/**
 * Built-in Site Settings page.
 *
 * Registers itself via the same public `orbitools/register_theme_pages`
 * filter themes use — dogfooding the API so the contract stays
 * honest. Hooks in at priority 5 so themes that register pages of
 * their own at the default priority can override or augment it.
 *
 * Sections (in order):
 *   - site      Title + tagline (bound to blogname / blogdescription)
 *   - header    Logo (site_logo) + show-search toggle
 *   - footer    Address (textarea) + copyright statement
 *   - socials   Share Links repeater + Social Links repeater
 *   - defaults  Default thumbnail + 404 page picker
 *
 * Themes read values from get_option('orbitools_settings') keyed
 * by `{slug}_{field_id}`, or from the bound WP option directly
 * (e.g. get_option('blogname')) where `wp_option` is declared.
 *
 * @package Orbitools
 * @since 3.1.0
 */
final class Site_Settings_Page
{
    public const SLUG = 'site-settings';

    public function __construct()
    {
        \add_filter('orbitools/register_theme_pages', [$this, 'register'], 5);
    }

    /**
     * @param array<string,array<string,mixed>> $pages
     * @return array<string,array<string,mixed>>
     */
    public function register(array $pages): array
    {
        // Yield to themes that have already registered a `site-settings`
        // page of their own — they win.
        if (isset($pages[self::SLUG])) {
            return $pages;
        }

        $pages[self::SLUG] = [
            'slug'        => self::SLUG,
            'label'       => \__('Site Settings', 'orbitools'),
            'description' => \__('Site-wide settings the theme can read from one place.', 'orbitools'),
            'icon'        => 'site',
            'position'    => 10,
            'sections'    => $this->sections(),
            'fields'      => $this->fields(),
        ];

        return $pages;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function sections(): array
    {
        return [
            [
                'id'          => 'site',
                'title'       => \__('Site', 'orbitools'),
                'description' => \__('Identity used across the site.', 'orbitools'),
            ],
            [
                'id'          => 'header',
                'title'       => \__('Header', 'orbitools'),
                'description' => \__('Branding and chrome shown in the site header.', 'orbitools'),
            ],
            [
                'id'          => 'footer',
                'title'       => \__('Footer', 'orbitools'),
                'description' => \__('Address and copyright shown in the footer.', 'orbitools'),
            ],
            [
                'id'          => 'socials',
                'title'       => \__('Socials', 'orbitools'),
                'description' => \__('Social profile links and share-this-page targets.', 'orbitools'),
            ],
            [
                'id'          => 'defaults',
                'title'       => \__('Defaults', 'orbitools'),
                'description' => \__('Fallback content used when individual posts/pages don\'t supply it.', 'orbitools'),
            ],
        ];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function fields(): array
    {
        return [
            // ---- Site ----
            [
                'id'          => 'site_title',
                'type'        => 'text',
                'label'       => \__('Site Title', 'orbitools'),
                'description' => \__('Bound to WordPress\'s built-in site title (blogname).', 'orbitools'),
                'default'     => '',
                'section'     => 'site',
                'wp_option'   => 'blogname',
            ],
            [
                'id'          => 'tagline',
                'type'        => 'text',
                'label'       => \__('Tagline', 'orbitools'),
                'description' => \__('Short site description (blogdescription).', 'orbitools'),
                'default'     => '',
                'section'     => 'site',
                'wp_option'   => 'blogdescription',
            ],

            // ---- Header ----
            [
                'id'          => 'logo',
                'type'        => 'media',
                'label'       => \__('Logo', 'orbitools'),
                'description' => \__('Bound to the core site_logo option (WP 5.8+).', 'orbitools'),
                'default'     => 0,
                'section'     => 'header',
                'wp_option'   => 'site_logo',
            ],
            [
                'id'          => 'show_search',
                'type'        => 'toggle',
                'label'       => \__('Show search', 'orbitools'),
                'description' => \__('Toggle the site-search affordance in the header. The theme decides where to render it.', 'orbitools'),
                'default'     => false,
                'section'     => 'header',
            ],

            // ---- Footer ----
            [
                'id'          => 'address',
                'type'        => 'textarea',
                'label'       => \__('Address', 'orbitools'),
                'description' => \__('Postal address shown in the footer. Newlines are preserved.', 'orbitools'),
                'default'     => '',
                'section'     => 'footer',
                'rows'        => 4,
            ],
            [
                'id'          => 'copyright',
                'type'        => 'text',
                'label'       => \__('Copyright statement', 'orbitools'),
                'description' => \__('Available placeholders: %copy% = ©, %year% = current year, %sitename% = site title.', 'orbitools'),
                'default'     => '%copy% %year% %sitename%',
                'section'     => 'footer',
            ],

            // ---- Socials ----
            [
                'id'                => 'share_links',
                'type'              => 'repeater',
                'label'             => \__('Share Links', 'orbitools'),
                'description'       => \__('Networks shown on posts/pages for sharing the current page. Override labels per row if needed.', 'orbitools'),
                'default'           => $this->default_share_links(),
                'section'           => 'socials',
                'add_button_label'  => \__('Add share target', 'orbitools'),
                'row_label_field'   => 'network',
                'sub_fields'        => $this->share_link_subfields(),
            ],
            [
                'id'                => 'social_links',
                'type'              => 'repeater',
                'label'             => \__('Social Links', 'orbitools'),
                'description'       => \__('Profile links shown in the social block — e.g. footer or contact page.', 'orbitools'),
                'default'           => [],
                'section'           => 'socials',
                'add_button_label'  => \__('Add social profile', 'orbitools'),
                'row_label_field'   => 'network',
                'sub_fields'        => $this->social_link_subfields(),
            ],

            // ---- Defaults ----
            [
                'id'          => 'default_thumbnail',
                'type'        => 'media',
                'label'       => \__('Default thumbnail', 'orbitools'),
                'description' => \__('Used as a fallback when a post has no featured image set.', 'orbitools'),
                'default'     => 0,
                'section'     => 'defaults',
            ],
            [
                'id'          => 'page_404',
                'type'        => 'page',
                'label'       => \__('404 page', 'orbitools'),
                'description' => \__('Pick a page whose content should populate the 404 screen.', 'orbitools'),
                'default'     => 0,
                'section'     => 'defaults',
            ],
        ];
    }

    /**
     * Curated network list used by both repeaters. The icon mapping
     * is owned by the theme — Orbitools only stores the handle.
     *
     * @return array<int,array<string,string>>
     */
    private function network_options(): array
    {
        return [
            ['value' => 'facebook',  'label' => \__('Facebook', 'orbitools')],
            ['value' => 'twitter',   'label' => \__('Twitter / X', 'orbitools')],
            ['value' => 'linkedin',  'label' => \__('LinkedIn', 'orbitools')],
            ['value' => 'instagram', 'label' => \__('Instagram', 'orbitools')],
            ['value' => 'youtube',   'label' => \__('YouTube', 'orbitools')],
            ['value' => 'tiktok',    'label' => \__('TikTok', 'orbitools')],
            ['value' => 'pinterest', 'label' => \__('Pinterest', 'orbitools')],
            ['value' => 'mastodon',  'label' => \__('Mastodon', 'orbitools')],
            ['value' => 'threads',   'label' => \__('Threads', 'orbitools')],
            ['value' => 'whatsapp',  'label' => \__('WhatsApp', 'orbitools')],
            ['value' => 'email',     'label' => \__('Email', 'orbitools')],
            ['value' => 'copy-link', 'label' => \__('Copy link', 'orbitools')],
        ];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function share_link_subfields(): array
    {
        return [
            [
                'id'      => 'network',
                'type'    => 'select',
                'label'   => \__('Network', 'orbitools'),
                'default' => 'facebook',
                'options' => $this->network_options(),
            ],
            [
                'id'          => 'label',
                'type'        => 'text',
                'label'       => \__('Label override', 'orbitools'),
                'description' => \__('Optional. Leave blank to use the network\'s default label.', 'orbitools'),
                'default'     => '',
            ],
            [
                'id'      => 'enabled',
                'type'    => 'toggle',
                'label'   => \__('Enabled', 'orbitools'),
                'default' => true,
            ],
        ];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function social_link_subfields(): array
    {
        return [
            [
                'id'      => 'network',
                'type'    => 'select',
                'label'   => \__('Network', 'orbitools'),
                'default' => 'facebook',
                'options' => $this->network_options(),
            ],
            [
                'id'          => 'url',
                'type'        => 'text',
                'label'       => \__('Profile URL', 'orbitools'),
                'description' => \__('Full URL to the profile on this network.', 'orbitools'),
                'default'     => '',
                'placeholder' => 'https://',
            ],
            [
                'id'          => 'label',
                'type'        => 'text',
                'label'       => \__('Link title', 'orbitools'),
                'description' => \__('Used for aria-label / title attributes. e.g. "Connect with us on LinkedIn".', 'orbitools'),
                'default'     => '',
            ],
        ];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function default_share_links(): array
    {
        $defaults = ['facebook', 'twitter', 'linkedin', 'whatsapp', 'email', 'copy-link'];
        $rows = [];
        foreach ($defaults as $network) {
            $rows[] = [
                'network' => $network,
                'label'   => '',
                'enabled' => true,
            ];
        }
        return $rows;
    }
}
