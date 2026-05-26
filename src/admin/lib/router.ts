/**
 * Minimal hash router.
 *
 * Hash-based — no @wordpress/router dependency, no react-router. The
 * shape is intentionally tiny: a Route discriminated union and one
 * hook that reads window.location.hash and updates on hashchange.
 *
 * Route table:
 *   #             → dashboard
 *   #blocks       → blocks category page
 *   #controls     → controls category page
 *   #modules      → modules category page (NOT all categories — just
 *                   the 'modules' category. The collision with the
 *                   broader 'module' concept is intentional: matches
 *                   the user-facing label.)
 *   #settings/X   → settings page for module slug X
 *
 * Anything else falls back to dashboard.
 */
import { useEffect, useState } from '@wordpress/element';
import type { ModuleCategory } from '../types';

export type Route =
    | { name: 'dashboard' }
    | { name: 'category'; category: ModuleCategory }
    | { name: 'settings'; slug: string };

const CATEGORY_SLUGS: ModuleCategory[] = ['blocks', 'controls', 'modules'];

export function parseHash(hash: string): Route {
    const cleaned = hash.replace(/^#\/?/, '');

    if ((CATEGORY_SLUGS as string[]).includes(cleaned)) {
        return { name: 'category', category: cleaned as ModuleCategory };
    }

    if (cleaned.startsWith('settings/')) {
        const slug = cleaned.slice('settings/'.length).split(/[?&]/)[0];
        if (slug !== undefined && slug !== '') {
            return { name: 'settings', slug };
        }
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
    settings: (slug: string): string => `#settings/${slug}`,
};
