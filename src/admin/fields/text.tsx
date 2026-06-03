import { TextControl } from '@wordpress/components';
import { registerFieldType, type FieldProps } from './registry';

/**
 * Resolve the effective placeholder for the text field. Order of
 * precedence:
 *
 *   1. `field.placeholder` — static placeholder from the schema.
 *   2. `field.placeholder_from` — name of another sub-field in the
 *      same repeater row. We use the current value of that sibling
 *      (resolved to the option label when the sibling is a `select`),
 *      so users editing a "Label override" / "Title" field can see
 *      the default that would be used if they leave it blank.
 */
function resolvePlaceholder(props: FieldProps): string | undefined {
    const { field, rowContext } = props;
    const staticPlaceholder = field.placeholder;
    if (typeof staticPlaceholder === 'string' && staticPlaceholder !== '') {
        return staticPlaceholder;
    }
    const fromId = (field as { placeholder_from?: unknown }).placeholder_from;
    if (typeof fromId !== 'string' || fromId === '' || rowContext === undefined) {
        return undefined;
    }
    const raw = rowContext.row[fromId];
    if (raw === undefined || raw === null || raw === '') {
        return undefined;
    }
    const sibling = rowContext.subFields.find((sf) => sf.id === fromId);
    if (sibling !== undefined && Array.isArray(sibling.options)) {
        const opt = sibling.options.find((o) => String(o.value) === String(raw));
        if (opt !== undefined) {
            return opt.label;
        }
    }
    return String(raw);
}

function TextField(props: FieldProps): JSX.Element {
    const { field, value, onChange } = props;
    return (
        <TextControl
            label={field.label}
            help={field.description}
            value={typeof value === 'string' ? value : ''}
            placeholder={resolvePlaceholder(props)}
            onChange={onChange}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
        />
    );
}

registerFieldType('text', TextField);
