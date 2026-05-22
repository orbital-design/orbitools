/**
 * @wordpress/data store.
 *
 * Three slices combine into a single namespaced store:
 *   - modules   (registry list + per-slug enabled state)
 *   - settings  (per-module settings dict)
 *   - ui        (current page, transient notices)
 *
 * The whole store is registered under the 'orbitools' key. Components
 * read via useSelect((s) => s('orbitools').getX()) and dispatch via
 * useDispatch('orbitools').
 */
import { createReduxStore, register } from '@wordpress/data';
import { modulesSlice } from './modules';
import { settingsSlice } from './settings';
import { uiSlice } from './ui';
import type { State } from './state';

export const STORE_KEY = 'orbitools';

const config = {
    reducer: combineReducers(),
    actions: {
        ...modulesSlice.actions,
        ...settingsSlice.actions,
        ...uiSlice.actions,
    },
    selectors: {
        ...modulesSlice.selectors,
        ...settingsSlice.selectors,
        ...uiSlice.selectors,
    },
    resolvers: {
        ...modulesSlice.resolvers,
    },
    controls: {},
};

function combineReducers() {
    return (state: State | undefined, action: { type: string; [key: string]: unknown }): State => {
        return {
            modules: modulesSlice.reducer(state?.modules, action),
            settings: settingsSlice.reducer(state?.settings, action),
            ui: uiSlice.reducer(state?.ui, action),
        };
    };
}

let registered = false;

export function registerStore(): void {
    if (registered) {
        return;
    }
    const store = createReduxStore(STORE_KEY, config);
    register(store);
    registered = true;
}
