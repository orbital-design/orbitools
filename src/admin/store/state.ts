/**
 * Root store state shape. Each slice owns one branch.
 */
import type { Module, ModuleSettings } from '../types';

export interface ModulesState {
    bySlug: Record<string, Module>;
    isLoading: boolean;
    error: string | null;
}

export interface SettingsState {
    bySlug: Record<string, ModuleSettings>;
    loadingBySlug: Record<string, boolean>;
    errorBySlug: Record<string, string | null>;
}

export interface Notice {
    id: string;
    status: 'success' | 'info' | 'warning' | 'error';
    message: string;
}

export interface UiState {
    notices: Notice[];
}

export interface State {
    modules: ModulesState;
    settings: SettingsState;
    ui: UiState;
}
