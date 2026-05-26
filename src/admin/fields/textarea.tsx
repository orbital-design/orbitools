import { TextareaControl } from '@wordpress/components';
import { registerFieldType, type FieldProps } from './registry';

function TextareaField({ field, value, onChange }: FieldProps): JSX.Element {
    return (
        <TextareaControl
            label={field.label}
            help={field.description}
            value={typeof value === 'string' ? value : ''}
            placeholder={field.placeholder}
            rows={typeof field.rows === 'number' ? field.rows : 4}
            onChange={onChange}
            __nextHasNoMarginBottom
        />
    );
}

registerFieldType('textarea', TextareaField);
