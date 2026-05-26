/**
 * Top-level navigation strip rendered by AppChrome. Four anchors:
 * Dashboard, Blocks, Controls, Modules. Drives the hash router.
 *
 * Active state on a settings page (`#settings/{slug}`) highlights the
 * tab matching the module's category — we look up the module in the
 * store to find out which one. While modules are still loading, no
 * tab is highlighted on settings routes (acceptable transient state).
 */
import { useSelect } from '@wordpress/data';
import { routes, type Route } from '../lib/router';
import { STORE_KEY } from '../store';
import type { Module, ModuleCategory } from '../types';

interface TopNavProps {
    route: Route;
}

interface NavItem {
    label: string;
    href: string;
    matches: (r: Route, activeCategory: ModuleCategory | null) => boolean;
}

const items: NavItem[] = [
    {
        label: 'Dashboard',
        href: routes.dashboard(),
        matches: (r) => r.name === 'dashboard',
    },
    {
        label: 'Blocks',
        href: routes.category('blocks'),
        matches: (r, cat) =>
            (r.name === 'category' && r.category === 'blocks') || cat === 'blocks',
    },
    {
        label: 'Controls',
        href: routes.category('controls'),
        matches: (r, cat) =>
            (r.name === 'category' && r.category === 'controls') || cat === 'controls',
    },
    {
        label: 'Modules',
        href: routes.category('modules'),
        matches: (r, cat) =>
            (r.name === 'category' && r.category === 'modules') || cat === 'modules',
    },
];

interface ModulesSelector {
    getModule: (slug: string) => Module | undefined;
}

export function TopNav({ route }: TopNavProps): JSX.Element {
    const activeCategory = useSelect(
        (select) => {
            if (route.name !== 'settings') {
                return null;
            }
            const store = select(STORE_KEY) as unknown as ModulesSelector;
            return store.getModule(route.slug)?.category ?? null;
        },
        [route]
    );

    return (
        <nav className="orbitools-nav" aria-label="Orbitools sections">
            {items.map((item) => {
                const active = item.matches(route, activeCategory);
                const className = active
                    ? 'orbitools-nav__link orbitools-nav__link--active'
                    : 'orbitools-nav__link';
                return (
                    <a
                        key={item.href}
                        href={item.href}
                        className={className}
                        aria-current={active ? 'page' : undefined}
                    >
                        {item.label}
                    </a>
                );
            })}
        </nav>
    );
}
