/**
 * Video block — Edit component.
 *
 * One URL field (we detect YouTube / Vimeo / generic oEmbed) OR a
 * file uploader for MP4 + WebM. The editor preview uses
 * ServerSideRender so the rendered output matches the front end
 * exactly (render.php → matching PHP partial).
 *
 * Ported 1:1 from the dream-and-leap theme's blocks/video/src/edit.js.
 * Only deviation: the aspect-ratio options now come from
 * `theme.json`'s `dimensions.aspectRatios` setting (with a hardcoded
 * fallback when the theme doesn't declare any).
 */
import {
    useBlockProps,
    InspectorControls,
    MediaPlaceholder,
    MediaUpload,
    MediaUploadCheck,
} from '@wordpress/block-editor';
import {
    BaseControl,
    Button,
    PanelBody,
    SelectControl,
    TextControl,
    ToggleControl,
} from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import { useEffect, useRef } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import { video as videoIcon } from '@wordpress/icons';

const ALLOWED_VIDEO_TYPES = ['video'];

// Hardcoded fallback for themes that don't declare
// `dimensions.aspectRatios` in theme.json.
const FALLBACK_ASPECT_RATIOS = [
    { value: '16-9', label: '16:9 (Widescreen)' },
    { value: '4-3', label: '4:3 (Standard)' },
    { value: '1-1', label: '1:1 (Square)' },
    { value: '9-16', label: '9:16 (Portrait)' },
    { value: '21-9', label: '21:9 (Ultrawide)' },
];

interface ThemeJsonAspectRatio {
    name?: string;
    slug?: string;
    ratio?: string;
}

/** Match a URL → 'youtube' | 'vimeo' | 'embed'. '' for empty. */
function detectProvider(url: string): '' | 'youtube' | 'vimeo' | 'embed' {
    if (!url) return '';
    if (/(?:youtube\.com|youtu\.be)/.test(url)) return 'youtube';
    if (/vimeo\.com/.test(url)) return 'vimeo';
    return 'embed';
}

/** Extract a Vimeo numeric video ID from a URL, or null. */
function extractVimeoId(url: string): string | null {
    if (!url) return null;
    const match = url.match(/vimeo\.com\/(?:video\/)?(\d+)/);
    return match ? match[1] : null;
}

/** Extract an 11-char YouTube video ID from a URL, or null. */
function extractYoutubeId(url: string): string | null {
    if (!url) return null;
    const match = url.match(
        /(?:youtube\.com\/(?:watch\?v=|embed\/|v\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/,
    );
    return match ? match[1] : null;
}

interface VideoAttributes {
    videoUrl: string;
    mp4Id?: number;
    mp4Url: string;
    webmId?: number;
    webmUrl: string;
    posterId?: number;
    posterUrl: string;
    aspectRatio: string;
    autoplay: boolean;
    loop: boolean;
    muted: boolean;
    controls: boolean;
    playsinline: boolean;
    ytDisableRelated: boolean;
    ytModestBranding: boolean;
    ytPauseOnScroll: boolean;
    ytPauseOthers: boolean;
    vimeoHideTitle: boolean;
    vimeoHideByline: boolean;
    vimeoHidePortrait: boolean;
    vimeoPauseOnScroll: boolean;
}

interface EditProps {
    attributes: VideoAttributes;
    setAttributes: (attrs: Partial<VideoAttributes>) => void;
}

export default function Edit({ attributes, setAttributes }: EditProps): JSX.Element {
    const {
        videoUrl,
        mp4Url,
        webmUrl,
        posterId,
        posterUrl,
        aspectRatio,
    } = attributes;

    const provider = detectProvider(videoUrl);
    const isFileMode = !!mp4Url || !!webmUrl;
    const isUrlMode = !!videoUrl;
    const hasVideo = isUrlMode || isFileMode;

    const blockProps = useBlockProps();

    // Pull aspect ratios from theme.json's dimensions.aspectRatios.
    // Falls back to the hardcoded list when the theme hasn't declared
    // its own.
    const themeAspectRatios = useSelect((select) => {
        const settings = (select('core/block-editor') as any)?.getSettings?.();
        const list: ThemeJsonAspectRatio[] | undefined =
            settings?.__experimentalFeatures?.dimensions?.aspectRatios?.theme ??
            settings?.__experimentalFeatures?.dimensions?.aspectRatios?.default ??
            settings?.dimensions?.aspectRatios;
        return Array.isArray(list) ? list : null;
    }, []);

    const aspectRatioOptions = themeAspectRatios && themeAspectRatios.length > 0
        ? themeAspectRatios.map((r) => ({
            value: typeof r.slug === 'string' ? r.slug : String(r.ratio ?? ''),
            label: typeof r.name === 'string' ? r.name : String(r.slug ?? r.ratio ?? ''),
        }))
        : FALLBACK_ASPECT_RATIOS;

    // Once a YouTube or Vimeo URL is set and no poster is chosen,
    // sideload the provider's thumbnail into the media library and
    // use it as the poster. The REST endpoint resolves the thumb URL
    // per provider then runs media_handle_sideload. The
    // sideloadedRef guards against re-firing during in-flight
    // requests or after the user has explicitly removed the
    // auto-sideloaded poster.
    const sideloadedRef = useRef(new Set<string>());
    useEffect(() => {
        if (posterId) return;
        const videoId =
            provider === 'vimeo'
                ? extractVimeoId(videoUrl)
                : provider === 'youtube'
                    ? extractYoutubeId(videoUrl)
                    : null;
        if (!videoId) return;
        const key = provider + ':' + videoId;
        if (sideloadedRef.current.has(key)) return;
        sideloadedRef.current.add(key);

        apiFetch<{ id: number; url: string }>({
            path: '/orbitools/v1/video/sideload-poster',
            method: 'POST',
            data: { provider, video_id: videoId },
        })
            .then(({ id, url }) => {
                setAttributes({ posterId: id, posterUrl: url });
            })
            .catch(() => {
                // Silent — render.php still falls back to provider-
                // derived thumb URLs at runtime, so the front end
                // stays correct.
                sideloadedRef.current.delete(key);
            });
    }, [provider, videoUrl, posterId, setAttributes]);

    /**
     * Clear everything that identifies the current video source,
     * returning the block to its empty (MediaPlaceholder) state so
     * the editor can pick a fresh URL or file.
     */
    const resetVideo = () =>
        setAttributes({
            videoUrl: '',
            mp4Id: undefined,
            mp4Url: '',
            webmId: undefined,
            webmUrl: '',
            posterId: undefined,
            posterUrl: '',
        });

    // ----------------------------------------------------------------
    // Empty state — MediaPlaceholder with URL input + file upload.
    // ----------------------------------------------------------------
    if (!hasVideo) {
        return (
            <div {...blockProps}>
                <MediaPlaceholder
                    icon={videoIcon}
                    labels={{
                        title: __('Video', 'orbitools'),
                        instructions: __(
                            'Paste a YouTube or Vimeo URL, paste any embeddable video URL, or upload a video file.',
                            'orbitools',
                        ),
                    }}
                    accept="video/*"
                    allowedTypes={ALLOWED_VIDEO_TYPES}
                    value={{}}
                    onSelect={(media: { id: number; url: string }) => {
                        // Picked from media library / uploaded — set MP4.
                        setAttributes({
                            mp4Id: media.id,
                            mp4Url: media.url,
                        });
                    }}
                    onSelectURL={(url: string) => setAttributes({ videoUrl: url })}
                />
            </div>
        );
    }

    // ----------------------------------------------------------------
    // Populated state — server-side render preview + sidebar settings.
    // ----------------------------------------------------------------
    return (
        <div {...blockProps}>
            <InspectorControls>
                <PanelBody title={__('Source', 'orbitools')} initialOpen={true}>
                    {isUrlMode && (
                        <TextControl
                            label={__('Video URL', 'orbitools')}
                            help={__(
                                'YouTube, Vimeo, or any embeddable URL.',
                                'orbitools',
                            )}
                            value={videoUrl}
                            onChange={(next: string) => setAttributes({ videoUrl: next })}
                            placeholder="https://"
                            __nextHasNoMarginBottom
                            __next40pxDefaultSize
                        />
                    )}

                    {isFileMode && (
                        <>
                            <MediaUploadCheck>
                                <MediaUpload
                                    allowedTypes={ALLOWED_VIDEO_TYPES}
                                    onSelect={(media: { id: number; url: string }) =>
                                        setAttributes({
                                            mp4Id: media.id,
                                            mp4Url: media.url,
                                        })
                                    }
                                    value={attributes.mp4Id}
                                    render={({ open }: { open: () => void }) => (
                                        <BaseControl
                                            __nextHasNoMarginBottom
                                            label={__('MP4 file', 'orbitools')}
                                        >
                                            <div className="orb-video__file-row">
                                                {mp4Url && (
                                                    <span className="orb-video__file-name">
                                                        {mp4Url.split('/').pop()}
                                                    </span>
                                                )}
                                                <Button
                                                    variant="secondary"
                                                    size="small"
                                                    onClick={open}
                                                >
                                                    {mp4Url
                                                        ? __('Replace', 'orbitools')
                                                        : __('Upload / select', 'orbitools')}
                                                </Button>
                                                {mp4Url && (
                                                    <Button
                                                        variant="link"
                                                        isDestructive
                                                        size="small"
                                                        onClick={() =>
                                                            setAttributes({
                                                                mp4Id: undefined,
                                                                mp4Url: '',
                                                            })
                                                        }
                                                    >
                                                        {__('Remove', 'orbitools')}
                                                    </Button>
                                                )}
                                            </div>
                                        </BaseControl>
                                    )}
                                />
                            </MediaUploadCheck>

                            <MediaUploadCheck>
                                <MediaUpload
                                    allowedTypes={ALLOWED_VIDEO_TYPES}
                                    onSelect={(media: { id: number; url: string }) =>
                                        setAttributes({
                                            webmId: media.id,
                                            webmUrl: media.url,
                                        })
                                    }
                                    value={attributes.webmId}
                                    render={({ open }: { open: () => void }) => (
                                        <BaseControl
                                            __nextHasNoMarginBottom
                                            label={__('WebM file (optional)', 'orbitools')}
                                            help={__(
                                                'Alternate format for browsers that prefer WebM.',
                                                'orbitools',
                                            )}
                                        >
                                            <div className="orb-video__file-row">
                                                {webmUrl && (
                                                    <span className="orb-video__file-name">
                                                        {webmUrl.split('/').pop()}
                                                    </span>
                                                )}
                                                <Button
                                                    variant="secondary"
                                                    size="small"
                                                    onClick={open}
                                                >
                                                    {webmUrl
                                                        ? __('Replace', 'orbitools')
                                                        : __('Upload / select', 'orbitools')}
                                                </Button>
                                                {webmUrl && (
                                                    <Button
                                                        variant="link"
                                                        isDestructive
                                                        size="small"
                                                        onClick={() =>
                                                            setAttributes({
                                                                webmId: undefined,
                                                                webmUrl: '',
                                                            })
                                                        }
                                                    >
                                                        {__('Remove', 'orbitools')}
                                                    </Button>
                                                )}
                                            </div>
                                        </BaseControl>
                                    )}
                                />
                            </MediaUploadCheck>
                        </>
                    )}

                    <MediaUploadCheck>
                        <MediaUpload
                            allowedTypes={['image']}
                            onSelect={(media: { id: number; url: string }) =>
                                setAttributes({
                                    posterId: media.id,
                                    posterUrl: media.url,
                                })
                            }
                            value={posterId}
                            render={({ open }: { open: () => void }) => (
                                <BaseControl
                                    __nextHasNoMarginBottom
                                    label={__('Poster image', 'orbitools')}
                                    help={__(
                                        'Shown before play. YouTube falls back to its own thumbnail if unset.',
                                        'orbitools',
                                    )}
                                >
                                    <div className="orb-video__file-row">
                                        {posterUrl && (
                                            <img
                                                src={posterUrl}
                                                alt=""
                                                className="orb-video__poster-thumb"
                                            />
                                        )}
                                        <Button
                                            variant="secondary"
                                            size="small"
                                            onClick={open}
                                        >
                                            {posterUrl
                                                ? __('Replace', 'orbitools')
                                                : __('Select image', 'orbitools')}
                                        </Button>
                                        {posterUrl && (
                                            <Button
                                                variant="link"
                                                isDestructive
                                                size="small"
                                                onClick={() =>
                                                    setAttributes({
                                                        posterId: undefined,
                                                        posterUrl: '',
                                                    })
                                                }
                                            >
                                                {__('Remove', 'orbitools')}
                                            </Button>
                                        )}
                                    </div>
                                </BaseControl>
                            )}
                        />
                    </MediaUploadCheck>

                    <div className="orb-video__reset">
                        <Button
                            variant="secondary"
                            size="small"
                            isDestructive
                            onClick={resetVideo}
                        >
                            {__('Replace video', 'orbitools')}
                        </Button>
                    </div>
                </PanelBody>

                <PanelBody title={__('Playback', 'orbitools')} initialOpen={false}>
                    <SelectControl
                        label={__('Aspect ratio', 'orbitools')}
                        value={aspectRatio}
                        onChange={(next: string) => setAttributes({ aspectRatio: next })}
                        options={aspectRatioOptions}
                        __nextHasNoMarginBottom
                        __next40pxDefaultSize
                    />

                    <ToggleControl
                        label={__('Autoplay', 'orbitools')}
                        checked={attributes.autoplay}
                        onChange={(v: boolean) => setAttributes({ autoplay: v })}
                        __nextHasNoMarginBottom
                    />
                    <ToggleControl
                        label={__('Loop', 'orbitools')}
                        checked={attributes.loop}
                        onChange={(v: boolean) => setAttributes({ loop: v })}
                        __nextHasNoMarginBottom
                    />
                    <ToggleControl
                        label={__('Muted', 'orbitools')}
                        checked={attributes.muted}
                        onChange={(v: boolean) => setAttributes({ muted: v })}
                        __nextHasNoMarginBottom
                    />
                    <ToggleControl
                        label={__('Show controls', 'orbitools')}
                        checked={attributes.controls}
                        onChange={(v: boolean) => setAttributes({ controls: v })}
                        __nextHasNoMarginBottom
                    />
                    <ToggleControl
                        label={__('Plays inline (mobile)', 'orbitools')}
                        checked={attributes.playsinline}
                        onChange={(v: boolean) => setAttributes({ playsinline: v })}
                        __nextHasNoMarginBottom
                    />
                </PanelBody>

                {provider === 'youtube' && (
                    <PanelBody
                        title={__('YouTube options', 'orbitools')}
                        initialOpen={false}
                    >
                        <ToggleControl
                            label={__('Hide related videos at end', 'orbitools')}
                            checked={attributes.ytDisableRelated}
                            onChange={(v: boolean) =>
                                setAttributes({ ytDisableRelated: v })
                            }
                            __nextHasNoMarginBottom
                        />
                        <ToggleControl
                            label={__('Modest branding', 'orbitools')}
                            help={__(
                                'Smaller YouTube logo in the player.',
                                'orbitools',
                            )}
                            checked={attributes.ytModestBranding}
                            onChange={(v: boolean) =>
                                setAttributes({ ytModestBranding: v })
                            }
                            __nextHasNoMarginBottom
                        />
                        <ToggleControl
                            label={__('Pause when scrolled off screen', 'orbitools')}
                            checked={attributes.ytPauseOnScroll}
                            onChange={(v: boolean) =>
                                setAttributes({ ytPauseOnScroll: v })
                            }
                            __nextHasNoMarginBottom
                        />
                        <ToggleControl
                            label={__('Pause other YouTube videos when this plays', 'orbitools')}
                            checked={attributes.ytPauseOthers}
                            onChange={(v: boolean) =>
                                setAttributes({ ytPauseOthers: v })
                            }
                            __nextHasNoMarginBottom
                        />
                    </PanelBody>
                )}

                {provider === 'vimeo' && (
                    <PanelBody
                        title={__('Vimeo options', 'orbitools')}
                        initialOpen={false}
                    >
                        <ToggleControl
                            label={__('Hide video title', 'orbitools')}
                            checked={attributes.vimeoHideTitle}
                            onChange={(v: boolean) =>
                                setAttributes({ vimeoHideTitle: v })
                            }
                            __nextHasNoMarginBottom
                        />
                        <ToggleControl
                            label={__('Hide uploader name', 'orbitools')}
                            checked={attributes.vimeoHideByline}
                            onChange={(v: boolean) =>
                                setAttributes({ vimeoHideByline: v })
                            }
                            __nextHasNoMarginBottom
                        />
                        <ToggleControl
                            label={__('Hide uploader avatar', 'orbitools')}
                            checked={attributes.vimeoHidePortrait}
                            onChange={(v: boolean) =>
                                setAttributes({ vimeoHidePortrait: v })
                            }
                            __nextHasNoMarginBottom
                        />
                        <ToggleControl
                            label={__('Pause when scrolled off screen', 'orbitools')}
                            checked={attributes.vimeoPauseOnScroll}
                            onChange={(v: boolean) =>
                                setAttributes({ vimeoPauseOnScroll: v })
                            }
                            __nextHasNoMarginBottom
                        />
                    </PanelBody>
                )}
            </InspectorControls>

            <ServerSideRender
                block="orb/video"
                attributes={attributes}
                EmptyResponsePlaceholder={() => (
                    <div className="orb-video__placeholder">
                        {__(
                            'Add a video URL or upload a file in the sidebar.',
                            'orbitools',
                        )}
                    </div>
                )}
            />
        </div>
    );
}
