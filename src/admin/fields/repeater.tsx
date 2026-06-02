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
 * Schema (extends FieldSchema):
 *   sub_fields:        FieldSchema[]     // shape of each row
 *   add_button_label?: string            // defaults to "Add row"
 *   row_label_field?:  string            // sub-field id to use as the row heading
 *   row_label_prefix?: string            // prepended to the row heading ("Row 1: …")
 */
import { BaseControl, Button } from '@wordpress/components';
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

    const updateRow = (rowIdx: number, key: string, val: unknown): void => {
        const next = rows.slice();
        next[rowIdx] = { ...next[rowIdx], [key]: val };
        onChange(next);
    };

    const removeRow = (rowIdx: number): void => {
        onChange(rows.filter((_, i) => i !== rowIdx));
    };

    const addRow = (): void => {
        onChange([...rows, defaultsRow(subFields)]);
    };

    return (
        <BaseControl
            id={field.id}
            label={field.label}
            help={field.description}
            __nextHasNoMarginBottom
        >
            <div className="orbitools-repeater">
                {rows.length === 0 ? (
                    <p className="orbitools-repeater__empty">No items yet.</p>
                ) : (
                    <ul className="orbitools-repeater__rows">
                        {rows.map((row, idx) => (
                            <li key={idx} className="orbitools-repeater__row">
                                <div className="orbitools-repeater__row-head">
                                    <span className="orbitools-repeater__row-heading">
                                        {rowHeading(row, idx, subFields, labelField, labelPrefix)}
                                    </span>
                                    <Button
                                        variant="tertiary"
                                        isDestructive
                                        onClick={() => removeRow(idx)}
                                    >
                                        Remove
                                    </Button>
                                </div>
                                <div className="orbitools-repeater__row-fields">
                                    {subFields.map((sf) => {
                                        // Evaluate per-row show_if against the row's own values,
                                        // not the top-level settings — a sub-field's `show_if`
                                        // gates it on other sub-fields in the same row.
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
                                </div>
                            </li>
                        ))}
                    </ul>
                )}
                <div className="orbitools-repeater__footer">
                    <Button variant="secondary" onClick={addRow}>
                        {addButtonLabel}
                    </Button>
                </div>
            </div>
        </BaseControl>
    );
}

registerFieldType('repeater', RepeaterField);
