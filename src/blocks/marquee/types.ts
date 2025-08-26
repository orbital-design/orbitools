/**
 * Marquee Block Type Definitions
 *
 * Shared TypeScript interfaces and types for the Marquee block.
 * This ensures consistency between edit.tsx, save.tsx, and other components.
 *
 * @file blocks/marquee/types.ts
 * @since 1.0.0
 */

/**
 * Marquee Block Attributes Interface
 *
 * Defines the structure of attributes for the Marquee block.
 * This interface matches the structure defined in block.json.
 *
 * The Marquee block supports horizontal and vertical scrolling content
 * with customizable animation speed, direction, and hover behavior.
 */
export interface MarqueeAttributes {
    /**
     * Animation orientation - horizontal (x) or vertical (y)
     */
    orientation?: 'x' | 'y';

    /**
     * Animation direction - normal (left-to-right/top-to-bottom) or reverse
     */
    direction?: 'normal' | 'reverse';

    /**
     * Animation state on hover - paused or continue running
     */
    hoverState?: 'paused' | 'running';

    /**
     * Animation speed (CSS duration value like "10s" or "5000ms")
     */
    speed?: string;

    /**
     * Overlay color for fade effects at edges
     */
    overlayColor?: string;
}

/**
 * Props interface for Marquee edit component
 */
export interface MarqueeEditProps {
    attributes: MarqueeAttributes;
    setAttributes: (attributes: Partial<MarqueeAttributes>) => void;
    clientId: string;
}

/**
 * Default values for marquee controls
 *
 * These defaults match the structure in block.json and provide
 * fallback values when attributes are undefined.
 */
export const MARQUEE_DEFAULTS = {
    orientation: 'x' as const,
    direction: 'normal' as const,
    hoverState: 'paused' as const,
    speed: '10s',
    overlayColor: undefined,
} as const;

/**
 * Animation duration options for the speed control
 */
export const ANIMATION_SPEED_OPTIONS = [
    { label: '5 seconds', value: '5s' },
    { label: '10 seconds', value: '10s' },
    { label: '15 seconds', value: '15s' },
    { label: '20 seconds', value: '20s' },
    { label: '30 seconds', value: '30s' }
] as const;

/**
 * Type guard to check if orientation is valid
 */
export function isValidOrientation(orientation: any): orientation is 'x' | 'y' {
    return orientation === 'x' || orientation === 'y';
}

/**
 * Type guard to check if animation direction is valid
 */
export function isValidAnimationDirection(direction: any): direction is 'normal' | 'reverse' {
    return direction === 'normal' || direction === 'reverse';
}

/**
 * Type guard to check if hover state is valid
 */
export function isValidHoverState(state: any): state is 'paused' | 'running' {
    return state === 'paused' || state === 'running';
}

/**
 * Utility function to safely get orientation with fallback
 */
export function getOrientation(attributes: MarqueeAttributes): 'x' | 'y' {
    return isValidOrientation(attributes.orientation)
        ? attributes.orientation
        : MARQUEE_DEFAULTS.orientation;
}

/**
 * Utility function to safely get animation direction with fallback
 */
export function getAnimationDirection(attributes: MarqueeAttributes): 'normal' | 'reverse' {
    return isValidAnimationDirection(attributes.direction)
        ? attributes.direction
        : MARQUEE_DEFAULTS.direction;
}

/**
 * Utility function to safely get hover state with fallback
 */
export function getHoverAnimationState(attributes: MarqueeAttributes): 'paused' | 'running' {
    return isValidHoverState(attributes.hoverState)
        ? attributes.hoverState
        : MARQUEE_DEFAULTS.hoverState;
}

/**
 * Utility function to safely get animation speed with fallback
 */
export function getAnimationSpeed(attributes: MarqueeAttributes): string {
    return attributes.speed || MARQUEE_DEFAULTS.speed;
}

/**
 * Utility function to generate CSS custom properties for the marquee
 */
export function getMarqueeStyles(attributes: MarqueeAttributes): Record<string, string> {
    const orientation = getOrientation(attributes);
    const direction = getAnimationDirection(attributes);
    const speed = getAnimationSpeed(attributes);
    const overlayColor = attributes.overlayColor;

    return {
        '--marquee-orientation': orientation,
        '--marquee-direction': direction,
        '--marquee-speed': speed,
        '--marquee-overlay-color': overlayColor || 'transparent',
    };
}
