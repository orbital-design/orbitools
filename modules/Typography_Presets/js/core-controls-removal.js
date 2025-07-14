/**
 * Typography Presets - Core Controls Removal
 * 
 * Removes WordPress core typography controls when replace_core_controls is enabled
 */

(function() {
    const { addFilter } = wp.hooks;
    
    addFilter(
        'blocks.registerBlockType',
        'orbitools/remove-core-typography-controls',
        function(settings, name) {
            // Get settings from localized data
            const { settings: moduleSettings } = window.orbitoolsTypographyPresets || {};

            if (!moduleSettings || !moduleSettings.replace_core_controls) {
                return settings;
            }
            
            // Define allowed blocks (with fallback)
            const allowedBlocks = moduleSettings.allowed_blocks || [
                'core/paragraph', 'core/heading', 'core/list', 'core/quote', 'core/button'
            ];
            
            if (!allowedBlocks.includes(name)) {
                return settings;
            }
            
            if (!settings.supports) {
                settings.supports = {};
            }
            
            // Remove all typography supports
            settings.supports.typography = false;
            settings.supports.fontSize = false;
            settings.supports.lineHeight = false;
            settings.supports.__experimentalFontFamily = false;
            settings.supports.__experimentalFontSize = false;
            settings.supports.__experimentalFontWeight = false;
            settings.supports.__experimentalLineHeight = false;
            settings.supports.__experimentalLetterSpacing = false;
            settings.supports.__experimentalTextDecoration = false;
            settings.supports.__experimentalTextTransform = false;
            settings.supports.__experimentalWritingMode = false;
            
            return settings;
        },
        5  // Early priority
    );
})();