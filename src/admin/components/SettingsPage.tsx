/**
 * Generic, manifest-driven module settings page.
 *
 * SettingsPage = AppChrome wrapper + ModuleSettingsBody. The body
 * resolves data from the store and hands it to SettingsRenderer for
 * the actual rendering. CategoryPage embeds ModuleSettingsBody
 * directly inside its two-column layout (no second chrome).
 */
import { useDispatch, useSelect } from '@wordpress/data';
import { Notice } from '@wordpress/components';
import { AppChrome } from './AppChrome';
import { LoadingState } from './LoadingState';
import { SettingsRenderer } from './SettingsRenderer';
import { STORE_KEY } from '../store';
import type { Module, ModuleSettings } from '../types';

interface StoreShape {
    getModules: () => Module[];
    getModule: (slug: string) => Module | undefined;
    isLoadingModules: () => boolean;
    getSettings: (slug: string) => ModuleSettings | undefined;
    isLoadingSettings: (slug: string) => boolean;
    getModulesError: () => string | null;
    getSettingsError: (slug: string) => string | null;
}

interface StoreDispatch {
    updateSetting: (slug: string, key: string, value: unknown) => unknown;
}

interface SettingsPageProps {
    slug: string;
}

/**
 * Standalone settings page — used by the `#settings/{slug}` route.
 * Wraps the body in AppChrome with the module name (or a fallback
 * loading/not-found title).
 */
export function SettingsPage({ slug }: SettingsPageProps): JSX.Element {
    const { module, isLoading } = useSelect(
        (select) => {
            const store = select(STORE_KEY) as unknown as StoreShape;
            store.getModules();
            return {
                module: store.getModule(slug),
                isLoading: store.isLoadingModules(),
            };
        },
        [slug]
    );

    let title = 'Module Settings';
    if (isLoading && module === undefined) {
        title = 'Loading…';
    } else if (module === undefined) {
        title = 'Module not found';
    } else {
        title = module.name;
    }

    return (
        <AppChrome title={title}>
            <ModuleSettingsBody slug={slug} />
        </AppChrome>
    );
}

/**
 * Chrome-less body — renders the manifest-driven fields, or one of
 * the loading / not-found / error / no-settings states. Designed to
 * be embedded inside another page (CategoryPage's right pane) or
 * inside SettingsPage's own AppChrome.
 */
export function ModuleSettingsBody({ slug }: SettingsPageProps): JSX.Element {
    const { module, settings, isLoading, errorMessage } = useSelect(
        (select) => {
            const store = select(STORE_KEY) as unknown as StoreShape;
            store.getModules();
            return {
                module: store.getModule(slug),
                settings: store.getSettings(slug),
                isLoading: store.isLoadingModules() || store.isLoadingSettings(slug),
                errorMessage: store.getModulesError() ?? store.getSettingsError(slug),
            };
        },
        [slug]
    );

    const { updateSetting } = useDispatch(STORE_KEY) as unknown as StoreDispatch;

    if (isLoading && module === undefined) {
        return <LoadingState message="Loading module settings…" />;
    }

    if (module === undefined) {
        return (
            <Notice status="error" isDismissible={false}>
                No module is registered with the slug <code>{slug}</code>.
            </Notice>
        );
    }

    if (errorMessage !== null && errorMessage !== undefined && settings === undefined) {
        return (
            <Notice status="error" isDismissible={false}>
                Failed to load settings: {errorMessage}
            </Notice>
        );
    }

    const fields = module.settings_schema ?? [];
    if (fields.length === 0) {
        return (
            <Notice status="info" isDismissible={false}>
                This module has no settings.
            </Notice>
        );
    }

    return (
        <SettingsRenderer
            slug={slug}
            fields={fields}
            sections={module.sections}
            settings={settings ?? {}}
            onChange={(key, value) => updateSetting(slug, key, value)}
        />
    );
}
