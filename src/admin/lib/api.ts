/**
 * REST client.
 *
 * Thin wrappers around @wordpress/api-fetch, configured at runtime
 * with the nonce + root URL bootstrapped by React_Admin into
 * window.orbitools.
 */
import apiFetch from '@wordpress/api-fetch';
import type { Module, ModuleSettings, ThemePageInfo } from '../types';

interface OrbitoolsBootstrap {
    restUrl: string;
    restNonce: string;
    adminUrl: string;
    pluginUrl: string;
    version: string;
    themePages?: ThemePageInfo[];
}

declare global {
    interface Window {
        orbitools?: OrbitoolsBootstrap;
    }
}

// Wire the nonce into every request. api-fetch reads from this header.
const bootstrap = window.orbitools;
if (bootstrap !== undefined) {
    apiFetch.use(apiFetch.createNonceMiddleware(bootstrap.restNonce));
    apiFetch.use(apiFetch.createRootURLMiddleware(bootstrap.restUrl.replace(/\/orbitools\/v1\/?$/, '/')));
}

/**
 * Module is keyed by manifest slug; matches the response shape of
 * Modules_Controller::prepare_module_for_response().
 */
export async function fetchModules(): Promise<Module[]> {
    const result = await apiFetch<{ modules: Module[] }>({
        path: 'orbitools/v1/modules',
    });
    return result.modules;
}

export async function fetchModule(slug: string): Promise<Module> {
    return apiFetch<Module>({
        path: `orbitools/v1/modules/${encodeURIComponent(slug)}`,
    });
}

export async function setModuleEnabled(slug: string, enabled: boolean): Promise<Module> {
    return apiFetch<Module>({
        path: `orbitools/v1/modules/${encodeURIComponent(slug)}/enabled`,
        method: 'POST',
        data: { enabled },
    });
}

export async function fetchSettings(slug: string): Promise<ModuleSettings> {
    return apiFetch<ModuleSettings>({
        path: `orbitools/v1/settings/${encodeURIComponent(slug)}`,
    });
}

export async function replaceSettings(slug: string, settings: ModuleSettings): Promise<ModuleSettings> {
    return apiFetch<ModuleSettings>({
        path: `orbitools/v1/settings/${encodeURIComponent(slug)}`,
        method: 'PUT',
        data: settings,
    });
}

export async function patchSettings(slug: string, partial: ModuleSettings): Promise<ModuleSettings> {
    return apiFetch<ModuleSettings>({
        path: `orbitools/v1/settings/${encodeURIComponent(slug)}`,
        method: 'PATCH',
        data: partial,
    });
}
