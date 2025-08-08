/**
 * Orbitools Configuration Reader
 * 
 * Unified system for reading configuration from:
 * 1. Theme orbitools.json (priority)
 * 2. Plugin defaults.json (fallback)
 * 
 * Provides breakpoints, typography presets, and other block settings
 * 
 * @file blocks/utils/config-reader.ts
 * @since 1.0.0
 */

export interface Breakpoint {
    value: string;
    slug: string;
    name: string;
}

export interface OrbitoolsConfig {
    settings: {
        breakpoints: Breakpoint[];
    };
    modules: {
        typographyPresets: any;
    };
}

/**
 * Cache for config data to avoid repeated fetches
 */
let configCache: OrbitoolsConfig | null = null;
let themeConfigCache: Partial<OrbitoolsConfig> | null = null;

/**
 * Get plugin default configuration
 */
async function getPluginDefaults(): Promise<OrbitoolsConfig> {
    const response = await fetch('/wp-content/plugins/orbitools/config/defaults.json');
    if (!response.ok) {
        throw new Error('Failed to load plugin defaults');
    }
    return response.json();
}

/**
 * Get theme configuration (if it exists)
 */
async function getThemeConfig(): Promise<Partial<OrbitoolsConfig> | null> {
    if (themeConfigCache !== null) {
        return themeConfigCache;
    }

    try {
        // Check if we can get the theme stylesheet name
        const stylesheetName = window.wp?.theme?.get('stylesheet');
        
        if (!stylesheetName) {
            // No theme info available, skip theme config
            themeConfigCache = null;
            return null;
        }

        const response = await fetch(`/wp-content/themes/${stylesheetName}/config/orbitools.json`);
        if (!response.ok) {
            themeConfigCache = null;
            return null;
        }
        const config = await response.json();
        themeConfigCache = config;
        return config;
    } catch (error) {
        themeConfigCache = null;
        return null;
    }
}

/**
 * Get merged configuration with theme overrides
 */
export async function getOrbitoolsConfig(): Promise<OrbitoolsConfig> {
    if (configCache) {
        return configCache;
    }

    try {
        const [pluginDefaults, themeConfig] = await Promise.all([
            getPluginDefaults(),
            getThemeConfig()
        ]);

        // Deep merge theme config over plugin defaults
        const mergedConfig: OrbitoolsConfig = {
            settings: {
                breakpoints: themeConfig?.settings?.breakpoints || pluginDefaults.settings.breakpoints
            },
            modules: {
                typographyPresets: {
                    ...pluginDefaults.modules.typographyPresets,
                    ...themeConfig?.modules?.typographyPresets
                }
            }
        };

        configCache = mergedConfig;
        return mergedConfig;
    } catch (error) {
        console.warn('Orbitools: Failed to load configuration, using fallback defaults');
        
        // Fallback defaults if all else fails
        const fallbackConfig: OrbitoolsConfig = {
            settings: {
                breakpoints: [
                    { value: '50.6875rem', slug: 'sm', name: '810px' },
                    { value: '67.5625rem', slug: 'md', name: '1080px' },
                    { value: '85rem', slug: 'lg', name: '1360px' },
                    { value: '100rem', slug: 'xl', name: '1600px' }
                ]
            },
            modules: {
                typographyPresets: {}
            }
        };
        
        configCache = fallbackConfig;
        return fallbackConfig;
    }
}

/**
 * Get breakpoints configuration
 */
export async function getBreakpoints(): Promise<Breakpoint[]> {
    const config = await getOrbitoolsConfig();
    return config.settings.breakpoints;
}

/**
 * Get typography presets configuration
 */
export async function getTypographyPresets(): Promise<any> {
    const config = await getOrbitoolsConfig();
    return config.modules.typographyPresets;
}

/**
 * Clear configuration cache (useful for development/testing)
 */
export function clearConfigCache(): void {
    configCache = null;
    themeConfigCache = null;
}

/**
 * Hook for using breakpoints in React components
 */
import { useState, useEffect } from '@wordpress/element';

export function useBreakpoints(): { breakpoints: Breakpoint[] | null; loading: boolean } {
    const [breakpoints, setBreakpoints] = useState<Breakpoint[] | null>(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        getBreakpoints().then((bp) => {
            setBreakpoints(bp);
            setLoading(false);
        }).catch((error) => {
            console.error('Failed to load breakpoints:', error);
            setLoading(false);
        });
    }, []);

    return { breakpoints, loading };
}

/**
 * Hook for using full Orbitools config in React components
 */
export function useOrbitoolsConfig(): { config: OrbitoolsConfig | null; loading: boolean } {
    const [config, setConfig] = useState<OrbitoolsConfig | null>(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        getOrbitoolsConfig().then((cfg) => {
            setConfig(cfg);
            setLoading(false);
        }).catch((error) => {
            console.error('Failed to load Orbitools config:', error);
            setLoading(false);
        });
    }, []);

    return { config, loading };
}