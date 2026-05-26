import { RangeControl } from '@wordpress/components';
import { registerFieldType, type FieldProps } from './registry';

function RangeField({ field, value, onChange }: FieldProps): JSX.Element {
    const numeric = typeof value === 'number' ? value : Number(value);
    return (
        <RangeControl
            label={field.label}
            help={field.description}
            value={Number.isFinite(numeric) ? numeric : undefined}
            min={field.min ?? 0}
            max={field.max ?? 100}
            step={field.step ?? 1}
            onChange={(next) => onChange(next ?? undefined)}
            __nextHasNoMarginBottom
        />
    );
}

registerFieldType('range', RangeField);
