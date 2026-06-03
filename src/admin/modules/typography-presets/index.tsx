/**
 * Typography Presets — admin extension entry.
 *
 * Dropped at src/admin/modules/{slug}/index.tsx so the build-time
 * discovery scan picks it up; the React shell renders this custom
 * Page in place of the generic SettingsPage when the standalone
 * settings route resolves to this slug.
 *
 * For now this just defers to the manifest-driven fields. The
 * richer preset-list management UI will replace this body when it
 * lands — but the file still needs to exist so the discovery
 * pipeline keeps the slot wired up for that future work.
 *
 * Renders `ModuleSettingsBody`, not `SettingsPage`: the parent
 * CategoryPage / standalone route already provides AppChrome, so
 * mounting `SettingsPage` here would render the whole admin chrome
 * (header, tabs) a second time inside its own content pane.
 */
import { ModuleSettingsBody } from '../../components/SettingsPage';
import type { ModuleExtension, ModulePage } from '../../types';

const Page: ModulePage = ({ slug, sectionLayoutOverride }) => (
    <ModuleSettingsBody slug={slug} sectionLayoutOverride={sectionLayoutOverride} />
);

const extension: ModuleExtension = { Page };
export default extension;
