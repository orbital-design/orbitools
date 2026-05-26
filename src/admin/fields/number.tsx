import { __experimentalNumberControl as NumberControl } from '@wordpress/components';
import { registerFieldType, type FieldProps } from './registry';

function NumberField({ field, value, onChange }: FieldProps): JSX.Element {
    const numeric = typeof value === 'number' ? value : Number(value);

    return (
        <NumberControl
            label={field.label}
            help={field.description}
            value={Number.isFinite(numeric) ? numeric : undefined}
            min={field.min}
            max={field.max}
            step={field.step}
            onChange={(next) => {
                if (next === undefined || next === '') {
                    onChange(undefined);
                    return;
                }
                const parsed = typeof next === 'number' ? next : Number(next);
                onChange(Number.isFinite(parsed) ? parsed : undefined);
            }}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
        />
    );
}

registerFieldType('number', NumberField);
