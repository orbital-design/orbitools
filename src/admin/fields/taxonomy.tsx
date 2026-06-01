/**
 * Taxonomy field — pick a registered taxonomy slug.
 *
 * Loads the catalog from `/wp/v2/taxonomies` on mount and renders a
 * select dropdown. Storage: the taxonomy slug as a string, or
 * undefined for "none selected".
 *
 * The list is filtered to public, non-internal taxonomies. Pass
 * `include_builtin: true` on the schema to include the WP built-ins
 * (`category`, `post_tag`, `nav_menu`, `link_category`, `post_format`,
 * `wp_pattern_category`).
 *
 * If `post_type` is set on the schema, the list is further filtered
 * to taxonomies registered for that post type — useful inside a
 * repeater where another row field already chose a post type. Pass
 * the literal slug, not a reactive reference; the field re-fetches
 * when the schema value changes.
 */
import apiFetch from '@wordpress/api-fetch';
import { useEffect, useState } from '@wordpress/element';
import { SelectControl } from '@wordpress/components';
import { registerFieldType, type FieldProps } from './registry';

interface WpTaxonomy {
    slug: string;
    name: string;
    types?: string[];
    visibility?: { public?: boolean; show_ui?: boolean };
}

type WpTaxonomiesResponse = Record<string, WpTaxonomy>;

const BUILT_IN = new Set([
    'category',
    'post_tag',
    'nav_menu',
    'link_category',
    'post_format',
    'wp_pattern_category',
]);

function TaxonomyField({ field, value, onChange }: FieldProps): JSX.Element {
    const [taxonomies, setTaxonomies] = useState<WpTaxonomy[]>([]);
    const [error, setError] = useState<string | undefined>(undefined);

    const includeBuiltin = Boolean((field as { include_builtin?: boolean }).include_builtin);
    const filterByPostType =
        typeof (field as { post_type?: unknown }).post_type === 'string'
            ? ((field as { post_type: string }).post_type as string)
            : undefined;

    useEffect(() => {
        let cancelled = false;
        apiFetch<WpTaxonomiesResponse>({
            path: 'wp/v2/taxonomies?context=edit',
        })
            .then((data) => {
                if (cancelled) return;
                const list = Object.values(data ?? {})
                    .filter((t) => t.visibility?.public !== false)
                    .filter((t) => includeBuiltin || !BUILT_IN.has(t.slug))
                    .filter((t) =>
                        filterByPostType === undefined
                            ? true
                            : Array.isArray(t.types) && t.types.includes(filterByPostType),
                    )
                    .sort((a, b) => a.name.localeCompare(b.name));
                setTaxonomies(list);
            })
            .catch(() => {
                if (!cancelled) setError('Failed to load taxonomies.');
            });
        return () => {
            cancelled = true;
        };
    }, [includeBuiltin, filterByPostType]);

    const current = typeof value === 'string' ? value : '';

    const options = [
        { value: '', label: '— Select —' },
        ...taxonomies.map((t) => ({
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

registerFieldType('taxonomy', TaxonomyField);
