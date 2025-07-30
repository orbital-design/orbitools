/**
 * Flex Layout Controls - Clean Rewrite
 * 
 * Simple, direct approach to flex layout controls using ToolsPanel
 */

(function() {
    const { addFilter } = wp.hooks;
    const { createHigherOrderComponent } = wp.compose;
    const { Fragment } = wp.element;
    const { InspectorControls, useSetting } = wp.blockEditor;
    const { 
        __experimentalToolsPanel: ToolsPanel,
        __experimentalToolsPanelItem: ToolsPanelItem,
        __experimentalToggleGroupControl: ToggleGroupControl,
        __experimentalToggleGroupControlOption: ToggleGroupControlOption,
        __experimentalToggleGroupControlOptionIcon: ToggleGroupControlOptionIcon,
        __experimentalSpacer: Spacer,
        ToggleControl,
        RangeControl
    } = wp.components;

    // Simple defaults - matches PHP Block_Helper
    const DEFAULTS = {
        columnCount: 2,
        flexDirection: 'row',
        alignItems: 'stretch',
        justifyContent: 'flex-start',
        alignContent: 'stretch',
        gapSize: undefined,
        restrictContentWidth: false,
        stackOnMobile: true,
        columnLayout: 'fit',
        gridSystem: '5'
    };

    // Helper to get default gap value from supports
    function getDefaultGapValue(flexSupports) {
        if (typeof flexSupports === 'object' && flexSupports.defaultGapValue) {
            return flexSupports.defaultGapValue;
        }
        return DEFAULTS.gapSize;
    }

    // Simple option definitions
    const DIRECTION_OPTIONS = [
        { value: 'row', label: 'Horizontal' },
        { value: 'column', label: 'Vertical' }
    ];

    const JUSTIFY_OPTIONS = [
        { value: 'flex-start', label: 'Start' },
        { value: 'center', label: 'Center' },
        { value: 'flex-end', label: 'End' },
        { value: 'space-between', label: 'Space Between' },
        { value: 'space-around', label: 'Space Around' },
        { value: 'space-evenly', label: 'Space Evenly' }
    ];

    const ALIGN_OPTIONS = [
        { value: 'stretch', label: 'Stretch' },
        { value: 'center', label: 'Center' },
        { value: 'flex-start', label: 'Start' },
        { value: 'flex-end', label: 'End' },
        { value: 'baseline', label: 'Baseline' }
    ];

    const COLUMN_LAYOUT_OPTIONS = [
        { value: 'fit', label: 'Fit Content' },
        { value: 'grow', label: 'Equal' },
        { value: 'custom', label: 'Custom (Grid)' }
    ];

    const GRID_SYSTEM_OPTIONS = [
        { value: '5', label: '5 Column Grid' },
        { value: '12', label: '12 Column Grid' }
    ];

    // Helper to get spacing marks from theme.json
    function getSpacingMarks(spacingSizes) {
        const marks = [];
        
        if (spacingSizes && Array.isArray(spacingSizes)) {
            spacingSizes.forEach((size, index) => {
                marks.push({
                    value: index,
                    label: size.name
                });
            });
        }
        
        return marks;
    }

    // Helper to get spacing value by index
    function getSpacingValueByIndex(spacingSizes, index) {
        if (spacingSizes && Array.isArray(spacingSizes) && spacingSizes[index]) {
            return spacingSizes[index].size;
        }
        return '';
    }

    // Helper to get spacing index by value
    function getSpacingIndexByValue(spacingSizes, value) {
        if (!spacingSizes || !Array.isArray(spacingSizes)) return -1;
        
        const index = spacingSizes.findIndex(size => size.size === value);
        return index >= 0 ? index : -1;
    }

    // Helper to get block supports
    function getBlockSupports(blockName) {
        const blockType = wp.blocks.getBlockType(blockName);
        return blockType?.supports?.flexControls;
    }

    // Helper to check if control should be available
    function isControlSupported(supports, controlName) {
        if (supports === true) return true;
        if (typeof supports === 'object' && supports[controlName] !== false) return true;
        return false;
    }

    // Helper to create ToolsPanelItem
    function createToolsPanelItem(controlName, hasValue, _onDeselect, label, children, isShownByDefault = false) {
        return wp.element.createElement(ToolsPanelItem, {
            hasValue,
            onDeselect: () => {}, // No-op to prevent attribute reset when hidden
            label,
            isShownByDefault,
            panelId: 'flex-layout-panel'
        }, children);
    }

    // Helper to create ToggleGroupControl with optional label
    function createToggleGroup(value, onChange, options, label = null) {
        const control = wp.element.createElement(ToggleGroupControl, {
            value,
            onChange,
            isBlock: true,
            __next40pxDefaultSize: true,
            __nextHasNoMarginBottom: true
        }, 
            options.map(option => wp.element.createElement(ToggleGroupControlOption, {
                key: option.value,
                value: option.value,
                label: option.label
            }))
        );

        if (label) {
            return wp.element.createElement('div', {},
                wp.element.createElement('label', {
                    style: { 
                        display: 'block', 
                        marginBottom: '8px',
                        fontSize: '11px',
                        fontWeight: '500',
                        textTransform: 'uppercase',
                        color: '#1e1e1e'
                    }
                }, label),
                control
            );
        }

        return control;
    }

    // Main component
    const withFlexLayoutControl = createHigherOrderComponent(function(BlockEdit) {
        return function(props) {
            // Get module data
            const flexData = window.orbitoolsFlexLayout || {};
            if (!flexData.isEnabled) {
                return wp.element.createElement(BlockEdit, props);
            }
            
            // Check block supports
            const flexSupports = getBlockSupports(props.name);
            if (!flexSupports) {
                return wp.element.createElement(BlockEdit, props);
            }

            const { attributes, setAttributes } = props;
            const flexControls = attributes.orbitoolsFlexControls || {};
            
            // Get spacing sizes from theme.json
            const spacingSizes = useSetting('spacing.spacingSizes');
            
            // Helper to update controls
            const updateControl = (controlName, value) => {
                const newControls = { ...flexControls };
                if (value === undefined || value === DEFAULTS[controlName]) {
                    delete newControls[controlName];
                } else {
                    newControls[controlName] = value;
                }
                setAttributes({ orbitoolsFlexControls: newControls });
            };

            // Helper to get current value with fallback
            const getValue = (controlName) => {
                if (controlName === 'gapSize') {
                    return flexControls[controlName] ?? getDefaultGapValue(flexSupports);
                }
                return flexControls[controlName] ?? DEFAULTS[controlName];
            };
            
            // Helper to check if value is set (not default)
            const hasValue = (controlName) => {
                const stored = flexControls[controlName];
                if (controlName === 'gapSize') {
                    const defaultValue = getDefaultGapValue(flexSupports);
                    return stored !== undefined && stored !== defaultValue;
                }
                return stored !== undefined && stored !== DEFAULTS[controlName];
            };

            const controls = [];

            // Column Count Control
            if (isControlSupported(flexSupports, 'columnCount')) {
                const currentColumnCount = getValue('columnCount');
                
                controls.push(createToolsPanelItem(
                    'columnCount',
                    () => hasValue('columnCount'),
                    () => updateControl('columnCount', undefined),
                    'Columns',
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
                            }, 'Columns'),
                            wp.element.createElement('span', {
                                style: {
                                    fontSize: '13px',
                                    fontWeight: '500',
                                    color: '#757575'
                                }
                            }, `${currentColumnCount} column${currentColumnCount !== 1 ? 's' : ''}`)
                        ),
                        wp.element.createElement(RangeControl, {
                            value: currentColumnCount,
                            onChange: (value) => updateControl('columnCount', value),
                            min: 1,
                            max: 10,
                            step: 1,
                            marks: true,
                            withInputField: false,
                            renderTooltipContent: (value) => `${value} column${value !== 1 ? 's' : ''}`,
                            __next40pxDefaultSize: true,
                            __nextHasNoMarginBottom: true
                        })
                    ),
                    true
                ));
            }

            // Gap Control (next to column count)
            if (isControlSupported(flexSupports, 'gapSize')) {
                const currentGapSize = getValue('gapSize');
                const currentIndex = getSpacingIndexByValue(spacingSizes, currentGapSize);
                const spacingMarks = getSpacingMarks(spacingSizes);
                const maxIndex = spacingSizes ? spacingSizes.length - 1 : 0;
                
                // Get current spacing name for display
                const currentSpacingName = spacingSizes && currentIndex >= 0 
                    ? spacingSizes[currentIndex].name 
                    : (currentGapSize ? currentGapSize : 'None');
                
                controls.push(createToolsPanelItem(
                    'gapSize',
                    () => hasValue('gapSize'),
                    () => updateControl('gapSize', undefined),
                    'Item Spacing',
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
                            }, 'Item Spacing'),
                            wp.element.createElement('span', {
                                style: {
                                    fontSize: '13px',
                                    fontWeight: '500',
                                    color: '#757575'
                                }
                            }, currentSpacingName)
                        ),
                        wp.element.createElement(RangeControl, {
                            value: Math.max(0, currentIndex),
                            onChange: (index) => {
                                const newValue = getSpacingValueByIndex(spacingSizes, index);
                                updateControl('gapSize', newValue || undefined);
                            },
                            min: 0,
                            max: maxIndex,
                            step: 1,
                            marks: true,
                            withInputField: false,
                            renderTooltipContent: (index) => {
                                const spacing = spacingSizes && spacingSizes[index];
                                return spacing ? spacing.name : 'None';
                            },
                            __next40pxDefaultSize: true,
                            __nextHasNoMarginBottom: true
                        })
                    ),
                    true
                ));
            }

            // Flex Direction Control  
            if (isControlSupported(flexSupports, 'flexDirection')) {
                controls.push(createToolsPanelItem(
                    'flexDirection',
                    () => hasValue('flexDirection'),
                    () => updateControl('flexDirection', undefined),
                    'Orientation',
                    createToggleGroup(
                        getValue('flexDirection'),
                        (value) => updateControl('flexDirection', value),
                        DIRECTION_OPTIONS,
                        'Orientation'
                    ),
                    true
                ));
            }

            // Combined Alignment Control
            const currentDirection = getValue('flexDirection');
            const isColumn = currentDirection.startsWith('column');
            
            if (isControlSupported(flexSupports, 'justifyContent') || isControlSupported(flexSupports, 'alignItems')) {
                const hasAlignmentValue = hasValue('justifyContent') || hasValue('alignItems');
                
                // Create alignment controls in the right order (horizontal first, then vertical)
                const horizontalControl = isColumn 
                    ? // When column: align-items controls horizontal
                      isControlSupported(flexSupports, 'alignItems') && wp.element.createElement('div', {},
                          wp.element.createElement('label', {
                              style: { 
                                  display: 'block', 
                                  marginBottom: '8px',
                                  fontSize: '11px',
                                  fontWeight: '500',
                                  textTransform: 'uppercase',
                                  color: '#1e1e1e'
                              }
                          }, 'Horizontal Alignment'),
                          createToggleGroup(
                              getValue('alignItems'),
                              (value) => updateControl('alignItems', value),
                              ALIGN_OPTIONS
                          )
                      )
                    : // When row: justify-content controls horizontal
                      isControlSupported(flexSupports, 'justifyContent') && wp.element.createElement('div', {},
                          wp.element.createElement('label', {
                              style: { 
                                  display: 'block', 
                                  marginBottom: '8px',
                                  fontSize: '11px',
                                  fontWeight: '500',
                                  textTransform: 'uppercase',
                                  color: '#1e1e1e'
                              }
                          }, 'Horizontal Alignment'),
                          createToggleGroup(
                              getValue('justifyContent'),
                              (value) => updateControl('justifyContent', value),
                              JUSTIFY_OPTIONS
                          )
                      );

                const verticalControl = isColumn 
                    ? // When column: justify-content controls vertical
                      isControlSupported(flexSupports, 'justifyContent') && wp.element.createElement('div', {},
                          wp.element.createElement('label', {
                              style: { 
                                  display: 'block', 
                                  marginBottom: '8px',
                                  fontSize: '11px',
                                  fontWeight: '500',
                                  textTransform: 'uppercase',
                                  color: '#1e1e1e'
                              }
                          }, 'Vertical Alignment'),
                          createToggleGroup(
                              getValue('justifyContent'),
                              (value) => updateControl('justifyContent', value),
                              JUSTIFY_OPTIONS
                          )
                      )
                    : // When row: align-items controls vertical
                      isControlSupported(flexSupports, 'alignItems') && wp.element.createElement('div', {},
                          wp.element.createElement('label', {
                              style: { 
                                  display: 'block', 
                                  marginBottom: '8px',
                                  fontSize: '11px',
                                  fontWeight: '500',
                                  textTransform: 'uppercase',
                                  color: '#1e1e1e'
                              }
                          }, 'Vertical Alignment'),
                          createToggleGroup(
                              getValue('alignItems'),
                              (value) => updateControl('alignItems', value),
                              ALIGN_OPTIONS
                          )
                      );

                const alignmentContent = wp.element.createElement('div', { 
                    style: { display: 'flex', flexDirection: 'column', gap: '16px' } 
                },
                    horizontalControl,
                    verticalControl
                );

                controls.push(createToolsPanelItem(
                    'alignment',
                    () => hasAlignmentValue,
                    () => {
                        updateControl('justifyContent', undefined);
                        updateControl('alignItems', undefined);
                    },
                    'Alignment',
                    alignmentContent
                ));
            }

            // Column Layout Control
            if (isControlSupported(flexSupports, 'columnLayout')) {
                controls.push(createToolsPanelItem(
                    'columnLayout',
                    () => hasValue('columnLayout'),
                    () => updateControl('columnLayout', undefined),
                    'Column Layout',
                    createToggleGroup(
                        getValue('columnLayout'),
                        (value) => updateControl('columnLayout', value),
                        COLUMN_LAYOUT_OPTIONS,
                        'Column Layout'
                    )
                ));
            }

            // Grid System Control (only if columnLayout is custom)
            if (isControlSupported(flexSupports, 'gridSystem') && getValue('columnLayout') === 'custom') {
                controls.push(createToolsPanelItem(
                    'gridSystem',
                    () => hasValue('gridSystem'),
                    () => updateControl('gridSystem', undefined),
                    'Grid System',
                    createToggleGroup(
                        getValue('gridSystem'),
                        (value) => updateControl('gridSystem', value),
                        GRID_SYSTEM_OPTIONS,
                        'Grid System'
                    )
                ));
            }


            // Stack on Mobile Control
            if (isControlSupported(flexSupports, 'stackOnMobile')) {
                controls.push(createToolsPanelItem(
                    'stackOnMobile',
                    () => hasValue('stackOnMobile'),
                    () => updateControl('stackOnMobile', undefined),
                    'Stack on Mobile',
                    wp.element.createElement(ToggleControl, {
                        label: 'Stack on Mobile',
                        help: 'Stack columns vertically on mobile devices',
                        checked: getValue('stackOnMobile'),
                        onChange: (value) => updateControl('stackOnMobile', value),
                        __nextHasNoMarginBottom: true
                    })
                ));
            }

            // Restrict Content Width Control (only for full-width blocks)
            if (isControlSupported(flexSupports, 'restrictContentWidth') && attributes.align === 'full') {
                controls.push(createToolsPanelItem(
                    'restrictContentWidth',
                    () => hasValue('restrictContentWidth'),
                    () => updateControl('restrictContentWidth', undefined),
                    'Constrain Content',
                    wp.element.createElement(ToggleControl, {
                        label: 'Constrain Content',
                        help: 'Limit content to the site\'s standard width',
                        checked: getValue('restrictContentWidth'),
                        onChange: (value) => updateControl('restrictContentWidth', value),
                        __nextHasNoMarginBottom: true
                    })
                ));
            }

            // Don't show panel if no controls
            if (controls.length === 0) {
                return wp.element.createElement(BlockEdit, props);
            }

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
                            label: 'Layout',
                            resetAll: () => setAttributes({ orbitoolsFlexControls: {} }),
                            panelId: 'flex-layout-panel'
                        },
                        ...controls
                    )
                )
            );
        };
    }, 'withFlexLayoutControl');

    addFilter(
        'editor.BlockEdit',
        'orbitools/flex-layout-controls',
        withFlexLayoutControl
    );

})();