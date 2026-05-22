/**
 * Shared type definitions for the admin app. Server payloads
 * mirror the shapes documented in inc/Core/Rest/README.md.
 */

export type ModuleCategory = 'blocks' | 'controls' | 'modules';

export interface Module {
    slug: string;
    name: string;
    description: string;
    version: string;
    category: ModuleCategory;
    default_enabled: boolean;
    enabled: boolean;
    has_custom_page: boolean;
    has_dashboard_card: boolean;
    requires: Record<string, string>;
    /**
     * Phase 3 fills this in. Phase 2 ships an always-empty array;
     * keeping the field present so the response shape doesn't churn.
     */
    settings_schema: unknown[];
}

/**
 * Per-module settings are returned as a flat key→value map with the
 * `{slug}_` prefix stripped server-side. Values are whatever the
 * underlying option holds (strings, booleans, arrays); Phase 3
 * introduces server-side coercion based on the field schema.
 */
export type ModuleSettings = Record<string, unknown>;
