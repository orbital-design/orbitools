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

        // Get allowed blocks from settings
        const allowedBlocks = moduleSettings.typography_allowed_blocks;
        
        if (!Array.isArray(allowedBlocks) || allowedBlocks.length === 0) {
            return settings;
        }

        if (allowedBlocks.includes(name)) {
            settings.attributes = {
                ...settings.attributes,
                orbitoolsTypographyPreset: {
                    // Responsive: an object keyed by breakpoint slug
                    // ({ base, tablet, mobile }). Legacy values are a bare
                    // string (the base preset); both shapes are read by the
                    // control and class application, so old content keeps
                    // working with no migration.
                    type: ['string', 'object'],
                    default: ''
                }
            };
        }
        return settings;
    },
    1
);
