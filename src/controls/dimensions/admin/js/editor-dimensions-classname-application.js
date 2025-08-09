/**
 * Dimensions Controls - Class Application
 *
 * Applies dimension CSS classes to blocks in both editor and frontend
 */

(function() {
    const { addFilter } = wp.hooks;
    const { createHigherOrderComponent } = wp.compose;

    /**
     * Generate responsive gap classes from gap data
     */
    function getGapClasses(gap) {
        if (!gap || typeof gap !== 'object') return '';

        const classes = [];
        
        Object.entries(gap).forEach(([breakpoint, value]) => {
            if (!value) return;
            
            let className = '';
            if (breakpoint === 'base') {
                className = `gap-${value}`;
            } else {
                className = `${breakpoint}:gap-${value}`;
            }
            classes.push(className);
        });
        
        return classes.join(' ');
    }

    /**
     * Generate responsive padding classes from padding data
     */
    function getPaddingClasses(padding) {
        if (!padding || typeof padding !== 'object') return '';

        const classes = [];
        
        Object.entries(padding).forEach(([breakpoint, config]) => {
            if (!config) return;
            
            // Handle different padding data formats
            let classNames = [];
            const prefix = breakpoint === 'base' ? 'p' : `${breakpoint}:p`;

            if (typeof config === 'string') {
                // Legacy format: just the value
                classNames.push(`${prefix}-${config}`);
            } else if (typeof config === 'object' && config.type) {
                // New format: { type: 'all', value: '4' } or { type: 'sides', top: '1', right: '2', ... }
                const type = config.type;
                
                switch (type) {
                    case 'all':
                        if (config.value) {
                            classNames.push(`${prefix}-${config.value}`);
                        }
                        break;
                    case 'split':
                        if (config.x) classNames.push(`${prefix}x-${config.x}`);
                        if (config.y) classNames.push(`${prefix}y-${config.y}`);
                        break;
                    case 'sides':
                        if (config.top) classNames.push(`${prefix}t-${config.top}`);
                        if (config.right) classNames.push(`${prefix}r-${config.right}`);
                        if (config.bottom) classNames.push(`${prefix}b-${config.bottom}`);
                        if (config.left) classNames.push(`${prefix}l-${config.left}`);
                        break;
                }
            } else {
                return;
            }

            classes.push(...classNames);
        });
        
        return classes.join(' ');
    }

    /**
     * Generate responsive margin classes from margin data
     */
    function getMarginClasses(margin) {
        if (!margin || typeof margin !== 'object') return '';

        const classes = [];
        
        Object.entries(margin).forEach(([breakpoint, config]) => {
            if (!config) return;
            
            // Handle different margin data formats
            let classNames = [];
            const prefix = breakpoint === 'base' ? 'm' : `${breakpoint}:m`;

            if (typeof config === 'string') {
                // Legacy format: just the value
                classNames.push(`${prefix}-${config}`);
            } else if (typeof config === 'object' && config.type) {
                // New format: { type: 'all', value: '4' } or { type: 'sides', top: '1', right: '2', ... }
                const type = config.type;
                
                switch (type) {
                    case 'all':
                        if (config.value) {
                            classNames.push(`${prefix}-${config.value}`);
                        }
                        break;
                    case 'split':
                        if (config.x) classNames.push(`${prefix}x-${config.x}`);
                        if (config.y) classNames.push(`${prefix}y-${config.y}`);
                        break;
                    case 'sides':
                        if (config.top) classNames.push(`${prefix}t-${config.top}`);
                        if (config.right) classNames.push(`${prefix}r-${config.right}`);
                        if (config.bottom) classNames.push(`${prefix}b-${config.bottom}`);
                        if (config.left) classNames.push(`${prefix}l-${config.left}`);
                        break;
                }
            } else {
                return;
            }

            classes.push(...classNames);
        });
        
        return classes.join(' ');
    }

    /**
     * Check if block has dimensions support
     */
    function blockHasDimensionsSupport(blockName) {
        const blockType = wp.blocks.getBlockType(blockName);
        if (!blockType || !blockType.supports || !blockType.supports.orbitools) {
            return false;
        }
        
        const dimensionsSupports = blockType.supports.orbitools.dimensions;
        return dimensionsSupports && dimensionsSupports !== false && 
               (dimensionsSupports === true || Object.keys(dimensionsSupports).length > 0);
    }

    // Add dimension classes to editor blocks
    const addDimensionClassesToEditor = createHigherOrderComponent(function(BlockListBlock) {
        return function(props) {
            if (!blockHasDimensionsSupport(props.name)) {
                return wp.element.createElement(BlockListBlock, props);
            }

            const { orbGap, orbPadding, orbMargin } = props.attributes;

            // Generate dimension classes
            const gapClasses = getGapClasses(orbGap);
            const paddingClasses = getPaddingClasses(orbPadding);
            const marginClasses = getMarginClasses(orbMargin);

            const dimensionClasses = [gapClasses, paddingClasses, marginClasses]
                .filter(Boolean)
                .join(' ');

            if (dimensionClasses) {
                const existingClasses = props.className || '';
                const newClassName = (existingClasses + ' ' + dimensionClasses).trim();

                const newProps = {
                    ...props,
                    className: newClassName
                };

                return wp.element.createElement(BlockListBlock, newProps);
            }

            return wp.element.createElement(BlockListBlock, props);
        };
    }, 'addDimensionClassesToEditor');

    // Add dimension classes to block wrapper for frontend
    function addDimensionClassesToSave(props, blockType, attributes) {
        if (!blockHasDimensionsSupport(blockType.name)) {
            return props;
        }

        const { orbGap, orbPadding, orbMargin } = attributes;

        // Generate dimension classes
        const gapClasses = getGapClasses(orbGap);
        const paddingClasses = getPaddingClasses(orbPadding);
        const marginClasses = getMarginClasses(orbMargin);

        const dimensionClasses = [gapClasses, paddingClasses, marginClasses]
            .filter(Boolean)
            .join(' ');

        if (dimensionClasses) {
            const existingClasses = props.className || '';
            props.className = (existingClasses + ' ' + dimensionClasses).trim();
        }

        return props;
    }

    addFilter(
        'editor.BlockListBlock',
        'orbitools/add-dimension-editor-classes',
        addDimensionClassesToEditor,
        20
    );

    addFilter(
        'blocks.getSaveContent.extraProps',
        'orbitools/add-dimension-classes',
        addDimensionClassesToSave,
        20
    );
})();