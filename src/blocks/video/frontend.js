/**
 * Video Block — frontend behaviour.
 *
 * Handles video playback enhancements, lazy loading, aspect-ratio
 * detection, and the YouTube + Vimeo privacy facades (click-to-play
 * with background preload).
 *
 * Ported 1:1 from the dream-and-leap theme's
 * blocks/video/src/js/_frontend.js. No behavioural changes.
 */

export class VideoBlockManager {
    constructor() {
        this.videoBlocks = [];
        this.observer = null;
        this.resizeTimer = null;
        this.observerOptions = {
            root: null,
            rootMargin: '0px',
            threshold: 0.5 // Trigger when 50% of video is visible
        };
        this.init();
    }

    /**
     * Initialize the video block manager.
     * Sets up intersection observer and video enhancements.
     */
    init() {
        this.videoBlocks = document.querySelectorAll('.orb-video');

        if (this.videoBlocks.length === 0) {
            return;
        }

        // Setup intersection observer for lazy loading and autoplay
        if ('IntersectionObserver' in window) {
            this.setupIntersectionObserver();
        } else {
            // Fallback for browsers without IntersectionObserver
            this.videoBlocks.forEach(block => this.initVideoBlock(block));
        }

        // Setup privacy facades (YouTube & Vimeo)
        this.videoBlocks.forEach(block => {
            this.setupYouTubeFacade(block);
            this.setupVimeoFacade(block);
        });

        // Setup video controls enhancements and aspect ratio
        this.videoBlocks.forEach(block => {
            this.setupVideoControls(block);
            this.setupVideoAccessibility(block);
            this.setupVideoAspectRatio(block);
            this.setupIframeAspectRatio(block);
        });
    }

    /**
     * Setup intersection observer for video blocks.
     */
    setupIntersectionObserver() {
        this.observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                const videoBlock = entry.target;
                const video = videoBlock.querySelector('video');

                if (!video) return;

                if (entry.isIntersecting) {
                    this.handleVideoInView(video, videoBlock);
                } else {
                    this.handleVideoOutOfView(video, videoBlock);
                }
            });
        }, this.observerOptions);

        this.videoBlocks.forEach(block => {
            this.observer.observe(block);
        });
    }

    handleVideoInView(video, block) {
        if (block.dataset.autoplay === 'true') {
            const playPromise = video.play();

            if (playPromise !== undefined) {
                playPromise.catch(error => {
                    console.log('VideoBlockManager: Autoplay was prevented:', error);
                    this.showPlayButton(block);
                });
            }
        }

        block.classList.add('is-in-view');
    }

    handleVideoOutOfView(video, block) {
        if (!video.paused && block.dataset.autoplay === 'true') {
            video.pause();
        }
        block.classList.remove('is-in-view');
    }

    initVideoBlock(block) {
        const video = block.querySelector('video');
        if (!video) return;

        if (block.dataset.autoplay === 'true') {
            video.autoplay = true;
            video.muted = true; // Required for autoplay in most browsers
        }

        if (block.dataset.loop === 'true') {
            video.loop = true;
        }

        if (block.dataset.noControls === 'true') {
            video.controls = false;
        }
    }

    setupVideoControls(block) {
        const video = block.querySelector('video');
        if (!video) return;

        video.addEventListener('loadstart', () => {
            block.classList.add('is-loading');
        }, { passive: true });

        video.addEventListener('loadeddata', () => {
            block.classList.remove('is-loading');
            block.classList.add('is-loaded');
        }, { passive: true });

        video.addEventListener('error', (e) => {
            console.error('VideoBlockManager: Video loading error:', e);
            block.classList.add('has-error');
            this.showErrorMessage(block);
        }, { passive: true });

        if (block.dataset.noControls === 'true') {
            video.addEventListener('click', () => {
                if (video.paused) {
                    video.play();
                    block.classList.add('is-playing');
                } else {
                    video.pause();
                    block.classList.remove('is-playing');
                }
            });
        }
    }

    setupVideoAccessibility(block) {
        const video = block.querySelector('video');
        if (!video) return;

        video.addEventListener('keydown', (e) => {
            switch (e.key) {
                case ' ':
                case 'Enter':
                    e.preventDefault();
                    if (video.paused) {
                        video.play();
                    } else {
                        video.pause();
                    }
                    break;
                case 'ArrowLeft':
                    e.preventDefault();
                    video.currentTime = Math.max(0, video.currentTime - 10);
                    break;
                case 'ArrowRight':
                    e.preventDefault();
                    video.currentTime = Math.min(video.duration, video.currentTime + 10);
                    break;
                case 'ArrowUp':
                    e.preventDefault();
                    video.volume = Math.min(1, video.volume + 0.1);
                    break;
                case 'ArrowDown':
                    e.preventDefault();
                    video.volume = Math.max(0, video.volume - 0.1);
                    break;
            }
        });

        if (!video.hasAttribute('tabindex')) {
            video.setAttribute('tabindex', '0');
        }

        if (!video.hasAttribute('aria-label')) {
            video.setAttribute('aria-label', 'Video player');
        }
    }

    showPlayButton(block) {
        if (block.querySelector('.orb-video__play-button')) return;

        const playButton = document.createElement('button');
        playButton.className = 'orb-video__play-button';
        playButton.innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="currentColor">
                <path d="M8 5v14l11-7z"/>
            </svg>
            <span class="screen-reader-text">Play video</span>
        `;
        playButton.setAttribute('aria-label', 'Play video');

        playButton.addEventListener('click', () => {
            const video = block.querySelector('video');
            if (video) {
                video.play();
                playButton.remove();
            }
        });

        block.appendChild(playButton);
    }

    showErrorMessage(block) {
        const errorMessage = document.createElement('div');
        errorMessage.className = 'orb-video__error';
        errorMessage.textContent = 'Unable to load video. Please try refreshing the page.';
        block.appendChild(errorMessage);
    }

    setupVideoAspectRatio(block) {
        const video = block.querySelector('video');
        if (!video) return;

        const applyAspectRatio = () => {
            if (video.videoWidth && video.videoHeight) {
                const aspectRatio = video.videoWidth / video.videoHeight;

                block.style.aspectRatio = aspectRatio;
                block.dataset.calculatedAspectRatio = aspectRatio.toFixed(4);

                const classes = block.className.split(' ');
                const filteredClasses = classes.filter(cls => !cls.includes('aspect-ratio'));
                block.className = filteredClasses.join(' ');

                block.classList.add('has-auto-aspect-ratio');

                console.log(`VideoBlockManager: Aspect ratio calculated: ${video.videoWidth}x${video.videoHeight} = ${aspectRatio.toFixed(4)}`);
            }
        };

        video.addEventListener('loadedmetadata', applyAspectRatio, { passive: true });

        if (video.readyState >= 1) {
            applyAspectRatio();
        }

        window.addEventListener('resize', () => {
            clearTimeout(this.resizeTimer);
            this.resizeTimer = setTimeout(() => {
                if (video.videoWidth && video.videoHeight) {
                    applyAspectRatio();
                }
            }, 250);
        }, { passive: true });
    }

    setupIframeAspectRatio(block) {
        const iframe = block.querySelector('iframe');
        if (!iframe) return;

        const extractAspectRatio = () => {
            let width = iframe.width || iframe.getAttribute('width');
            let height = iframe.height || iframe.getAttribute('height');

            if (!width || !height || width.includes('%') || height.includes('%')) {
                const computedStyle = window.getComputedStyle(iframe);
                width = parseInt(computedStyle.width);
                height = parseInt(computedStyle.height);
            }

            width = parseInt(width);
            height = parseInt(height);

            if (width && height && !isNaN(width) && !isNaN(height)) {
                const aspectRatio = width / height;

                block.style.aspectRatio = aspectRatio;
                block.dataset.calculatedAspectRatio = aspectRatio.toFixed(4);

                block.classList.add('has-auto-aspect-ratio');

                console.log(`VideoBlockManager: Iframe aspect ratio calculated: ${width}x${height} = ${aspectRatio.toFixed(4)}`);
            } else {
                block.style.aspectRatio = '16/9';
                block.dataset.calculatedAspectRatio = '1.7778';
                block.classList.add('has-default-aspect-ratio');
            }
        };

        if (iframe.contentWindow) {
            iframe.addEventListener('load', extractAspectRatio);
        }

        extractAspectRatio();

        const src = iframe.src || iframe.getAttribute('src');
        if (src) {
            if (src.includes('youtube.com') || src.includes('youtu.be') || src.includes('vimeo.com')) {
                if (!block.style.aspectRatio) {
                    block.style.aspectRatio = '16/9';
                    block.dataset.calculatedAspectRatio = '1.7778';
                    block.classList.add('has-video-platform-aspect-ratio');
                }
            }
        }
    }

    /**
     * Setup YouTube privacy facade with background preload.
     *
     * Features:
     * - Preloads iframe behind facade when block scrolls into view
     * - Click triggers play via postMessage (no src swap)
     * - Waits for buffering to finish before revealing
     * - Optional: pause on scroll out of view
     * - Optional: pause other YT videos when this one plays
     */
    setupYouTubeFacade(block) {
        // Exclude Vimeo facades — both share the .orb-video__facade class
        const facade = block.querySelector('.orb-video__facade:not([data-provider="vimeo"])');
        if (!facade) return;

        const videoId = facade.dataset.videoId;
        if (!videoId) return;

        const params = facade.dataset.params || 'autoplay=1';
        const pauseOnScroll = facade.hasAttribute('data-pause-on-scroll');
        const pauseOthers = facade.hasAttribute('data-pause-others');
        let iframe = null;
        let isPlaying = false;

        // Thumbnail fallback: maxresdefault → hqdefault
        const thumb = facade.querySelector('.orb-video__thumb');
        if (thumb && thumb.dataset.fallback) {
            thumb.addEventListener('error', function () {
                if (this.src !== this.dataset.fallback) {
                    this.src = this.dataset.fallback;
                }
            }, { once: true });
        }

        // Helper: send command to YouTube iframe
        const ytCommand = (func) => {
            if (iframe && iframe.contentWindow) {
                iframe.contentWindow.postMessage(
                    JSON.stringify({ event: 'command', func }),
                    '*'
                );
            }
        };

        // Build params for preload: enablejsapi for postMessage, origin for event listening
        const preloadParams = params
            .split('&')
            .filter(p => !p.startsWith('autoplay'))
            .concat(['enablejsapi=1', 'origin=' + encodeURIComponent(window.location.origin)])
            .join('&');

        // Listen for YouTube player state changes (buffering detection)
        const handleYtMessage = (e) => {
            if (!iframe || !e.data) return;

            let data;
            try {
                data = typeof e.data === 'string' ? JSON.parse(e.data) : e.data;
            } catch (_) {
                return;
            }

            // YouTube sends info events with playerState
            // States: -1 unstarted, 0 ended, 1 playing, 2 paused, 3 buffering, 5 cued
            if (data.event === 'infoDelivery' && data.info && typeof data.info.playerState !== 'undefined') {
                const state = data.info.playerState;

                if (state === 1) {
                    // Playing — safe to reveal
                    isPlaying = true;
                    block.classList.add('is-yt-playing');
                    revealPlayer();
                } else if (state === 2 || state === 0) {
                    isPlaying = false;
                    block.classList.remove('is-yt-playing');
                }
            }
        };

        // Facade reveal logic — only runs once
        let revealed = false;
        const revealPlayer = () => {
            if (revealed) return;
            revealed = true;

            facade.style.transition = 'opacity 0.8s ease';
            facade.style.opacity = '0';
            facade.addEventListener('transitionend', function () {
                facade.style.zIndex = '-1';
                window.removeEventListener('message', handleYtMessage);
            }, { once: true });
        };

        // Preload iframe when block enters viewport
        const preloadObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting && !iframe) {
                    iframe = document.createElement('iframe');
                    iframe.src = 'https://www.youtube-nocookie.com/embed/' +
                        encodeURIComponent(videoId) +
                        '?' + preloadParams;
                    iframe.title = 'YouTube video player';
                    iframe.setAttribute('frameborder', '0');
                    iframe.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share';
                    iframe.referrerPolicy = 'strict-origin-when-cross-origin';
                    iframe.allowFullscreen = true;
                    iframe.style.cssText = 'position:absolute;top:0;left:0;width:100%;height:100%;border:0;z-index:0;';

                    block.insertBefore(iframe, facade);

                    block.style.aspectRatio = '16/9';
                    block.classList.add('has-video-platform-aspect-ratio');

                    // Start listening for player state events
                    window.addEventListener('message', handleYtMessage);

                    // Ask YouTube to send us state updates
                    iframe.addEventListener('load', () => {
                        iframe.contentWindow.postMessage(
                            JSON.stringify({ event: 'listening' }),
                            '*'
                        );
                    }, { once: true });

                    preloadObserver.disconnect();
                }
            });
        }, { rootMargin: '200px' });

        preloadObserver.observe(block);

        // Click → trigger play, show loading state
        facade.addEventListener('click', function (e) {
            e.preventDefault();

            facade.style.pointerEvents = 'none';
            facade.style.zIndex = '2';
            facade.classList.add('is-loading');

            if (iframe) {
                ytCommand('playVideo');

                // Pause other YT videos on this page
                if (pauseOthers) {
                    document.querySelectorAll('.orb-video.is-yt-playing').forEach(other => {
                        if (other === block) return;
                        const otherIframe = other.querySelector('iframe');
                        if (otherIframe && otherIframe.contentWindow) {
                            otherIframe.contentWindow.postMessage(
                                JSON.stringify({ event: 'command', func: 'pauseVideo' }),
                                '*'
                            );
                        }
                        other.classList.remove('is-yt-playing');
                    });
                }

                // Fallback reveal after 2s if state event never fires (e.g. blocked postMessage)
                setTimeout(() => revealPlayer(), 2000);
            } else {
                // Fallback if preload hasn't fired — create with autoplay
                iframe = document.createElement('iframe');
                iframe.src = 'https://www.youtube-nocookie.com/embed/' +
                    encodeURIComponent(videoId) + '?' + params;
                iframe.title = 'YouTube video player';
                iframe.setAttribute('frameborder', '0');
                iframe.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share';
                iframe.referrerPolicy = 'strict-origin-when-cross-origin';
                iframe.allowFullscreen = true;
                iframe.style.cssText = 'position:absolute;top:0;left:0;width:100%;height:100%;border:0;';
                block.insertBefore(iframe, facade);

                block.style.aspectRatio = '16/9';
                block.classList.add('has-video-platform-aspect-ratio');

                // No state events available, use timed reveal
                setTimeout(() => revealPlayer(), 300);
            }
        });

        // Pause on scroll out of view
        if (pauseOnScroll) {
            const scrollObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (!iframe || !isPlaying) return;

                    if (!entry.isIntersecting) {
                        ytCommand('pauseVideo');
                    } else {
                        ytCommand('playVideo');
                    }
                });
            }, { threshold: 0.25 });

            scrollObserver.observe(block);
        }
    }

    /**
     * Setup Vimeo privacy facade with background preload.
     *
     * Same pattern as YouTube: preloads iframe behind facade on
     * scroll, click triggers play, smooth fade reveal.
     */
    setupVimeoFacade(block) {
        const facade = block.querySelector('.orb-video__facade[data-provider="vimeo"]');
        if (!facade) return;

        const videoId = facade.dataset.videoId;
        if (!videoId) return;

        const params = facade.dataset.params || 'dnt=1';
        const pauseOnScroll = facade.hasAttribute('data-pause-scroll');
        let iframe = null;

        // Build preload params (without autoplay)
        const preloadParams = params
            .split('&')
            .filter(p => !p.startsWith('autoplay'))
            .join('&');

        // Preload iframe when block enters viewport
        const preloadObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting && !iframe) {
                    iframe = document.createElement('iframe');
                    iframe.src = 'https://player.vimeo.com/video/' +
                        encodeURIComponent(videoId) +
                        '?' + preloadParams;
                    iframe.title = 'Vimeo video player';
                    iframe.setAttribute('frameborder', '0');
                    iframe.allow = 'autoplay; fullscreen; picture-in-picture';
                    iframe.referrerPolicy = 'strict-origin-when-cross-origin';
                    iframe.allowFullscreen = true;
                    iframe.style.cssText = 'position:absolute;top:0;left:0;width:100%;height:100%;border:0;z-index:0;';

                    block.insertBefore(iframe, facade);

                    block.style.aspectRatio = '16/9';
                    block.classList.add('has-video-platform-aspect-ratio');

                    preloadObserver.disconnect();
                }
            });
        }, { rootMargin: '200px' });

        preloadObserver.observe(block);

        // Helper to send Vimeo postMessage commands
        const vimeoCommand = (method) => {
            if (iframe && iframe.contentWindow) {
                iframe.contentWindow.postMessage(
                    JSON.stringify({ method: method }),
                    '*'
                );
            }
        };

        // Fade reveal helper
        const revealPlayer = () => {
            facade.style.pointerEvents = 'none';
            facade.style.zIndex = '2';
            facade.classList.add('is-loading');
            setTimeout(() => {
                facade.style.transition = 'opacity 0.8s ease';
                facade.style.opacity = '0';
                facade.addEventListener('transitionend', function () {
                    facade.style.zIndex = '-1';
                }, { once: true });
            }, 600);
        };

        // Click → trigger play and reveal
        facade.addEventListener('click', function (e) {
            e.preventDefault();

            if (iframe) {
                vimeoCommand('play');
            } else {
                // Fallback: create with autoplay
                iframe = document.createElement('iframe');
                iframe.src = 'https://player.vimeo.com/video/' +
                    encodeURIComponent(videoId) + '?' + params + '&autoplay=1';
                iframe.title = 'Vimeo video player';
                iframe.setAttribute('frameborder', '0');
                iframe.allow = 'autoplay; fullscreen; picture-in-picture';
                iframe.referrerPolicy = 'strict-origin-when-cross-origin';
                iframe.allowFullscreen = true;
                iframe.style.cssText = 'position:absolute;top:0;left:0;width:100%;height:100%;border:0;';
                block.insertBefore(iframe, facade);

                block.style.aspectRatio = '16/9';
                block.classList.add('has-video-platform-aspect-ratio');
            }

            revealPlayer();
        });

        // Pause on scroll out of view
        if (pauseOnScroll) {
            const scrollObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (!iframe) return;

                    if (!entry.isIntersecting) {
                        vimeoCommand('pause');
                    } else {
                        vimeoCommand('play');
                    }
                });
            }, { threshold: 0.25 });

            scrollObserver.observe(block);
        }
    }

    /**
     * Clean up event listeners and observer.
     */
    destroy() {
        if (this.observer) {
            this.observer.disconnect();
            this.observer = null;
        }

        if (this.resizeTimer) {
            clearTimeout(this.resizeTimer);
            this.resizeTimer = null;
        }
    }
}

/**
 * Auto-initialization function.
 */
export const initVideo = () => {
    const videoBlocks = document.querySelectorAll('.orb-video');

    if (videoBlocks.length > 0) {
        return new VideoBlockManager();
    }
};

// Self-initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initVideo);
} else {
    initVideo();
}
