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
 * Type guard to check if orientation is valid
 * 
 * @param orientation - Value to check
 * @returns True if orientation is 'x' or 'y'
 * 
 * @example
 * ```typescript
 * const userInput = 'x';
 * if (isValidOrientation(userInput)) {
 *   // userInput is now typed as 'x' | 'y'
 *   console.log('Valid orientation:', userInput);
 * }
 * ```
 */
export function isValidOrientation(orientation: any): orientation is 'x' | 'y' {
    return orientation === 'x' || orientation === 'y';
}

/**
 * Type guard to check if animation direction is valid
 * 
 * @param direction - Value to check
 * @returns True if direction is 'normal' or 'reverse'
 * 
 * @example
 * ```typescript
 * const userDirection = 'reverse';
 * if (isValidAnimationDirection(userDirection)) {
 *   // userDirection is now typed as 'normal' | 'reverse'
 *   console.log('Valid direction:', userDirection);
 * }
 * ```
 */
export function isValidAnimationDirection(direction: any): direction is 'normal' | 'reverse' {
    return direction === 'normal' || direction === 'reverse';
}

/**
 * Type guard to check if hover state is valid
 * 
 * @param state - Value to check
 * @returns True if state is 'paused' or 'running'
 * 
 * @example
 * ```typescript
 * const hoverBehavior = 'paused';
 * if (isValidHoverState(hoverBehavior)) {
 *   // hoverBehavior is now typed as 'paused' | 'running'
 *   console.log('Valid hover state:', hoverBehavior);
 * }
 * ```
 */
export function isValidHoverState(state: any): state is 'paused' | 'running' {
    return state === 'paused' || state === 'running';
}

/**
 * Utility function to safely get orientation with fallback
 * 
 * @param attributes - Marquee block attributes
 * @returns Valid orientation value with fallback to default
 * 
 * @example
 * ```typescript
 * const orientation = getOrientation({ orientation: 'invalid' });
 * console.log(orientation); // 'x' (default fallback)
 * ```
 */
export function getOrientation(attributes: MarqueeAttributes): 'x' | 'y' {
    return isValidOrientation(attributes.orientation)
        ? attributes.orientation
        : MARQUEE_DEFAULTS.orientation;
}

/**
 * Utility function to safely get animation direction with fallback
 * 
 * @param attributes - Marquee block attributes
 * @returns Valid direction value with fallback to default
 * 
 * @example
 * ```typescript
 * const direction = getAnimationDirection({ direction: undefined });
 * console.log(direction); // 'normal' (default fallback)
 * ```
 */
export function getAnimationDirection(attributes: MarqueeAttributes): 'normal' | 'reverse' {
    return isValidAnimationDirection(attributes.direction)
        ? attributes.direction
        : MARQUEE_DEFAULTS.direction;
}

/**
 * Utility function to safely get hover state with fallback
 * 
 * @param attributes - Marquee block attributes
 * @returns Valid hover state value with fallback to default
 * 
 * @example
 * ```typescript
 * const hoverState = getHoverAnimationState({ hoverState: 'running' });
 * console.log(hoverState); // 'running'
 * ```
 */
export function getHoverAnimationState(attributes: MarqueeAttributes): 'paused' | 'running' {
    return isValidHoverState(attributes.hoverState)
        ? attributes.hoverState
        : MARQUEE_DEFAULTS.hoverState;
}

/**
 * Utility function to safely get animation speed with fallback
 * 
 * @param attributes - Marquee block attributes
 * @returns Animation speed string (e.g., "10s") with fallback to default
 * 
 * @example
 * ```typescript
 * const speed = getAnimationSpeed({ speed: '15s' });
 * console.log(speed); // '15s'
 * 
 * const speedWithFallback = getAnimationSpeed({ speed: undefined });
 * console.log(speedWithFallback); // '10s' (default)
 * ```
 */
export function getAnimationSpeed(attributes: MarqueeAttributes): string {
    return attributes.speed || MARQUEE_DEFAULTS.speed;
}

/**
 * Utility function to generate CSS custom properties for the marquee
 * 
 * @param attributes - Marquee block attributes
 * @returns Object containing CSS custom properties for styling
 * 
 * @example
 * ```typescript
 * const styles = getMarqueeStyles({ overlayColor: '#ff0000' });
 * console.log(styles); // { '--marquee-overlay-color': '#ff0000' }
 * 
 * // Apply to element
 * const element = document.querySelector('.orb-marquee');
 * Object.assign(element.style, styles);
 * ```
 */
export function getMarqueeStyles(attributes: MarqueeAttributes): Record<string, string> {
    const overlayColor = attributes.overlayColor;

    return {
        '--marquee-overlay-color': overlayColor || 'transparent',
    };
}
