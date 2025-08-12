/**
 * Typography Presets - Core Controls Removal
 *
 * Removes WordPress core typography controls for blocks that support typography presets
 */

(function() {
    const { addFilter } = wp.hooks;

    addFilter(
        'blocks.registerBlockType',
        'orbitools/remove-core-typography-controls',
        function(settings, name) {
            // Get settings from localized data
            const { settings: moduleSettings } = window.orbitoolsTypographyPresets || {};

            if (!moduleSettings) {
                return settings;
            }
            // Get allowed blocks from settings
            const allowedBlocks = moduleSettings.typography_allowed_blocks;

            if (!Array.isArray(allowedBlocks) || allowedBlocks.length === 0) {
                return settings;
            }

            if (!allowedBlocks.includes(name)) {
                return settings;
            }

            if (!settings.supports) {
                settings.supports = {};
            }

            // Define typography keys to disable at different levels
            const typographyKeys = {
                'supports': ['fontSize', 'lineHeight', '__experimentalFontFamily', '__experimentalDefaultControls', '__experimentalFontSize', '__experimentalFontWeight', '__experimentalLineHeight', '__experimentalLetterSpacing', '__experimentalWritingMode'],
                'supports.typography': ['fontSize', 'lineHeight', '__experimentalFontFamily', '__experimentalDefaultControls', '__experimentalFontSize', '__experimentalFontWeight', '__experimentalLineHeight', '__experimentalLetterSpacing', '__experimentalWritingMode']
            };

            // Function to get nested object property
            const getNestedProperty = (obj, path) => {
                return path.split('.').reduce((current, key) => current && current[key], obj);
            };

            // Function to set nested object property
            const setNestedProperty = (obj, path, value) => {
                const keys = path.split('.');
                const lastKey = keys.pop();
                const target = keys.reduce((current, key) => {
                    if (!current[key]) current[key] = {};
                    return current[key];
                }, obj);
                target[lastKey] = value;
            };

            // Remove typography controls dynamically
            Object.entries(typographyKeys).forEach(([path, keys]) => {
                const targetObject = getNestedProperty(settings, path);
                if (targetObject) {
                    keys.forEach(key => {
                        if (key in targetObject) {
                            setNestedProperty(settings, `${path}.${key}`, false);
                        }
                    });
                }
            });

            return settings;
        },
        20  // Early priority
    );
})();
