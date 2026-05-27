/**
 * Top-level navigation strip rendered inside AppChrome. Four anchors:
 * Dashboard, Blocks, Controls, Modules. Each pairs a small icon with
 * a label, RunCache-style, with a 2px primary-blue underline marking
 * the active tab.
 *
 * Active state on a settings page (`#settings/{slug}`) highlights the
 * tab matching the module's category — looked up in the store. While
 * modules are still loading, no tab is highlighted on settings routes.
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
    icon: () => JSX.Element;
    matches: (r: Route, activeCategory: ModuleCategory | null) => boolean;
}

// Lucide-style stroke icons, sized 20×20, currentColor — kept inline
// so the nav has no external icon-library dependency.
function DashboardIcon(): JSX.Element {
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            width="20"
            height="20"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="1.75"
            strokeLinecap="round"
            strokeLinejoin="round"
            aria-hidden="true"
            focusable="false"
        >
            <rect width="7" height="9" x="3" y="3" rx="1" />
            <rect width="7" height="5" x="14" y="3" rx="1" />
            <rect width="7" height="9" x="14" y="12" rx="1" />
            <rect width="7" height="5" x="3" y="16" rx="1" />
        </svg>
    );
}

function BlocksIcon(): JSX.Element {
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            width="20"
            height="20"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="1.75"
            strokeLinecap="round"
            strokeLinejoin="round"
            aria-hidden="true"
            focusable="false"
        >
            <rect width="7" height="7" x="3" y="3" rx="1" />
            <rect width="7" height="7" x="14" y="3" rx="1" />
            <rect width="7" height="7" x="14" y="14" rx="1" />
            <rect width="7" height="7" x="3" y="14" rx="1" />
        </svg>
    );
}

function ControlsIcon(): JSX.Element {
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            width="20"
            height="20"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="1.75"
            strokeLinecap="round"
            strokeLinejoin="round"
            aria-hidden="true"
            focusable="false"
        >
            <line x1="21" y1="6" x2="3" y2="6" />
            <line x1="21" y1="12" x2="3" y2="12" />
            <line x1="21" y1="18" x2="3" y2="18" />
            <circle cx="9" cy="6" r="2" fill="currentColor" />
            <circle cx="15" cy="12" r="2" fill="currentColor" />
            <circle cx="7" cy="18" r="2" fill="currentColor" />
        </svg>
    );
}

function ModulesIcon(): JSX.Element {
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            width="20"
            height="20"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="1.75"
            strokeLinecap="round"
            strokeLinejoin="round"
            aria-hidden="true"
            focusable="false"
        >
            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z" />
            <polyline points="3.27 6.96 12 12.01 20.73 6.96" />
            <line x1="12" y1="22.08" x2="12" y2="12" />
        </svg>
    );
}

const items: NavItem[] = [
    {
        label: 'Dashboard',
        href: routes.dashboard(),
        icon: DashboardIcon,
        matches: (r) => r.name === 'dashboard',
    },
    {
        label: 'Block Settings',
        href: routes.category('blocks'),
        icon: BlocksIcon,
        matches: (r, cat) =>
            (r.name === 'category' && r.category === 'blocks') || cat === 'blocks',
    },
    {
        label: 'Control Settings',
        href: routes.category('controls'),
        icon: ControlsIcon,
        matches: (r, cat) =>
            (r.name === 'category' && r.category === 'controls') || cat === 'controls',
    },
    {
        label: 'Module Settings',
        href: routes.category('modules'),
        icon: ModulesIcon,
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
                const Icon = item.icon;
                return (
                    <a
                        key={item.href}
                        href={item.href}
                        className={className}
                        aria-current={active ? 'page' : undefined}
                    >
                        <Icon />
                        <span>{item.label}</span>
                    </a>
                );
            })}
        </nav>
    );
}
