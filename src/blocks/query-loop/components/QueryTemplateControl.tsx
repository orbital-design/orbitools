/**
 * Query Template Control Component
 * 
 * Provides a dropdown to select templates for the Query Loop block.
 * Fetches available templates from the REST API based on the current layout.
 */

import { useState, useEffect } from '@wordpress/element';
import { SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

interface TemplateOption {
    label: string;
    value: string;
}

interface QueryTemplateControlProps {
    layout: string;
    template: string;
    onChange: (value: string) => void;
}

export default function QueryTemplateControl({
    layout,
    template,
    onChange
}: QueryTemplateControlProps) {
    const [templates, setTemplates] = useState<TemplateOption[]>([]);
    const [loading, setLoading] = useState(true);

    // Fetch templates when layout changes
    useEffect(() => {
        const fetchTemplates = async () => {
            setLoading(true);
            try {
                const response = await apiFetch({
                    path: `/orbitools/v1/query-loop/templates?layout=${layout}`
                });
                
                if (Array.isArray(response)) {
                    setTemplates(response as TemplateOption[]);
                } else {
                    console.warn('Unexpected template response format:', response);
                    setTemplates([]);
                }
            } catch (error) {
                console.error('Error fetching templates:', error);
                // Provide fallback options
                setTemplates([
                    { label: __('Plugin Default', 'orbitools'), value: 'plugin-default' }
                ]);
            } finally {
                setLoading(false);
            }
        };

        fetchTemplates();
    }, [layout]);

    // Ensure the current template is valid for the selected layout
    useEffect(() => {
        if (!loading && templates.length > 0) {
            const currentTemplateExists = templates.some(t => t.value === template);
            if (!currentTemplateExists) {
                // If current template doesn't exist for this layout, default to plugin-default
                const defaultTemplate = templates.find(t => t.value === 'plugin-default') || templates[0];
                onChange(defaultTemplate.value);
            }
        }
    }, [loading, templates, template, onChange]);

    if (loading) {
        return (
            <SelectControl
                label={__('Template', 'orbitools')}
                value={template}
                options={[
                    { label: __('Loading templates...', 'orbitools'), value: template }
                ]}
                disabled={true}
                help={__('Select a template to control how posts are displayed', 'orbitools')}
                __nextHasNoMarginBottom={true}
            />
        );
    }

    return (
        <SelectControl
            label={__('Template', 'orbitools')}
            value={template}
            options={templates}
            onChange={onChange}
            help={__('Select a template to control how posts are displayed', 'orbitools')}
            __nextHasNoMarginBottom={true}
        />
    );
}