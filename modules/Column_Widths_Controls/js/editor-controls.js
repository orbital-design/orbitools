/**
 * Column Widths Controls - Editor Controls
 * 
 * Adds column width controls to the block editor inspector using ToolsPanel
 */

(function() {
    const { addFilter } = wp.hooks;
    const { createHigherOrderComponent } = wp.compose;
    const { Fragment } = wp.element;
    const { InspectorControls } = wp.blockEditor;
    const { 
        __experimentalToolsPanel: ToolsPanel,
        __experimentalToolsPanelItem: ToolsPanelItem,
        SelectControl,
        __experimentalSpacer: Spacer
    } = wp.components;

    // Helper function to get block supports
    function getBlockSupports(blockName) {
        const blockType = wp.blocks.getBlockType(blockName);
        return blockType?.supports?.columnWidthControls;
    }

    // Helper function to get parent block name
    function getParentBlockName(clientId) {
        const { getBlock, getBlockParents } = wp.data.select('core/block-editor');
        const parents = getBlockParents(clientId);
        if (parents.length > 0) {
            const parentBlock = getBlock(parents[parents.length - 1]);
            return parentBlock ? parentBlock.name : null;
        }
        return null;
    }

    // 12-column grid options
    const columnOptions = [
        { label: 'Auto', value: 'auto' },
        { label: '1 of 12 (8.33%)', value: '1_col' },
        { label: '2 of 12 (16.67%)', value: '2_cols' },
        { label: '3 of 12 (25%)', value: '3_cols' },
        { label: '4 of 12 (33.33%)', value: '4_cols' },
        { label: '5 of 12 (41.67%)', value: '5_cols' },
        { label: '6 of 12 (50%)', value: '6_cols' },
        { label: '7 of 12 (58.33%)', value: '7_cols' },
        { label: '8 of 12 (66.67%)', value: '8_cols' },
        { label: '9 of 12 (75%)', value: '9_cols' },
        { label: '10 of 12 (83.33%)', value: '10_cols' },
        { label: '11 of 12 (91.67%)', value: '11_cols' },
        { label: '12 of 12 (100%)', value: '12_cols' }
    ];

    // Breakpoint configurations
    const breakpoints = {
        base: { label: 'Base', description: 'Default width for all screen sizes' },
        sm: { label: 'Small (576px+)', description: 'Width on small screens and up' },
        md: { label: 'Medium (768px+)', description: 'Width on medium screens and up' },
        lg: { label: 'Large (992px+)', description: 'Width on large screens and up' },
        xl: { label: 'Extra Large (1200px+)', description: 'Width on extra large screens and up' }
    };

    // Add inspector control
    const withColumnWidthsControl = createHigherOrderComponent(function(BlockEdit) {
        return function(props) {
            // Get data from localized script
            const columnWidthsData = window.orbitoolsColumnWidths || {};
            
            // Debug logging
            console.log('Column Widths Debug:', {
                blockName: props.name,
                columnWidthsData: columnWidthsData,
                isEnabled: columnWidthsData.isEnabled,
                blockSupports: getBlockSupports(props.name)
            });
            
            if (!columnWidthsData.isEnabled) {
                console.log('Column widths module not enabled');
                return wp.element.createElement(BlockEdit, props);
            }
            
            // Check if this block supports column width controls
            const widthSupports = getBlockSupports(props.name);
            
            if (!widthSupports) {
                console.log('Block does not support column width controls:', props.name);
                return wp.element.createElement(BlockEdit, props);
            }
            
            console.log('Column width controls should be visible for:', props.name);

            const { attributes, setAttributes, clientId } = props;
            
            // Get parent block to determine control behavior
            const parentBlockName = getParentBlockName(clientId);
            const isInRow = parentBlockName === 'orbital/row';
            const isInGrid = parentBlockName === 'orbital/grid';
            
            // Get column widths from the object attribute, with fallbacks
            const columnWidths = attributes.orbitoolsColumnWidths || {};
            
            // Helper function to update column widths
            const updateColumnWidth = (breakpoint, value) => {
                const newColumnWidths = {
                    ...columnWidths,
                    [breakpoint]: value || undefined // Remove if empty/undefined
                };
                
                // Clean up undefined values
                Object.keys(newColumnWidths).forEach(key => {
                    if (newColumnWidths[key] === undefined) {
                        delete newColumnWidths[key];
                    }
                });
                
                setAttributes({ orbitoolsColumnWidths: newColumnWidths });
            };

            // Create width control for a specific breakpoint
            const createWidthControl = (breakpoint, config) => {
                const value = columnWidths[breakpoint] || '';
                const hasValue = value !== '' && value !== undefined;
                
                return wp.element.createElement(ToolsPanelItem, {
                    key: breakpoint,
                    hasValue: () => hasValue,
                    label: config.label,
                    onDeselect: () => updateColumnWidth(breakpoint, undefined),
                    isShownByDefault: breakpoint === 'base', // Only show base by default
                    panelId: 'column-widths-panel'
                },
                    wp.element.createElement(SelectControl, {
                        label: config.label,
                        help: config.description,
                        value: value,
                        options: columnOptions,
                        onChange: (newValue) => updateColumnWidth(breakpoint, newValue === 'auto' ? undefined : newValue),
                        __next40pxDefaultSize: true,
                        __nextHasNoMarginBottom: true
                    })
                );
            };

            // Determine which breakpoints to show based on parent
            const getAvailableBreakpoints = () => {
                if (isInRow) {
                    // For orbital/row: only show base width (since row can stack on mobile)
                    return { base: breakpoints.base };
                } else if (isInGrid) {
                    // For orbital/grid: show all breakpoints
                    return breakpoints;
                } else {
                    // Default: show all breakpoints
                    return breakpoints;
                }
            };

            const availableBreakpoints = getAvailableBreakpoints();
            
            // Create controls for available breakpoints
            const controls = Object.entries(availableBreakpoints).map(([breakpoint, config]) => 
                createWidthControl(breakpoint, config)
            );

            // Panel title based on parent context
            const getPanelTitle = () => {
                if (isInRow) {
                    return 'Column Width';
                } else if (isInGrid) {
                    return 'Grid Column Widths';
                } else {
                    return 'Column Widths';
                }
            };

            return wp.element.createElement(
                Fragment,
                {},
                wp.element.createElement(BlockEdit, props),
                wp.element.createElement(
                    InspectorControls,
                    { group: 'settings' },
                    wp.element.createElement(
                        ToolsPanel,
                        {
                            label: getPanelTitle(),
                            resetAll: () => setAttributes({ orbitoolsColumnWidths: {} }),
                            panelId: 'column-widths-panel'
                        },
                        wp.element.createElement(
                            'p',
                            { 
                                style: { 
                                    fontSize: '13px', 
                                    color: '#757575', 
                                    margin: '0 0 16px 0',
                                    lineHeight: '1.4'
                                } 
                            },
                            isInRow ? 
                                'Set the column width within the row layout.' :
                                isInGrid ?
                                'Set responsive column widths for different screen sizes.' :
                                'Set column widths using a 12-column grid system.'
                        ),
                        ...controls,
                        isInGrid && wp.element.createElement(Spacer, { 
                            marginTop: 4,
                            marginBottom: 0
                        })
                    )
                )
            );
        };
    }, 'withColumnWidthsControl');

    addFilter(
        'editor.BlockEdit',
        'orbitools/column-widths-controls',
        withColumnWidthsControl
    );

})();