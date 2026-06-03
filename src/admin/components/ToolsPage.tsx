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

type SelectionKey = 'category:blocks' | 'category:editor' | 'category:controls' | 'category:modules' | 'theme-pages';

const SELECTION_LABELS: Record<SelectionKey, string> = {
    'category:blocks':   'Blocks',
    'category:editor':   'Editor',
    'category:controls': 'Controls',
    'category:modules':  'Modules',
    'theme-pages':       'Theme pages (Site Settings, etc.)',
};

const SELECTION_ORDER: SelectionKey[] = [
    'category:blocks',
    'category:editor',
    'category:controls',
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
        'category:blocks':   [],
        'category:editor':   [],
        'category:controls': [],
        'category:modules':  [],
        'theme-pages':       [],
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

type ToolId = 'export' | 'import';

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
