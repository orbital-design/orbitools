/**
 * Read per-module settings from the store.
 */
import { useSelect } from '@wordpress/data';
import { STORE_KEY } from '../store';
import type { ModuleSettings } from '../types';

interface UseSettingsResult {
    settings: ModuleSettings | undefined;
    isLoading: boolean;
    error: string | null;
}

interface SettingsSelectors {
    getSettings: (slug: string) => ModuleSettings | undefined;
    isLoadingSettings: (slug: string) => boolean;
    getSettingsError: (slug: string) => string | null;
}

export function useSettings(slug: string): UseSettingsResult {
    return useSelect(
        (select) => {
            const store = select(STORE_KEY) as unknown as SettingsSelectors;
            return {
                settings: store.getSettings(slug),
                isLoading: store.isLoadingSettings(slug),
                error: store.getSettingsError(slug),
            };
        },
        [slug]
    );
}
