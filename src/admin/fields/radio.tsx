import { RadioControl } from '@wordpress/components';
import { registerFieldType, type FieldProps } from './registry';
import type { FieldOption } from '../types';

function RadioField({ field, value, onChange }: FieldProps): JSX.Element {
    const options: FieldOption[] = Array.isArray(field.options) ? field.options : [];
    return (
        <RadioControl
            label={field.label}
            help={field.description}
            selected={typeof value === 'string' || typeof value === 'number' ? String(value) : ''}
            options={options.map((opt) => ({
                value: String(opt.value),
                label: opt.label,
            }))}
            onChange={onChange}
        />
    );
}

registerFieldType('radio', RadioField);
