/**
 * Media field — opens WP's standard media library modal via the
 * wp.media() global (loaded by wp_enqueue_media() on the PHP side).
 *
 * Field value is the attachment ID (number) or undefined when empty.
 * On selection, we ask the modal for the picked attachment and store
 * its id. The preview thumbnail uses the URL the modal hands back;
 * we cache it on a ref so it survives re-renders without re-fetching.
 */
import apiFetch from '@wordpress/api-fetch';
import { useEffect, useRef, useState } from '@wordpress/element';
import { BaseControl, Button } from '@wordpress/components';
import { registerFieldType, type FieldProps } from './registry';

interface WpMediaAttachment {
    id: number;
    url: string;
    sizes?: Record<string, { url: string; width: number; height: number }>;
    mime?: string;
    type?: string;
}

// `first()` returns a Backbone Model — *not* a plain object. We must
// call `.toJSON()` before reading attributes, otherwise `model.url`
// resolves to Backbone's REST-URL method (a function), and passing
// that function into a React state setter triggers the functional-
// updater pattern, which then *invokes* the Backbone url() and
// throws "A 'url' property or function must be specified".
interface WpMediaBackboneModel {
    toJSON: () => WpMediaAttachment;
}

interface WpMediaFrame {
    open: () => void;
    on: (event: string, handler: () => void) => void;
    state: () => { get: (key: 'selection') => { first: () => WpMediaBackboneModel | undefined } };
}

interface WpMediaGlobal {
    (opts: {
        title?: string;
        button?: { text?: string };
        multiple?: boolean;
        library?: { type?: string };
    }): WpMediaFrame;
}

declare global {
    interface Window {
        wp?: { media?: WpMediaGlobal };
    }
}

function MediaField({ field, value, onChange }: FieldProps): JSX.Element {
    // Accept either a number or a numeric string — `site_logo` and
    // friends come back from get_option() as strings, and we don't
    // want the typeguard to drop them on the floor (which would
    // nuke the preview via the effect below).
    const numericValue = Number(value);
    const attachmentId = Number.isInteger(numericValue) && numericValue > 0
        ? numericValue
        : undefined;
    const [previewUrl, setPreviewUrl] = useState<string | undefined>(undefined);
    const frameRef = useRef<WpMediaFrame | null>(null);

    // Fetch the attachment's URL when we have an id but no preview
    // cached — covers the "page just loaded with a stored value" case.
    useEffect(() => {
        if (attachmentId === undefined) {
            setPreviewUrl(undefined);
            return;
        }
        if (previewUrl !== undefined) {
            return;
        }
        let cancelled = false;
        apiFetch<{
            source_url?: string;
            media_details?: { sizes?: Record<string, { source_url: string }> };
        }>({
            path: `wp/v2/media/${attachmentId}`,
        })
            .then((attachment) => {
                if (cancelled) {
                    return;
                }
                const thumb =
                    attachment.media_details?.sizes?.thumbnail?.source_url ??
                    attachment.source_url;
                if (thumb !== undefined) {
                    setPreviewUrl(thumb);
                }
            })
            .catch(() => {
                if (!cancelled) {
                    setPreviewUrl(undefined);
                }
            });
        return () => {
            cancelled = true;
        };
    }, [attachmentId, previewUrl]);

    const openLibrary = (): void => {
        const wpMedia = window.wp?.media;
        if (wpMedia === undefined) {
            return;
        }
        if (frameRef.current === null) {
            frameRef.current = wpMedia({
                title: typeof field.label === 'string' ? field.label : 'Select media',
                button: { text: 'Use this media' },
                multiple: false,
                library: { type: typeof field.libraryType === 'string' ? field.libraryType : 'image' },
            });
            frameRef.current.on('select', () => {
                const model = frameRef.current?.state().get('selection').first();
                if (model === undefined) {
                    return;
                }
                const attachment = model.toJSON();
                // Coerce to number so the parent always sees the
                // attachment id in a consistent shape.
                onChange(Number(attachment.id));
                setPreviewUrl(
                    attachment.sizes?.thumbnail?.url ?? attachment.url,
                );
            });
        }
        frameRef.current.open();
    };

    const clear = (): void => {
        // Send explicit null so the key survives JSON serialisation
        // (undefined would be silently dropped, leaving the server-
        // side option untouched). The controller treats null as
        // "delete this option" for wp_option-bound fields.
        onChange(null);
        setPreviewUrl(undefined);
    };

    return (
        <BaseControl
            id={field.id}
            label={field.label}
            help={field.description}
            __nextHasNoMarginBottom
        >
            <div className="orbitools-media-field">
                {previewUrl !== undefined ? (
                    <img
                        src={previewUrl}
                        alt=""
                        className="orbitools-media-field__preview"
                    />
                ) : (
                    <div className="orbitools-media-field__placeholder" aria-hidden="true">
                        No media selected
                    </div>
                )}
                <div className="orbitools-media-field__actions">
                    <Button variant="secondary" onClick={openLibrary}>
                        {attachmentId === undefined ? 'Select media' : 'Replace media'}
                    </Button>
                    {attachmentId !== undefined && (
                        <Button variant="tertiary" isDestructive onClick={clear}>
                            Remove
                        </Button>
                    )}
                </div>
            </div>
        </BaseControl>
    );
}

registerFieldType('media', MediaField);
