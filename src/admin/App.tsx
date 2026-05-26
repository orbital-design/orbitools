/**
 * Root component. Phase 2 shipped a placeholder dashboard; Phase 3
 * adds minimal hash routing so the manifest-driven SettingsPage is
 * reachable. Phase 4 hooks in the discovered admin-extension manifest
 * (a module's optional Page replaces the generic SettingsPage; its
 * optional Fills mount globally under SlotFillProvider). Phase 5 will
 * replace this with the full router and the real Dashboard / Modules
 * pages.
 *
 * Route table (hash-based):
 *   #                              → placeholder dashboard
 *   #settings/{slug}               → discovered[slug].Page (if any)
 *                                    else manifest-driven SettingsPage
 */
import { Slot, SlotFillProvider } from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import { AppChrome } from './components/AppChrome';
import { LoadingState } from './components/LoadingState';
import { SettingsPage } from './components/SettingsPage';
import { useModules } from './hooks/useModules';
import { SLOTS } from './lib/slots';
import { discovered } from './.generated/discovered';

interface Route {
    name: 'dashboard' | 'settings';
    slug?: string;
}

function parseHash(hash: string): Route {
    const cleaned = hash.replace(/^#\/?/, '');
    if (cleaned.startsWith('settings/')) {
        const slug = cleaned.slice('settings/'.length).split(/[?&]/)[0];
        if (slug !== undefined && slug !== '') {
            return { name: 'settings', slug };
        }
    }
    return { name: 'dashboard' };
}

function useHashRoute(): Route {
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

export function App(): JSX.Element {
    const route = useHashRoute();

    return (
        <SlotFillProvider>
            <DiscoveredFills />
            {route.name === 'settings' && route.slug !== undefined ? (
                <RoutedSettings slug={route.slug} />
            ) : (
                <AppChrome title="Orbitools">
                    <PlaceholderDashboard />
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

function PlaceholderDashboard(): JSX.Element {
    const { modules, isLoading, error } = useModules();

    if (isLoading) {
        return <LoadingState message="Loading modules…" />;
    }

    if (error !== null) {
        return (
            <div className="orbitools-error">
                <p>Failed to load modules: {error}</p>
            </div>
        );
    }

    const enabledCount = modules.filter((m) => m.enabled).length;
    const withSettings = modules.filter((m) => m.settings_schema.length > 0);

    return (
        <div className="orbitools-placeholder">
            <p>
                React admin shell is live. {enabledCount} of {modules.length} modules
                enabled. Real pages land in Phase 5.
            </p>
            {withSettings.length > 0 && (
                <>
                    <p style={{ marginTop: 16 }}>
                        Phase 3: modules with declared settings (click to open the
                        manifest-driven SettingsPage):
                    </p>
                    <ul>
                        {withSettings.map((m) => (
                            <li key={m.slug}>
                                <a href={`#settings/${m.slug}`}>{m.name}</a>{' '}
                                <code>({m.slug})</code> — {m.settings_schema.length} field(s)
                            </li>
                        ))}
                    </ul>
                </>
            )}
            <div className="orbitools-placeholder__discovered" style={{ marginTop: 16 }}>
                <p>Phase 4: discovered admin extensions (rendered via SlotFill):</p>
                <Slot name={SLOTS.DASHBOARD_CARDS} />
            </div>
        </div>
    );
}
