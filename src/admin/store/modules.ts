/**
 * Modules slice.
 *
 * Resolvers fetch the modules list from the REST API the first time
 * a selector is called. Actions support optimistic toggle with
 * rollback on failure.
 */
import type { Module } from '../types';
import * as api from '../lib/api';
import type { ModulesState, State } from './state';

const initialState: ModulesState = {
    bySlug: {},
    isLoading: false,
    error: null,
};

// ---- Action types -----------------------------------------------------------

type Action =
    | { type: 'MODULES_FETCH_START' }
    | { type: 'MODULES_FETCH_SUCCESS'; modules: Module[] }
    | { type: 'MODULES_FETCH_ERROR'; error: string }
    | { type: 'MODULE_REPLACE'; module: Module };

// ---- Reducer ----------------------------------------------------------------

function reducer(state: ModulesState | undefined, action: { type: string; [key: string]: unknown }): ModulesState {
    const s = state ?? initialState;

    switch (action.type) {
        case 'MODULES_FETCH_START':
            return { ...s, isLoading: true, error: null };

        case 'MODULES_FETCH_SUCCESS': {
            const modules = action['modules'] as Module[];
            const bySlug: Record<string, Module> = {};
            for (const m of modules) {
                bySlug[m.slug] = m;
            }
            return { bySlug, isLoading: false, error: null };
        }

        case 'MODULES_FETCH_ERROR':
            return { ...s, isLoading: false, error: action['error'] as string };

        case 'MODULE_REPLACE': {
            const m = action['module'] as Module;
            return { ...s, bySlug: { ...s.bySlug, [m.slug]: m } };
        }

        default:
            return s;
    }
}

// ---- Actions ----------------------------------------------------------------

const actions = {
    fetchModulesStart: (): Action => ({ type: 'MODULES_FETCH_START' }),
    fetchModulesSuccess: (modules: Module[]): Action => ({ type: 'MODULES_FETCH_SUCCESS', modules }),
    fetchModulesError: (error: string): Action => ({ type: 'MODULES_FETCH_ERROR', error }),
    replaceModule: (module: Module): Action => ({ type: 'MODULE_REPLACE', module }),

    /**
     * Optimistic toggle: flip the local state immediately, fire the
     * REST request, replace with server response on success, or roll
     * back on failure.
     */
    toggleModule:
        (slug: string, enabled: boolean) =>
        async ({ select, dispatch }: { select: (k: string) => Selectors; dispatch: (k: string) => Dispatchers }) => {
            const before = select('orbitools').getModule(slug);
            if (before === undefined) {
                return;
            }
            // Optimistic update.
            dispatch('orbitools').replaceModule({ ...before, enabled });
            try {
                const updated = await api.setModuleEnabled(slug, enabled);
                dispatch('orbitools').replaceModule(updated);
            } catch (err) {
                // Roll back.
                dispatch('orbitools').replaceModule(before);
                const message = err instanceof Error ? err.message : String(err);
                dispatch('orbitools').addNotice({
                    status: 'error',
                    message: `Failed to update ${slug}: ${message}`,
                });
            }
        },
};

// ---- Selectors --------------------------------------------------------------

const selectors = {
    getModules: (state: State): Module[] => Object.values(state.modules.bySlug),
    getModule: (state: State, slug: string): Module | undefined => state.modules.bySlug[slug],
    isModuleEnabled: (state: State, slug: string): boolean => Boolean(state.modules.bySlug[slug]?.enabled),
    isLoadingModules: (state: State): boolean => state.modules.isLoading,
    getModulesError: (state: State): string | null => state.modules.error,
};

// ---- Resolvers --------------------------------------------------------------
//
// Run on first call of getModules / getModule. The store's @wordpress/data
// runtime invokes these once per resolver-key and tracks completion so
// repeat calls don't re-fetch.

const resolvers = {
    getModules:
        () =>
        async ({ dispatch }: { dispatch: (k: string) => Dispatchers }) => {
            dispatch('orbitools').fetchModulesStart();
            try {
                const modules = await api.fetchModules();
                dispatch('orbitools').fetchModulesSuccess(modules);
            } catch (err) {
                const message = err instanceof Error ? err.message : String(err);
                dispatch('orbitools').fetchModulesError(message);
            }
        },
};

// Loose types for store thunks — @wordpress/data's TS support is partial.
type Selectors = {
    getModule: (slug: string) => Module | undefined;
};
type Dispatchers = {
    fetchModulesStart: () => Action;
    fetchModulesSuccess: (modules: Module[]) => Action;
    fetchModulesError: (error: string) => Action;
    replaceModule: (module: Module) => Action;
    addNotice: (notice: { status: string; message: string }) => unknown;
};

export const modulesSlice = {
    reducer,
    actions,
    selectors,
    resolvers,
};
