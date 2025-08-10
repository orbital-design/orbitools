/**
 * Shared control helper functions for consistent styling across blocks
 * 
 * @file blocks/utils/control-helpers.ts
 * @since 1.0.0
 */

import React from 'react';
import {
    __experimentalToolsPanelItem as ToolsPanelItem,
    __experimentalToggleGroupControl as ToggleGroupControl,
    __experimentalToggleGroupControlOption as ToggleGroupControlOption,
} from '@wordpress/components';

/**
 * Helper function to create a ToolsPanelItem with consistent styling
 */
export function createToolsPanelItem(
    controlName: string,
    hasValue: () => boolean,
    onDeselect: () => void,
    label: string,
    children: React.ReactNode,
    isShownByDefault = false,
    panelId = 'default-panel'
) {
    return (
        <ToolsPanelItem
            hasValue={hasValue}
            onDeselect={onDeselect}
            label={label}
            isShownByDefault={isShownByDefault}
            panelId={panelId}
        >
            {children}
        </ToolsPanelItem>
    );
}

/**
 * Helper function to create labeled toggle group controls
 */
export function createToggleGroup(
    value: string | number,
    onChange: (value: string | number) => void,
    options: readonly { value: string | number; label: string }[],
    label?: string
) {
    const control = (
        <ToggleGroupControl
            value={value}
            onChange={onChange}
            isBlock={true}
            __next40pxDefaultSize={true}
            __nextHasNoMarginBottom={true}
        >
            {options.map(option => (
                <ToggleGroupControlOption
                    key={option.value}
                    value={option.value}
                    label={option.label}
                />
            ))}
        </ToggleGroupControl>
    );

    if (label) {
        return (
            <div>
                <label style={{
                    display: 'block',
                    marginBottom: '8px',
                    fontSize: '11px',
                    fontWeight: '500',
                    textTransform: 'uppercase',
                    color: '#1e1e1e'
                }}>
                    {label}
                </label>
                {control}
            </div>
        );
    }

    return control;
}

/**
 * Helper to get spacing value by index from theme.json spacing sizes
 */
export function getSpacingValueByIndex(spacingSizes: any[], index: number) {
    if (spacingSizes && Array.isArray(spacingSizes) && spacingSizes[index]) {
        return spacingSizes[index].size;
    }
    return '';
}

/**
 * Helper to get spacing index by value from theme.json spacing sizes
 */
export function getSpacingIndexByValue(spacingSizes: any[], value: string) {
    if (!spacingSizes || !Array.isArray(spacingSizes) || !value) return -1;
    return spacingSizes.findIndex((size: any) => size.slug === value);
}

