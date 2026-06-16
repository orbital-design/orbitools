/**
 * Tools — Import / Export the plugin's settings as a single JSON
 * bundle, so a fresh install can be primed from another site.
 *
 * Export: builds the bundle from `/orbitools/v1/tools/export`. The
 * user picks which categories to include via checkboxes; the result
 * is filtered locally before download (so the JSON file matches what
 * the user actually wants). Entity-ID fields (page, media) are
 * stripped server-side before the bundle ever reaches the wire.
 *
 * Import: paste-or-drop the JSON. We read the bundle's manifest to
 * surface checkboxes — same shape as the Export UI — then POST the
 * payload + selected slugs to `/orbitools/v1/tools/import`.
 */
import { useCallback, useEffect, useMemo, useState } from '@wordpress/element';
import {
    Button,
    Card,
    CardBody,
    CheckboxControl,
    Notice,
    TextControl,
    TextareaControl,
    __experimentalVStack as VStack,
} from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';

interface BundleModule {
    slug: string;
    name: string;
    category: string;
}

interface BundleThemePage {
    slug: string;
    label: string;
}

interface Bundle {
    version: string;
    exported_at: string;
    source: { site_url: string; wp_version: string };
    modules: BundleModule[];
    theme_pages: BundleThemePage[];
    settings: Record<string, unknown>;
    stripped_keys: string[];
}

/**
 * Tools UI scope buckets. Each checkbox the user sees on Export /
 * Import corresponds to one of these.
 *
 * ────────────────────────────────────────────────────────────
 *   IMPORTANT — keep this union + the two records below in sync
 *   with `ModuleCategory`. A new module category that doesn't
 *   land here is silently unreachable from the Tools UI: the
 *   modules show up in the bundle but the user can never tick
 *   their category, so their settings get dropped during the
 *   client-side filter.
 *
 *   The matching enforcement points:
 *     - `ModuleCategory` (`src/admin/types/index.ts`)
 *     - `Module_Manifest::ALLOWED_CATEGORIES` (PHP)
 *     - `CATEGORY_TITLES` (`src/admin/App.tsx`)
 *     - `CATEGORY_META` (`src/admin/components/CategoryPage.tsx`)
 *     - `categoryIcon` (`src/admin/components/category-icons.tsx`)
 *
 *   See CLAUDE.md → "Tools (Import / Export)" for the wider
 *   contract.
 * ────────────────────────────────────────────────────────────
 */
type SelectionKey =
    | 'category:blocks'
    | 'category:editor'
    | 'category:controls'
    | 'category:integrations'
    | 'category:modules'
    | 'theme-pages';

const SELECTION_LABELS: Record<SelectionKey, string> = {
    'category:blocks':       'Blocks',
    'category:editor':       'Editor',
    'category:controls':     'Controls',
    'category:integrations': 'Integrations',
    'category:modules':      'Modules',
    'theme-pages':           'Theme pages (Site Settings, etc.)',
};

const SELECTION_ORDER: SelectionKey[] = [
    'category:blocks',
    'category:editor',
    'category:controls',
    'category:integrations',
    'category:modules',
    'theme-pages',
];

/**
 * For a given bundle, work out which slugs belong to which SelectionKey
 * — used both to populate the checkbox list and to filter the payload
 * down to the user's picks.
 */
function bundleSelections(bundle: Bundle): Record<SelectionKey, string[]> {
    const out: Record<SelectionKey, string[]> = {
        'category:blocks':       [],
        'category:editor':       [],
        'category:controls':     [],
        'category:integrations': [],
        'category:modules':      [],
        'theme-pages':           [],
    };
    for (const m of bundle.modules) {
        const key = `category:${m.category}` as SelectionKey;
        if (key in out) {
            out[key].push(m.slug);
        }
    }
    for (const p of bundle.theme_pages) {
        out['theme-pages'].push(p.slug);
    }
    return out;
}

/**
 * Keep only settings keys whose `{slug}_…` prefix is in the slug set.
 * Bare `{slug}_enabled` keys count under their slug. Used both for the
 * export-time filter (so the downloaded JSON matches the checkbox
 * picks) and to compute the slug whitelist for the import POST.
 */
function filterSettingsBySlugs(
    settings: Record<string, unknown>,
    slugs: ReadonlySet<string>,
): Record<string, unknown> {
    const out: Record<string, unknown> = {};
    for (const [key, value] of Object.entries(settings)) {
        for (const slug of slugs) {
            if (key === `${slug}_enabled` || key.startsWith(`${slug}_`)) {
                out[key] = value;
                break;
            }
        }
    }
    return out;
}

type ToolId = 'export' | 'import' | 'migrate' | 'reset';

interface ToolDescriptor {
    id: ToolId;
    label: string;
    description: string;
    render: () => JSX.Element;
}

const TOOLS: ToolDescriptor[] = [
    {
        id:          'export',
        label:       'Export',
        description: 'Bundle your Orbitools settings into a JSON file you can import on another site.',
        render:      () => <ExportBody />,
    },
    {
        id:          'import',
        label:       'Import',
        description: 'Drop in a JSON bundle from another site and merge its settings into this install.',
        render:      () => <ImportBody />,
    },
    {
        id:          'migrate',
        label:       'Migrate breakpoints',
        description: 'Update block content that still uses the old sm/md/lg/xl responsive breakpoints.',
        render:      () => <MigrateBody />,
    },
    {
        id:          'reset',
        label:       'Reset',
        description: 'Wipe every module toggle and setting. The plugin lands back at its first-active state.',
        render:      () => <ResetBody />,
    },
];

export function ToolsPage(): JSX.Element {
    const [activeId, setActiveId] = useState<ToolId>('export');
    const active = TOOLS.find((t) => t.id === activeId) ?? TOOLS[0];

    return (
        <div className="orbitools-page orbitools-tools-page">
            <header className="orbitools-section-header">
                <h2 className="orbitools-section-header__title">Tools</h2>
                <p className="orbitools-section-header__subtitle">
                    Move your Orbitools configuration between sites. Settings get
                    exported as a JSON bundle you can drop into a fresh install.
                </p>
            </header>

            <div className="orbitools-category-split">
                <aside className="orbitools-category-split__sidebar" aria-label="Tools">
                    <ul className="orbitools-sidebar-list">
                        {TOOLS.map((tool) => {
                            const isActive = tool.id === activeId;
                            return (
                                <li key={tool.id} className="orbitools-sidebar-list__item">
                                    <button
                                        type="button"
                                        className={
                                            isActive
                                                ? 'orbitools-sidebar-list__link orbitools-sidebar-list__link--active'
                                                : 'orbitools-sidebar-list__link'
                                        }
                                        aria-current={isActive ? 'page' : undefined}
                                        onClick={() => setActiveId(tool.id)}
                                    >
                                        <span className="orbitools-sidebar-list__label">
                                            <span className="orbitools-sidebar-list__label-text">
                                                {tool.label}
                                            </span>
                                        </span>
                                    </button>
                                </li>
                            );
                        })}
                    </ul>
                </aside>
                <section className="orbitools-category-split__content">
                    <header className="orbitools-category-split__content-header">
                        <h3 className="orbitools-category-split__content-title">
                            {active.label}
                        </h3>
                        <p className="orbitools-category-split__content-description">
                            {active.description}
                        </p>
                    </header>
                    <Card className="orbitools-card">
                        <CardBody>{active.render()}</CardBody>
                    </Card>
                </section>
            </div>
        </div>
    );
}

// =============================================================================
// Export
// =============================================================================

function ExportBody(): JSX.Element {
    const [bundle, setBundle]   = useState<Bundle | null>(null);
    const [loading, setLoading] = useState(true);
    const [error, setError]     = useState<string | null>(null);
    const [selection, setSelection] = useState<Set<SelectionKey>>(
        () => new Set(SELECTION_ORDER),
    );

    // Fetch the bundle on mount — checkboxes show as soon as we
    // have a payload. No "Prepare export" gate.
    useEffect(() => {
        let cancelled = false;
        apiFetch<Bundle>({ path: 'orbitools/v1/tools/export' })
            .then((resp) => {
                if (!cancelled) {
                    setBundle(resp);
                    setLoading(false);
                }
            })
            .catch((e) => {
                if (!cancelled) {
                    setError(e instanceof Error ? e.message : 'Failed to load export payload.');
                    setLoading(false);
                }
            });
        return () => {
            cancelled = true;
        };
    }, []);

    const selections = useMemo(() => (bundle === null ? null : bundleSelections(bundle)), [bundle]);

    const toggle = (key: SelectionKey, on: boolean): void => {
        setSelection((prev) => {
            const next = new Set(prev);
            if (on) next.add(key);
            else next.delete(key);
            return next;
        });
    };

    const allOn = SELECTION_ORDER.every((k) => selection.has(k));

    const download = useCallback(() => {
        if (bundle === null || selections === null) return;
        const slugs = new Set<string>();
        for (const key of selection) {
            for (const slug of selections[key]) {
                slugs.add(slug);
            }
        }
        const filtered = filterSettingsBySlugs(bundle.settings, slugs);
        const out: Bundle = { ...bundle, settings: filtered };
        const blob = new Blob([JSON.stringify(out, null, 2)], { type: 'application/json' });
        const url  = URL.createObjectURL(blob);
        const a    = document.createElement('a');
        const stamp = new Date().toISOString().replace(/[:.]/g, '-').slice(0, 19);
        a.href     = url;
        a.download = `orbitools-export-${stamp}.json`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }, [bundle, selections, selection]);

    if (error !== null) {
        return (
            <Notice status="error" isDismissible={false}>
                {error}
            </Notice>
        );
    }

    if (loading || bundle === null) {
        return <p className="orbitools-tools-body__loading">Loading…</p>;
    }

    return (
        <VStack spacing={3} className="orbitools-tools-body">
            <CheckboxControl
                label="Select all"
                checked={allOn}
                indeterminate={!allOn && selection.size > 0}
                onChange={(on) =>
                    setSelection(on ? new Set(SELECTION_ORDER) : new Set())
                }
                __nextHasNoMarginBottom
            />
            <ul className="orbitools-tools-card__list">
                {SELECTION_ORDER.map((key) => {
                    const count = selections === null ? 0 : selections[key].length;
                    return (
                        <li key={key}>
                            <CheckboxControl
                                label={`${SELECTION_LABELS[key]} (${count})`}
                                checked={selection.has(key)}
                                onChange={(on) => toggle(key, on)}
                                disabled={count === 0}
                                __nextHasNoMarginBottom
                            />
                        </li>
                    );
                })}
            </ul>
            {bundle.stripped_keys.length > 0 && (
                <Notice status="info" isDismissible={false}>
                    {bundle.stripped_keys.length} page / media reference
                    {bundle.stripped_keys.length === 1 ? '' : 's'} will be blanked
                    on export.
                </Notice>
            )}
            <div className="orbitools-tools-card__actions">
                <Button
                    variant="primary"
                    onClick={download}
                    disabled={selection.size === 0}
                    __next40pxDefaultSize
                >
                    Download JSON
                </Button>
            </div>
        </VStack>
    );
}

// =============================================================================
// Import
// =============================================================================

interface ImportApiResponse {
    applied: number;
}

function ImportBody(): JSX.Element {
    const [raw, setRaw]               = useState('');
    const [bundle, setBundle]         = useState<Bundle | null>(null);
    const [parseError, setParseError] = useState<string | null>(null);
    const [submitError, setSubmitError] = useState<string | null>(null);
    const [success, setSuccess]       = useState<number | null>(null);
    const [submitting, setSubmitting] = useState(false);
    const [selection, setSelection]   = useState<Set<SelectionKey>>(
        () => new Set(SELECTION_ORDER),
    );

    const parse = useCallback((text: string): void => {
        setRaw(text);
        setSuccess(null);
        setSubmitError(null);
        if (text.trim() === '') {
            setBundle(null);
            setParseError(null);
            return;
        }
        try {
            const parsed = JSON.parse(text) as Bundle;
            if (typeof parsed !== 'object' || parsed === null || typeof parsed.settings !== 'object') {
                throw new Error('Missing `settings` object.');
            }
            setBundle(parsed);
            setParseError(null);
            setSelection(new Set(SELECTION_ORDER));
        } catch (e) {
            setBundle(null);
            setParseError(e instanceof Error ? e.message : 'Invalid JSON.');
        }
    }, []);

    const onFile = useCallback((file: File): void => {
        const reader = new FileReader();
        reader.onload = () => parse(String(reader.result ?? ''));
        reader.readAsText(file);
    }, [parse]);

    const selections = useMemo(() => (bundle === null ? null : bundleSelections(bundle)), [bundle]);

    const toggle = (key: SelectionKey, on: boolean): void => {
        setSelection((prev) => {
            const next = new Set(prev);
            if (on) next.add(key);
            else next.delete(key);
            return next;
        });
    };

    const allOn = SELECTION_ORDER.every((k) => selection.has(k));

    const apply = useCallback(async () => {
        if (bundle === null || selections === null) return;
        const slugs: string[] = [];
        for (const key of selection) {
            for (const slug of selections[key]) {
                slugs.push(slug);
            }
        }
        setSubmitting(true);
        setSubmitError(null);
        setSuccess(null);
        try {
            const resp = await apiFetch<ImportApiResponse>({
                path: 'orbitools/v1/tools/import',
                method: 'POST',
                data: {
                    payload: bundle,
                    apply_slugs: slugs,
                },
            });
            setSuccess(resp.applied);
        } catch (e) {
            setSubmitError(e instanceof Error ? e.message : 'Import failed.');
        } finally {
            setSubmitting(false);
        }
    }, [bundle, selections, selection]);

    return (
        <VStack spacing={3} className="orbitools-tools-body">
            <div className="orbitools-tools-card__file-row">
                <input
                    type="file"
                    accept="application/json,.json"
                    onChange={(e) => {
                        const f = e.target.files?.[0];
                        if (f !== undefined) onFile(f);
                    }}
                />
            </div>
            <TextareaControl
                label="…or paste the JSON"
                value={raw}
                onChange={parse}
                rows={6}
                __nextHasNoMarginBottom
            />
            {parseError !== null && (
                <Notice status="error" isDismissible={false}>
                    Couldn't parse the JSON: {parseError}
                </Notice>
            )}
            {submitError !== null && (
                <Notice status="error" isDismissible={false}>
                    {submitError}
                </Notice>
            )}
            {success !== null && (
                <Notice status="success" isDismissible={false}>
                    Imported {success} setting{success === 1 ? '' : 's'}. Reload to see
                    the new values everywhere.
                </Notice>
            )}
            {bundle !== null && (
                <>
                    <CheckboxControl
                        label="Select all"
                        checked={allOn}
                        indeterminate={!allOn && selection.size > 0}
                        onChange={(on) =>
                            setSelection(on ? new Set(SELECTION_ORDER) : new Set())
                        }
                        __nextHasNoMarginBottom
                    />
                    <ul className="orbitools-tools-card__list">
                        {SELECTION_ORDER.map((key) => {
                            const count = selections === null ? 0 : selections[key].length;
                            return (
                                <li key={key}>
                                    <CheckboxControl
                                        label={`${SELECTION_LABELS[key]} (${count})`}
                                        checked={selection.has(key)}
                                        onChange={(on) => toggle(key, on)}
                                        disabled={count === 0}
                                        __nextHasNoMarginBottom
                                    />
                                </li>
                            );
                        })}
                    </ul>
                    <div className="orbitools-tools-card__actions">
                        <Button
                            variant="primary"
                            onClick={apply}
                            disabled={submitting || selection.size === 0}
                            __next40pxDefaultSize
                        >
                            {submitting ? 'Importing…' : 'Apply'}
                        </Button>
                    </div>
                </>
            )}
        </VStack>
    );
}

// =============================================================================
// Migrate breakpoints
// =============================================================================

interface MigrationOverride {
    block: string;
    attr: string;
    key: string;
}

interface MigrationPost {
    id: number;
    type: string;
    title: string;
    edit_link: string;
    overrides: MigrationOverride[];
}

interface MigrationReport {
    applied: boolean;
    posts_scanned: number;
    posts_affected: number;
    total_overrides: number;
    rewritten: number;
    failed: number[];
    details: MigrationPost[];
}

/**
 * Migrate breakpoints — drop the legacy mobile-first sm/md/lg/xl
 * responsive overrides from block content. Each block's unqualified
 * `base` value is kept (it means the same thing in both systems); the
 * old min-width slugs have no max-width equivalent, so they're removed
 * and reported. The old content is preserved as a post revision, so the
 * rewrite is recoverable per-post.
 *
 * Scan first (dry-run, read-only) → review the affected posts → Apply.
 */
function MigrateBody(): JSX.Element {
    const [report, setReport]         = useState<MigrationReport | null>(null);
    const [scanning, setScanning]     = useState(false);
    const [applying, setApplying]     = useState(false);
    const [error, setError]           = useState<string | null>(null);

    const scan = useCallback(async () => {
        setScanning(true);
        setError(null);
        try {
            const resp = await apiFetch<MigrationReport>({
                path: 'orbitools/v1/tools/breakpoint-migration',
            });
            setReport(resp);
        } catch (e) {
            setError(e instanceof Error ? e.message : 'Scan failed.');
        } finally {
            setScanning(false);
        }
    }, []);

    const apply = useCallback(async () => {
        setApplying(true);
        setError(null);
        try {
            const resp = await apiFetch<MigrationReport>({
                path: 'orbitools/v1/tools/breakpoint-migration',
                method: 'POST',
                data: { confirm: true },
            });
            setReport(resp);
        } catch (e) {
            setError(e instanceof Error ? e.message : 'Migration failed.');
        } finally {
            setApplying(false);
        }
    }, []);

    const hasWork = report !== null && report.posts_affected > 0;
    const done    = report !== null && report.applied;

    return (
        <VStack spacing={3} className="orbitools-tools-body">
            <Notice status="info" isDismissible={false}>
                Blocks keep their <strong>base</strong> (all-screens) value. The old{' '}
                <code>sm</code> / <code>md</code> / <code>lg</code> / <code>xl</code>{' '}
                overrides are removed — they have no equivalent in the new Tablet /
                Mobile system. Affected posts are listed so you can re-apply Tablet /
                Mobile values in the editor where needed. Each rewritten post keeps a
                revision of its previous content.
            </Notice>

            {error !== null && (
                <Notice status="error" isDismissible={false}>
                    {error}
                </Notice>
            )}

            <div className="orbitools-tools-card__actions">
                <Button
                    variant="secondary"
                    onClick={scan}
                    disabled={scanning || applying}
                    __next40pxDefaultSize
                >
                    {scanning ? 'Scanning…' : 'Scan content'}
                </Button>
            </div>

            {report !== null && (
                <>
                    {done ? (
                        <Notice status="success" isDismissible={false}>
                            Rewrote {report.rewritten} post
                            {report.rewritten === 1 ? '' : 's'} ({report.total_overrides}{' '}
                            legacy override{report.total_overrides === 1 ? '' : 's'}{' '}
                            removed).
                            {report.failed.length > 0 && (
                                <>
                                    {' '}
                                    {report.failed.length} post
                                    {report.failed.length === 1 ? '' : 's'} could not be
                                    updated (IDs: {report.failed.join(', ')}).
                                </>
                            )}
                        </Notice>
                    ) : hasWork ? (
                        <Notice status="warning" isDismissible={false}>
                            Found <strong>{report.total_overrides}</strong> legacy
                            override{report.total_overrides === 1 ? '' : 's'} across{' '}
                            <strong>{report.posts_affected}</strong> post
                            {report.posts_affected === 1 ? '' : 's'} (of{' '}
                            {report.posts_scanned} scanned). Review below, then apply.
                        </Notice>
                    ) : (
                        <Notice status="success" isDismissible={false}>
                            No legacy breakpoints found in {report.posts_scanned}{' '}
                            scanned post{report.posts_scanned === 1 ? '' : 's'}. Nothing
                            to migrate.
                        </Notice>
                    )}

                    {report.details.length > 0 && (
                        <ul className="orbitools-tools-card__list">
                            {report.details.map((post) => (
                                <li key={post.id}>
                                    {post.edit_link !== '' ? (
                                        <a href={post.edit_link} target="_blank" rel="noreferrer">
                                            {post.title}
                                        </a>
                                    ) : (
                                        <strong>{post.title}</strong>
                                    )}{' '}
                                    <code>{post.type}</code> — {post.overrides.length}{' '}
                                    override{post.overrides.length === 1 ? '' : 's'}{' '}
                                    ({post.overrides
                                        .map((o) => `${o.attr}.${o.key}`)
                                        .join(', ')})
                                </li>
                            ))}
                        </ul>
                    )}

                    {hasWork && !done && (
                        <div className="orbitools-tools-card__actions">
                            <Button
                                variant="primary"
                                isDestructive
                                onClick={apply}
                                disabled={applying || scanning}
                                __next40pxDefaultSize
                            >
                                {applying ? 'Migrating…' : `Apply — rewrite ${report.posts_affected} post${report.posts_affected === 1 ? '' : 's'}`}
                            </Button>
                        </div>
                    )}
                </>
            )}
        </VStack>
    );
}

// =============================================================================
// Reset
// =============================================================================

interface ResetApiResponse {
    cleared: string[];
}

/**
 * The literal phrase the user has to type to confirm. Mirrors the
 * server-side check in `Tools_Controller::RESET_CONFIRM_PHRASE` —
 * keep them in sync.
 */
const RESET_CONFIRM_PHRASE = 'RESET';

function ResetBody(): JSX.Element {
    const [phrase, setPhrase]         = useState('');
    const [submitting, setSubmitting] = useState(false);
    const [error, setError]           = useState<string | null>(null);
    const [cleared, setCleared]       = useState<string[] | null>(null);

    const confirmed = phrase === RESET_CONFIRM_PHRASE;

    const apply = useCallback(async () => {
        if (!confirmed) return;
        setSubmitting(true);
        setError(null);
        setCleared(null);
        try {
            const resp = await apiFetch<ResetApiResponse>({
                path: 'orbitools/v1/tools/reset',
                method: 'POST',
                data: { confirm: RESET_CONFIRM_PHRASE },
            });
            setCleared(resp.cleared);
            setPhrase('');
        } catch (e) {
            setError(e instanceof Error ? e.message : 'Reset failed.');
        } finally {
            setSubmitting(false);
        }
    }, [confirmed]);

    return (
        <VStack spacing={3} className="orbitools-tools-body">
            <Notice status="warning" isDismissible={false}>
                <strong>This action is destructive.</strong> Every module toggle,
                every per-module setting, and every theme-page setting stored under{' '}
                <code>orbitools_settings</code> will be deleted. The plugin will
                land back at its first-active state — modules fall back to their
                manifest <code>default_enabled</code>, fields to their schema
                <code>default</code>. There is no undo.
            </Notice>
            <p className="orbitools-tools-body__hint">
                Type <code>{RESET_CONFIRM_PHRASE}</code> below to enable the reset
                button.
            </p>
            <TextControl
                label={`Type "${RESET_CONFIRM_PHRASE}" to confirm`}
                value={phrase}
                onChange={setPhrase}
                __next40pxDefaultSize
                __nextHasNoMarginBottom
            />
            {error !== null && (
                <Notice status="error" isDismissible={false}>
                    {error}
                </Notice>
            )}
            {cleared !== null && (
                <Notice status="success" isDismissible={false}>
                    Cleared {cleared.length} option
                    {cleared.length === 1 ? '' : 's'}. Reload the page to see the
                    fresh-install state.
                </Notice>
            )}
            <div className="orbitools-tools-card__actions">
                <Button
                    variant="primary"
                    isDestructive
                    onClick={apply}
                    disabled={!confirmed || submitting}
                    __next40pxDefaultSize
                >
                    {submitting ? 'Resetting…' : 'Reset everything'}
                </Button>
            </div>
        </VStack>
    );
}
