/**
 * Typography Presets - Class Application
 * 
 * Applies preset CSS classes to blocks in both editor and frontend
 */

(function() {
    const { addFilter } = wp.hooks;
    const { createHigherOrderComponent } = wp.compose;

    // Add preset classes to editor blocks
    const addPresetClassToEditor = createHigherOrderComponent(function(BlockListBlock) {
        return function(props) {
            // Get data from localized script
            const { settings: moduleSettings } = window.orbitoolsTypographyPresets || {};
            
            if (!moduleSettings) {
                return wp.element.createElement(BlockListBlock, props);
            }

            // Define allowed blocks (with fallback)
            const allowedBlocks = moduleSettings.allowed_blocks || [
                'core/paragraph', 'core/heading', 'core/list', 'core/quote', 'core/button'
            ];

            if (!allowedBlocks.includes(props.name)) {
                return wp.element.createElement(BlockListBlock, props);
            }

            const { orbitoolsTypographyPreset } = props.attributes;

            if (orbitoolsTypographyPreset) {
                const existingClasses = props.className || '';
                const presetClasses = `has-type-preset has-type-preset-${orbitoolsTypographyPreset}`;
                const newClassName = (existingClasses + ' ' + presetClasses).trim();
                
                const newProps = {
                    ...props,
                    className: newClassName
                };
                
                return wp.element.createElement(BlockListBlock, newProps);
            }

            return wp.element.createElement(BlockListBlock, props);
        };
    }, 'addPresetClassToEditor');

    // Add preset classes to block wrapper for frontend
    function addPresetClassToSave(props, blockType, attributes) {
        // Get data from localized script
        const { settings: moduleSettings } = window.orbitoolsTypographyPresets || {};
        
        if (!moduleSettings) {
            return props;
        }

        // Define allowed blocks (with fallback)
        const allowedBlocks = moduleSettings.allowed_blocks || [
            'core/paragraph', 'core/heading', 'core/list', 'core/quote', 'core/button'
        ];

        if (!allowedBlocks.includes(blockType.name)) {
            return props;
        }

        const { orbitoolsTypographyPreset } = attributes;

        if (orbitoolsTypographyPreset) {
            const existingClasses = props.className || '';
            const presetClasses = `has-type-preset has-type-preset-${orbitoolsTypographyPreset}`;
            props.className = (existingClasses + ' ' + presetClasses).trim();
        }

        return props;
    }

    addFilter(
        'editor.BlockListBlock',
        'orbitools/add-preset-editor-class',
        addPresetClassToEditor,
        20
    );

    addFilter(
        'blocks.getSaveContent.extraProps',
        'orbitools/add-preset-class',
        addPresetClassToSave,
        20
    );
})();