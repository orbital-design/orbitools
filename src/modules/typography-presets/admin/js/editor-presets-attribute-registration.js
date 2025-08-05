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
        // Check if orbitoolsTypographyPresets is available
        if (!window.orbitoolsTypographyPresets || !window.orbitoolsTypographyPresets.settings) {
            return settings;
        }

        const moduleSettings = window.orbitoolsTypographyPresets.settings;

        // Ensure allowed_blocks is an array
        let allowedBlocks = moduleSettings.typography_allowed_blocks;
        if (!Array.isArray(allowedBlocks)) {
            allowedBlocks = [
                'core/paragraph', 'core/heading', 'core/post-title', 'core/list', 'core/quote', 'core/button'
            ];
        }

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
