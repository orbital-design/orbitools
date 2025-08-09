/**
 * Dimensions Controls - Attribute Registration
 *
 * Handles registering orb* dimension attributes on blocks with orbitools.dimensions support
 */


// Register attributes immediately when script loads
wp.hooks.addFilter(
    'blocks.registerBlockType',
    'orbitools/add-dimensions-attributes',
    function(settings, name) {
        // Check if block has orbitools dimensions support
        if (!settings.supports || !settings.supports.orbitools || !settings.supports.orbitools.dimensions) {
            return settings;
        }

        const dimensionsSupports = settings.supports.orbitools.dimensions;

        // Only proceed if dimensions support is enabled
        if (dimensionsSupports === false || (typeof dimensionsSupports === 'object' && Object.keys(dimensionsSupports).length === 0)) {
            return settings;
        }

        // Add dimension attributes based on what's enabled in supports
        const newAttributes = {};

        // Add gap attribute if gap support is enabled
        if (dimensionsSupports.gap === true) {
            newAttributes.orbGap = {
                type: 'object',
                default: {}
            };
        }

        // Add padding attribute if padding support is enabled
        if (dimensionsSupports.padding === true) {
            newAttributes.orbPadding = {
                type: 'object',
                default: {}
            };
        }

        // Add margin attribute if margin support is enabled
        if (dimensionsSupports.margin === true) {
            newAttributes.orbMargin = {
                type: 'object',
                default: {}
            };
        }

        // Merge new attributes with existing ones
        if (Object.keys(newAttributes).length > 0) {
            settings.attributes = {
                ...settings.attributes,
                ...newAttributes
            };
        }

        return settings;
    },
    1  // Early priority to ensure attributes are registered before controls
);