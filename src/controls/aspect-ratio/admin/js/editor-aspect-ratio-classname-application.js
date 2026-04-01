/**
 * Aspect Ratio Controls - Class Application
 *
 * Applies responsive aspect ratio CSS classes to blocks in both editor and frontend.
 *
 * @since 1.4.0
 */

(function() {
    var addFilter = wp.hooks.addFilter;
    var createHigherOrderComponent = wp.compose.createHigherOrderComponent;

    /**
     * Generate responsive aspect ratio classes from attribute data
     */
    function getAspectRatioClasses(aspectRatio) {
        if (!aspectRatio || typeof aspectRatio !== 'object') return '';

        var classes = ['has-aspect-ratio'];

        Object.entries(aspectRatio).forEach(function(entry) {
            var breakpoint = entry[0];
            var slug = entry[1];

            if (!slug) return;

            if (breakpoint === 'base') {
                classes.push('has-aspect-ratio--' + slug);
            } else {
                classes.push(breakpoint + ':has-aspect-ratio--' + slug);
            }
        });

        // Only return classes if we have modifiers (not just the base class)
        return classes.length > 1 ? classes.join(' ') : '';
    }

    /**
     * Check if block has aspect ratio support
     */
    function blockHasAspectRatioSupport(blockName) {
        var blockType = wp.blocks.getBlockType(blockName);
        if (!blockType || !blockType.supports || !blockType.supports.orbitools) {
            return false;
        }

        var arSupports = blockType.supports.orbitools.aspectRatio;
        return arSupports && arSupports !== false &&
               (arSupports === true || Object.keys(arSupports).length > 0);
    }

    // Add aspect ratio classes to editor blocks
    var addAspectRatioClassesToEditor = createHigherOrderComponent(function(BlockListBlock) {
        return function(props) {
            if (!blockHasAspectRatioSupport(props.name)) {
                return wp.element.createElement(BlockListBlock, props);
            }

            var orbAspectRatio = props.attributes.orbAspectRatio;
            var arClasses = getAspectRatioClasses(orbAspectRatio);

            if (arClasses) {
                var existingClasses = props.className || '';
                var newClassName = (existingClasses + ' ' + arClasses).trim();

                return wp.element.createElement(BlockListBlock, Object.assign({}, props, {
                    className: newClassName
                }));
            }

            return wp.element.createElement(BlockListBlock, props);
        };
    }, 'addAspectRatioClassesToEditor');

    // Add aspect ratio classes to block wrapper for frontend
    function addAspectRatioClassesToSave(props, blockType, attributes) {
        if (!blockHasAspectRatioSupport(blockType.name)) {
            return props;
        }

        var orbAspectRatio = attributes.orbAspectRatio;
        var arClasses = getAspectRatioClasses(orbAspectRatio);

        if (arClasses) {
            var existingClasses = props.className || '';
            props.className = (existingClasses + ' ' + arClasses).trim();
        }

        return props;
    }

    addFilter(
        'editor.BlockListBlock',
        'orbitools/add-aspect-ratio-editor-classes',
        addAspectRatioClassesToEditor,
        20
    );

    addFilter(
        'blocks.getSaveContent.extraProps',
        'orbitools/add-aspect-ratio-classes',
        addAspectRatioClassesToSave,
        20
    );
})();
