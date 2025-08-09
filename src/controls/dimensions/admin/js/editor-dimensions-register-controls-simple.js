/**
 * Dimensions Controls - Simple Control Registration
 *
 * Automatically adds simple dimension controls to blocks with orbitools.dimensions support
 */

(function() {
    const { addFilter } = wp.hooks;
    const { createHigherOrderComponent } = wp.compose;
    const { Fragment } = wp.element;
    const { InspectorControls } = wp.blockEditor;
    const { 
        __experimentalToolsPanel: ToolsPanel,
        __experimentalToolsPanelItem: ToolsPanelItem,
        RangeControl
    } = wp.components;

    /**
     * Check if block has dimensions support
     */
    function blockHasDimensionsSupport(blockName) {
        const blockType = wp.blocks.getBlockType(blockName);
        if (!blockType || !blockType.supports || !blockType.supports.orbitools) {
            return null;
        }
        
        const dimensionsSupports = blockType.supports.orbitools.dimensions;
        if (!dimensionsSupports || dimensionsSupports === false) {
            return null;
        }

        return dimensionsSupports;
    }

    /**
     * Simple Dimensions Control Component
     */
    function SimpleDimensionsControl({ gap, padding, margin, onGapChange, onPaddingChange, onMarginChange, supports }) {
        /**
         * Reset all dimensions
         */
        const resetAllDimensions = () => {
            if (supports.gap && onGapChange) onGapChange({});
            if (supports.padding && onPaddingChange) onPaddingChange({});
            if (supports.margin && onMarginChange) onMarginChange({});
        };

        // Simple range options (0-10 for now)
        const rangeOptions = Array.from({length: 11}, (_, i) => ({
            value: i,
            label: i === 0 ? 'None' : i.toString()
        }));

        return wp.element.createElement(ToolsPanel, {
            label: 'Dimensions',
            resetAll: resetAllDimensions,
            panelId: 'dimensions-panel'
        },
            // Base breakpoint only for now
            wp.element.createElement('div', { 
                style: { padding: '16px', borderBottom: '1px solid #e0e0e0' }
            },
                wp.element.createElement('h4', { 
                    style: { margin: '0 0 12px 0', fontSize: '13px', fontWeight: '600' } 
                }, 'Base'),
                
                // Gap Control
                supports.gap && wp.element.createElement(ToolsPanelItem, {
                    hasValue: () => Boolean(gap?.base),
                    label: 'Gap',
                    onDeselect: () => {
                        const newGap = { ...gap };
                        delete newGap.base;
                        onGapChange(newGap);
                    },
                    panelId: 'dimensions-panel'
                },
                    wp.element.createElement('div', { style: { marginBottom: '16px' } },
                        wp.element.createElement('label', {
                            style: {
                                display: 'block',
                                marginBottom: '8px',
                                fontSize: '11px',
                                fontWeight: '500',
                                textTransform: 'uppercase',
                                color: '#1e1e1e'
                            }
                        }, 'Gap'),
                        wp.element.createElement(RangeControl, {
                            value: parseInt(gap?.base) || 0,
                            onChange: (value) => {
                                onGapChange({ ...gap, base: value.toString() });
                            },
                            min: 0,
                            max: 10,
                            step: 1,
                            withInputField: false,
                            renderTooltipContent: (value) => `Gap ${value}`,
                            __next40pxDefaultSize: true,
                            __nextHasNoMarginBottom: true
                        })
                    )
                ),

                // Padding Control
                supports.padding && wp.element.createElement(ToolsPanelItem, {
                    hasValue: () => Boolean(padding?.base),
                    label: 'Padding',
                    onDeselect: () => {
                        const newPadding = { ...padding };
                        delete newPadding.base;
                        onPaddingChange(newPadding);
                    },
                    panelId: 'dimensions-panel'
                },
                    wp.element.createElement('div', { style: { marginBottom: '16px' } },
                        wp.element.createElement('label', {
                            style: {
                                display: 'block',
                                marginBottom: '8px',
                                fontSize: '11px',
                                fontWeight: '500',
                                textTransform: 'uppercase',
                                color: '#1e1e1e'
                            }
                        }, 'Padding'),
                        wp.element.createElement(RangeControl, {
                            value: typeof padding?.base === 'object' ? parseInt(padding.base.value) || 0 : parseInt(padding?.base) || 0,
                            onChange: (value) => {
                                onPaddingChange({ ...padding, base: { type: 'all', value: value.toString() } });
                            },
                            min: 0,
                            max: 10,
                            step: 1,
                            withInputField: false,
                            renderTooltipContent: (value) => `Padding ${value}`,
                            __next40pxDefaultSize: true,
                            __nextHasNoMarginBottom: true
                        })
                    )
                ),

                // Margin Control
                supports.margin && wp.element.createElement(ToolsPanelItem, {
                    hasValue: () => Boolean(margin?.base),
                    label: 'Margin',
                    onDeselect: () => {
                        const newMargin = { ...margin };
                        delete newMargin.base;
                        onMarginChange(newMargin);
                    },
                    panelId: 'dimensions-panel'
                },
                    wp.element.createElement('div', { style: { marginBottom: '16px' } },
                        wp.element.createElement('label', {
                            style: {
                                display: 'block',
                                marginBottom: '8px',
                                fontSize: '11px',
                                fontWeight: '500',
                                textTransform: 'uppercase',
                                color: '#1e1e1e'
                            }
                        }, 'Margin'),
                        wp.element.createElement(RangeControl, {
                            value: typeof margin?.base === 'object' ? parseInt(margin.base.value) || 0 : parseInt(margin?.base) || 0,
                            onChange: (value) => {
                                onMarginChange({ ...margin, base: { type: 'all', value: value.toString() } });
                            },
                            min: 0,
                            max: 10,
                            step: 1,
                            withInputField: false,
                            renderTooltipContent: (value) => `Margin ${value}`,
                            __next40pxDefaultSize: true,
                            __nextHasNoMarginBottom: true
                        })
                    )
                )
            )
        );
    }

    // Add automatic dimension controls
    const withDimensionControls = createHigherOrderComponent(function(BlockEdit) {
        return function(props) {
            const dimensionsSupports = blockHasDimensionsSupport(props.name);
            
            if (!dimensionsSupports) {
                return wp.element.createElement(BlockEdit, props);
            }

            const { attributes, setAttributes } = props;
            const { orbGap, orbPadding, orbMargin } = attributes;

            // Handle dimension changes
            const handleGapChange = (newGap) => {
                setAttributes({ orbGap: newGap });
            };

            const handlePaddingChange = (newPadding) => {
                setAttributes({ orbPadding: newPadding });
            };

            const handleMarginChange = (newMargin) => {
                setAttributes({ orbMargin: newMargin });
            };

            return wp.element.createElement(Fragment, {},
                wp.element.createElement(BlockEdit, props),
                wp.element.createElement(InspectorControls, { group: 'styles' },
                    wp.element.createElement(SimpleDimensionsControl, {
                        gap: orbGap,
                        padding: orbPadding,
                        margin: orbMargin,
                        onGapChange: handleGapChange,
                        onPaddingChange: handlePaddingChange,
                        onMarginChange: handleMarginChange,
                        supports: dimensionsSupports
                    })
                )
            );
        };
    }, 'withDimensionControls');

    addFilter(
        'editor.BlockEdit',
        'orbitools/add-dimension-controls',
        withDimensionControls,
        5
    );
})();