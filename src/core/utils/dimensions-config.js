/**
 * Dimensions Configuration JavaScript Interface
 * 
 * Provides JavaScript access to the spacing and breakpoint configuration
 * resolved on the PHP side with caching.
 * 
 * @since 1.0.0
 */

/**
 * Get configuration for a specific block
 * 
 * @param {string} blockName - The block name (e.g., 'orb/collection')
 * @returns {Object} Block configuration including spacings, breakpoints, and dimensions settings
 */
export function getBlockDimensionsConfig(blockName) {
    // Get the configuration from PHP localized data
    const configData = window.orbitoolsDimensionsConfig || {};
    
    if (configData[blockName]) {
        return configData[blockName];
    }
    
    // Fallback: return disabled configuration
    return {
        spacings: [],
        breakpoints: [],
        dimensions: {
            enabled: false,
            breakpoints: false,
            gap: false,
            margin: false,
            padding: false
        }
    };
}

/**
 * Get spacing options for a block
 * 
 * @param {string} blockName - The block name
 * @returns {Array} Array of spacing options with slug, size, and name
 */
export function getSpacingOptions(blockName) {
    const config = getBlockDimensionsConfig(blockName);
    return config.spacings || [];
}

/**
 * Get breakpoint options for a block
 * 
 * @param {string} blockName - The block name
 * @returns {Array} Array of breakpoint options with slug, value, and name
 * @deprecated Use getBreakpointOptions from '../../core/utils/breakpoints' instead
 */
export function getBreakpointOptions(blockName) {
    const config = getBlockDimensionsConfig(blockName);
    return config.breakpoints || [];
}

/**
 * Check if dimensions are enabled for a block
 * 
 * @param {string} blockName - The block name
 * @returns {boolean} Whether dimensions are enabled
 */
export function isDimensionsEnabled(blockName) {
    const config = getBlockDimensionsConfig(blockName);
    return config.dimensions?.enabled || false;
}

/**
 * Check if a specific dimension type is enabled for a block
 * 
 * @param {string} blockName - The block name
 * @param {string} dimensionType - The dimension type ('gap', 'margin', 'padding')
 * @returns {boolean} Whether the dimension type is enabled
 */
export function isDimensionTypeEnabled(blockName, dimensionType) {
    const config = getBlockDimensionsConfig(blockName);
    return config.dimensions?.[dimensionType] || false;
}

/**
 * Check if responsive breakpoints are enabled for a block
 * 
 * @param {string} blockName - The block name
 * @returns {boolean} Whether responsive breakpoints are enabled
 */
export function areBreakpointsEnabled(blockName) {
    const config = getBlockDimensionsConfig(blockName);
    return config.dimensions?.breakpoints || false;
}

/**
 * Get all enabled dimension types for a block
 * 
 * @param {string} blockName - The block name
 * @returns {Array} Array of enabled dimension type names
 */
export function getEnabledDimensionTypes(blockName) {
    const config = getBlockDimensionsConfig(blockName);
    const dimensions = config.dimensions || {};
    
    return ['gap', 'margin', 'padding'].filter(type => dimensions[type]);
}

/**
 * Create responsive value object with all breakpoints
 * 
 * @param {string} blockName - The block name
 * @param {*} baseValue - The base value
 * @returns {Object} Responsive value object with base and breakpoint keys
 */
export function createResponsiveValue(blockName, baseValue = undefined) {
    const responsiveValue = { base: baseValue };
    
    if (areBreakpointsEnabled(blockName)) {
        const breakpoints = getBreakpointOptions(blockName);
        breakpoints.forEach(breakpoint => {
            responsiveValue[breakpoint.slug] = undefined;
        });
    }
    
    return responsiveValue;
}

/**
 * Normalize spacing value to ensure it exists in the available options
 * 
 * @param {string} blockName - The block name
 * @param {string} value - The spacing value to normalize
 * @returns {string|undefined} Normalized spacing value or undefined if invalid
 */
export function normalizeSpacingValue(blockName, value) {
    if (!value) return undefined;
    
    const spacings = getSpacingOptions(blockName);
    const found = spacings.find(spacing => spacing.slug === value);
    
    return found ? found.slug : undefined;
}

/**
 * Get spacing option by slug
 * 
 * @param {string} blockName - The block name
 * @param {string} slug - The spacing slug
 * @returns {Object|null} Spacing option or null if not found
 */
export function getSpacingOption(blockName, slug) {
    if (!slug) return null;
    
    const spacings = getSpacingOptions(blockName);
    return spacings.find(spacing => spacing.slug === slug) || null;
}

/**
 * Convert responsive value to CSS classes
 * 
 * @param {Object} responsiveValue - The responsive value object
 * @param {string} prefix - The CSS class prefix (e.g., 'gap', 'm', 'p')
 * @param {string} blockName - The block name for breakpoint context
 * @returns {string} Space-separated CSS classes
 */
export function responsiveValueToClasses(responsiveValue, prefix, blockName) {
    if (!responsiveValue || typeof responsiveValue !== 'object') {
        return '';
    }
    
    const classes = [];
    
    // Base class
    if (responsiveValue.base !== undefined && responsiveValue.base !== '') {
        classes.push(`${prefix}-${responsiveValue.base}`);
    }
    
    // Breakpoint classes
    if (areBreakpointsEnabled(blockName)) {
        const breakpoints = getBreakpointOptions(blockName);
        breakpoints.forEach(breakpoint => {
            const value = responsiveValue[breakpoint.slug];
            if (value !== undefined && value !== '') {
                classes.push(`${breakpoint.slug}:${prefix}-${value}`);
            }
        });
    }
    
    return classes.join(' ');
}