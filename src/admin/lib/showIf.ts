/**
 * `show_if` evaluator.
 *
 * Equality-only by design (per brief): each key in the predicate map
 * must equal the corresponding current value for the field to render.
 * Anything richer routes to a custom Page.tsx.
 */
import type { ModuleSettings } from '../types';

export function evaluateShowIf(
    predicate: Record<string, unknown> | undefined,
    settings: ModuleSettings
): boolean {
    if (predicate === undefined) {
        return true;
    }
    for (const [key, expected] of Object.entries(predicate)) {
        // Loose equality (==) handles the legacy '1'/'0' vs true/false
        // mismatch between stored option values and schema defaults.
        // eslint-disable-next-line eqeqeq
        if (settings[key] != expected) {
            return false;
        }
    }
    return true;
}
