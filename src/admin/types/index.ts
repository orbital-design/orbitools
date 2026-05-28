/**
 * Shared type definitions for the admin app. Server payloads
 * mirror the shapes documented in inc/Core/Rest/README.md.
 */

export type ModuleCategory = 'blocks' | 'controls' | 'modules';

export type FieldType =
    | 'text'
    | 'textarea'
    | 'number'
    | 'toggle'
    | 'select'
    | 'multiselect'
    | 'radio'
    | 'checkbox-group'
    | 'color'
    | 'range'
    | 'media'
    | 'page'
    | 'repeater';

export interface FieldOption {
    value: string | number;
    label: string;
}

/**
 * Common shape every field schema entry shares. Per-type props (min,
 * max, options, etc.) sit on the same object; field components pull
 * what they need via the catch-all index signature.
 */
export interface FieldSchema {
    id: string;
    type: FieldType | string;
    label: string;
    description?: string;
    default: unknown;
    section?: string;
    show_if?: Record<string, unknown>;
    placeholder?: string;
    options?: FieldOption[];
    min?: number;
    max?: number;
    step?: number;
    rows?: number;
    [key: string]: unknown;
}

export interface SectionDescriptor {
    id: string;
    title: string;
    description?: string;
}

/**
 * Layout the React admin uses when a settings page has 2+ sections.
 *   - 'sidebar' (default) — vertical tab list on the left, active
 *     section's fields on the right.
 *   - 'stacked' — each section as its own collapsible card.
 * Single-section pages ignore this and render flat.
 */
export type SectionLayout = 'sidebar' | 'stacked';

export interface Module {
    slug: string;
    name: string;
    description: string;
    version: string;
    category: ModuleCategory;
    default_enabled: boolean;
    enabled: boolean;
    /**
     * Dashicon slug ("format-image") or inline SVG markup
     * ("<svg…>…</svg>"). Populated only for category='blocks'
     * modules — the controller resolves it from the registered
     * block's icon. Null when no icon is available.
     */
    icon: string | null;
    has_custom_page: boolean;
    has_dashboard_card: boolean;
    requires: Record<string, string>;
    sections: SectionDescriptor[];
    settings_schema: FieldSchema[];
    section_layout: SectionLayout;
}

/**
 * Per-module settings are returned as a flat key→value map with the
 * `{slug}_` prefix stripped server-side. Values are whatever the
 * underlying option holds (strings, booleans, arrays); a future
 * iteration may introduce server-side coercion based on the schema.
 */
export type ModuleSettings = Record<string, unknown>;

export interface FieldTypeCatalogEntry {
    id: string;
    label: string;
}

/**
 * Optional admin extensions a module can ship. The build-time
 * discovery scan (scripts/discover-admin-extensions.js) finds modules
 * at src/admin/modules/{slug}/index.tsx and writes a static manifest
 * at src/admin/.generated/discovered.ts.
 *
 * Page  — replaces the generic SettingsPage when the module is opened
 *         at #settings/{slug}. The component receives the slug as a
 *         prop so it can reuse hooks like useSettings(slug).
 * Fills — a component rendered globally inside the SlotFillProvider.
 *         It is expected to return one or more <Fill> elements
 *         targeting names from src/admin/lib/slots.ts. Mounted once,
 *         outside the routed page, so its fills persist across routes.
 *
 * Both are optional; a module may ship either, both, or neither.
 */
export interface ModuleExtension {
    Page?: ModulePage;
    Fills?: () => JSX.Element | null;
}

export type ModulePage = (props: { slug: string }) => JSX.Element;

/**
 * Theme-registered top-level page metadata, delivered via the
 * `window.orbitools.themePages` bootstrap (see React_Admin.php). The
 * field schema follows the same FieldSchema contract modules use,
 * with the optional `wp_option` per-field binding that
 * Settings_Controller honours server-side.
 */
export interface ThemePageInfo {
    slug: string;
    label: string;
    description: string;
    icon: string;
    position: number;
    sections: SectionDescriptor[];
    settings_schema: FieldSchema[];
    section_layout: SectionLayout;
}
