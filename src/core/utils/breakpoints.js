/**
 * Breakpoints Utility
 *
 * Standalone utility for getting breakpoint configuration
 * Can be imported anywhere without dimensions control dependencies
 *
 * @since 1.0.0
 */

/**
 * Get breakpoint options - completely standalone from dimensions control
 *
 * @returns {Array} Array of breakpoint options with slug, value, and name
 */
export function getBreakpointOptions(blockName = '') {
    // Priority Order:
    // 1. Block Supports (if using block-level configuration)  
    // 2. Theme configuration (themeRoot/config/orbitools.json)
    // 3. Global plugin configuration (final fallback)
    // 4. Empty array (no breakpoints available)
    
    
    // 1. Check block-specific configuration first
    const configData = window.orbitoolsDimensionsConfig || {};
    if (configData[blockName] && configData[blockName].breakpoints) {
        return configData[blockName].breakpoints;
    }
    
    // 2. Check theme orbitools.json settings
    const themeConfig = window.orbitoolsThemeConfig || {};
    if (themeConfig.settings && themeConfig.settings.breakpoints) {
        return themeConfig.settings.breakpoints;
    }

    // 3. Check global plugin breakpoints configuration (final fallback)
    const globalBreakpoints = window.orbitoolsBreakpoints;
    if (globalBreakpoints && Array.isArray(globalBreakpoints)) {
        return globalBreakpoints;
    }

    // 4. Return empty array - no breakpoints configured
    return [];
}

/**
 * Check if breakpoints are enabled - always true since breakpoints are now standalone
 *
 * @returns {boolean} Whether breakpoints are enabled
 */
export function areBreakpointsEnabled() {
    // Breakpoints are always available as a standalone utility
    // blockName parameter kept for API compatibility but unused
    return true;
}

/**
 * Get all available breakpoints including base
 *
 * @param {string} blockName - The block name
 * @returns {Array} Array of all breakpoints (null for base + configured breakpoints)
 */
export function getAllBreakpoints(blockName = '') {
    const breakpoints = getBreakpointOptions(blockName);
    return [null, ...breakpoints]; // null represents base breakpoint
}
