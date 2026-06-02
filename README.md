# Orbitools

A WordPress plugin that ships a React-based admin shell, a manifest-driven module system, and an extensible theme-pages API — plus the Gutenberg blocks, editor-side controls, and site-wide modules our themes use.

This README is the high-level orientation. **[`CLAUDE.md`](CLAUDE.md) is the working contract for anyone (human or otherwise) touching the codebase** — read that before editing.

## What's in here

### React admin (`?page=orbitools`)

- Full-width chrome with a top-level tab nav: **Dashboard** + per-category settings pages (Block / Control / Module Settings) + theme-registered pages
- Two-column drill-downs for each category — vertical list of items on the left, manifest-rendered settings on the right
- Auto-switches to a section-sidebar layout when a settings page declares 2+ sections
- Built on `@wordpress/components`; no external UI library

### REST API at `/wp-json/orbitools/v1/`

- `modules`, `modules/{slug}/enabled`, `settings/{slug}`, `field-types`, `theme-pages`
- Auth: `manage_options` + the standard `wp_rest` nonce
- Full reference in [`inc/Core/Rest/README.md`](inc/Core/Rest/README.md)

### Module system

- One `module.json` + one PHP class per module — both auto-discovered, no central registration list
- Settings UI generated from the manifest's `settings` array (13 field types: text, textarea, number, toggle, select, multiselect, radio, checkbox-group, color, range, media, page, repeater)
- Categories: `blocks` / `controls` / `modules` — drives where the module surfaces in the React admin
- Disabled modules cost zero — class is never autoloaded

### Theme pages — drop-in option pages for themes

Themes (or other plugins) register top-level admin pages via the `orbitools/register_theme_pages` filter. Same field-schema shape modules use, plus a per-field `wp_option` binding for the cases where a field should read/write a WordPress core option directly (`blogname`, `blogdescription`, `site_logo`, etc.).

The plugin ships its own **Site Settings** page through this same public API — dogfooding the contract. Sections: Site, Header, Footer, Socials, Defaults.

See `CLAUDE.md` § "Theme pages" for the full registration shape.

## What ships out of the box

### Built-in blocks (`inc/Blocks/`)

- **Collection** — container for layouts (row / 5-col / 12-col grid systems)
- **Entry** — content item within a Collection
- **Group** — flexible layout container with semantic HTML support
- **Marquee** — scrolling content on either axis
- **Query Loop** — high-performance custom-template query loop (see [Query Loop customization hooks](#query-loop-block-template-system))
- **Read More** — collapsible content container with customisable icon ([Read More icon hook](#read-more-block-icon-customization))
- **Spacer** — responsive spacing using `theme.json` sizes

### Built-in controls (`inc/Controls/`)

- **Typography Presets** — preset-based typography control via `theme.json` ([module README](inc/Controls/Typography_Presets/README.md))
- **Spacings** — responsive spacing helpers
- **Aspect Ratio** — responsive aspect-ratio controls

### Built-in modules (`inc/Modules/`)

- **Layout Guides** — visual grid + ruler overlays for development ([module README](inc/Modules/Layout_Guides/README.md))
- **Analytics** — GA4 + GTM integration, consent mode, custom events ([module README](inc/Modules/Analytics/README.md))
- **User Avatars** — local avatar uploads, optional Gravatar disable
- **Menu Dividers** — visual dividers in WP nav menus
- **Menu Groups** — grouped menu items
- **Toolbar Reveal** — hide the admin toolbar on the frontend until the cursor reaches the top of the page
- **Post Templates** — auto-load synced block patterns for configured CPTs and per-taxonomy archive templates (themes wire pairs via the `orbitools/post_templates/configs` filter)
- **Upload Guard** — block theme `.zip` uploads on `local` environments so a stray drag-and-drop can't clobber in-progress theme work
- **External Rewrites** — per-rule exclusion + 301 engine for CPT posts whose canonical home has moved off WordPress (Yoast sitemap exclusions + single-post / term-archive redirects)
- **Core Overrides** — hide built-in WP admin pages (Settings → Connectors / Site Health / etc.) the site doesn't need

## Project structure

```text
orbitools/
├── inc/
│   ├── Blocks/         # Gutenberg blocks
│   ├── Controls/       # Editor-side controls
│   ├── Modules/        # Site-wide features
│   └── Core/
│       ├── Abstracts/  # Module_Base etc.
│       ├── Admin/      # React_Admin (mounts the React app)
│       ├── Module/     # Module_Manifest (parses module.json)
│       ├── Pages/      # Theme_Pages_Registry + Site_Settings_Page
│       ├── Rest/       # REST controllers
│       └── Helpers/    # Settings_Manager, Asset_Manager, etc.
├── src/
│   ├── admin/          # React admin (TypeScript + @wordpress/components)
│   ├── blocks/         # Block source (TSX / SCSS)
│   ├── controls/       # Editor-control source
│   ├── modules/        # Module-specific frontend assets
│   └── frontend/       # Site-wide frontend assets
├── build/              # Compiled assets (gitignored)
├── scripts/            # Build helpers (discovery scan, version bump, release)
└── webpack.*.js        # Three build configs: blocks / assets / admin
```

PSR-4 autoload: `Orbitools\\` → `inc/`.

## Development

```bash
npm install                # one-time setup
npm run build              # build everything (blocks + assets + admin)
npm run dev                # parallel watch for all three configs

# Targeted builds:
npm run build:blocks       # Gutenberg blocks bundle
npm run build:assets       # frontend + per-module assets
npm run build:admin        # React admin bundle

npm run typecheck          # tsc --noEmit
```

> **Heads up:** `build/` is gitignored. On a fresh checkout (or after a clean), run `npm run build` once before activating the plugin — otherwise the React admin and per-module frontend assets will 404. The Layout Guides module's frontend assets specifically come from `build:assets`, so re-run that whenever frontend module sources change.

### Adding a module

Drop a folder under `inc/Blocks/` / `inc/Controls/` / `inc/Modules/`. Three artifacts:

1. **`module.json`** — manifest with `slug`, `name`, `description`, `version`, `category`, `class`, `default_enabled`, optional `settings` + `sections` arrays
2. **`{Name}.php`** — class extending `Orbitools\Core\Abstracts\Module_Base` (implements `get_slug`, `get_name`, `get_description`, `init`)
3. Frontend assets if needed under `src/{category}/{slug}/`

`Module_Manager` scans on every request — no central registration list to update. Disabled modules are never autoloaded.

Full architecture notes in `CLAUDE.md` § "v2 Module Architecture".

### Adding a theme page

```php
add_filter('orbitools/register_theme_pages', function (array $pages): array {
    $pages['my-page'] = [
        'slug'     => 'my-page',
        'label'    => __('My Page', 'my-theme'),
        'position' => 20,      // Dashboard=0, Block/Control/Module Settings = 80/90/100
        'sections' => [['id' => 'general', 'title' => 'General']],
        'fields'   => [
            [
                'id' => 'site_title',
                'type' => 'text',
                'label' => 'Site Title',
                'default' => '',
                'section' => 'general',
                'wp_option' => 'blogname',   // optional: bind read/write to a WP option
            ],
        ],
    ];
    return $pages;
});
```

Settings persist in `orbitools_settings` under `{slug}_{field_id}` keys (or in the bound WP option for `wp_option`-flagged fields).

## Customisation hooks

### Read More block icon customization

Override or add icon types for the Read More block:

```php
add_filter('orbitools/read_more/icons', function($icons) {
    $icons['chevron'] = '<span class="custom-chevron">→</span>';
    $icons['heart']   = '<span class="custom-heart">♥</span>';
    $icons['arrow']   = '<i class="fas fa-arrow-down"></i>';
    return $icons;
});
```

Icons should use `currentColor` for SVG strokes/fills to inherit theme colors. All HTML is escaped on output.

### Query Loop block template system

Custom templates are registered via the `orbitools/query_loop/available_templates` filter and called directly (no file includes or output buffering). Each template function receives `($post, $layout_type, $columns)`.

```php
function orbitools_query_loop_template_my_custom($post, $layout_type, $columns) {
    $html  = '<article class="my-custom" data-template="my-custom">';
    $html .= '<h3><a href="' . esc_url(get_permalink($post->ID)) . '">';
    $html .= esc_html(get_the_title($post->ID));
    $html .= '</a></h3>';
    $html .= '</article>';
    return $html;
}

add_filter('orbitools/query_loop/available_templates', function($templates, $layout_type) {
    $templates['my-custom'] = [
        'label'       => 'My Custom Template',
        'description' => 'Custom template with special styling',
        'callback'    => 'orbitools_query_loop_template_my_custom',
        'layouts'     => ['grid'], // optional — restrict to specific layout types
    ];
    return $templates;
}, 10, 2);
```

Layout types: `'grid'` (with `columns` of `'2'` / `'3'` / `'4'` / `'5'`) or `'list'`. Omit the `layouts` key to make a template available in every layout.

**Always `esc_html` / `esc_url` / `esc_attr` your output** — template HTML is echoed directly.

## Requirements

- WordPress 6.4+
- PHP 7.4+
- For development: Node 18+, npm

## Security

- Nonce-protected REST and AJAX
- Capability checks on every write
- Input sanitisation at the boundary
- SHA256 verification on auto-updates

## License

Proprietary — developed for Orbital Design's WordPress implementations.

---

For development guidelines, architectural decisions, and the foot-guns we've already hit, see `CLAUDE.md`.
