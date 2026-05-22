/**
 * Standard page wrapper used by every page in the admin app. Owns the
 * header, the main content well, and the top-level slot fills.
 */
import { Slot } from '@wordpress/components';
import { Flex } from '@wordpress/components';
import type { ReactNode } from 'react';
import { SLOTS } from '../lib/slots';

interface AppChromeProps {
    title: string;
    actions?: ReactNode;
    children: ReactNode;
}

export function AppChrome({ title, actions, children }: AppChromeProps): JSX.Element {
    return (
        <div className="orbitools-app">
            <header className="orbitools-app__header">
                <h1 className="orbitools-app__title">{title}</h1>
                <Flex justify="flex-end" gap={2} className="orbitools-app__actions">
                    {actions}
                    <Slot name={SLOTS.APP_HEADER_ACTIONS} />
                </Flex>
            </header>
            <main className="orbitools-app__main">{children}</main>
        </div>
    );
}
