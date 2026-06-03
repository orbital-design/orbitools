/**
 * Category settings page (Block / Control / Module Settings).
 *
 * Two-column layout:
 *
 *   ┌──────────────┬──────────────────────────────────┐
 *   │ Item A       │  Settings for whatever item       │
 *   │ Item B ⭢    │  is currently selected (route's    │
 *   │ Item C       │  optional slug). Falls back to    │
 *   │ Item D       │  the first item if no slug.       │
 *   └──────────────┴──────────────────────────────────┘
 *
 * Sidebar items act as vertical tabs — clicking one navigates to
 * `#{category}/{slug}` so the selection is part of the URL.
 *
 * The toggleModule thunk writes orbitools_settings via the REST API;
 * each sidebar row carries a small toggle as an at-a-glance affordance.
 */
import { Notice, Panel, PanelBody, ToggleControl } from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { useModules } from '../hooks/useModules';
import { LoadingState } from './LoadingState';
import { ModuleSettingsBody } from './SettingsPage';
import { BlockIcon } from './BlockIcon';
import { discovered } from '../.generated/discovered';
import { routes } from '../lib/router';
import { STORE_KEY } from '../store';
import type { Module, ModuleCategory } from '../types';

interface ModulesDispatch {
    toggleModule: (slug: string, enabled: boolean) => unknown;
}

interface CategoryPageProps {
    category: ModuleCategory;
    selectedSlug?: string;
}

const CATEGORY_META: Record<
    ModuleCategory,
    { label: string; singular: string; description: string }
> = {
    blocks: {
        label: 'Blocks',
        singular: 'block',
        description: 'Custom Gutenberg blocks shipped with Orbitools.',
    },
    editor: {
        label: 'Editor Settings',
        singular: 'item',
        description: 'Block editor cleanup, Block Manager, and the settings for every enabled Orbital block + control.',
    },
    controls: {
        label: 'Controls',
        singular: 'control',
        description: 'Editor-side controls injected into existing blocks.',
    },
    modules: {
        label: 'Modules',
        singular: 'module',
        description: 'Site-wide features and integrations.',
    },
};

/**
 * Virtual sidebar entries injected into the Editor tab. These don't
 * correspond to a module — they aggregate every enabled module of
 * another category into a single stacked-PanelBody view in the
 * content pane.
 */
const VIRTUAL_ENTRIES: ReadonlyArray<{
    slug: string;
    name: string;
    description: string;
    aggregate: ModuleCategory;
}> = [
    {
        slug: 'orbital-blocks',
        name: 'Orbital Blocks',
        description: 'Settings for every Orbital block currently enabled on the Dashboard.',
        aggregate: 'blocks',
    },
    {
        slug: 'orbital-controls',
        name: 'Orbital Controls',
        description: 'Settings for every Orbital control currently enabled on the Dashboard.',
        aggregate: 'controls',
    },
];

interface VirtualItem {
    kind: 'virtual';
    slug: string;
    name: string;
    description: string;
    aggregate: ModuleCategory;
}

interface ModuleItem {
    kind: 'module';
    module: Module;
}

type SidebarItem = ModuleItem | VirtualItem;

export function CategoryPage({ category, selectedSlug }: CategoryPageProps): JSX.Element {
    const { modules, isLoading, error } = useModules();
    const { toggleModule } = useDispatch(STORE_KEY) as unknown as ModulesDispatch;

    if (isLoading && modules.length === 0) {
        return <LoadingState message="Loading modules…" />;
    }

    if (error !== null) {
        return (
            <div className="orbitools-error">
                <p>Failed to load modules: {error}</p>
            </div>
        );
    }

    const moduleItems: ModuleItem[] = modules
        .filter((m) => m.category === category)
        .sort((a, b) => a.name.localeCompare(b.name))
        .map((module) => ({ kind: 'module' as const, module }));

    // Editor tab additionally surfaces virtual entries for the merged
    // Blocks + Controls categories so they live alongside Editor
    // Settings / Block Manager in the same sidebar.
    const virtualItems: VirtualItem[] =
        category === 'editor'
            ? VIRTUAL_ENTRIES.map((v) => ({ kind: 'virtual' as const, ...v }))
            : [];

    const items: SidebarItem[] = [...moduleItems, ...virtualItems];

    const meta = CATEGORY_META[category];

    if (items.length === 0) {
        return (
            <div className="orbitools-page">
                <header className="orbitools-section-header">
                    <h2 className="orbitools-section-header__title">{meta.label}</h2>
                    <p className="orbitools-section-header__subtitle">{meta.description}</p>
                </header>
                <Notice status="info" isDismissible={false}>
                    No {meta.label.toLowerCase()} are registered.
                </Notice>
            </div>
        );
    }

    const findItem = (slug: string | undefined): SidebarItem | undefined => {
        if (slug === undefined) return undefined;
        return items.find((it) =>
            it.kind === 'module' ? it.module.slug === slug : it.slug === slug,
        );
    };
    // No auto-selection: the right pane stays in placeholder state
    // until the user picks an item from the sidebar. If the URL
    // names an item that doesn't exist in this category we also
    // fall through to the placeholder.
    const activeItem = findItem(selectedSlug);
    const activeSlug = activeItem !== undefined ? selectedSlug : undefined;

    return (
        <div className="orbitools-page">
            <header className="orbitools-section-header">
                <h2 className="orbitools-section-header__title">{meta.label}</h2>
                <p className="orbitools-section-header__subtitle">{meta.description}</p>
            </header>

            <div className="orbitools-category-split">
                <aside className="orbitools-category-split__sidebar" aria-label={`${meta.label} list`}>
                    <ul className="orbitools-sidebar-list">
                        {items.map((it) => {
                            const slug = it.kind === 'module' ? it.module.slug : it.slug;
                            const active = slug === activeSlug;
                            if (it.kind === 'module') {
                                return (
                                    <SidebarRow
                                        key={slug}
                                        module={it.module}
                                        active={active}
                                        category={category}
                                        onToggle={(next) => toggleModule(it.module.slug, next)}
                                    />
                                );
                            }
                            return (
                                <VirtualSidebarRow
                                    key={slug}
                                    item={it}
                                    active={active}
                                    category={category}
                                />
                            );
                        })}
                    </ul>
                </aside>
                <section className="orbitools-category-split__content">
                    {activeItem !== undefined && activeSlug !== undefined ? (
                        <>
                            <header className="orbitools-category-split__content-header">
                                <h3 className="orbitools-category-split__content-title">
                                    {activeItem.kind === 'module'
                                        ? activeItem.module.name
                                        : activeItem.name}
                                </h3>
                                <p className="orbitools-category-split__content-description">
                                    {activeItem.kind === 'module'
                                        ? activeItem.module.description
                                        : activeItem.description}
                                </p>
                            </header>
                            {activeItem.kind === 'module' ? (
                                <CategoryItemBody slug={activeSlug} />
                            ) : (
                                <AggregateModulesPanel
                                    aggregate={activeItem.aggregate}
                                    allModules={modules}
                                />
                            )}
                        </>
                    ) : (
                        <div className="orbitools-category-split__empty">
                            <p>Select a {meta.singular} from the list to see its settings.</p>
                        </div>
                    )}
                </section>
            </div>
        </div>
    );
}

/**
 * Render whichever body the module wants — its discovered custom
 * Page if it ships one, the auto-rendered settings otherwise. Same
 * shape as App.tsx's RoutedSettings; the category and standalone
 * routes need to behave identically when a custom Page exists.
 */
function CategoryItemBody({ slug }: { slug: string }): JSX.Element {
    const CustomPage = discovered[slug]?.Page;
    if (CustomPage !== undefined) {
        return <CustomPage slug={slug} />;
    }
    return <ModuleSettingsBody slug={slug} />;
}

/**
 * Stacked-PanelBody view of every enabled module in a given category.
 * Used by the Editor tab's "Orbital Blocks" + "Orbital Controls"
 * virtual sidebar entries — clicking either renders all enabled items
 * in that category as collapsible panels in the right pane.
 *
 * Modules with no settings_schema still get a row so users can see
 * what's enabled; the body just shows the description in that case.
 */
function AggregateModulesPanel({
    aggregate,
    allModules,
}: {
    aggregate: ModuleCategory;
    allModules: Module[];
}): JSX.Element {
    const enabled = allModules
        .filter((m) => m.category === aggregate && m.enabled)
        .sort((a, b) => a.name.localeCompare(b.name));

    if (enabled.length === 0) {
        return (
            <Notice status="info" isDismissible={false}>
                Nothing enabled yet. Enable {aggregate === 'blocks' ? 'blocks' : 'controls'}{' '}
                from the Dashboard and they'll appear here.
            </Notice>
        );
    }

    return (
        <Panel className="orbitools-aggregate-panel">
            {enabled.map((mod) => (
                <PanelBody key={mod.slug} title={mod.name} initialOpen={false}>
                    <CategoryItemBody slug={mod.slug} />
                </PanelBody>
            ))}
        </Panel>
    );
}

interface VirtualSidebarRowProps {
    item: VirtualItem;
    active: boolean;
    category: ModuleCategory;
}

function VirtualSidebarRow({
    item,
    active,
    category,
}: VirtualSidebarRowProps): JSX.Element {
    return (
        <li className="orbitools-sidebar-list__item">
            <a
                className={
                    active
                        ? 'orbitools-sidebar-list__link orbitools-sidebar-list__link--active'
                        : 'orbitools-sidebar-list__link'
                }
                href={routes.categoryItem(category, item.slug)}
                aria-current={active ? 'page' : undefined}
            >
                <span className="orbitools-sidebar-list__label">
                    <span className="orbitools-sidebar-list__label-text">{item.name}</span>
                </span>
            </a>
        </li>
    );
}

interface SidebarRowProps {
    module: Module;
    active: boolean;
    category: ModuleCategory;
    onToggle: (enabled: boolean) => void;
}

function SidebarRow({ module: mod, active, category, onToggle }: SidebarRowProps): JSX.Element {
    // Toggle stops propagation so flipping enable doesn't also fire
    // the row's navigation. mousedown prevents the link's focus
    // ring from flashing as the toggle is clicked.
    const toggle = (
        <span
            className="orbitools-sidebar-list__toggle"
            onClick={(e) => e.stopPropagation()}
            onMouseDown={(e) => e.preventDefault()}
        >
            <ToggleControl
                label=""
                checked={mod.enabled}
                onChange={onToggle}
                __nextHasNoMarginBottom
            />
        </span>
    );
    const label = (
        <span className="orbitools-sidebar-list__label">
            {category === 'blocks' && (
                <span className="orbitools-sidebar-list__icon" aria-hidden="true">
                    <BlockIcon icon={mod.icon} />
                </span>
            )}
            <span className="orbitools-sidebar-list__label-text">{mod.name}</span>
        </span>
    );

    if (!mod.enabled) {
        // Disabled rows aren't navigable — render a non-link span
        // so the click target on the label is inert. The toggle
        // stays functional so the user can enable from here.
        return (
            <li className="orbitools-sidebar-list__item">
                <span
                    className="orbitools-sidebar-list__link orbitools-sidebar-list__link--disabled"
                    aria-disabled="true"
                >
                    {label}
                    {toggle}
                </span>
            </li>
        );
    }

    return (
        <li className="orbitools-sidebar-list__item">
            <a
                className={
                    active
                        ? 'orbitools-sidebar-list__link orbitools-sidebar-list__link--active'
                        : 'orbitools-sidebar-list__link'
                }
                href={routes.categoryItem(category, mod.slug)}
                aria-current={active ? 'page' : undefined}
            >
                {label}
                {toggle}
            </a>
        </li>
    );
}
