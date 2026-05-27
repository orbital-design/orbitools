/**
 * Theme-pages bootstrap accessor.
 *
 * Pages are injected at first paint via window.orbitools.themePages —
 * see React_Admin.php::collect_theme_pages(). Centralising the read
 * here means consumers don't reach into the global directly.
 */
import type { ThemePageInfo } from '../types';

export function getThemePages(): ThemePageInfo[] {
    return window.orbitools?.themePages ?? [];
}

export function getThemePage(slug: string): ThemePageInfo | undefined {
    return getThemePages().find((p) => p.slug === slug);
}
