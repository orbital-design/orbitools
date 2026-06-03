/**
 * Top-level navigation strip rendered inside AppChrome. Renders the
 * four built-in tabs plus any theme-registered pages, all merged
 * into one list sorted by position. Each pairs a small icon with a
 * label, RunCache-style, with a 2px primary-blue underline marking
 * the active tab.
 *
 * Active state on a `#settings/{slug}` route highlights the tab
 * matching the module's category — looked up in the store. Theme
 * pages route to `#pages/{slug}` and highlight via slug match.
 */
import { useSelect } from '@wordpress/data';
import { routes, type Route } from '../lib/router';
import { STORE_KEY } from '../store';
import { getThemePages } from '../lib/themePages';
import type { Module, ModuleCategory } from '../types';

interface TopNavProps {
    route: Route;
}

interface NavItem {
    label: string;
    href: string;
    icon: () => JSX.Element;
    position: number;
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

function EditorIcon(): JSX.Element {
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
            <path d="M12 20h9" />
            <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4Z" />
        </svg>
    );
}

function ToolsIcon(): JSX.Element {
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
            <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z" />
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

// Lightweight icon for unrecognised theme-page `icon` strings. Page
// authors can ship a dashicons handle or inline SVG via the manifest
// — for v1 we just render a neutral fallback regardless and theme
// authors can iterate later.
function PageIcon(): JSX.Element {
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
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
            <polyline points="14 2 14 8 20 8" />
        </svg>
    );
}

const builtins: NavItem[] = [
    {
        label: 'Dashboard',
        href: routes.dashboard(),
        icon: DashboardIcon,
        position: 0,
        matches: (r) => r.name === 'dashboard',
    },
    {
        // Block Settings + Control Settings are no longer top-level
        // tabs — they live as virtual entries inside the Editor tab's
        // sidebar (see CategoryPage's editor-specific branch). The
        // legacy `#blocks` / `#controls` routes still work for any
        // bookmarks pointing at them.
        label: 'Editor Settings',
        href: routes.category('editor'),
        icon: EditorIcon,
        position: 85,
        matches: (r, cat) =>
            (r.name === 'category' && (r.category === 'editor' || r.category === 'blocks' || r.category === 'controls')) ||
            cat === 'editor' ||
            cat === 'blocks' ||
            cat === 'controls',
    },
    {
        label: 'Module Settings',
        href: routes.category('modules'),
        icon: ModulesIcon,
        position: 100,
        matches: (r, cat) =>
            (r.name === 'category' && r.category === 'modules') || cat === 'modules',
    },
    {
        label: 'Tools',
        href: routes.tools(),
        icon: ToolsIcon,
        position: 110,
        matches: (r) => r.name === 'tools',
    },
];

/**
 * Merge built-in tabs with theme-page tabs, sorted by position. Ties
 * keep registration order so themes registering at the same position
 * land in filter order.
 */
function buildNavItems(): NavItem[] {
    const pageItems: NavItem[] = getThemePages().map((page) => ({
        label: page.label,
        href: routes.page(page.slug),
        icon: PageIcon,
        position: page.position,
        matches: (r) => r.name === 'page' && r.slug === page.slug,
    }));
    const merged = [...builtins, ...pageItems];
    return merged
        .map((item, idx) => ({ item, idx }))
        .sort((a, b) => a.item.position - b.item.position || a.idx - b.idx)
        .map(({ item }) => item);
}

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

    const items = buildNavItems();

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
