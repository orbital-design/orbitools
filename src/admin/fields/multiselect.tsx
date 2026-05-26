import { FormTokenField } from '@wordpress/components';
import { registerFieldType, type FieldProps } from './registry';
import type { FieldOption } from '../types';

function MultiSelectField({ field, value, onChange }: FieldProps): JSX.Element {
    const options: FieldOption[] = Array.isArray(field.options) ? field.options : [];
    const labelFor = new Map(options.map((o) => [String(o.value), o.label]));
    const valueFor = new Map(options.map((o) => [o.label, String(o.value)]));

    const selectedValues = Array.isArray(value) ? value.map(String) : [];
    const tokens = selectedValues.map((v) => labelFor.get(v) ?? v);

    return (
        <FormTokenField
            label={field.label}
            value={tokens}
            suggestions={options.map((o) => o.label)}
            onChange={(nextTokens) => {
                const tokensArray = (nextTokens as Array<string | { value: string }>).map((t) =>
                    typeof t === 'string' ? t : t.value
                );
                const next = tokensArray.map((t) => valueFor.get(t) ?? t);
                onChange(next);
            }}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
        />
    );
}

registerFieldType('multiselect', MultiSelectField);
