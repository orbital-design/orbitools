/**
 * Settings slice.
 *
 * Per-module settings keyed by slug. Lazy-loaded on first access via
 * the getSettings resolver; PUT and PATCH go through the REST API.
 */
import type { ModuleSettings } from '../types';
import * as api from '../lib/api';
import type { SettingsState, State } from './state';

const initialState: SettingsState = {
    bySlug: {},
    loadingBySlug: {},
    errorBySlug: {},
};

type Action =
    | { type: 'SETTINGS_FETCH_START'; slug: string }
    | { type: 'SETTINGS_FETCH_SUCCESS'; slug: string; settings: ModuleSettings }
    | { type: 'SETTINGS_FETCH_ERROR'; slug: string; error: string }
    | { type: 'SETTINGS_REPLACE'; slug: string; settings: ModuleSettings };

function reducer(state: SettingsState | undefined, action: { type: string; [key: string]: unknown }): SettingsState {
    const s = state ?? initialState;

    switch (action.type) {
        case 'SETTINGS_FETCH_START': {
            const slug = action['slug'] as string;
            return {
                ...s,
                loadingBySlug: { ...s.loadingBySlug, [slug]: true },
                errorBySlug: { ...s.errorBySlug, [slug]: null },
            };
        }

        case 'SETTINGS_FETCH_SUCCESS': {
            const slug = action['slug'] as string;
            const settings = action['settings'] as ModuleSettings;
            return {
                bySlug: { ...s.bySlug, [slug]: settings },
                loadingBySlug: { ...s.loadingBySlug, [slug]: false },
                errorBySlug: { ...s.errorBySlug, [slug]: null },
            };
        }

        case 'SETTINGS_FETCH_ERROR': {
            const slug = action['slug'] as string;
            return {
                ...s,
                loadingBySlug: { ...s.loadingBySlug, [slug]: false },
                errorBySlug: { ...s.errorBySlug, [slug]: action['error'] as string },
            };
        }

        case 'SETTINGS_REPLACE': {
            const slug = action['slug'] as string;
            const settings = action['settings'] as ModuleSettings;
            return {
                ...s,
                bySlug: { ...s.bySlug, [slug]: settings },
            };
        }

        default:
            return s;
    }
}

const actions = {
    fetchSettingsStart: (slug: string): Action => ({ type: 'SETTINGS_FETCH_START', slug }),
    fetchSettingsSuccess: (slug: string, settings: ModuleSettings): Action => ({
        type: 'SETTINGS_FETCH_SUCCESS',
        slug,
        settings,
    }),
    fetchSettingsError: (slug: string, error: string): Action => ({ type: 'SETTINGS_FETCH_ERROR', slug, error }),
    replaceSettings: (slug: string, settings: ModuleSettings): Action => ({
        type: 'SETTINGS_REPLACE',
        slug,
        settings,
    }),

    saveSettings:
        (slug: string, settings: ModuleSettings) =>
        async ({ dispatch }: { dispatch: (k: string) => Dispatchers }) => {
            try {
                const updated = await api.replaceSettings(slug, settings);
                dispatch('orbitools').replaceSettings(slug, updated);
            } catch (err) {
                const message = err instanceof Error ? err.message : String(err);
                dispatch('orbitools').addNotice({
                    status: 'error',
                    message: `Failed to save settings for ${slug}: ${message}`,
                });
            }
        },

    updateSetting:
        (slug: string, key: string, value: unknown) =>
        async ({ select, dispatch }: { select: (k: string) => Selectors; dispatch: (k: string) => Dispatchers }) => {
            const current = select('orbitools').getSettings(slug) ?? {};
            const next = { ...current, [key]: value };
            // Optimistic local update.
            dispatch('orbitools').replaceSettings(slug, next);
            try {
                const updated = await api.patchSettings(slug, { [key]: value });
                dispatch('orbitools').replaceSettings(slug, updated);
            } catch (err) {
                dispatch('orbitools').replaceSettings(slug, current);
                const message = err instanceof Error ? err.message : String(err);
                dispatch('orbitools').addNotice({
                    status: 'error',
                    message: `Failed to update ${slug}.${key}: ${message}`,
                });
            }
        },
};

const selectors = {
    getSettings: (state: State, slug: string): ModuleSettings | undefined => state.settings.bySlug[slug],
    getSetting: <T = unknown>(state: State, slug: string, key: string): T | undefined =>
        state.settings.bySlug[slug]?.[key] as T | undefined,
    isLoadingSettings: (state: State, slug: string): boolean => Boolean(state.settings.loadingBySlug[slug]),
    getSettingsError: (state: State, slug: string): string | null => state.settings.errorBySlug[slug] ?? null,
};

const resolvers = {
    getSettings:
        (slug: string) =>
        async ({ dispatch }: { dispatch: (k: string) => Dispatchers }) => {
            dispatch('orbitools').fetchSettingsStart(slug);
            try {
                const settings = await api.fetchSettings(slug);
                dispatch('orbitools').fetchSettingsSuccess(slug, settings);
            } catch (err) {
                const message = err instanceof Error ? err.message : String(err);
                dispatch('orbitools').fetchSettingsError(slug, message);
            }
        },
};

type Selectors = {
    getSettings: (slug: string) => ModuleSettings | undefined;
};
type Dispatchers = {
    fetchSettingsStart: (slug: string) => Action;
    fetchSettingsSuccess: (slug: string, settings: ModuleSettings) => Action;
    fetchSettingsError: (slug: string, error: string) => Action;
    replaceSettings: (slug: string, settings: ModuleSettings) => Action;
    addNotice: (notice: { status: string; message: string }) => unknown;
};

export const settingsSlice = {
    reducer,
    actions,
    selectors,
    resolvers,
};
