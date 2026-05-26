/**
 * Typography Presets — admin extension entry.
 *
 * Phase 4 wiring demonstration: a module drops this file at
 * src/admin/modules/{slug}/index.tsx, the build-time discovery scan
 * picks it up, and the React shell renders the custom Page in place
 * of the generic SettingsPage and mounts the Fills globally.
 *
 * The richer preset-list management UI lives later; for now this
 * verifies that the discovery pipeline reaches the runtime.
 */
import { Fill, Notice } from '@wordpress/components';
import { SettingsPage } from '../../components/SettingsPage';
import { SLOTS } from '../../lib/slots';
import type { ModuleExtension, ModulePage } from '../../types';

const Page: ModulePage = ({ slug }) => (
    <>
        <Notice status="info" isDismissible={false}>
            Custom Typography Presets page (mounted via Phase 4 discovery).
            Manifest-driven fields render below; the preset-list UI lands
            in a follow-up.
        </Notice>
        <SettingsPage slug={slug} />
    </>
);

function Fills(): JSX.Element {
    return (
        <Fill name={SLOTS.DASHBOARD_CARDS}>
            <div className="orbitools-discovered-card">
                <strong>Typography Presets</strong> — extension discovered.
            </div>
        </Fill>
    );
}

const extension: ModuleExtension = { Page, Fills };
export default extension;
