/**
 * Taxonomy Terms Selector Component
 *
 * A searchable multi-select for picking terms from a specific taxonomy.
 * Fetches all terms on mount and filters client-side.
 * Stores term IDs (as strings) for backward compatibility with existing blocks.
 *
 * @file blocks/query-loop/components/TaxonomyTermsSelector.tsx
 * @since 1.0.0
 */

import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { FormTokenField } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';

interface Term {
	id: number;
	name: string;
	slug: string;
	count: number;
}

interface TaxonomyTermsSelectorProps {
	taxonomy: string;
	value: string[];
	onChange: (termIds: string[]) => void;
	label?: string;
	help?: string;
}

export default function TaxonomyTermsSelector({
	taxonomy,
	value = [],
	onChange,
	label,
	help,
}: TaxonomyTermsSelectorProps) {
	const [allTerms, setAllTerms] = useState<Term[]>([]);
	const [isLoading, setIsLoading] = useState(false);
	const [displayTokens, setDisplayTokens] = useState<string[]>([]);

	// Fetch all terms for this taxonomy
	useEffect(() => {
		if (!taxonomy) {
			setAllTerms([]);
			setDisplayTokens([]);
			return;
		}

		let cancelled = false;

		const fetchTerms = async () => {
			setIsLoading(true);
			try {
				const terms = (await apiFetch({
					path: `/wp/v2/${taxonomy}?per_page=100&_fields=id,name,slug,count&orderby=name&order=asc`,
				})) as Term[];

				if (!cancelled) {
					setAllTerms(terms);
				}
			} catch {
				// Some taxonomies use a different rest_base — try via the
				// taxonomies endpoint to discover it
				try {
					const taxObj = (await apiFetch({
						path: `/wp/v2/taxonomies/${taxonomy}?_fields=rest_base`,
					})) as { rest_base: string };

					if (taxObj.rest_base && taxObj.rest_base !== taxonomy) {
						const terms = (await apiFetch({
							path: `/wp/v2/${taxObj.rest_base}?per_page=100&_fields=id,name,slug,count&orderby=name&order=asc`,
						})) as Term[];

						if (!cancelled) {
							setAllTerms(terms);
						}
					} else if (!cancelled) {
						setAllTerms([]);
					}
				} catch {
					if (!cancelled) {
						setAllTerms([]);
					}
				}
			} finally {
				if (!cancelled) {
					setIsLoading(false);
				}
			}
		};

		fetchTerms();

		return () => {
			cancelled = true;
		};
	}, [taxonomy]);

	// Convert stored term IDs to display names when terms load or value changes
	useEffect(() => {
		if (value.length === 0) {
			setDisplayTokens([]);
			return;
		}

		const tokens = value.map((termId) => {
			// Try matching by ID first (primary storage format)
			const byId = allTerms.find((t) => t.id.toString() === termId);
			if (byId) return byId.name;

			// Fallback: try matching by slug (for older data)
			const bySlug = allTerms.find((t) => t.slug === termId);
			if (bySlug) return bySlug.name;

			// Term not found (deleted or not yet loaded) — show the raw value
			return termId;
		});

		setDisplayTokens(tokens);
	}, [value.join(','), allTerms]);

	// Build suggestion list (term names)
	const suggestions = allTerms.map((t) => t.name);

	// Convert display names back to term IDs on change
	const handleChange = (tokens: string[]) => {
		const ids = tokens
			.map((token) => {
				// Match by name
				const term = allTerms.find(
					(t) => t.name.toLowerCase() === token.toLowerCase()
				);
				if (term) return term.id.toString();

				// If user typed a raw ID that's still valid, keep it
				if (/^\d+$/.test(token)) return token;

				// Check if it's a slug
				const bySlug = allTerms.find((t) => t.slug === token);
				if (bySlug) return bySlug.id.toString();

				return null;
			})
			.filter((id): id is string => id !== null);

		onChange(ids);
	};

	return (
		<FormTokenField
			label={label || __('Terms', 'orbitools')}
			help={help}
			value={displayTokens}
			suggestions={suggestions}
			onChange={handleChange}
			placeholder={
				isLoading
					? __('Loading terms…', 'orbitools')
					: __('Search terms…', 'orbitools')
			}
			__experimentalExpandOnFocus={true}
			__experimentalShowHowTo={false}
			__nextHasNoMarginBottom={true}
			maxSuggestions={20}
			disabled={isLoading}
		/>
	);
}
