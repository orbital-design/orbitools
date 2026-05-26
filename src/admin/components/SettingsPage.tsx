/**
 * Generic, manifest-driven settings page.
 *
 * Reads the target module + its current settings from the store,
 * groups its declared fields by section, evaluates show_if to filter,
 * and renders each field via the registry. PATCH on change is wired
 * to the settings slice's optimistic updateSetting thunk.
 */
import { useDispatch, useSelect } from '@wordpress/data';
import { useEffect } from '@wordpress/element';
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

export function SettingsPage({ slug }: SettingsPageProps): JSX.Element {
    const { module, settings, isLoading, errorMessage } = useSelect(
        (select) => {
            const store = select(STORE_KEY) as unknown as StoreShape;
            // Touch getModules so its resolver fires when landing
            // directly on a settings page (no dashboard pre-render).
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

    // Touch the settings selector once so its resolver kicks off.
    useEffect(() => {
        // useSelect above already triggers the resolver via getSettings.
        // No-op effect; kept for clarity should we later add side effects.
    }, [slug]);

    if (isLoading && module === undefined) {
        return (
            <AppChrome title="Loading…">
                <LoadingState message="Loading module settings…" />
            </AppChrome>
        );
    }

    if (module === undefined) {
        return (
            <AppChrome title="Module not found">
                <Notice status="error" isDismissible={false}>
                    No module is registered with the slug <code>{slug}</code>.
                </Notice>
            </AppChrome>
        );
    }

    if (errorMessage !== null && errorMessage !== undefined && settings === undefined) {
        return (
            <AppChrome title={module.name}>
                <Notice status="error" isDismissible={false}>
                    Failed to load settings: {errorMessage}
                </Notice>
            </AppChrome>
        );
    }

    const currentSettings: ModuleSettings = settings ?? {};
    const fields = module.settings_schema ?? [];

    if (fields.length === 0) {
        return (
            <AppChrome title={module.name}>
                <Notice status="info" isDismissible={false}>
                    This module has no settings.
                </Notice>
            </AppChrome>
        );
    }

    const visibleFields = fields.filter((f) => evaluateShowIf(f.show_if, currentSettings));
    const grouped = groupBySection(visibleFields, module.sections);

    return (
        <AppChrome title={module.name}>
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
        </AppChrome>
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
