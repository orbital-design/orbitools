import { TextControl } from '@wordpress/components';
import { registerFieldType, type FieldProps } from './registry';

function TextField({ field, value, onChange }: FieldProps): JSX.Element {
    return (
        <TextControl
            label={field.label}
            help={field.description}
            value={typeof value === 'string' ? value : ''}
            placeholder={field.placeholder}
            onChange={onChange}
            __nextHasNoMarginBottom
        />
    );
}

registerFieldType('text', TextField);
