/**
 * Read modules + their loading/error state from the store.
 *
 * Wrapping useSelect keeps page components from importing the store
 * key string directly.
 */
import { useSelect } from '@wordpress/data';
import { STORE_KEY } from '../store';
import type { Module } from '../types';

interface UseModulesResult {
    modules: Module[];
    isLoading: boolean;
    error: string | null;
}

interface ModulesSelectors {
    getModules: () => Module[];
    isLoadingModules: () => boolean;
    getModulesError: () => string | null;
}

export function useModules(): UseModulesResult {
    return useSelect(
        (select) => {
            const store = select(STORE_KEY) as unknown as ModulesSelectors;
            return {
                modules: store.getModules(),
                isLoading: store.isLoadingModules(),
                error: store.getModulesError(),
            };
        },
        []
    );
}
