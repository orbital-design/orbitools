/**
 * Column Widths Controls - Attribute Registration
 * 
 * Registers the orbitoolsColumnWidths attribute for blocks that support column width controls
 */

(function() {
    const { addFilter } = wp.hooks;

    // Helper function to get block supports
    function getBlockSupports(blockName) {
        const blockType = wp.blocks.getBlockType(blockName);
        return blockType?.supports?.columnWidthControls;
    }

    // Add orbitoolsColumnWidths attribute to blocks that support it
    const addColumnWidthsAttribute = (settings, name) => {
        const columnWidthSupports = settings.supports?.columnWidthControls;
        
        console.log('Attribute registration for', name, 'supports:', columnWidthSupports);
        
        if (!columnWidthSupports) {
            return settings;
        }

        // Add the column widths attribute
        settings.attributes = {
            ...settings.attributes,
            orbitoolsColumnWidths: {
                type: 'object',
                default: {}
            }
        };

        return settings;
    };

    addFilter(
        'blocks.registerBlockType',
        'orbitools/column-widths-controls/add-attributes',
        addColumnWidthsAttribute
    );

})();