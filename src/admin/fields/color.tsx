import { BaseControl, ColorPicker } from '@wordpress/components';
import { registerFieldType, type FieldProps } from './registry';

function ColorField({ field, value, onChange }: FieldProps): JSX.Element {
    return (
        <BaseControl
            id={field.id}
            label={field.label}
            help={field.description}
            __nextHasNoMarginBottom
        >
            <ColorPicker
                color={typeof value === 'string' ? value : ''}
                enableAlpha={false}
                onChange={onChange}
            />
        </BaseControl>
    );
}

registerFieldType('color', ColorField);
