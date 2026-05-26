import { ToggleControl } from '@wordpress/components';
import { registerFieldType, type FieldProps } from './registry';

/**
 * Coerce storage values to boolean. WordPress stores option booleans
 * as the strings '1' / '0' historically, so we accept both.
 */
function asBool(value: unknown): boolean {
    if (typeof value === 'boolean') {
        return value;
    }
    if (typeof value === 'number') {
        return value !== 0;
    }
    if (typeof value === 'string') {
        return value !== '' && value !== '0' && value.toLowerCase() !== 'false';
    }
    return Boolean(value);
}

function ToggleField({ field, value, onChange }: FieldProps): JSX.Element {
    return (
        <ToggleControl
            label={field.label}
            help={field.description}
            checked={asBool(value)}
            onChange={onChange}
            __nextHasNoMarginBottom
        />
    );
}

registerFieldType('toggle', ToggleField);
