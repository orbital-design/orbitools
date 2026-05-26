/**
 * Category-filtered listing page. Phase 5.
 *
 * One tab per ModuleCategory — Blocks / Controls / Modules — each
 * renders this component with a different `category` prop. Shows a
 * grid of ModuleCards, each with an enable toggle and a Settings
 * deep-link. The toggleModule thunk writes the same orbitools_settings
 * option AdminKit reads, so the parallel-running AdminKit pages stay
 * in sync until Phase 7 retires that side entirely.
 */
import {
    Button,
    Card,
    CardBody,
    CardHeader,
    ToggleControl,
} from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { useModules } from '../hooks/useModules';
import { LoadingState } from './LoadingState';
import { routes } from '../lib/router';
import { STORE_KEY } from '../store';
import type { Module, ModuleCategory } from '../types';

interface ModulesDispatch {
    toggleModule: (slug: string, enabled: boolean) => unknown;
}

interface CategoryMeta {
    label: string;
    description: string;
}

const CATEGORY_META: Record<ModuleCategory, CategoryMeta> = {
    blocks: {
        label: 'Blocks',
        description: 'Custom Gutenberg blocks shipped with Orbitools.',
    },
    controls: {
        label: 'Controls',
        description: 'Editor-side controls injected into existing blocks.',
    },
    modules: {
        label: 'Modules',
        description: 'Site-wide features and integrations.',
    },
};

interface CategoryPageProps {
    category: ModuleCategory;
}

export function CategoryPage({ category }: CategoryPageProps): JSX.Element {
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

    return (
        <div className="orbitools-modules">
            <Card className="orbitools-modules__section">
                <CardHeader>
                    <div>
                        <h2 className="orbitools-modules__heading">{meta.label}</h2>
                        <p className="orbitools-modules__description">{meta.description}</p>
                    </div>
                </CardHeader>
                <CardBody>
                    {items.length === 0 ? (
                        <p className="orbitools-modules__empty">
                            No {meta.label.toLowerCase()} are registered.
                        </p>
                    ) : (
                        <ul className="orbitools-modules__grid">
                            {items.map((mod) => (
                                <li key={mod.slug} className="orbitools-modules__item">
                                    <ModuleCard
                                        module={mod}
                                        onToggle={(next) => toggleModule(mod.slug, next)}
                                    />
                                </li>
                            ))}
                        </ul>
                    )}
                </CardBody>
            </Card>
        </div>
    );
}

interface ModuleCardProps {
    module: Module;
    onToggle: (enabled: boolean) => void;
}

function ModuleCard({ module: mod, onToggle }: ModuleCardProps): JSX.Element {
    const hasSettings = mod.settings_schema.length > 0;
    return (
        <div className="orbitools-module-card">
            <div className="orbitools-module-card__head">
                <h3 className="orbitools-module-card__title">{mod.name}</h3>
                <ToggleControl
                    label=""
                    checked={mod.enabled}
                    onChange={onToggle}
                    __nextHasNoMarginBottom
                />
            </div>
            <p className="orbitools-module-card__description">{mod.description}</p>
            <div className="orbitools-module-card__meta">
                <code className="orbitools-module-card__slug">{mod.slug}</code>
                {hasSettings && (
                    <Button
                        variant="secondary"
                        size="small"
                        href={routes.settings(mod.slug)}
                        disabled={!mod.enabled}
                    >
                        Settings
                    </Button>
                )}
            </div>
        </div>
    );
}
