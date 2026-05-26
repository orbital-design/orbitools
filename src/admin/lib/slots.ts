/**
 * Slot name registry.
 *
 * Single source of truth for every named Slot/Fill in the admin app.
 * Growing this map is fine; growing slot names organically across the
 * codebase is not.
 *
 * Per-module slot names use the slug to scope (e.g.
 * orbitools.settings.typography-presets.before). Phase 4 introduces
 * the discovery manifest that auto-mounts module fills into these.
 */

export const SLOTS = {
    // Global chrome
    APP_HEADER_ACTIONS: 'orbitools.app.header.actions',

    // Dashboard
    DASHBOARD_CARDS: 'orbitools.dashboard.cards',
    DASHBOARD_BEFORE: 'orbitools.dashboard.before',
    DASHBOARD_AFTER: 'orbitools.dashboard.after',

    // Modules page
    MODULES_HEADER_ACTIONS: 'orbitools.modules.header.actions',

    // Per-module settings pages
    settingsBefore:  (slug: string): string => `orbitools.settings.${slug}.before`,
    settingsAfter:   (slug: string): string => `orbitools.settings.${slug}.after`,
    settingsSidebar: (slug: string): string => `orbitools.settings.${slug}.sidebar`,
} as const;
