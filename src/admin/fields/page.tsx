/**
 * Page field — pick a WP page from the core /wp/v2/pages endpoint.
 *
 * Storage: page ID (number) or 0 / undefined for "none".
 *
 * Loads all published pages on mount (per_page=100, fine for most
 * sites). For sites with hundreds of pages we'd need to switch to
 * search-as-you-type; defer that until someone hits the limit.
 */
import apiFetch from '@wordpress/api-fetch';
import { useEffect, useState } from '@wordpress/element';
import { SelectControl } from '@wordpress/components';
import { registerFieldType, type FieldProps } from './registry';

interface WpPage {
    id: number;
    title: { rendered: string };
}

function PageField({ field, value, onChange }: FieldProps): JSX.Element {
    const [pages, setPages] = useState<WpPage[]>([]);
    const [error, setError] = useState<string | undefined>(undefined);

    useEffect(() => {
        let cancelled = false;
        apiFetch<WpPage[]>({
            path: 'wp/v2/pages?per_page=100&status=publish&_fields=id,title&orderby=title&order=asc',
        })
            .then((list) => {
                if (!cancelled) {
                    setPages(list);
                }
            })
            .catch(() => {
                if (!cancelled) {
                    setError('Failed to load pages.');
                }
            });
        return () => {
            cancelled = true;
        };
    }, []);

    const current = typeof value === 'number' && value > 0 ? String(value) : '';

    const options = [
        { value: '', label: '— Select —' },
        ...pages.map((p) => ({
            value: String(p.id),
            label: p.title.rendered || `(#${p.id})`,
        })),
    ];

    return (
        <SelectControl
            label={field.label}
            help={error ?? field.description}
            value={current}
            options={options}
            onChange={(v) => onChange(v === '' ? undefined : parseInt(v, 10))}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
        />
    );
}

registerFieldType('page', PageField);
