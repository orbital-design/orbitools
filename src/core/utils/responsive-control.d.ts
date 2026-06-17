/**
 * Type declarations for the plain-JS ResponsiveControl framework
 * (responsive-control.js), so TSX blocks can import it with types.
 */
import type { ReactNode } from 'react';

export interface ResponsiveBreakpoint {
    slug: string;
    name?: string;
    value: string;
    query?: string;
}

export interface ResponsiveRenderContext {
    /** Editor device type: 'Desktop' | 'Tablet' | 'Mobile'. */
    device: string;
    /** Breakpoint slug for the active device: 'base' | 'tablet' | 'mobile'. */
    slug: string;
    /** The matching breakpoint config entry, or null for base. */
    breakpoint: ResponsiveBreakpoint | null;
}

export interface ResponsiveControlProps {
    title?: string;
    blockName: string;
    initialOpen?: boolean;
    /** Pass false to skip the PanelBody wrapper. */
    wrap?: boolean;
    /** Optional <ResponsiveDots/> rendered inline in the panel title. */
    indicator?: ReactNode;
    render: (ctx: ResponsiveRenderContext) => ReactNode;
}

export function ResponsiveControl(props: ResponsiveControlProps): JSX.Element;

export interface ResponsiveDotsProps {
    /** Responsive value keyed by slug; a slug is "set" when non-empty. */
    value?: Record<string, unknown> | null;
    /** Custom predicate for controls that span several values. */
    isSet?: (slug: string) => boolean;
}

export function ResponsiveDots(props: ResponsiveDotsProps): JSX.Element;

export function deviceToSlug(device: string): string;

export function useDeviceType(): { device: string; setDevice: (device: string) => void };

export function getResponsiveClasses(
    responsiveValue: Record<string, unknown> | null | undefined,
    classPrefix: string,
    formatValue?: (value: unknown) => string,
): string;

export const DEVICE_ORDER: string[];
export const DEVICE_TO_SLUG: Record<string, string>;
export const SLUG_TO_DEVICE: Record<string, string>;
