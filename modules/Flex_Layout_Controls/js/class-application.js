/**
 * Flex Layout Controls - Class Application
 * 
 * Applies flex layout CSS classes to blocks in both editor and frontend
 */

(function() {
    const { addFilter } = wp.hooks;
    const { createHigherOrderComponent } = wp.compose;
    
    // Use external configuration object - source of truth for defaults
    const flexControlsConfig = window.FlexControlsConfig || {
        flexDirection: { default: 'row' },
        flexWrap: { default: 'nowrap' },
        alignItems: { default: 'stretch' },
        justifyContent: { default: 'flex-start' },
        alignContent: { default: 'stretch' },
        stackOnMobile: { default: true }
    };

    // Helper function to get block supports
    function getBlockSupports(blockName) {
        const blockType = wp.blocks.getBlockType(blockName);
        return blockType?.supports?.flexControls;
    }

    // Add flex layout classes to editor blocks
    const addFlexClassesToEditor = createHigherOrderComponent(function(BlockListBlock) {
        return function(props) {
            // Get data from localized script
            const flexData = window.orbitoolsFlexLayout || {};
            
            if (!flexData.isEnabled) {
                return wp.element.createElement(BlockListBlock, props);
            }

            // Check if this block supports flex controls
            const flexSupports = getBlockSupports(props.name);
            
            if (!flexSupports) {
                return wp.element.createElement(BlockListBlock, props);
            }

            // Get flex controls from the object attribute
            const flexControls = props.attributes.orbitoolsFlexControls || {};

            // Always apply flex layout to supported blocks
            {
                const existingClasses = props.className || '';
                let flexClasses = 'has-flex-layout';
                
                // Use defaults if values are not set
                const flexDirection = flexControls.flexDirection || flexControlsConfig.flexDirection.default;
                const flexWrap = flexControls.flexWrap || flexControlsConfig.flexWrap.default;
                const alignItems = flexControls.alignItems || flexControlsConfig.alignItems.default;
                const justifyContent = flexControls.justifyContent || flexControlsConfig.justifyContent.default;
                const alignContent = flexControls.alignContent || flexControlsConfig.alignContent.default;
                const stackOnMobile = flexControls.stackOnMobile || flexControlsConfig.stackOnMobile.default;
                
                // Add flex direction class
                if (flexDirection !== 'row') {
                    flexClasses += ` has-flex-direction-${flexDirection}`;
                }
                
                // Add flex wrap class
                if (flexWrap !== 'nowrap') {
                    flexClasses += ` has-flex-wrap-${flexWrap.replace('-', '-')}`;
                }
                
                // Add align items class
                if (alignItems !== 'stretch') {
                    flexClasses += ` has-align-items-${alignItems.replace('-', '-')}`;
                }
                
                // Add justify content class
                if (justifyContent !== 'flex-start') {
                    flexClasses += ` has-justify-content-${justifyContent.replace('-', '-')}`;
                }
                
                // Add align content class
                if (alignContent !== 'stretch') {
                    flexClasses += ` has-align-content-${alignContent.replace('-', '-')}`;
                }
                
                // Add stack on mobile class
                if (stackOnMobile) {
                    flexClasses += ' flex-stack-mobile';
                }
                
                const newClassName = (existingClasses + ' ' + flexClasses).trim();
                
                const newProps = {
                    ...props,
                    className: newClassName
                };
                
                return wp.element.createElement(BlockListBlock, newProps);
            }
        };
    }, 'addFlexClassesToEditor');

    // Add flex layout classes to block wrapper for frontend
    function addFlexClassesToSave(props, blockType, attributes) {
        // Check if this block supports flex controls
        const flexSupports = blockType?.supports?.flexControls;
        
        if (!flexSupports) {
            return props;
        }

        // Get flex controls from the object attribute
        const flexControls = attributes.orbitoolsFlexControls || {};
        
        // Only apply classes if we have flex controls data
        if (Object.keys(flexControls).length === 0) {
            return props;
        }

        // Always apply flex layout to supported blocks
        const existingClasses = props.className || '';
        let flexClasses = 'has-flex-layout';
        
        // Use defaults if values are not set
        const flexDirection = flexControls.flexDirection || flexControlsConfig.flexDirection.default;
        const flexWrap = flexControls.flexWrap || flexControlsConfig.flexWrap.default;
        const alignItems = flexControls.alignItems || flexControlsConfig.alignItems.default;
        const justifyContent = flexControls.justifyContent || flexControlsConfig.justifyContent.default;
        const alignContent = flexControls.alignContent || flexControlsConfig.alignContent.default;
        
        // Add flex direction class (always add, even for row)
        flexClasses += ` has-flex-direction-${flexDirection}`;
        
        // Add flex wrap class (always add, even for nowrap)
        flexClasses += ` has-flex-wrap-${flexWrap}`;
        
        // Add align items class (always add, even for stretch)
        flexClasses += ` has-align-items-${alignItems}`;
        
        // Add justify content class (always add, even for flex-start)
        flexClasses += ` has-justify-content-${justifyContent}`;
        
        // Add align content class (always add, even for stretch)
        flexClasses += ` has-align-content-${alignContent}`;
        
        // Add stack on mobile class
        if (stackOnMobile) {
            flexClasses += ' flex-stack-mobile';
        }
        
        props.className = (existingClasses + ' ' + flexClasses).trim();

        return props;
    }

    addFilter(
        'editor.BlockListBlock',
        'orbitools/add-flex-editor-classes',
        addFlexClassesToEditor,
        20
    );

    addFilter(
        'blocks.getSaveContent.extraProps',
        'orbitools/add-flex-classes',
        addFlexClassesToSave,
        20
    );
})();