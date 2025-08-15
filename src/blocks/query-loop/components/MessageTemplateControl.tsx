/**
 * Message Template Control Component
 * 
 * Provides a dropdown to select message templates for the Query Loop block.
 * Fetches available message templates from the REST API.
 */

import { useState, useEffect } from '@wordpress/element';
import { SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

interface MessageTemplateOption {
    label: string;
    value: string;
}

interface MessageTemplateControlProps {
    messageTemplate: string;
    onChange: (value: string) => void;
}

export default function MessageTemplateControl({
    messageTemplate,
    onChange
}: MessageTemplateControlProps) {
    const [templates, setTemplates] = useState<MessageTemplateOption[]>([]);
    const [loading, setLoading] = useState(true);

    // Fetch message templates
    useEffect(() => {
        const fetchTemplates = async () => {
            setLoading(true);
            try {
                const response = await apiFetch({
                    path: '/orbitools/v1/query-loop/message-templates'
                });
                
                if (Array.isArray(response)) {
                    setTemplates(response as MessageTemplateOption[]);
                } else {
                    console.warn('Unexpected message template response format:', response);
                    setTemplates([]);
                }
            } catch (error) {
                console.error('Error fetching message templates:', error);
                // Provide fallback options
                setTemplates([
                    { label: __('Default', 'orbitools'), value: 'default' }
                ]);
            } finally {
                setLoading(false);
            }
        };

        fetchTemplates();
    }, []);

    // Ensure the current template is valid
    useEffect(() => {
        if (!loading && templates.length > 0) {
            const currentTemplateExists = templates.some(t => t.value === messageTemplate);
            if (!currentTemplateExists) {
                // If current template doesn't exist, default to 'default' or first available
                const defaultTemplate = templates.find(t => t.value === 'default') || templates[0];
                onChange(defaultTemplate.value);
            }
        }
    }, [loading, templates, messageTemplate, onChange]);

    if (loading) {
        return (
            <SelectControl
                label={__('Message Template', 'orbitools')}
                value={messageTemplate}
                options={[
                    { label: __('Loading templates...', 'orbitools'), value: messageTemplate }
                ]}
                disabled={true}
                help={__('Select a template for empty state messages', 'orbitools')}
                __nextHasNoMarginBottom={true}
            />
        );
    }

    return (
        <SelectControl
            label={__('Message Template', 'orbitools')}
            value={messageTemplate}
            options={templates}
            onChange={onChange}
            help={__('Select a template for empty state messages', 'orbitools')}
            __nextHasNoMarginBottom={true}
        />
    );
}