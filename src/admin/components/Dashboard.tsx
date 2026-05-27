/**
 * Real dashboard. Phase 5.
 *
 * Layout, modeled on RunCache:
 *   1. Overview row: master toggle card + per-category counts box
 *   2. Sub-tab strip (Blocks / Controls / Modules) — same pattern as
 *      RunCache's Full Page Cache / Asset CDN / Object Cache tabs.
 *      The active sub-tab lists every module in that category with
 *      an inline enable toggle.
 *   3. Configured-modules quick links + Phase 4 dashboard slot.
 *
 * Top-level Blocks / Controls / Modules tabs in TopNav still route
 * to CategoryPage; that's intentional duplication for now — the
 * Dashboard sub-tabs are a quick-access surface, the top tabs are
 * deeper drill-downs (per-item settings live there).
 */
import { ToggleControl } from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { useState } from '@wordpress/element';
import { useModules } from '../hooks/useModules';
import { LoadingState } from './LoadingState';
import { routes } from '../lib/router';
import { STORE_KEY } from '../store';
import { categoryIcon } from './category-icons';
import type { Module, ModuleCategory } from '../types';

const CATEGORIES: { id: ModuleCategory; label: string }[] = [
    { id: 'blocks', label: 'Blocks' },
    { id: 'controls', label: 'Controls' },
    { id: 'modules', label: 'Modules' },
];

interface ModulesDispatch {
    toggleModule: (slug: string, enabled: boolean) => unknown;
}

export function Dashboard(): JSX.Element {
    const { modules, isLoading, error } = useModules();
    const { toggleModule } = useDispatch(STORE_KEY) as unknown as ModulesDispatch;
    const [subTab, setSubTab] = useState<ModuleCategory>('blocks');

    if (isLoading && modules.length === 0) {
        return <LoadingState message="Loading dashboard…" />;
    }

    if (error !== null) {
        return (
            <div className="orbitools-error">
                <p>Failed to load modules: {error}</p>
            </div>
        );
    }

    const subTabItems = modules
        .filter((m) => m.category === subTab)
        .sort((a, b) => a.name.localeCompare(b.name));

    return (
        <div className="orbitools-dashboard">
            <header className="orbitools-section-header">
                <h2 className="orbitools-section-header__title">Modules Manager</h2>
                <p className="orbitools-section-header__subtitle">
                    Enable or disable individual blocks, controls, and modules.
                </p>
            </header>

            <div className="orbitools-subtab-card">
                <div className="orbitools-subtabs" role="tablist" aria-label="Module categories">
                    {CATEGORIES.map((cat) => {
                        const active = subTab === cat.id;
                        const Icon = categoryIcon[cat.id];
                        const inCategory = modules.filter((m) => m.category === cat.id);
                        const enabled = inCategory.filter((m) => m.enabled).length;
                        return (
                            <button
                                key={cat.id}
                                type="button"
                                role="tab"
                                aria-selected={active}
                                className={
                                    active
                                        ? 'orbitools-subtabs__tab orbitools-subtabs__tab--active'
                                        : 'orbitools-subtabs__tab'
                                }
                                onClick={() => setSubTab(cat.id)}
                            >
                                <span className="orbitools-subtabs__icon" aria-hidden="true">
                                    <Icon size={22} />
                                </span>
                                <span className="orbitools-subtabs__name">{cat.label}</span>
                                <span className="orbitools-subtabs__count">
                                    {enabled} / {inCategory.length}
                                </span>
                            </button>
                        );
                    })}
                </div>

                <div className="orbitools-subtab-panel" role="tabpanel">
                    {subTabItems.length === 0 ? (
                        <p className="orbitools-subtab-panel__empty">
                            No {CATEGORIES.find((c) => c.id === subTab)?.label.toLowerCase()} are
                            registered.
                        </p>
                    ) : (
                        <ul className="orbitools-item-grid">
                            {subTabItems.map((mod) => (
                                <ItemCard
                                    key={mod.slug}
                                    module={mod}
                                    onToggle={(next) => toggleModule(mod.slug, next)}
                                />
                            ))}
                        </ul>
                    )}
                </div>
            </div>
        </div>
    );
}

interface ItemCardProps {
    module: Module;
    onToggle: (enabled: boolean) => void;
}

function ItemCard({ module: mod, onToggle }: ItemCardProps): JSX.Element {
    const hasSettings = mod.settings_schema.length > 0;
    return (
        <li className="orbitools-item-grid__cell">
            <div className="orbitools-item-card">
                <div className="orbitools-item-card__head">
                    <ToggleControl
                        label=""
                        checked={mod.enabled}
                        onChange={onToggle}
                        __nextHasNoMarginBottom
                    />
                    <h3 className="orbitools-item-card__title">{mod.name}</h3>
                </div>
                <p className="orbitools-item-card__description">{mod.description}</p>
                {hasSettings && (
                    <a className="orbitools-item-card__settings" href={routes.settings(mod.slug)}>
                        Settings →
                    </a>
                )}
            </div>
        </li>
    );
}
