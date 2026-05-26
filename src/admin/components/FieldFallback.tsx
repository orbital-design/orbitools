/**
 * Rendered when a field schema references a type not in the registry.
 * Surfaces the id and type in the UI so authors can spot the typo,
 * while letting the rest of the settings page render normally.
 */
import { Notice } from '@wordpress/components';
import type { FieldSchema } from '../types';

interface FieldFallbackProps {
    field: FieldSchema;
}

export function FieldFallback({ field }: FieldFallbackProps): JSX.Element {
    return (
        <Notice status="warning" isDismissible={false}>
            <strong>{field.label || field.id}</strong> — field type{' '}
            <code>{String(field.type)}</code> is not registered. This setting
            cannot be edited from the new admin until a renderer is added.
        </Notice>
    );
}
