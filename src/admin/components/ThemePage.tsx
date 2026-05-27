/**
 * Theme-registered page (or built-in Site Settings).
 *
 * Page metadata (label, description, fields, sections) comes from
 * the bootstrap window.orbitools.themePages list; per-field values
 * flow through the same Settings store used by modules — the REST
 * layer doesn't distinguish module slugs from page slugs.
 *
 * Rendering reuses SettingsRenderer, so any layout improvements
 * (section sidebar etc.) automatically apply here.
 */
import { useDispatch, useSelect } from '@wordpress/data';
import { Notice } from '@wordpress/components';
import { LoadingState } from './LoadingState';
import { SettingsRenderer } from './SettingsRenderer';
import { getThemePage } from '../lib/themePages';
import { STORE_KEY } from '../store';
import type { ModuleSettings } from '../types';

interface StoreShape {
    getSettings: (slug: string) => ModuleSettings | undefined;
    isLoadingSettings: (slug: string) => boolean;
    getSettingsError: (slug: string) => string | null;
}

interface StoreDispatch {
    updateSetting: (slug: string, key: string, value: unknown) => unknown;
}

interface ThemePageProps {
    slug: string;
}

export function ThemePage({ slug }: ThemePageProps): JSX.Element {
    const page = getThemePage(slug);

    const { settings, isLoading, errorMessage } = useSelect(
        (select) => {
            const store = select(STORE_KEY) as unknown as StoreShape;
            return {
                settings: store.getSettings(slug),
                isLoading: store.isLoadingSettings(slug),
                errorMessage: store.getSettingsError(slug),
            };
        },
        [slug]
    );

    const { updateSetting } = useDispatch(STORE_KEY) as unknown as StoreDispatch;

    if (page === undefined) {
        return (
            <div className="orbitools-page">
                <Notice status="error" isDismissible={false}>
                    No theme page is registered with the slug <code>{slug}</code>.
                </Notice>
            </div>
        );
    }

    if (isLoading && settings === undefined) {
        return (
            <div className="orbitools-page">
                <LoadingState message={`Loading ${page.label}…`} />
            </div>
        );
    }

    if (errorMessage !== null && errorMessage !== undefined && settings === undefined) {
        return (
            <div className="orbitools-page">
                <Notice status="error" isDismissible={false}>
                    Failed to load settings: {errorMessage}
                </Notice>
            </div>
        );
    }

    return (
        <div className="orbitools-page">
            <header className="orbitools-section-header">
                <h2 className="orbitools-section-header__title">{page.label}</h2>
                {page.description !== '' && (
                    <p className="orbitools-section-header__subtitle">{page.description}</p>
                )}
            </header>
            {page.settings_schema.length === 0 ? (
                <Notice status="info" isDismissible={false}>
                    This page has no settings yet.
                </Notice>
            ) : (
                <SettingsRenderer
                    slug={slug}
                    fields={page.settings_schema}
                    sections={page.sections}
                    settings={settings ?? {}}
                    onChange={(key, value) => updateSetting(slug, key, value)}
                />
            )}
        </div>
    );
}
