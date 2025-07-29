/**
 * Flex Layout Controls - Attribute Registration
 * 
 * Handles registering flex layout attributes on blocks based on their supports.flexControls configuration
 */

// Simple defaults - matches editor-controls.js and PHP Block_Helper
const DEFAULTS = {
    columnCount: 2,
    flexDirection: 'row',
    alignItems: 'stretch',
    justifyContent: 'flex-start',
    alignContent: 'stretch',
    enableGap: true,
    restrictContentWidth: false,
    stackOnMobile: true,
    columnLayout: 'fit',
    gridSystem: '5'
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
            defaultFlexControls.columnCount = DEFAULTS.columnCount;
            defaultFlexControls.flexDirection = DEFAULTS.flexDirection;
            defaultFlexControls.alignItems = DEFAULTS.alignItems;
            defaultFlexControls.justifyContent = DEFAULTS.justifyContent;
            defaultFlexControls.alignContent = DEFAULTS.alignContent;
            defaultFlexControls.enableGap = DEFAULTS.enableGap;
            defaultFlexControls.restrictContentWidth = DEFAULTS.restrictContentWidth;
            defaultFlexControls.stackOnMobile = DEFAULTS.stackOnMobile;
            defaultFlexControls.columnLayout = DEFAULTS.columnLayout;
            defaultFlexControls.gridSystem = DEFAULTS.gridSystem;
        } else if (typeof flexSupports === 'object') {
            // Add specific controls based on configuration
            if (flexSupports.columnCount !== false) {
                defaultFlexControls.columnCount = DEFAULTS.columnCount;
            }
            if (flexSupports.flexDirection !== false) {
                defaultFlexControls.flexDirection = DEFAULTS.flexDirection;
            }
            if (flexSupports.alignItems !== false) {
                defaultFlexControls.alignItems = DEFAULTS.alignItems;
            }
            if (flexSupports.justifyContent !== false) {
                defaultFlexControls.justifyContent = DEFAULTS.justifyContent;
            }
            if (flexSupports.alignContent !== false) {
                defaultFlexControls.alignContent = DEFAULTS.alignContent;
            }
            if (flexSupports.enableGap !== false) {
                defaultFlexControls.enableGap = DEFAULTS.enableGap;
            }
            if (flexSupports.restrictContentWidth !== false) {
                defaultFlexControls.restrictContentWidth = DEFAULTS.restrictContentWidth;
            }
            if (flexSupports.stackOnMobile !== false) {
                defaultFlexControls.stackOnMobile = DEFAULTS.stackOnMobile;
            }
            if (flexSupports.columnLayout !== false) {
                defaultFlexControls.columnLayout = DEFAULTS.columnLayout;
            }
            if (flexSupports.gridSystem !== false) {
                defaultFlexControls.gridSystem = DEFAULTS.gridSystem;
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