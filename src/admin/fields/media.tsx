/**
 * Media field — opens WP's standard media library modal via the
 * wp.media() global (loaded by wp_enqueue_media() on the PHP side).
 *
 * Field value is the attachment ID (number) or undefined when empty.
 * On selection, we ask the modal for the picked attachment and store
 * its id. The preview thumbnail uses the URL the modal hands back;
 * we cache it on a ref so it survives re-renders without re-fetching.
 */
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

interface WpMediaFrame {
    open: () => void;
    on: (event: string, handler: () => void) => void;
    state: () => { get: (key: 'selection') => { first: () => WpMediaAttachment | undefined } };
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
    const attachmentId = typeof value === 'number' ? value : undefined;
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
        const bootstrap = window.orbitools;
        if (bootstrap === undefined) {
            return;
        }
        let cancelled = false;
        const url = bootstrap.adminUrl.replace(/\/?$/, '/') + 'admin-ajax.php';
        // Use the REST media endpoint — wp-api-fetch already has the
        // root URL + nonce wired in lib/api.ts. Inline import keeps
        // the field component focused; it's the only field that
        // needs WP REST data outside of orbitools/v1.
        import('@wordpress/api-fetch').then((mod) => {
            mod.default<{ source_url?: string; media_details?: { sizes?: Record<string, { source_url: string }> } }>({
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
        });
        // url unused — wp-api-fetch handles its own root. Variable
        // kept named so the inline ref above stays explanatory.
        void url;
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
                const attachment = frameRef.current?.state().get('selection').first();
                if (attachment === undefined) {
                    return;
                }
                onChange(attachment.id);
                setPreviewUrl(
                    attachment.sizes?.thumbnail?.url ?? attachment.url,
                );
            });
        }
        frameRef.current.open();
    };

    const clear = (): void => {
        onChange(undefined);
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
