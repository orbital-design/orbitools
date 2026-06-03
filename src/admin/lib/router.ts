/**
 * Minimal hash router.
 *
 * Hash-based — no @wordpress/router dependency, no react-router. The
 * shape is intentionally tiny: a Route discriminated union and one
 * hook that reads window.location.hash and updates on hashchange.
 *
 * Route table:
 *   #                  → dashboard
 *   #blocks            → blocks category page (sidebar + first item)
 *   #blocks/{slug}     → blocks page with {slug} selected in sidebar
 *   #controls[/{slug}] → same, for controls
 *   #modules[/{slug}]  → same, for the 'modules' category
 *   #pages/{slug}      → theme-registered page (or built-in Site Settings)
 *   #settings/{slug}   → standalone module settings page (deep link)
 *
 * `#modules` refers to the 'modules' category specifically, not all
 * categories — the collision with the broader 'module' concept is
 * intentional and matches the user-facing label.
 *
 * Anything else falls back to dashboard.
 */
import { useEffect, useState } from '@wordpress/element';
import type { ModuleCategory } from '../types';

export type Route =
    | { name: 'dashboard' }
    | { name: 'category'; category: ModuleCategory; slug?: string }
    | { name: 'page'; slug: string }
    | { name: 'settings'; slug: string }
    | { name: 'tools' };

const CATEGORY_SLUGS: ModuleCategory[] = ['blocks', 'controls', 'modules', 'editor'];

export function parseHash(hash: string): Route {
    const cleaned = hash.replace(/^#\/?/, '');

    if (cleaned === 'tools') {
        return { name: 'tools' };
    }

    if (cleaned.startsWith('settings/')) {
        const slug = cleaned.slice('settings/'.length).split(/[?&]/)[0];
        if (slug !== undefined && slug !== '') {
            return { name: 'settings', slug };
        }
    }

    if (cleaned.startsWith('pages/')) {
        const slug = cleaned.slice('pages/'.length).split(/[?&]/)[0];
        if (slug !== undefined && slug !== '') {
            return { name: 'page', slug };
        }
    }

    const [head, tail] = cleaned.split('/', 2);
    if (head !== undefined && (CATEGORY_SLUGS as string[]).includes(head)) {
        return {
            name: 'category',
            category: head as ModuleCategory,
            slug: tail !== undefined && tail !== '' ? tail : undefined,
        };
    }

    return { name: 'dashboard' };
}

export function useHashRoute(): Route {
    const [route, setRoute] = useState<Route>(() => parseHash(window.location.hash));

    useEffect(() => {
        const handler = (): void => {
            setRoute(parseHash(window.location.hash));
        };
        window.addEventListener('hashchange', handler);
        return () => window.removeEventListener('hashchange', handler);
    }, []);

    return route;
}

/**
 * URL helpers — keep route construction in one place so the route
 * table is the single source of truth.
 */
export const routes = {
    dashboard: (): string => '#',
    category: (category: ModuleCategory): string => `#${category}`,
    categoryItem: (category: ModuleCategory, slug: string): string => `#${category}/${slug}`,
    page: (slug: string): string => `#pages/${slug}`,
    settings: (slug: string): string => `#settings/${slug}`,
    tools: (): string => '#tools',
};
