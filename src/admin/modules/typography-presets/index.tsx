/**
 * Typography Presets — admin extension entry.
 *
 * Dropped at src/admin/modules/{slug}/index.tsx so the build-time
 * discovery scan picks it up; the React shell renders this custom
 * Page in place of the generic SettingsPage when the standalone
 * settings route resolves to this slug.
 *
 * The richer preset-list management UI lives later; for now the
 * Page just frames the manifest-driven fields with an info notice.
 */
import { Notice } from '@wordpress/components';
import { SettingsPage } from '../../components/SettingsPage';
import type { ModuleExtension, ModulePage } from '../../types';

const Page: ModulePage = ({ slug }) => (
    <>
        <Notice status="info" isDismissible={false}>
            Custom Typography Presets page (mounted via the discovery
            pipeline). Manifest-driven fields render below; the
            preset-list UI lands in a follow-up.
        </Notice>
        <SettingsPage slug={slug} />
    </>
);

const extension: ModuleExtension = { Page };
export default extension;
