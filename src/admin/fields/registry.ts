/**
 * Field type registry.
 *
 * Each field type registers a React component here at import time.
 * SettingsPage looks up the component by the field's `type` string;
 * unknown types render a FieldFallback.
 *
 * Adding a new field type is one register call from the field
 * component's module.
 */
import type { ComponentType } from 'react';
import type { FieldSchema } from '../types';

export interface FieldProps {
    field: FieldSchema;
    value: unknown;
    onChange: (value: unknown) => void;
}

const registry = new Map<string, ComponentType<FieldProps>>();

export function registerFieldType(type: string, component: ComponentType<FieldProps>): void {
    registry.set(type, component);
}

export function getFieldComponent(type: string): ComponentType<FieldProps> | null {
    return registry.get(type) ?? null;
}

export function getRegisteredTypes(): string[] {
    return Array.from(registry.keys());
}
