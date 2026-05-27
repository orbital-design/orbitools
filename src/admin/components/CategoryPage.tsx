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
import { Notice, ToggleControl } from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { useModules } from '../hooks/useModules';
import { LoadingState } from './LoadingState';
import { ModuleSettingsBody } from './SettingsPage';
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

    const items = modules
        .filter((m) => m.category === category)
        .sort((a, b) => a.name.localeCompare(b.name));

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

    // No auto-selection: the right pane stays in placeholder state
    // until the user picks an item from the sidebar. If the URL
    // names an item that doesn't exist in this category we also
    // fall through to the placeholder.
    const activeSlug =
        selectedSlug !== undefined && items.some((m) => m.slug === selectedSlug)
            ? selectedSlug
            : undefined;
    const activeItem =
        activeSlug !== undefined ? items.find((m) => m.slug === activeSlug) : undefined;

    return (
        <div className="orbitools-page">
            <header className="orbitools-section-header">
                <h2 className="orbitools-section-header__title">{meta.label}</h2>
                <p className="orbitools-section-header__subtitle">{meta.description}</p>
            </header>

            <div className="orbitools-category-split">
                <aside className="orbitools-category-split__sidebar" aria-label={`${meta.label} list`}>
                    <ul className="orbitools-sidebar-list">
                        {items.map((mod) => (
                            <SidebarRow
                                key={mod.slug}
                                module={mod}
                                active={mod.slug === activeSlug}
                                category={category}
                                onToggle={(next) => toggleModule(mod.slug, next)}
                            />
                        ))}
                    </ul>
                </aside>
                <section className="orbitools-category-split__content">
                    {activeItem !== undefined && activeSlug !== undefined ? (
                        <>
                            <header className="orbitools-category-split__content-header">
                                <h3 className="orbitools-category-split__content-title">
                                    {activeItem.name}
                                </h3>
                                <p className="orbitools-category-split__content-description">
                                    {activeItem.description}
                                </p>
                            </header>
                            <ModuleSettingsBody slug={activeSlug} />
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
    const label = <span className="orbitools-sidebar-list__label">{mod.name}</span>;

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
