/**
 * Post-type field — pick a registered post type slug.
 *
 * Loads the catalog from `/wp/v2/types` on mount and renders a select
 * dropdown. Storage: the post type slug as a string, or undefined for
 * "none selected".
 *
 * The list is filtered to viewable post types (the ones a user would
 * actually want to template) — that drops `nav_menu_item`, `wp_block`,
 * etc. that aren't meaningful in a templating UI. Pass
 * `include_builtin: true` on the schema if you want the built-ins
 * (`post`, `page`, `attachment`) in the list anyway.
 */
import apiFetch from '@wordpress/api-fetch';
import { useEffect, useState } from '@wordpress/element';
import { SelectControl } from '@wordpress/components';
import { registerFieldType, type FieldProps } from './registry';

interface WpType {
    slug: string;
    name: string;
    viewable?: boolean;
    hierarchical?: boolean;
}

type WpTypesResponse = Record<string, WpType>;

const BUILT_IN = new Set(['post', 'page', 'attachment']);

function PostTypeField({ field, value, onChange }: FieldProps): JSX.Element {
    const [types, setTypes] = useState<WpType[]>([]);
    const [error, setError] = useState<string | undefined>(undefined);

    const includeBuiltin = Boolean((field as { include_builtin?: boolean }).include_builtin);

    useEffect(() => {
        let cancelled = false;
        apiFetch<WpTypesResponse>({
            path: 'wp/v2/types?context=edit',
        })
            .then((data) => {
                if (cancelled) return;
                const list = Object.values(data ?? {})
                    .filter((t) => t.viewable !== false)
                    .filter((t) => includeBuiltin || !BUILT_IN.has(t.slug))
                    .sort((a, b) => a.name.localeCompare(b.name));
                setTypes(list);
            })
            .catch(() => {
                if (!cancelled) setError('Failed to load post types.');
            });
        return () => {
            cancelled = true;
        };
    }, [includeBuiltin]);

    const current = typeof value === 'string' ? value : '';

    const options = [
        { value: '', label: '— Select —' },
        ...types.map((t) => ({
            value: t.slug,
            label: `${t.name} (${t.slug})`,
        })),
    ];

    return (
        <SelectControl
            label={field.label}
            help={error ?? field.description}
            value={current}
            options={options}
            onChange={(v) => onChange(v === '' ? undefined : v)}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
        />
    );
}

registerFieldType('post-type', PostTypeField);
