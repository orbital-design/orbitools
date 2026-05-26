import { SelectControl } from '@wordpress/components';
import { registerFieldType, type FieldProps } from './registry';
import type { FieldOption } from '../types';

function SelectField({ field, value, onChange }: FieldProps): JSX.Element {
    const options: FieldOption[] = Array.isArray(field.options) ? field.options : [];
    return (
        <SelectControl
            label={field.label}
            help={field.description}
            value={typeof value === 'string' || typeof value === 'number' ? String(value) : ''}
            options={options.map((opt) => ({
                value: String(opt.value),
                label: opt.label,
            }))}
            onChange={onChange}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
        />
    );
}

registerFieldType('select', SelectField);
