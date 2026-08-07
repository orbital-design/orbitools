# Orbitools Development Notes

This file contains important development lessons, patterns, and guidelines for working on the Orbitools WordPress plugin.

## 🏗 v2 Module Architecture

The plugin uses a registry-based module system. Adding a new module requires three artifacts and no edits to core code.

### How to add a module

1. **Create the folder** under one of the three category roots:
   - `inc/Blocks/{Name}/` — Gutenberg blocks
   - `inc/Controls/{Name}/` — editor-side controls injected into existing blocks
   - `inc/Modules/{Name}/` — anything else

2. **Add the class** at `inc/{Category}/{Name}/{Name}.php` extending `Orbitools\Core\Abstracts\Module_Base`:
   ```php
   namespace Orbitools\Modules\Example;
   use Orbitools\Core\Abstracts\Module_Base;

   class Example extends Module_Base
   {
       public function get_slug(): string { return 'example'; }
       public function get_name(): string { return \__('Example', 'orbitools'); }
       public function get_description(): string { return \__('What it does.', 'orbitools'); }
       public function init(): void { /* register hooks here */ }
   }
   ```

3. **Add `module.json`** alongside the class. Required fields: `slug`, `name`, `description`, `version`, `category`, `class`, `default_enabled`. Categories are `blocks` | `controls` | `modules`. The `slug` must equal the class's `get_slug()` return value — `Module_Manager` warns on mismatch under `WP_DEBUG`.

That's it. `Module_Manager` scans `inc/{Blocks,Controls,Modules}/*/module.json` on every request and registers each manifest. The module is instantiated only when its `{slug}_enabled` setting is true; manifest `default_enabled` controls the value when no setting is stored.

### How Module_Manager works

- `Loader::init()` builds one `Module_Manager`, calls `register_built_in()` (manifest scan + `do_action('orbitools/register_modules', $manager)`), then `boot()` (instantiates enabled modules only).
- `Settings_Manager::is_module_enabled($slug)` consults a resolver registered by `Module_Manager` — the resolver looks up the manifest's `default_enabled` when no `{slug}_enabled` setting is stored.
- **Disabled modules cost zero**: their class is never autoloaded, never constructed, never asset-registered. Verified by `class_exists()` being called only after the enable check.
- `Module_Manager::instance()` returns the active manager (used by the REST `Modules_Controller` to assemble the React admin's modules list).

### External modules

Themes or other plugins can register their own modules:
```php
add_action('orbitools/register_modules', function ($manager) {
    $manager->register('my-feature', \My\Namespace\Feature::class);
});
```
External modules have no manifest, so they default to enabled (the resolver's fallback) and surface in the React admin under the generic "modules" category with no `settings_schema`.

### Slug stability

Settings are keyed by `{slug}_enabled`. **Never rename a `get_slug()` return value** — existing client sites have their toggles stored against that key. The `WP_DEBUG` slug-mismatch warning in `Module_Manager::boot()` catches accidental drift.

### Filesystem layout

```
inc/
├── Blocks/         # Gutenberg blocks (PascalCase namespace + folder)
├── Controls/       # Editor-side controls
├── Core/           # Plugin core (Loader, Module_Manager, Module_Base, REST,
│                   #             React_Admin, etc.)
└── Modules/        # Other modules
```

Composer autoload is a single PSR-4 mapping: `"Orbitools\\": "inc/"`. macOS is case-insensitive, but the codebase must work on Linux — paths in code MUST match folder casing exactly.

## 🧩 v3 React Admin Layer

The plugin admin is a single-page React app served from `?page=orbitools`. The PHP side renders one mount div (`Orbitools\Core\Admin\React_Admin`) and exposes a versioned REST surface; everything visible is rendered by `src/admin/index.tsx`.

### Surface map

```
src/admin/
├── index.tsx              # Mount + store registration + field side-effect imports
├── App.tsx                # Hash-routed root: dashboard / category / settings
├── components/            # Dashboard, CategoryPage, SettingsPage, AppChrome, TopNav, …
├── fields/                # One file per field type (text, toggle, range, …);
│                          #   each self-registers with fields/registry.ts at import
├── hooks/                 # useModules, useSettings
├── lib/                   # api.ts (apiFetch wiring), router.ts, slots.ts, showIf.ts
├── modules/               # Per-module admin extensions (drop-a-file, see below)
├── store/                 # @wordpress/data store: modules / settings / ui slices
└── types/                 # FieldSchema, Module, ModuleExtension, etc.
```

### REST API

Base path: `/wp-json/orbitools/v1/` (registered by `Rest_Server`, hosting `Modules_Controller`, `Settings_Controller`, `Field_Types_Controller`).

```
GET    /modules                 # list + per-module settings_schema
GET    /modules/{slug}
POST   /modules/{slug}/enabled  # body: { enabled: bool }
GET    /settings/{slug}         # slug-prefix stripped from keys
PUT    /settings/{slug}         # replace
PATCH  /settings/{slug}         # partial
GET    /field-types             # catalog of the 10 built-in types
```

All write endpoints require `manage_options` and the `wp_rest` nonce (set by `lib/api.ts` via `apiFetch.createNonceMiddleware`).

### Settings schema in module.json

The React admin's per-module settings page is **auto-rendered from a `settings` array in `module.json`** — modules don't write React. Fields are flat, ID-relative to the module slug, and validated under `WP_DEBUG` by `Module_Manifest::validate_settings_schema()`.

```json
{
  "slug": "example",
  "settings": [
    {
      "id": "show_grids",
      "type": "toggle",
      "label": "Enable grids",
      "description": "…",
      "default": true
    },
    {
      "id": "color",
      "type": "color",
      "label": "Guide colour",
      "default": "#32a3e2",
      "show_if": { "show_grids": true }
    }
  ]
}
```

Stored option keys are `{slug}_{field_id}` (e.g. `example_show_grids`). The REST controller strips/adds the prefix; the React store and the field schema both speak slug-relative IDs.

The 13 v1 field types: `text`, `textarea`, `number`, `toggle`, `select`, `multiselect`, `radio`, `checkbox-group`, `color`, `range`, `media`, `page`, `repeater`. Anything richer than `show_if` equality should route to a custom Page (see below) instead of trying to encode logic in the schema.

**`page`** stores a WP page ID; the field component fetches `/wp/v2/pages` and renders a select dropdown. Use for "pick a page" settings (e.g. a 404 page picker).

**`repeater`** is a variable-length list of rows, each described by a `sub_fields` array that uses the same field schema as the top-level. Storage is `Array<Record<string, unknown>>`. Schema extras:

```json
{
  "id": "social_links",
  "type": "repeater",
  "label": "Social Links",
  "default": [],
  "add_button_label": "Add social profile",
  "row_label_field": "network",
  "sub_fields": [
    { "id": "network", "type": "select", "label": "Network", "default": "facebook", "options": [...] },
    { "id": "url", "type": "text", "label": "URL", "default": "" }
  ]
}
```

`row_label_field` names the sub-field whose value labels each row's heading (looks up the select option's label automatically when applicable).

**`wp_option` per-field binding.** Any field can declare `"wp_option": "blogname"` to read/write directly to that WordPress option instead of the `orbitools_settings` row. `Settings_Controller` honours the mapping for both module fields and theme-page fields — used so the built-in Site Settings page actually updates `blogname`, `blogdescription`, and `site_logo` (not just a private mirror). Use it sparingly; plugins reading `get_option('blogname')` is the use case.

String values for options like `blogname`/`blogdescription` are HTML-entity-decoded on read by `Settings_Controller` because WP's `sanitize_option` runs `esc_html` on save for those keys — without the decode, `&` would round-trip as `&amp;` in the editor.

### Theme pages (drop-in option pages for themes)

Themes (or any other plugin) register top-level admin pages via the `orbitools/register_theme_pages` filter — same shape as `module.json`'s settings, plus a label / description / icon / position. The React admin slots them into the TopNav sorted by position; per-field values flow through the same `/orbitools/v1/settings/{slug}` endpoint modules use.

```php
add_filter('orbitools/register_theme_pages', function (array $pages): array {
    $pages['my-theme'] = [
        'slug'     => 'my-theme',
        'label'    => __('Theme Options', 'my-theme'),
        'position' => 20,                // Dashboard=0, Block/Control/Module Settings = 80/90/100
        'sections' => [
            ['id' => 'header', 'title' => 'Header'],
        ],
        'fields'   => [
            [
                'id'        => 'primary_color',
                'type'      => 'color',
                'label'     => 'Primary color',
                'default'   => '#000',
                'section'   => 'header',
            ],
            [
                'id'        => 'logo',
                'type'      => 'media',
                'label'     => 'Logo',
                'default'   => 0,
                'section'   => 'header',
                'wp_option' => 'site_logo',   // optional: bind to a WP option
            ],
        ],
    ];
    return $pages;
});
```

Themes read values from PHP via `get_option('orbitools_settings')['{slug}_{field_id}']` (or via `get_option('blogname')` etc. for `wp_option`-bound fields).

`inc/Core/Pages/Site_Settings_Page.php` is the built-in page that ships with the plugin — it's registered via the same filter at priority 5 so themes can override or augment it.

**Section sidebar.** When a page (or module) declares 2+ sections, the React renderer automatically switches to a sidebar layout (sections on the left, active section's fields on the right). 0 or 1 sections render flat.

### Drop-a-file admin extensions

A module can replace the auto-rendered settings page or contribute UI to named slots by dropping `src/admin/modules/{slug}/index.tsx`:

```tsx
import type { ModuleExtension, ModulePage } from '../../types';

const Page: ModulePage = ({ slug }) => <CustomTypographyEditor slug={slug} />;
function Fills() { return <Fill name="orbitools.dashboard.cards">…</Fill>; }

const extension: ModuleExtension = { Page, Fills };
export default extension;
```

`scripts/discover-admin-extensions.js` runs from the webpack `beforeRun` hook, writes `src/admin/.generated/discovered.ts` as a static import map, and `App.tsx` consults it to decide whether to render the custom Page or fall back to `SettingsPage`. `Fills` are mounted globally inside the `SlotFillProvider` so dashboard/sidebar contributions persist across routes. Slot names live in `src/admin/lib/slots.ts`.

### Routing

Hash-based, no router dependency. See `src/admin/lib/router.ts` for the full table — `#`, `#blocks`, `#controls`, `#modules`, `#editor`, `#tools`, `#settings/{slug}`. `routes.X()` is the single constructor; never hand-build hash strings elsewhere.

## 🛠 Tools (Import / Export) — **READ THIS BEFORE ADDING / REMOVING THINGS**

The `Tools` top-level tab (`#tools`) lets users dump the plugin's configuration as a JSON bundle and restore it on another install. **Every change to the schema surface has implications for this — when you touch any of the items below, also touch the matching Tools moving part.**

The flow:

```
   ┌─────────────────────────────────────────────────────────┐
   │ orbitools_settings = { '{slug}_{field_id}' => value }   │
   │                                                          │
   │            ↓ Tools_Controller::export()                  │
   │            walks every module manifest + theme page,     │
   │            blanks `page` / `media` field values, ships:  │
   │                                                          │
   │      { version, exported_at, source,                     │
   │        modules: [{slug,name,category}, …],               │
   │        theme_pages: [{slug,label}, …],                   │
   │        settings: { … keys filtered by slug … },          │
   │        stripped_keys: [ … ] }                            │
   │                                                          │
   │            ↑ Tools_Controller::import()                  │
   │            merges incoming `settings` into the option    │
   │            (slug-whitelist filters first if requested).  │
   └─────────────────────────────────────────────────────────┘
```

### When you add things

- **New module (block / control / modules / editor)** — picked up automatically. The export iterates `Module_Manager::get_manifests()` so the slug, category, settings schema, and current values all flow through without manual work. Its `{slug}_enabled` and `{slug}_{field_id}` keys land under the right category on the UI checkbox grid.

- **New theme page** — same deal; the export walks `apply_filters('orbitools/register_theme_pages', [])` so any page registered through the standard filter is already in scope.

- **New field type that stores a WordPress entity ID (page picker, attachment, term, comment, user, etc.)** — **YOU MUST ADD IT TO `Tools_Controller::ENTITY_FIELD_TYPES`**. Otherwise the values get shipped verbatim to the export bundle and break on the destination site. The constant currently holds `['page', 'media']`. The strip walker handles nesting (a `page` field inside a `repeater` sub_field is found recursively), so the only step is the constant addition.

- **New module category** (peer to `blocks` / `controls` / `editor` / `integrations` / `modules`) — three pieces:
    1. `Module_Manifest::FIELD_TYPES`-style `ALLOWED_CATEGORIES` in `inc/Core/Module/Module_Manifest.php`.
    2. `ModuleCategory` TS union + the matching `CATEGORY_TITLES` / `CATEGORY_META` / `CATEGORY_SLUGS` / `categoryIcon` records (search `category-icons.tsx`, `App.tsx`, `CategoryPage.tsx`, `router.ts`).
    3. `SELECTION_LABELS` / `SELECTION_ORDER` in `src/admin/components/ToolsPage.tsx` — the Tools UI hardcodes a `category:<id>` SelectionKey union, **so a new category must be added or its modules will silently be unreachable from the Export / Import checkbox grid**.

### When you remove things

- **Removing a module or theme page** — its `{slug}_*` keys may linger in `orbitools_settings` (orphans). Add a one-shot migration in `inc/Core/Migrations.php` to clean them out (the existing `maybe_drop_toolbar_fab` is a worked example). Otherwise an export will keep shipping dead data and an import will keep restoring it.

- **Renaming a slug** — never rename a `get_slug()` value or a theme page slug. The settings keys are stored against it; existing installs would silently get a new module with no toggle state and the old toggle key would orphan. Same rule as the v2 slug migration described in the module architecture section above.

- **Adding a new migration to `Migrations::run()`** — also add its flag-option name to `Tools_Controller::MIGRATION_FLAG_OPTIONS`. The Reset tool deletes those flags so migrations re-run on the next request; leaving one out means after a Reset the migration silently skips and your defaults aren't seeded.

### What does NOT round-trip

- **Fields with `wp_option` binding** — these write directly to the named WP option (`blogname`, `blogdescription`, `site_logo`) rather than `orbitools_settings`. They are deliberately out of scope for Tools; site identity / branding values aren't expected to transfer. If you ever need them to, the export needs an `options` section keyed by `wp_option` name and the import needs the matching `update_option` loop.

- **Entity IDs (page / media)** — blanked on export by design (page ID 47 on site A is a totally different page on site B). The export payload's `stripped_keys` array surfaces which fields were blanked so the UI can prompt the user to re-pick them on the destination site.

### Breakpoint migration (content rewrite, not a settings round-trip)

The Tools tab also hosts **Migrate breakpoints** — a separate, on-demand
content migration (not part of export/import/reset, not flag-gated in
`Migrations.php`). It rewrites `post_content` block attributes, not the
`orbitools_settings` option.

- Endpoints: `GET /tools/breakpoint-migration` (dry-run scan, read-only) and
  `POST /tools/breakpoint-migration` with `{ confirm: true }` (apply).
- It walks `parse_blocks()` over every non-trashed post whose body mentions a
  responsive attribute (`Tools_Controller::RESPONSIVE_BLOCK_ATTRS` =
  `orbGap`/`orbPadding`/`orbMargin`/`orbAspectRatio`), drops the legacy
  `Tools_Controller::LEGACY_BREAKPOINT_KEYS` (`sm`/`md`/`lg`/`xl`) from each,
  and `wp_update_post()`s the result (so the old content survives as a
  revision). `base` and all non-orb attrs are preserved; the walker recurses
  into `innerBlocks`.
- **If you add a new responsive block attribute, add it to
  `RESPONSIVE_BLOCK_ATTRS`** or its legacy overrides won't be migrated.

### Process discipline

When you open a PR that touches:
- `Module_Manifest::ALLOWED_CATEGORIES`
- `ModuleCategory` TS union
- A new field type that stores an entity ID
- A field-schema change that introduces new `sub_fields`
- A module's `get_slug()` value (don't do this — see above)
- A theme page's `slug`
- `Tools_Controller::ENTITY_FIELD_TYPES` or `Tools_Controller::*_index()`
- `ToolsPage`'s `SelectionKey` / `SELECTION_ORDER` / `SELECTION_LABELS`

…explicitly note in the PR description how you verified the Tools round-trip still works (or why it doesn't apply). Tools is the kind of feature that breaks quietly — an export looks fine until you import it on a fresh site and discover half the modules are missing.

### Store

`@wordpress/data` — one store, three slices (`modules`, `settings`, `ui`), all under the key `'orbitools'` (exported as `STORE_KEY` from `src/admin/store/index.ts`).

**Critical gotcha:** inside thunks and resolvers, `dispatch` and `select` are **already bound** to the current store. Calling `dispatch('orbitools').actionName()` dispatches the literal string `'orbitools'` as an action and silently no-ops. Always call `dispatch.actionName()` / `select.selectorName()` directly.

### @wordpress/components imports

`VStack`, `HStack`, `NumberControl` are exported only under the experimental name (`__experimentalVStack`, etc.). Importing as plain `VStack` resolves to `undefined` at runtime and renders as `<undefined />` (React #130). Alias on import:

```tsx
import { __experimentalVStack as VStack } from '@wordpress/components';
```

Controls with the 36px-default-size deprecation (SelectControl, FormTokenField, TextControl, RangeControl, NumberControl) need `__next40pxDefaultSize` to silence the warning.

## 🎯 Core Development Principles

### 1. Systematic API Changes
**CRITICAL**: When fixing API issues, always apply changes systematically across the entire codebase.

#### Example: useSettings API Fix
**Issue Encountered**: During spacer block development, `useSettings(['spacing.spacingSizes'])` was causing React hook errors.

**Mistake Made**: Fixed only the spacer block without checking other blocks.

**Correct Approach**:
```bash
# 1. Search entire codebase for the pattern
grep -r "useSettings.*spacing" src/

# 2. Fix ALL instances systematically  
# ❌ Wrong: useSettings(['spacing.spacingSizes'])
# ✅ Correct: useSettings('spacing.spacingSizes')

# 3. Test all affected blocks together
```

**Result of Incomplete Fix**: Collection and Entry block spacing controls broke, causing reliability issues.

### 2. WordPress Block Development Patterns

#### Consistent useSettings Usage
```tsx
// ✅ Correct pattern for ALL blocks
const [spacingSizes] = useSettings('spacing.spacingSizes');

// ❌ Avoid - causes hook ordering issues
const [spacingSizes] = useSettings(['spacing.spacingSizes']);
```

#### Responsive Control Architecture (device-aware, no tab bar)
Responsive block controls are driven by the **editor's native screen-size
preview toggle**, not a bespoke breakpoint tab bar. The shared framework lives
at [`src/core/utils/responsive-control.js`](src/core/utils/responsive-control.js)
— plain-JS `createElement` because the controls build (`webpack.assets.js`)
only runs `@babel/preset-env` (no JSX/TS).

- Wrap a control in `ResponsiveControl({ title, blockName, render })`. The
  `render({ device, slug, breakpoint })` callback returns the input for the
  active device; the framework owns device detection, the device switcher
  (which drives the real preview), and the cascade hint.
- Device → slug mapping is fixed: **Desktop→`base`, Tablet→`tablet`,
  Mobile→`mobile`**. Attribute storage stays an object keyed by slug
  (`{ base, tablet, mobile }`), so existing values round-trip.
- `useDeviceType()` is version-safe across `core/editor` (`getDeviceType`/
  `setDeviceType`, WP 6.5+) and the legacy `core/edit-post` experimental API.
- Controls that use it must enqueue `wp-data` + `wp-icons` as script deps.
- Breakpoints come from theme.json `settings.custom.breakpoints` (3-tier,
  desktop-first **max-width**: tablet 781px, mobile 479px — aligned to WP's
  device-preview canvas). The aspect-ratio and spacings controls are the
  reference conversions.
- Legacy `sm/md/lg/xl` (mobile-first min-width) content is migrated via the
  **Tools → Migrate breakpoints** tool (see below), which drops them.

### 3. Code Quality Standards

#### Before Committing Changes:
1. **Search for patterns**: `grep -r "pattern" src/` 
2. **Fix systematically**: Update ALL instances
3. **Test all affected areas**: Not just the current feature
4. **Build successfully**: `npm run build` (blocks + assets + admin) or the targeted `npm run build:admin`
5. **Verify in browser**: Test all related functionality

#### File Organization:
- Keep blocks modular and focused
- Remove unused files promptly (e.g., `controls.tsx`, `frontend.js`)
- Use consistent naming patterns
- Document complex logic

### 4. WordPress Integration

#### Theme.json Integration:
- Always use theme spacing values via `useSettings`
- Provide fallback defaults in plugin config
- Support theme override capability

#### Block Registration:
- Update webpack config for new blocks
- Register in PHP Layout_Blocks class
- Follow WordPress block.json standards

## 🔧 Technical Patterns

### Responsive Controls Implementation:
```tsx
// 1. Config system reads breakpoints from theme → plugin defaults
import { useBreakpoints } from './config-reader';

// 2. ResponsiveToolsPanel creates ToolsPanelItems for each breakpoint
<ResponsiveToolsPanel
  controls={[heightControlConfig]}
  values={{ height }}
  onValuesChange={handleChange}
/>

// 3. CSS classes generated: h-medium, sm:h-large, md:h-fill
const classes = getResponsiveClasses(height, 'h', formatValue);
```

### WordPress Block Structure:
```
/src/blocks/[block-name]/
├── block.json          # WordPress metadata
├── index.tsx          # Registration & imports  
├── edit.tsx           # Editor component
├── save.tsx           # Frontend output
├── [control].tsx      # Specific controls
├── index.scss         # Frontend styles
└── editor.scss        # Editor styles
```

## 🚨 Common Pitfalls

1. **Incomplete Refactoring**: Fixing API issues in one place but not others
2. **Hook Ordering**: Calling hooks conditionally or in wrong order
3. **Copy-Paste Errors**: Copying broken patterns between blocks
4. **Missing Dependencies**: Not updating webpack/PHP registration for new blocks
5. **Theme Integration**: Hardcoding values instead of using theme.json
6. **Store thunk dispatch**: Using registry-style `dispatch('orbitools').x()` inside a thunk silently no-ops — always use bound `dispatch.x()`
7. **Experimental @wordpress/components imports**: `VStack`, `HStack`, `NumberControl` etc. need the `__experimental` prefix alias

## 📋 Quality Checklist

Before marking any feature complete:
- [ ] All `useSettings` calls use string format
- [ ] All bundles build successfully (`npm run build`)
- [ ] All controls appear and function correctly  
- [ ] No unused files remain
- [ ] Consistent patterns across blocks
- [ ] Theme integration working
- [ ] Responsive features tested
- [ ] React admin settings pages render for any module changes (manifest schema validated under `WP_DEBUG`)

## 🎯 Future Development

When adding new blocks or features:
1. **Start with existing patterns** - Don't reinvent the wheel
2. **Check ALL similar code** - Ensure consistency from day one  
3. **Test systematically** - Don't just test the new feature
4. **Document decisions** - Update this file with new patterns
5. **Clean as you go** - Remove unused code immediately

### Planned: Grid block

A dedicated `orb/grid` block (separate from the flex-based Row Layout) is
planned, with **responsive per-breakpoint column spans** (e.g. desktop 8/4 →
tablet 6/6 → stacked-mobile full). It uses CSS Grid — `grid-template-columns:
repeat(N, minmax(0,1fr))` + `grid-column: span N` — because flexbox `%` widths
can't reconcile with `gap`. Reuses the `ResponsiveControl`/`ResponsiveDots`
framework and the `has-gap` system; "stack on mobile" locks the mobile span to
full.

- Full design: `GRID-BLOCK-SPEC.md` (local/untracked — `*.md` is gitignored).
- Working CSS-Grid prototype (built on Row Layout, then reverted): commit
  `1632f26` — `git show 1632f26` to lift the container + span CSS.

---
*Last Updated: 2026-05-26 (v3 React admin layer + AdminKit retirement)*
*This file should be updated whenever new development patterns or lessons are discovered.*
