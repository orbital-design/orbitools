import { CheckboxControl, BaseControl, Flex, FlexBlock } from '@wordpress/components';
import { registerFieldType, type FieldProps } from './registry';
import type { FieldOption } from '../types';

function CheckboxGroupField({ field, value, onChange }: FieldProps): JSX.Element {
    const options: FieldOption[] = Array.isArray(field.options) ? field.options : [];
    const selected = new Set(
        Array.isArray(value) ? value.map(String) : []
    );

    const toggle = (optionValue: string): void => {
        const next = new Set(selected);
        if (next.has(optionValue)) {
            next.delete(optionValue);
        } else {
            next.add(optionValue);
        }
        onChange(Array.from(next));
    };

    return (
        <BaseControl
            id={field.id}
            label={field.label}
            help={field.description}
            __nextHasNoMarginBottom
        >
            <Flex direction="column" gap={1} align="flex-start">
                {options.map((opt) => {
                    const optValue = String(opt.value);
                    return (
                        <FlexBlock key={optValue}>
                            <CheckboxControl
                                label={opt.label}
                                checked={selected.has(optValue)}
                                onChange={() => toggle(optValue)}
                                __nextHasNoMarginBottom
                            />
                        </FlexBlock>
                    );
                })}
            </Flex>
        </BaseControl>
    );
}

registerFieldType('checkbox-group', CheckboxGroupField);
