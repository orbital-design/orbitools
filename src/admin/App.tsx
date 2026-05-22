/**
 * Root component. Phase 2 ships a placeholder dashboard; Phase 5
 * fills in the real pages and the Router component.
 */
import { SlotFillProvider } from '@wordpress/components';
import { AppChrome } from './components/AppChrome';
import { LoadingState } from './components/LoadingState';
import { useModules } from './hooks/useModules';

export function App(): JSX.Element {
    return (
        <SlotFillProvider>
            <AppChrome title="Orbitools">
                <PlaceholderDashboard />
            </AppChrome>
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

    return (
        <div className="orbitools-placeholder">
            <p>
                React admin shell is live. {enabledCount} of {modules.length} modules
                enabled. Real pages land in Phase 5.
            </p>
        </div>
    );
}
