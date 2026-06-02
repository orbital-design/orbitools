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
 * Layout: the field's label + description render in a dedicated
 * Card at the top; each row gets its own collapsible Card below.
 * Newly-added rows open by default; existing rows start collapsed
 * so a long list stays scannable.
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
    CardBody,
    __experimentalVStack as VStack,
} from '@wordpress/components';
import { useState } from '@wordpress/element';
import { chevronDown, chevronUp } from '@wordpress/icons';
import { registerFieldType, type FieldProps, getFieldComponent } from './registry';
import { FieldFallback } from '../components/FieldFallback';
import { evaluateShowIf } from '../lib/showIf';
import type { FieldSchema } from '../types';

function defaultsRow(subFields: FieldSchema[]): Record<string, unknown> {
    const row: Record<string, unknown> = {};
    for (const sf of subFields) {
        row[sf.id] = sf.default;
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

    // Open/closed state per row index. Existing rows start collapsed for
    // scannability; addRow() opens the new index immediately so the user
    // doesn't have to click to start filling it in. removeRow() compacts
    // the set so indices above the removed row shift down with the data.
    const [openRows, setOpenRows] = useState<Set<number>>(new Set());

    const isOpen = (idx: number): boolean => openRows.has(idx);

    const toggleRow = (idx: number): void => {
        setOpenRows((prev) => {
            const next = new Set(prev);
            if (next.has(idx)) {
                next.delete(idx);
            } else {
                next.add(idx);
            }
            return next;
        });
    };

    const updateRow = (rowIdx: number, key: string, val: unknown): void => {
        const next = rows.slice();
        next[rowIdx] = { ...next[rowIdx], [key]: val };
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
            <Card className="orbitools-repeater__header-card">
                <CardHeader>
                    <h3 className="orbitools-repeater__title">{field.label}</h3>
                </CardHeader>
                {field.description !== undefined && field.description !== '' && (
                    <CardBody>
                        <p className="orbitools-repeater__description">{field.description}</p>
                    </CardBody>
                )}
            </Card>

            {rows.length === 0 ? (
                <p className="orbitools-repeater__empty">No items yet.</p>
            ) : (
                rows.map((row, idx) => {
                    const open = isOpen(idx);
                    return (
                        <Card key={idx} className="orbitools-repeater__row-card">
                            <CardHeader className="orbitools-repeater__row-head">
                                <Button
                                    icon={open ? chevronUp : chevronDown}
                                    label={open ? 'Collapse' : 'Expand'}
                                    onClick={() => toggleRow(idx)}
                                    className="orbitools-repeater__toggle"
                                />
                                <button
                                    type="button"
                                    className="orbitools-repeater__row-heading-button"
                                    onClick={() => toggleRow(idx)}
                                >
                                    {rowHeading(row, idx, subFields, labelField, labelPrefix)}
                                </button>
                                <Button
                                    variant="tertiary"
                                    isDestructive
                                    onClick={() => removeRow(idx)}
                                    className="orbitools-repeater__remove"
                                >
                                    Remove
                                </Button>
                            </CardHeader>
                            {open && (
                                <CardBody>
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
                                                />
                                            );
                                        })}
                                    </VStack>
                                </CardBody>
                            )}
                        </Card>
                    );
                })
            )}

            <div className="orbitools-repeater__footer">
                <Button variant="secondary" onClick={addRow}>
                    {addButtonLabel}
                </Button>
            </div>
        </VStack>
    );
}

registerFieldType('repeater', RepeaterField);
