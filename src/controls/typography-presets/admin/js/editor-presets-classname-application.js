/**
 * Typography Presets - Class Application
 *
 * Applies preset CSS classes to blocks in both editor and frontend.
 *
 * The preset attribute is responsive — an object keyed by breakpoint slug
 * ({ base, tablet, mobile }) — with back-compat for the legacy bare-string
 * value (treated as the base preset). For a base-only value the output is
 * identical to the pre-responsive class, so existing saved blocks stay valid.
 */

(function() {
    const { addFilter } = wp.hooks;
    const { createHigherOrderComponent } = wp.compose;

    /**
     * Build the preset class list from a responsive (or legacy string) value.
     * base → has-type-preset-{id}; other slugs → {slug}:has-type-preset-{id}.
     */
    function getPresetClasses(value) {
        if (!value) {
            return '';
        }

        // Normalise: legacy string → { base: string }.
        const bySlug = (typeof value === 'object') ? value : { base: value };

        const modifiers = [];
        Object.keys(bySlug).forEach(function(slug) {
            const preset = bySlug[slug];
            if (!preset) {
                return;
            }
            modifiers.push(
                slug === 'base'
                    ? 'has-type-preset-' + preset
                    : slug + ':has-type-preset-' + preset
            );
        });

        return modifiers.length ? 'has-type-preset ' + modifiers.join(' ') : '';
    }

    function isAllowedBlock(blockName) {
        const { settings: moduleSettings } = window.orbitoolsTypographyPresets || {};
        const allowedBlocks = moduleSettings && moduleSettings.typography_allowed_blocks;
        return Array.isArray(allowedBlocks) && allowedBlocks.indexOf(blockName) !== -1;
    }

    // Add preset classes to editor blocks
    const addPresetClassToEditor = createHigherOrderComponent(function(BlockListBlock) {
        return function(props) {
            if (!isAllowedBlock(props.name)) {
                return wp.element.createElement(BlockListBlock, props);
            }

            const presetClasses = getPresetClasses(props.attributes.orbitoolsTypographyPreset);

            if (presetClasses) {
                const existingClasses = props.className || '';
                const newProps = {
                    ...props,
                    className: (existingClasses + ' ' + presetClasses).trim()
                };
                return wp.element.createElement(BlockListBlock, newProps);
            }

            return wp.element.createElement(BlockListBlock, props);
        };
    }, 'addPresetClassToEditor');

    // Add preset classes to block wrapper for frontend
    function addPresetClassToSave(props, blockType, attributes) {
        if (!isAllowedBlock(blockType.name)) {
            return props;
        }

        const presetClasses = getPresetClasses(attributes.orbitoolsTypographyPreset);

        if (presetClasses) {
            const existingClasses = props.className || '';
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
