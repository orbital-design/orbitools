/**
 * Spacings Controls - Attribute Registration
 *
 * Handles registering orb* spacing attributes on blocks with orbitools.spacings support
 */


// Register attributes immediately when script loads
wp.hooks.addFilter(
    'blocks.registerBlockType',
    'orbitools/add-spacings-attributes',
    function(settings, name) {
        // Check if block has orbitools spacings support
        if (!settings.supports || !settings.supports.orbitools || !settings.supports.orbitools.spacings) {
            return settings;
        }

        const spacingsSupports = settings.supports.orbitools.spacings;

        // Only proceed if spacings support is enabled
        if (spacingsSupports === false || (typeof spacingsSupports === 'object' && Object.keys(spacingsSupports).length === 0)) {
            return settings;
        }

        // Add spacing attributes based on what's enabled in supports
        const newAttributes = {};

        // Add gap attribute if gap support is enabled
        if (spacingsSupports.gap === true) {
            newAttributes.orbGap = {
                type: 'object',
                default: {}
            };
        }

        // Add padding attribute if padding support is enabled
        if (spacingsSupports.padding === true) {
            newAttributes.orbPadding = {
                type: 'object',
                default: {}
            };
        }

        // Add margin attribute if margin support is enabled
        if (spacingsSupports.margin === true) {
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