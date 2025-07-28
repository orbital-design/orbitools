/**
 * Flex Layout Controls - Attribute Registration
 * 
 * Handles registering flex layout attributes on blocks based on their supports.flexControls configuration
 */

// Use external configuration object - source of truth for defaults
const flexControlsConfig = window.FlexControlsConfig || {
    flexDirection: { default: 'row' },
    flexWrap: { default: 'nowrap' },
    alignItems: { default: 'stretch' },
    justifyContent: { default: 'flex-start' },
    alignContent: { default: 'stretch' },
    stackOnMobile: { default: true }
};

// Register attributes immediately when script loads
wp.hooks.addFilter(
    'blocks.registerBlockType',
    'orbitools/add-flex-attributes',
    function(settings, name) {
        // Check if this block supports flex controls
        const flexSupports = settings.supports?.flexControls;
        
        if (!flexSupports) {
            return settings;
        }
        
        // Create default flex controls object
        const defaultFlexControls = {};
        
        if (flexSupports === true) {
            // Add all flex controls when flexControls: true
            defaultFlexControls.flexDirection = flexControlsConfig.flexDirection.default;
            defaultFlexControls.flexWrap = flexControlsConfig.flexWrap.default;
            defaultFlexControls.alignItems = flexControlsConfig.alignItems.default;
            defaultFlexControls.justifyContent = flexControlsConfig.justifyContent.default;
            defaultFlexControls.alignContent = flexControlsConfig.alignContent.default;
            defaultFlexControls.stackOnMobile = flexControlsConfig.stackOnMobile.default;
        } else if (typeof flexSupports === 'object') {
            // Add specific controls based on configuration
            if (flexSupports.flexDirection !== false) {
                defaultFlexControls.flexDirection = flexControlsConfig.flexDirection.default;
            }
            if (flexSupports.flexWrap !== false) {
                defaultFlexControls.flexWrap = flexControlsConfig.flexWrap.default;
            }
            if (flexSupports.alignItems !== false) {
                defaultFlexControls.alignItems = flexControlsConfig.alignItems.default;
            }
            if (flexSupports.justifyContent !== false) {
                defaultFlexControls.justifyContent = flexControlsConfig.justifyContent.default;
            }
            if (flexSupports.alignContent !== false) {
                defaultFlexControls.alignContent = flexControlsConfig.alignContent.default;
            }
            if (flexSupports.stackOnMobile !== false) {
                defaultFlexControls.stackOnMobile = flexControlsConfig.stackOnMobile.default;
            }
        }
        
        // Add the single flex controls attribute to the block
        if (Object.keys(defaultFlexControls).length > 0) {
            settings.attributes = {
                ...settings.attributes,
                orbitoolsFlexControls: {
                    type: 'object',
                    default: defaultFlexControls
                }
            };
        }
        
        return settings;
    },
    1
);