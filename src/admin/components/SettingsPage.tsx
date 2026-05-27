/**
 * Generic, manifest-driven settings page.
 *
 * SettingsPage = AppChrome wrapper + ModuleSettingsBody. The body is
 * exported separately so CategoryPage can embed it inside its
 * two-column layout without nesting a second chrome.
 *
 * Reads the target module + its current settings from the store,
 * groups its declared fields by section, evaluates show_if to filter,
 * and renders each field via the registry. PATCH on change is wired
 * to the settings slice's optimistic updateSetting thunk.
 */
import { useDispatch, useSelect } from '@wordpress/data';
import {
    Notice,
    Slot,
    // VStack is only exported under the experimental name in
    // @wordpress/components; importing it as plain `VStack` resolves
    // to undefined at runtime and renders as <undefined /> (React #130).
    __experimentalVStack as VStack,
} from '@wordpress/components';
import { AppChrome } from './AppChrome';
import { LoadingState } from './LoadingState';
import { SettingsSection } from './SettingsSection';
import { FieldFallback } from './FieldFallback';
import { getFieldComponent } from '../fields/registry';
import { evaluateShowIf } from '../lib/showIf';
import { SLOTS } from '../lib/slots';
import { STORE_KEY } from '../store';
import type { FieldSchema, Module, ModuleSettings, SectionDescriptor } from '../types';

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

    const currentSettings: ModuleSettings = settings ?? {};
    const fields = module.settings_schema ?? [];

    if (fields.length === 0) {
        return (
            <Notice status="info" isDismissible={false}>
                This module has no settings.
            </Notice>
        );
    }

    const visibleFields = fields.filter((f) => evaluateShowIf(f.show_if, currentSettings));
    const grouped = groupBySection(visibleFields, module.sections);

    return (
        <>
            <Slot name={SLOTS.settingsBefore(slug)} />
            <VStack spacing={4}>
                {grouped.map((group) => (
                    <SettingsSection
                        key={group.section?.id ?? '__default__'}
                        title={group.section?.title}
                        description={group.section?.description}
                    >
                        {group.fields.map((field) => {
                            const Component = getFieldComponent(String(field.type));
                            const fieldValue =
                                currentSettings[field.id] !== undefined
                                    ? currentSettings[field.id]
                                    : field.default;
                            if (Component === null) {
                                return <FieldFallback key={field.id} field={field} />;
                            }
                            return (
                                <Component
                                    key={field.id}
                                    field={field}
                                    value={fieldValue}
                                    onChange={(next) => updateSetting(slug, field.id, next)}
                                />
                            );
                        })}
                    </SettingsSection>
                ))}
            </VStack>
            <Slot name={SLOTS.settingsAfter(slug)} />
        </>
    );
}

interface FieldGroup {
    section: SectionDescriptor | null;
    fields: FieldSchema[];
}

function groupBySection(fields: FieldSchema[], sections: SectionDescriptor[]): FieldGroup[] {
    if (sections.length === 0) {
        return [{ section: null, fields }];
    }

    const groups: FieldGroup[] = sections.map((s) => ({ section: s, fields: [] }));
    const fallback: FieldGroup = { section: null, fields: [] };
    const sectionIds = new Set(sections.map((s) => s.id));

    for (const field of fields) {
        if (field.section !== undefined && sectionIds.has(field.section)) {
            const group = groups.find((g) => g.section?.id === field.section);
            if (group !== undefined) {
                group.fields.push(field);
                continue;
            }
        }
        fallback.fields.push(field);
    }

    if (fallback.fields.length > 0) {
        groups.push(fallback);
    }
    return groups.filter((g) => g.fields.length > 0);
}
