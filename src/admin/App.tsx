/**
 * Root component.
 *
 * AppChrome owns the header + section nav; each routed page renders
 * its own content well inside it.
 *
 * Route table (hash-based, see lib/router.ts):
 *   #             → Dashboard
 *   #blocks       → CategoryPage('blocks')
 *   #controls     → CategoryPage('controls')
 *   #modules      → CategoryPage('modules')
 *   #pages/{X}    → ThemePage (theme-registered or built-in Site Settings)
 *   #settings/{X} → discovered[X].Page (if any) else SettingsPage
 */
import { SlotFillProvider } from '@wordpress/components';
import { AppChrome } from './components/AppChrome';
import { CategoryPage } from './components/CategoryPage';
import { Dashboard } from './components/Dashboard';
import { SettingsPage } from './components/SettingsPage';
import { ThemePage } from './components/ThemePage';
import { useHashRoute } from './lib/router';
import { discovered } from './.generated/discovered';
import { getThemePage } from './lib/themePages';
import type { ModuleCategory } from './types';

const CATEGORY_TITLES: Record<ModuleCategory, string> = {
    blocks: 'Block Settings',
    controls: 'Control Settings',
    modules: 'Module Settings',
};

export function App(): JSX.Element {
    const route = useHashRoute();

    return (
        <SlotFillProvider>
            <DiscoveredFills />
            {route.name === 'settings' ? (
                <RoutedSettings slug={route.slug} />
            ) : route.name === 'page' ? (
                <AppChrome title={getThemePage(route.slug)?.label ?? 'Page'}>
                    <ThemePage slug={route.slug} />
                </AppChrome>
            ) : route.name === 'category' ? (
                <AppChrome title={CATEGORY_TITLES[route.category]}>
                    <CategoryPage category={route.category} selectedSlug={route.slug} />
                </AppChrome>
            ) : (
                <AppChrome title="Orbitools">
                    <Dashboard />
                </AppChrome>
            )}
        </SlotFillProvider>
    );
}

/**
 * Route the settings page. If the module ships a custom Page via
 * src/admin/modules/{slug}/index.tsx, render it; otherwise fall back
 * to the manifest-driven SettingsPage.
 */
function RoutedSettings({ slug }: { slug: string }): JSX.Element {
    const CustomPage = discovered[slug]?.Page;
    if (CustomPage !== undefined) {
        return <CustomPage slug={slug} />;
    }
    return <SettingsPage slug={slug} />;
}

/**
 * Mount every discovered module's Fills component inside the
 * SlotFillProvider. Each module's Fills component is expected to
 * return one or more <Fill> elements; rendering them here once,
 * outside the routed page, keeps the fills alive across route
 * changes so dashboard/sidebar contributions don't unmount.
 */
function DiscoveredFills(): JSX.Element {
    return (
        <>
            {Object.entries(discovered).map(([slug, ext]) => {
                if (ext.Fills === undefined) {
                    return null;
                }
                const Fills = ext.Fills;
                return <Fills key={slug} />;
            })}
        </>
    );
}
