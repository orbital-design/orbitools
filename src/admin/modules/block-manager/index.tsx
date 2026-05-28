/**
 * Block Manager — admin extension entry.
 *
 * Custom Page that fetches every registered block via
 * /orbitools/v1/blocks, groups by WP category, and lets the user
 * toggle which appear in the editor inserter. The on/off state is
 * stored in `block-manager` module settings as `disabled: string[]`;
 * the PHP side subtracts that from `allowed_block_types_all`.
 */
import { useEffect, useMemo, useState } from '@wordpress/element';
import { useDispatch, useSelect } from '@wordpress/data';
import {
    Dashicon,
    Notice,
    PanelBody,
    SearchControl,
    ToggleControl,
} from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import { STORE_KEY } from '../../store';
import type {
    ModuleExtension,
    ModulePage,
    ModuleSettings,
} from '../../types';

interface BlockInfo {
    name: string;
    title: string;
    category: string;
    description: string;
    icon: string | null;
}

interface BlocksResponse {
    blocks: BlockInfo[];
}

interface StoreShape {
    getSettings: (slug: string) => ModuleSettings | undefined;
}

interface StoreDispatch {
    updateSetting: (slug: string, key: string, value: unknown) => void;
}

const CATEGORY_LABELS: Record<string, string> = {
    text: 'Text',
    media: 'Media',
    design: 'Design',
    widgets: 'Widgets',
    theme: 'Theme',
    embed: 'Embeds',
    reusable: 'Reusable',
    uncategorized: 'Uncategorized',
};

const Page: ModulePage = ({ slug }) => {
    const [blocks, setBlocks] = useState<BlockInfo[] | null>(null);
    const [fetchError, setFetchError] = useState<string | null>(null);
    const [search, setSearch] = useState<string>('');

    const settings = useSelect(
        (select) => (select(STORE_KEY) as unknown as StoreShape).getSettings(slug),
        [slug],
    );

    const disabled = useMemo<string[]>(() => {
        const raw = settings?.disabled;
        return Array.isArray(raw)
            ? raw.filter((v): v is string => typeof v === 'string')
            : [];
    }, [settings]);

    const { updateSetting } = useDispatch(STORE_KEY) as unknown as StoreDispatch;

    useEffect(() => {
        let cancelled = false;
        apiFetch<BlocksResponse>({ path: 'orbitools/v1/blocks' })
            .then((res) => {
                if (!cancelled) {
                    setBlocks(res.blocks);
                }
            })
            .catch((err: unknown) => {
                if (!cancelled) {
                    const message = err instanceof Error ? err.message : String(err);
                    setFetchError(message);
                }
            });
        return () => {
            cancelled = true;
        };
    }, []);

    const toggle = (name: string, enabled: boolean): void => {
        const next = enabled
            ? disabled.filter((b) => b !== name)
            : Array.from(new Set([...disabled, name]));
        updateSetting(slug, 'disabled', next);
    };

    const filtered = useMemo<BlockInfo[]>(() => {
        if (blocks === null) {
            return [];
        }
        if (search === '') {
            return blocks;
        }
        const q = search.toLowerCase();
        return blocks.filter(
            (b) =>
                b.title.toLowerCase().includes(q) ||
                b.name.toLowerCase().includes(q) ||
                b.description.toLowerCase().includes(q),
        );
    }, [blocks, search]);

    const grouped = useMemo<Record<string, BlockInfo[]>>(() => {
        const out: Record<string, BlockInfo[]> = {};
        for (const b of filtered) {
            const list = out[b.category] ?? [];
            list.push(b);
            out[b.category] = list;
        }
        return out;
    }, [filtered]);

    if (fetchError !== null) {
        return (
            <Notice status="error" isDismissible={false}>
                Failed to load blocks: {fetchError}
            </Notice>
        );
    }

    if (blocks === null) {
        return (
            <Notice status="info" isDismissible={false}>
                Loading blocks…
            </Notice>
        );
    }

    const categoryOrder = Object.keys(grouped).sort();
    const total = blocks.length;
    const disabledCount = disabled.length;

    return (
        <div className="orbitools-block-manager">
            <p className="orbitools-block-manager__summary">
                <strong>{total}</strong> blocks registered ·{' '}
                <strong>{disabledCount}</strong> disabled
            </p>
            <SearchControl
                label="Filter blocks"
                value={search}
                onChange={setSearch}
                __nextHasNoMarginBottom
            />
            {categoryOrder.length === 0 && (
                <Notice status="info" isDismissible={false}>
                    No blocks match the current filter.
                </Notice>
            )}
            {categoryOrder.map((category) => (
                <PanelBody
                    key={category}
                    title={`${CATEGORY_LABELS[category] ?? category} (${grouped[category].length})`}
                    initialOpen
                >
                    <div className="orbitools-block-manager__list">
                        {grouped[category].map((block) => (
                            <BlockRow
                                key={block.name}
                                block={block}
                                enabled={!disabled.includes(block.name)}
                                onChange={(next) => toggle(block.name, next)}
                            />
                        ))}
                    </div>
                </PanelBody>
            ))}
        </div>
    );
};

interface BlockRowProps {
    block: BlockInfo;
    enabled: boolean;
    onChange: (enabled: boolean) => void;
}

function BlockRow({ block, enabled, onChange }: BlockRowProps): JSX.Element {
    return (
        <div className="orbitools-block-manager__row">
            <div className="orbitools-block-manager__icon" aria-hidden="true">
                <BlockIcon icon={block.icon} />
            </div>
            <div className="orbitools-block-manager__meta">
                <div className="orbitools-block-manager__title">{block.title}</div>
                <div className="orbitools-block-manager__name">
                    <code>{block.name}</code>
                </div>
                {block.description !== '' && (
                    <div className="orbitools-block-manager__desc">{block.description}</div>
                )}
            </div>
            <div className="orbitools-block-manager__toggle">
                <ToggleControl
                    label={enabled ? 'Enabled' : 'Disabled'}
                    checked={enabled}
                    onChange={onChange}
                    __nextHasNoMarginBottom
                />
            </div>
        </div>
    );
}

interface BlockIconProps {
    icon: string | null;
}

/**
 * Render whatever WP gave us as the block icon. Three shapes:
 *   - inline SVG markup ("<svg…")
 *   - dashicon slug ("format-image")
 *   - null → generic placeholder
 */
function BlockIcon({ icon }: BlockIconProps): JSX.Element {
    if (icon === null || icon === '') {
        return <Dashicon icon="block-default" />;
    }
    if (icon.trim().startsWith('<svg')) {
        return (
            <span
                className="orbitools-block-manager__svg"
                // eslint-disable-next-line react/no-danger
                dangerouslySetInnerHTML={{ __html: icon }}
            />
        );
    }
    return <Dashicon icon={icon as Parameters<typeof Dashicon>[0]['icon']} />;
}

const extension: ModuleExtension = { Page };
export default extension;
