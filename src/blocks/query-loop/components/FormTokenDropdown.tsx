/**
 * Reusable FormTokenField Dropdown Component
 * 
 * A consistent dropdown/token field component that combines FormTokenField
 * with suggested options dropdown functionality.
 */

import { FormTokenField } from '@wordpress/components';

interface FormTokenDropdownProps {
    label?: string;
    help?: string;
    value: string[];
    suggestions: string[];
    onChange: (tokens: string[]) => void;
    placeholder?: string;
    multiple?: boolean;
    disabled?: boolean;
}

export default function FormTokenDropdown({
    label,
    help,
    value = [],
    suggestions = [],
    onChange,
    placeholder = 'Type to search...',
    multiple = true,
    disabled = false
}: FormTokenDropdownProps) {
    return (
        <FormTokenField
            label={label}
            help={help}
            value={value}
            suggestions={suggestions}
            onChange={onChange}
            placeholder={placeholder}
            __experimentalExpandOnFocus={true}
            __experimentalShowHowTo={false}
            multiple={multiple}
            disabled={disabled}
            __nextHasNoMarginBottom={true}
        />
    );
}