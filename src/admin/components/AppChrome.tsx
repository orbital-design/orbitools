/**
 * Standard page wrapper used by every page in the admin app.
 *
 * Layout, modeled on RunCache:
 *
 *   ┌─ blue 4px accent ─────────────────────────────────────┐
 *   │ white chrome band (full width)                         │
 *   │   ┌─ max-w 1280px ──────────────────────────────────┐ │
 *   │   │ [logo] Orbitools             [status][version]  │ │
 *   │   │ by Orbital Design                                │ │
 *   │   │ ───────────────────────────────────────────────  │ │
 *   │   │ ▣ Dashboard  ◫ Blocks  ⛭ Controls  ⬡ Modules    │ │
 *   │   └──────────────────────────────────────────────────┘ │
 *   └────────────────────────────────────────────────────────┘
 *   ┌─ admin gray background ───────────────────────────────┐
 *   │   ┌─ max-w 1280px ──────────────────────────────────┐ │
 *   │   │ {page content}                                   │ │
 *   │   └──────────────────────────────────────────────────┘ │
 *
 * The page title is rendered visually-hidden — the active tab in
 * TopNav names the page, but assistive tech still needs a heading.
 */
import { Slot, VisuallyHidden } from '@wordpress/components';
import type { ReactNode } from 'react';
import { SLOTS } from '../lib/slots';
import { useHashRoute } from '../lib/router';
import { useModules } from '../hooks/useModules';
import { BrandMark } from './BrandMark';
import { TopNav } from './TopNav';

interface AppChromeProps {
    title: string;
    actions?: ReactNode;
    children: ReactNode;
}

export function AppChrome({ title, actions, children }: AppChromeProps): JSX.Element {
    const route = useHashRoute();

    return (
        <div className="orbitools-app">
            <VisuallyHidden as="h1">{title}</VisuallyHidden>
            <header className="orbitools-app__chrome">
                <div className="orbitools-app__container">
                    <div className="orbitools-app__header">
                        <div className="orbitools-app__brand">
                            <span className="orbitools-app__logo" aria-hidden="true">
                                <BrandMark size={36} />
                            </span>
                            <span className="orbitools-app__brand-text">
                                <span className="orbitools-app__wordmark">Orbitools</span>
                                <span className="orbitools-app__tagline">by Orbital Design</span>
                            </span>
                        </div>
                        <div className="orbitools-app__meta">
                            <ModuleStatusPill />
                            <VersionPill />
                            {actions}
                            <Slot name={SLOTS.APP_HEADER_ACTIONS} />
                        </div>
                    </div>
                    <TopNav route={route} />
                </div>
            </header>
            <main className="orbitools-app__main">
                <div className="orbitools-app__container">{children}</div>
            </main>
        </div>
    );
}

function ModuleStatusPill(): JSX.Element | null {
    const { modules, isLoading } = useModules();
    if (isLoading && modules.length === 0) {
        return null;
    }
    const enabled = modules.filter((m) => m.enabled).length;
    return (
        <span className="orbitools-app__status" title="Modules enabled / total">
            <span className="orbitools-app__status-dot" aria-hidden="true" />
            {enabled} / {modules.length} enabled
        </span>
    );
}

function VersionPill(): JSX.Element | null {
    const version = window.orbitools?.version;
    if (version === undefined) {
        return null;
    }
    return (
        <span className="orbitools-app__version" title="Plugin version">
            v{version}
        </span>
    );
}
