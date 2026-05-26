/**
 * Root component. Phase 2 shipped a placeholder dashboard; Phase 3
 * adds minimal hash routing so the manifest-driven SettingsPage is
 * reachable. Phase 5 will replace this with the full router and the
 * real Dashboard / Modules pages.
 *
 * Route table (hash-based):
 *   #                              → placeholder dashboard
 *   #settings/{slug}               → manifest-driven settings page
 */
import { SlotFillProvider } from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import { AppChrome } from './components/AppChrome';
import { LoadingState } from './components/LoadingState';
import { SettingsPage } from './components/SettingsPage';
import { useModules } from './hooks/useModules';

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
            {route.name === 'settings' && route.slug !== undefined ? (
                <SettingsPage slug={route.slug} />
            ) : (
                <AppChrome title="Orbitools">
                    <PlaceholderDashboard />
                </AppChrome>
            )}
        </SlotFillProvider>
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
        </div>
    );
}
