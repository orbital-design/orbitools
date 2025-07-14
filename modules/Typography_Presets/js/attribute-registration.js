/**
 * Typography Presets - Attribute Registration
 * 
 * Handles registering the orbitoolsTypographyPreset attribute on allowed block types
 */

// Register attribute immediately when script loads
wp.hooks.addFilter(
    'blocks.registerBlockType',
    'orbitools/add-preset-attribute',
    function(settings, name) {
        const { settings: moduleSettings } = window.orbitoolsTypographyPresets || {};
        
        if (!moduleSettings) {
            return settings;
        }

        const allowedBlocks = moduleSettings.allowed_blocks || [
            'core/paragraph', 'core/heading', 'core/list', 'core/quote', 'core/button'
        ];

        if (allowedBlocks.includes(name)) {
            settings.attributes = {
                ...settings.attributes,
                orbitoolsTypographyPreset: {
                    type: 'string',
                    default: ''
                }
            };
        }
        return settings;
    },
    1
);