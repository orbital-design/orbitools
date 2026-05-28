/**
 * Manifest-driven settings renderer.
 *
 * Pure rendering layer shared between module settings (ModuleSettingsBody)
 * and theme-page settings (ThemePageBody). Takes the schema + sections +
 * current values + an onChange callback; doesn't know anything about
 * where the data came from or where it's going.
 *
 * Layout decisions live here:
 *   - filter fields by show_if against current values
 *   - group fields by section
 *   - 2+ sections → sidebar layout; 0 or 1 section → flat
 *   - emit settings-before / settings-after slot fills around the body
 *
 * Adding a new sections-aware UI surface elsewhere = render this with
 * different `slug` / `fields` / `sections`.
 */
import { useState } from '@wordpress/element';
import {
    Panel,
    PanelBody,
    Slot,
    __experimentalVStack as VStack,
} from '@wordpress/components';
import { SettingsSection } from './SettingsSection';
import { FieldFallback } from './FieldFallback';
import { getFieldComponent } from '../fields/registry';
import { evaluateShowIf } from '../lib/showIf';
import { SLOTS } from '../lib/slots';
import type {
    FieldSchema,
    ModuleSettings,
    SectionDescriptor,
    SectionLayout,
} from '../types';

interface SettingsRendererProps {
    slug: string;
    fields: FieldSchema[];
    sections: SectionDescriptor[];
    settings: ModuleSettings;
    onChange: (key: string, value: unknown) => void;
    sectionLayout?: SectionLayout;
}

export function SettingsRenderer({
    slug,
    fields,
    sections,
    settings,
    onChange,
    sectionLayout = 'sidebar',
}: SettingsRendererProps): JSX.Element {
    const visibleFields = fields.filter((f) => evaluateShowIf(f.show_if, settings));
    const grouped = groupBySection(visibleFields, sections);

    const renderFields = (groupFields: FieldSchema[]): JSX.Element[] =>
        groupFields.map((field) => {
            const Component = getFieldComponent(String(field.type));
            const fieldValue =
                settings[field.id] !== undefined ? settings[field.id] : field.default;
            if (Component === null) {
                return <FieldFallback key={field.id} field={field} />;
            }
            return (
                <Component
                    key={field.id}
                    field={field}
                    value={fieldValue}
                    onChange={(next) => onChange(field.id, next)}
                />
            );
        });

    // Stacked layout applies regardless of section count — even one
    // section becomes a collapsible card. Sidebar layout needs 2+
    // sections to make sense and falls back to flat below.
    if (sectionLayout === 'stacked' && grouped.length >= 1 && grouped[0].section !== null) {
        return (
            <>
                <Slot name={SLOTS.settingsBefore(slug)} />
                <StackedSectionsLayout grouped={grouped} renderFields={renderFields} />
                <Slot name={SLOTS.settingsAfter(slug)} />
            </>
        );
    }

    if (grouped.length >= 2) {
        return (
            <>
                <Slot name={SLOTS.settingsBefore(slug)} />
                <SectionSidebarLayout grouped={grouped} renderFields={renderFields} />
                <Slot name={SLOTS.settingsAfter(slug)} />
            </>
        );
    }

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
                        {renderFields(group.fields)}
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

interface SectionSidebarLayoutProps {
    grouped: FieldGroup[];
    renderFields: (fields: FieldSchema[]) => JSX.Element[];
}

function SectionSidebarLayout({
    grouped,
    renderFields,
}: SectionSidebarLayoutProps): JSX.Element {
    const sectionId = (g: FieldGroup): string => g.section?.id ?? '__default__';
    const [activeId, setActiveId] = useState<string>(sectionId(grouped[0]));
    const activeGroup = grouped.find((g) => sectionId(g) === activeId) ?? grouped[0];

    return (
        <div className="orbitools-section-split">
            <aside className="orbitools-section-split__sidebar" aria-label="Sections">
                <ul className="orbitools-sidebar-list">
                    {grouped.map((group) => {
                        const id = sectionId(group);
                        const active = id === activeId;
                        const label = group.section?.title ?? 'General';
                        return (
                            <li key={id} className="orbitools-sidebar-list__item">
                                <button
                                    type="button"
                                    className={
                                        active
                                            ? 'orbitools-sidebar-list__link orbitools-sidebar-list__link--active'
                                            : 'orbitools-sidebar-list__link'
                                    }
                                    aria-current={active ? 'page' : undefined}
                                    onClick={() => setActiveId(id)}
                                >
                                    <span className="orbitools-sidebar-list__label">{label}</span>
                                </button>
                            </li>
                        );
                    })}
                </ul>
            </aside>
            <section className="orbitools-section-split__content">
                <header className="orbitools-section-split__content-header">
                    <h3 className="orbitools-section-split__content-title">
                        {activeGroup.section?.title ?? 'General'}
                    </h3>
                    {activeGroup.section?.description !== undefined && (
                        <p className="orbitools-section-split__content-description">
                            {activeGroup.section.description}
                        </p>
                    )}
                </header>
                <VStack spacing={4}>{renderFields(activeGroup.fields)}</VStack>
            </section>
        </div>
    );
}

/**
 * `stacked` layout — each section renders as its own collapsible
 * PanelBody, stacked vertically. All open by default; the user
 * collapses what they don't want to see. Modelled on RunCache's
 * "stacked cards per concern" pattern.
 */
function StackedSectionsLayout({
    grouped,
    renderFields,
}: SectionSidebarLayoutProps): JSX.Element {
    return (
        <Panel className="orbitools-section-stack">
            {grouped.map((group) => {
                const id = group.section?.id ?? '__default__';
                return (
                    <PanelBody
                        key={id}
                        title={group.section?.title ?? 'General'}
                        initialOpen={true}
                    >
                        {group.section?.description !== undefined && (
                            <p className="orbitools-section-stack__description">
                                {group.section.description}
                            </p>
                        )}
                        <VStack spacing={4}>{renderFields(group.fields)}</VStack>
                    </PanelBody>
                );
            })}
        </Panel>
    );
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
