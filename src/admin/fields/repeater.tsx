/**
 * Repeater field — variable-length list of records, each described
 * by a `sub_fields` schema that uses the same field types as the
 * top-level schema. Sub-fields are rendered recursively via the
 * field registry, so anything you can do at the top level (text,
 * select, toggle, color, even another repeater) works inside a row.
 *
 * Storage: Array<Record<string,unknown>> — one object per row, keyed
 * by sub-field id. Empty repeaters store `[]` and the theme just
 * doesn't render anything for them.
 *
 * Layout matches the Core Cleanup stacked-section look:
 *
 *   * A header card with the field's title + description.
 *   * A Panel containing one PanelBody per row — each row is a
 *     stand-alone collapsible card with the row heading + a Remove
 *     button in the body.
 *   * An Add button below, naked on the gray background.
 *
 * Existing rows start collapsed for scannability; newly-added rows
 * open immediately so the user can start filling them in without
 * an extra click.
 *
 * Schema (extends FieldSchema):
 *   sub_fields:        FieldSchema[]     // shape of each row
 *   add_button_label?: string            // defaults to "Add row"
 *   row_label_field?:  string            // sub-field id to use as the row heading
 *   row_label_prefix?: string            // prepended to the row heading ("Row 1: …")
 */
import {
    Button,
    Card,
    CardHeader,
    Panel,
    PanelBody,
    __experimentalVStack as VStack,
} from '@wordpress/components';
import { useState } from '@wordpress/element';
import { registerFieldType, type FieldProps, getFieldComponent } from './registry';
import { FieldFallback } from '../components/FieldFallback';
import { evaluateShowIf } from '../lib/showIf';
import type { FieldSchema } from '../types';

/**
 * Resolve a sub-field's value, honouring its `default_from` directive
 * if present — looks up the named sibling's current value and
 * substitutes the option label when the sibling is a `select`. Used
 * both at row creation (to seed the value) and when a linked field
 * changes (to keep the dependent in sync until the user customizes
 * it). Returns the field's static `default` when there's nothing to
 * derive from.
 */
function resolveValueFromLinked(
    field: FieldSchema,
    row: Record<string, unknown>,
    subFields: FieldSchema[],
): unknown {
    const fromId = (field as { default_from?: unknown }).default_from;
    if (typeof fromId !== 'string' || fromId === '') {
        return field.default;
    }
    const raw = row[fromId];
    if (raw === undefined || raw === null || raw === '') {
        return field.default;
    }
    const sibling = subFields.find((sf) => sf.id === fromId);
    if (sibling !== undefined && Array.isArray(sibling.options)) {
        const opt = sibling.options.find((o) => String(o.value) === String(raw));
        if (opt !== undefined) {
            return opt.label;
        }
    }
    return raw;
}

function defaultsRow(subFields: FieldSchema[]): Record<string, unknown> {
    const row: Record<string, unknown> = {};
    // First pass: seed the static defaults so `default_from` lookups
    // in the second pass have something to read.
    for (const sf of subFields) {
        row[sf.id] = sf.default;
    }
    // Second pass: any field with `default_from` derives its initial
    // value from the linked sibling.
    for (const sf of subFields) {
        const fromId = (sf as { default_from?: unknown }).default_from;
        if (typeof fromId === 'string' && fromId !== '') {
            row[sf.id] = resolveValueFromLinked(sf, row, subFields);
        }
    }
    return row;
}

function rowHeading(
    row: Record<string, unknown>,
    index: number,
    subFields: FieldSchema[],
    labelField?: string,
    prefix?: string,
): string {
    let head = `Row ${index + 1}`;
    if (labelField !== undefined) {
        const raw = row[labelField];
        if (raw !== undefined && raw !== '' && raw !== null) {
            const sub = subFields.find((sf) => sf.id === labelField);
            if (sub !== undefined && Array.isArray(sub.options)) {
                const opt = sub.options.find((o) => String(o.value) === String(raw));
                head = opt?.label ?? String(raw);
            } else {
                head = String(raw);
            }
        }
    }
    if (prefix !== undefined && prefix !== '') {
        head = `${prefix} ${head}`;
    }
    return head;
}

function RepeaterField({ field, value, onChange }: FieldProps): JSX.Element {
    const subFields: FieldSchema[] = Array.isArray(field.sub_fields)
        ? (field.sub_fields as FieldSchema[])
        : [];
    const rows: Record<string, unknown>[] = Array.isArray(value)
        ? (value as Record<string, unknown>[])
        : [];
    const addButtonLabel =
        typeof field.add_button_label === 'string' ? field.add_button_label : 'Add row';
    const labelField =
        typeof field.row_label_field === 'string' ? field.row_label_field : undefined;
    const labelPrefix =
        typeof field.row_label_prefix === 'string' ? field.row_label_prefix : undefined;

    // Tracks which rows are open. PanelBody manages its own open state
    // when uncontrolled, but we need to seed newly-added rows as open
    // and keep existing rows collapsed — so we drive it via `opened`
    // (a controlled prop) and bump a render key only when the open-set
    // needs to override PanelBody's internal state.
    const [openRows, setOpenRows] = useState<Set<number>>(new Set());

    const updateRow = (rowIdx: number, key: string, val: unknown): void => {
        const next = rows.slice();
        const prevRow = next[rowIdx] ?? {};
        const updatedRow: Record<string, unknown> = { ...prevRow, [key]: val };

        // After the primary update, propagate to any dependent fields
        // (`default_from: key`). We only overwrite the dependent when
        // its current value still matches what `key`'s *previous* value
        // would have resolved to — i.e. the user hasn't customized it
        // since the row was created or the last sync. Once a user types
        // anything else, the link breaks and the field stays put.
        for (const sf of subFields) {
            const fromId = (sf as { default_from?: unknown }).default_from;
            if (fromId !== key) {
                continue;
            }
            const prevResolved = resolveValueFromLinked(sf, prevRow, subFields);
            const dependentCurrent = prevRow[sf.id];
            const isUntouched =
                dependentCurrent === undefined ||
                dependentCurrent === '' ||
                dependentCurrent === sf.default ||
                dependentCurrent === prevResolved;
            if (isUntouched) {
                updatedRow[sf.id] = resolveValueFromLinked(sf, updatedRow, subFields);
            }
        }

        next[rowIdx] = updatedRow;
        onChange(next);
    };

    const removeRow = (rowIdx: number): void => {
        setOpenRows((prev) => {
            const next = new Set<number>();
            prev.forEach((i) => {
                if (i < rowIdx) next.add(i);
                else if (i > rowIdx) next.add(i - 1);
            });
            return next;
        });
        onChange(rows.filter((_, i) => i !== rowIdx));
    };

    const addRow = (): void => {
        const newIdx = rows.length;
        setOpenRows((prev) => {
            const next = new Set(prev);
            next.add(newIdx);
            return next;
        });
        onChange([...rows, defaultsRow(subFields)]);
    };

    return (
        <VStack spacing={4} className="orbitools-repeater">
            <Card className="orbitools-repeater__header">
                <CardHeader>
                    <div>
                        <h3 className="orbitools-repeater__title">{field.label}</h3>
                        {field.description !== undefined && field.description !== '' && (
                            <p className="orbitools-repeater__description">{field.description}</p>
                        )}
                    </div>
                </CardHeader>
            </Card>

            <div className="orbitools-repeater__children">
                {rows.length > 0 && (
                    <Panel className="orbitools-repeater__rows">
                        {rows.map((row, idx) => (
                            <PanelBody
                                key={idx}
                                title={rowHeading(row, idx, subFields, labelField, labelPrefix)}
                                initialOpen={openRows.has(idx)}
                            >
                                <VStack spacing={3}>
                                    {subFields.map((sf) => {
                                        // Per-row show_if — evaluated against the row's
                                        // own values, not the top-level settings.
                                        if (
                                            !evaluateShowIf(
                                                sf.show_if as Record<string, unknown> | undefined,
                                                row,
                                            )
                                        ) {
                                            return null;
                                        }
                                        const SubComponent = getFieldComponent(String(sf.type));
                                        const subValue =
                                            row[sf.id] !== undefined ? row[sf.id] : sf.default;
                                        if (SubComponent === null) {
                                            return <FieldFallback key={sf.id} field={sf} />;
                                        }
                                        return (
                                            <SubComponent
                                                key={sf.id}
                                                field={sf}
                                                value={subValue}
                                                onChange={(v) => updateRow(idx, sf.id, v)}
                                                rowContext={{ row, subFields }}
                                            />
                                        );
                                    })}
                                    <div className="orbitools-repeater__row-actions">
                                        <Button
                                            variant="tertiary"
                                            isDestructive
                                            onClick={() => removeRow(idx)}
                                        >
                                            Remove
                                        </Button>
                                    </div>
                                </VStack>
                            </PanelBody>
                        ))}
                    </Panel>
                )}

                <div className="orbitools-repeater__footer">
                    <Button variant="primary" onClick={addRow}>
                        {addButtonLabel}
                    </Button>
                </div>
            </div>
        </VStack>
    );
}

registerFieldType('repeater', RepeaterField);
