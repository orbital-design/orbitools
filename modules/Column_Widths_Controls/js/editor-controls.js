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
        RangeControl,
        __experimentalSpacer: Spacer
    } = wp.components;

    // Helper function to get block supports
    function getBlockSupports(blockName) {
        const blockType = wp.blocks.getBlockType(blockName);
        return blockType?.supports?.columnWidthControls;
    }

    // Helper function to get parent block name and data
    function getParentBlock(clientId) {
        const { getBlock, getBlockParents } = wp.data.select('core/block-editor');
        const parents = getBlockParents(clientId);
        if (parents.length > 0) {
            const parentBlock = getBlock(parents[parents.length - 1]);
            return parentBlock;
        }
        return null;
    }


    // Generate column configuration based on grid system
    function getColumnConfig(gridSystem = '12') {
        if (gridSystem === '5') {
            return {
                max: 5,
                marks: [
                    { value: 0 },
                    { value: 1 },
                    { value: 2 },
                    { value: 3 },
                    { value: 4 },
                    { value: 5 }
                ],
                getValueLabel: (value) => {
                    if (value === 0) return 'Auto';
                    const percentage = (value / 5 * 100).toFixed(0);
                    return `${value} of 5 (${percentage}%)`;
                },
                getTooltipLabel: (value) => {
                    if (value === 0) return 'Auto';
                    return `${value}/5`;
                },
                getValueKey: (value) => {
                    if (value === 0) return 'auto';
                    return value.toString();
                },
                getKeyValue: (key) => {
                    if (key === 'auto' || !key) return 0;
                    return parseInt(key) || 0;
                }
            };
        } else {
            return {
                max: 12,
                marks: [
                    { value: 0 },
                    { value: 1 },
                    { value: 2 },
                    { value: 3 },
                    { value: 4 },
                    { value: 5 },
                    { value: 6 },
                    { value: 7 },
                    { value: 8 },
                    { value: 9 },
                    { value: 10 },
                    { value: 11 },
                    { value: 12 }
                ],
                getValueLabel: (value) => {
                    if (value === 0) return 'Auto';
                    const percentage = (value / 12 * 100).toFixed(2);
                    return `${value} of 12 (${percentage}%)`;
                },
                getTooltipLabel: (value) => {
                    if (value === 0) return 'Auto';
                    return `${value}/12`;
                },
                getValueKey: (value) => {
                    if (value === 0) return 'auto';
                    return value.toString();
                },
                getKeyValue: (key) => {
                    if (key === 'auto' || !key) return 0;
                    return parseInt(key) || 0;
                }
            };
        }
    }

    // Breakpoint configurations
    const breakpoints = {
        base: { label: 'All Screens', description: 'Default width for all screen sizes' },
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
            
            // Get parent block to determine control behavior and grid system
            const parentBlock = getParentBlock(clientId);
            const parentBlockName = parentBlock ? parentBlock.name : null;
            const isInRow = parentBlockName === 'orbital/row';
            const isInGrid = parentBlockName === 'orbital/grid';
            
            // Check if parent row has column layout set to 'custom' - only show controls if it does
            if (isInRow && parentBlock?.attributes?.orbitoolsFlexControls) {
                const parentFlexControls = parentBlock.attributes.orbitoolsFlexControls;
                const parentColumnLayout = parentFlexControls.columnLayout || 'fit'; // Default from Flex Layout Controls
                
                console.log('Parent column layout:', parentColumnLayout);
                
                // Only show column width controls if parent is using custom (grid) layout
                if (parentColumnLayout !== 'custom') {
                    console.log('Column widths controls hidden - parent not using custom layout');
                    return wp.element.createElement(BlockEdit, props);
                }
            } else if (isInRow && !parentBlock?.attributes?.orbitoolsFlexControls) {
                console.log('Column widths controls hidden - parent row has no flex controls', parentBlock?.attributes);
                return wp.element.createElement(BlockEdit, props);
            }
            
            // Get parent row's grid system setting (default to '12' if not found)
            let gridSystem = '12'; // Default to 12-column grid
            
            // Try to get grid system from parent row
            if (isInRow && parentBlock?.attributes?.orbitoolsFlexControls) {
                const flexControls = parentBlock.attributes.orbitoolsFlexControls;
                if (flexControls.gridSystem) {
                    gridSystem = flexControls.gridSystem;
                } else {
                    // Fallback: if no gridSystem is set, use the default from Flex Layout Controls (5-column)
                    gridSystem = '5'; // Match the default from Flex Layout Controls
                }
            }
            
            // Enhanced debugging
            console.log('Column Widths Debug - Parent Block Info:', {
                parentBlockName: parentBlockName,
                isInRow: isInRow,
                isInGrid: isInGrid,
                parentBlock: parentBlock,
                flexControlsAttribute: parentBlock?.attributes?.orbitoolsFlexControls,
                detectedGridSystem: gridSystem,
                blockClientId: clientId,
                parentColumnLayout: parentBlock?.attributes?.orbitoolsFlexControls?.columnLayout,
                shouldShow: isInRow && parentBlock?.attributes?.orbitoolsFlexControls?.columnLayout === 'custom'
            });
            
            // Generate column configuration based on the grid system
            const columnConfig = getColumnConfig(gridSystem);
            
            // Get column widths from the object attribute, with fallbacks
            const columnWidths = attributes.orbitoolsColumnWidths || {};
            
            // Helper function to update column widths
            const updateColumnWidth = (breakpoint, sliderValue) => {
                // Convert slider value to column key
                const columnKey = columnConfig.getValueKey(sliderValue);
                const value = columnKey === 'auto' ? undefined : columnKey;
                
                const newColumnWidths = {
                    ...columnWidths,
                    [breakpoint]: value
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
                const storedValue = columnWidths[breakpoint];
                const sliderValue = columnConfig.getKeyValue(storedValue);
                const hasValue = storedValue !== undefined && storedValue !== '';
                
                // Get current label for display
                const currentLabel = columnConfig.getValueLabel(sliderValue);
                
                return wp.element.createElement(ToolsPanelItem, {
                    key: breakpoint,
                    hasValue: () => hasValue,
                    label: config.label,
                    onDeselect: () => updateColumnWidth(breakpoint, 0), // Reset to auto (0)
                    isShownByDefault: breakpoint === 'base', // Only show base by default
                    panelId: 'column-widths-panel'
                },
                    wp.element.createElement('div', {},
                        wp.element.createElement('div', {
                            style: {
                                display: 'flex',
                                justifyContent: 'space-between',
                                alignItems: 'center',
                                marginBottom: '8px'
                            }
                        },
                            wp.element.createElement('label', {
                                style: {
                                    fontSize: '11px',
                                    fontWeight: '500',
                                    textTransform: 'uppercase',
                                    color: '#1e1e1e',
                                    margin: 0
                                }
                            }, config.label),
                            wp.element.createElement('span', {
                                style: {
                                    fontSize: '13px',
                                    fontWeight: '500',
                                    color: '#757575'
                                }
                            }, currentLabel)
                        ),
                        wp.element.createElement(RangeControl, {
                            value: sliderValue,
                            onChange: (newValue) => updateColumnWidth(breakpoint, newValue),
                            min: 0,
                            max: columnConfig.max,
                            step: 1,
                            marks: columnConfig.marks,
                            withInputField: false,
                            renderTooltipContent: (value) => columnConfig.getTooltipLabel(value),
                            __next40pxDefaultSize: true,
                            __nextHasNoMarginBottom: true
                        })
                    )
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