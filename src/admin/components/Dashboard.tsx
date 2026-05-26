/**
 * Real dashboard. Phase 5.
 *
 * Shows a stats strip (modules enabled / total / per-category) and a
 * panel for module-contributed cards (the Phase 4 DASHBOARD_CARDS
 * slot). No actions live here yet — module management goes through
 * the Modules page; settings open via deep link.
 */
import { Card, CardBody, CardHeader, Slot } from '@wordpress/components';
import { useModules } from '../hooks/useModules';
import { LoadingState } from './LoadingState';
import { SLOTS } from '../lib/slots';
import { routes } from '../lib/router';
import type { Module, ModuleCategory } from '../types';

const CATEGORIES: { id: ModuleCategory; label: string }[] = [
    { id: 'blocks', label: 'Blocks' },
    { id: 'controls', label: 'Controls' },
    { id: 'modules', label: 'Modules' },
];

export function Dashboard(): JSX.Element {
    const { modules, isLoading, error } = useModules();

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

    const withSettings = modules.filter((m) => m.enabled && m.settings_schema.length > 0);

    return (
        <div className="orbitools-dashboard">
            <Card className="orbitools-dashboard__stats">
                <CardHeader>
                    <h2 className="orbitools-dashboard__heading">Overview</h2>
                </CardHeader>
                <CardBody>
                    <div className="orbitools-stat-grid">
                        {CATEGORIES.map((cat) => (
                            <Stat
                                key={cat.id}
                                label={cat.label}
                                value={renderCategoryCount(modules, cat.id)}
                            />
                        ))}
                    </div>
                </CardBody>
            </Card>

            {withSettings.length > 0 && (
                <Card className="orbitools-dashboard__settings">
                    <CardHeader>
                        <h2 className="orbitools-dashboard__heading">Configured modules</h2>
                    </CardHeader>
                    <CardBody>
                        <ul className="orbitools-link-list">
                            {withSettings.map((m) => (
                                <li key={m.slug}>
                                    <a href={routes.settings(m.slug)}>{m.name}</a>
                                    <span className="orbitools-link-list__meta">
                                        {m.settings_schema.length} setting
                                        {m.settings_schema.length === 1 ? '' : 's'}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    </CardBody>
                </Card>
            )}

            <Card className="orbitools-dashboard__extensions">
                <CardHeader>
                    <h2 className="orbitools-dashboard__heading">Discovered extensions</h2>
                </CardHeader>
                <CardBody>
                    <Slot name={SLOTS.DASHBOARD_CARDS} />
                </CardBody>
            </Card>
        </div>
    );
}

function Stat({ label, value }: { label: string; value: string }): JSX.Element {
    return (
        <div className="orbitools-stat">
            <div className="orbitools-stat__value">{value}</div>
            <div className="orbitools-stat__label">{label}</div>
        </div>
    );
}

function renderCategoryCount(modules: Module[], category: ModuleCategory): string {
    const matching = modules.filter((m) => m.category === category);
    const enabled = matching.filter((m) => m.enabled).length;
    return `${enabled} / ${matching.length}`;
}
