/**
 * Flex Layout Controls - Clean Rewrite
 * 
 * Simple, direct approach to flex layout controls using ToolsPanel
 */

(function() {
    const { addFilter } = wp.hooks;
    const { createHigherOrderComponent } = wp.compose;
    const { Fragment } = wp.element;
    const { InspectorControls, BlockControls, useSetting } = wp.blockEditor;
    const { 
        __experimentalToolsPanel: ToolsPanel,
        __experimentalToolsPanelItem: ToolsPanelItem,
        __experimentalToggleGroupControl: ToggleGroupControl,
        __experimentalToggleGroupControlOption: ToggleGroupControlOption,
        __experimentalToggleGroupControlOptionIcon: ToggleGroupControlOptionIcon,
        __experimentalSpacer: Spacer,
        ToolbarGroup,
        ToolbarDropdownMenu,
        ToggleControl,
        RangeControl,
        SVG,
        Path
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

    // Helper function to get alignment icon SVG
    function getAlignmentIconSVG(alignmentValue, isColumn, property) {
        if (typeof window.ALIGNMENT_ICONS === 'undefined') {
            return '<svg width="24" height="24" viewBox="0 0 24 24"><path d="M12 2L12 22" stroke="currentColor" stroke-width="1.5"/></svg>';
        }
        
        const orientation = isColumn ? 'column' : 'row';
        const icons = window.ALIGNMENT_ICONS[orientation]?.[property];
        return icons?.[alignmentValue] || icons?.['stretch'] || '';
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

            // Create alignment toolbar controls
            const currentDirection = getValue('flexDirection');
            const isColumn = currentDirection.startsWith('column');
            
            const alignmentControls = [];
            
            // Orientation Control (first in toolbar)
            if (isControlSupported(flexSupports, 'flexDirection')) {
                const controls = [
                    {
                        icon: wp.element.createElement('div', {
                            className: 'orbitools-alignment-icon',
                            dangerouslySetInnerHTML: { __html: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M5 12L19 12M12 5L19 12L12 19" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>' }
                        }),
                        title: 'Horizontal',
                        onClick: () => updateControl('flexDirection', 'row'),
                        isActive: currentDirection === 'row'
                    },
                    {
                        icon: wp.element.createElement('div', {
                            className: 'orbitools-alignment-icon',
                            dangerouslySetInnerHTML: { __html: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 5L12 19M5 12L12 19L19 12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>' }
                        }),
                        title: 'Vertical',
                        onClick: () => updateControl('flexDirection', 'column'),
                        isActive: currentDirection === 'column'
                    }
                ];
                
                const currentIcon = controls.find(c => c.title.toLowerCase() === (currentDirection === 'row' ? 'horizontal' : 'vertical'))?.icon || controls[0].icon;
                
                alignmentControls.push(
                    wp.element.createElement(ToolbarDropdownMenu, {
                        controls: controls,
                        icon: currentIcon,
                        label: 'Direction',
                        className: 'orbitools-alignment-dropdown'
                    })
                );
            }
            
            // Row orientation controls
            if (!isColumn) {
                // Justify Content (horizontal alignment for row)
                if (isControlSupported(flexSupports, 'justifyContent')) {
                    const currentValue = getValue('justifyContent');
                    const controls = [
                        {
                            icon: wp.element.createElement('div', {
                                className: 'orbitools-alignment-icon',
                                dangerouslySetInnerHTML: { __html: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"> <path d="M4 2L4 22M11 16L11 8C11 7.44772 10.5523 7 10 7H8C7.44772 7 7 7.44772 7 8L7 16C7 16.5523 7.44772 17 8 17H10C10.5523 17 11 16.5523 11 16ZM18 16V8C18 7.44771 17.5523 7 17 7H15C14.4477 7 14 7.44772 14 8V16C14 16.5523 14.4477 17 15 17H17C17.5523 17 18 16.5523 18 16Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/> </svg>' }
                            }),
                            title: 'Start',
                            onClick: () => updateControl('justifyContent', 'flex-start'),
                            isActive: currentValue === 'flex-start'
                        },
                        {
                            icon: wp.element.createElement('div', {
                                className: 'orbitools-alignment-icon',
                                dangerouslySetInnerHTML: { __html: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 2L12 22M9 16L9 8C9 7.44772 8.55228 7 8 7H6C5.44772 7 5 7.44772 5 8L5 16C5 16.5523 5.44772 17 6 17H8C8.55229 17 9 16.5523 9 16ZM19 16V8C19 7.44772 18.5523 7 18 7H16C15.4477 7 15 7.44772 15 8V16C15 16.5523 15.4477 17 16 17H18C18.5523 17 19 16.5523 19 16Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>' }
                            }),
                            title: 'Center',
                            onClick: () => updateControl('justifyContent', 'center'),
                            isActive: currentValue === 'center'
                        },
                        {
                            icon: wp.element.createElement('div', {
                                className: 'orbitools-alignment-icon',
                                dangerouslySetInnerHTML: { __html: getAlignmentIconSVG('flex-end', false, 'justifyContent') }
                            }),
                            title: 'End',
                            onClick: () => updateControl('justifyContent', 'flex-end'),
                            isActive: currentValue === 'flex-end'
                        },
                        {
                            icon: wp.element.createElement('div', {
                                className: 'orbitools-alignment-icon',
                                dangerouslySetInnerHTML: { __html: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 2L4 22M20 2L20 22M11 16L11 8C11 7.44772 10.5523 7 10 7H8C7.44772 7 7 7.44772 7 8L7 16C7 16.5523 7.44772 17 8 17H10C10.5523 17 11 16.5523 11 16ZM17 16V8C17 7.44772 16.5523 7 16 7H14C13.4477 7 13 7.44772 13 8V16C13 16.5523 13.4477 17 14 17H16C16.5523 17 17 16.5523 17 16Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>' }
                            }),
                            title: 'Space Between',
                            onClick: () => updateControl('justifyContent', 'space-between'),
                            isActive: currentValue === 'space-between'
                        },
                        {
                            icon: wp.element.createElement('div', {
                                className: 'orbitools-alignment-icon',
                                dangerouslySetInnerHTML: { __html: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M21 2V22M4 2L4 22M10 16L10 8C10 7.44772 9.55229 7 9 7H7C6.44772 7 6 7.44772 6 8L6 16C6 16.5523 6.44772 17 7 17H9C9.55229 17 10 16.5523 10 16ZM19 16V8C19 7.44772 18.5523 7 18 7H16C15.4477 7 15 7.44772 15 8V16C15 16.5523 15.4477 17 16 17H18C18.5523 17 19 16.5523 19 16Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>' }
                            }),
                            title: 'Space Around',
                            onClick: () => updateControl('justifyContent', 'space-around'),
                            isActive: currentValue === 'space-around'
                        },
                        {
                            icon: wp.element.createElement('div', {
                                className: 'orbitools-alignment-icon',
                                dangerouslySetInnerHTML: { __html: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M2 2L2 22M22 2L22 22M11 16L11 8C11 7.44772 10.5523 7 10 7H8C7.44772 7 7 7.44772 7 8L7 16C7 16.5523 7.44772 17 8 17H10C10.5523 17 11 16.5523 11 16ZM17 16V8C17 7.44772 16.5523 7 16 7H14C13.4477 7 13 7.44772 13 8V16C13 16.5523 13.4477 17 14 17H16C16.5523 17 17 16.5523 17 16Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>' }
                            }),
                            title: 'Space Evenly',
                            onClick: () => updateControl('justifyContent', 'space-evenly'),
                            isActive: currentValue === 'space-evenly'
                        }
                    ];
                    
                    const currentIcon = controls.find(c => c.title.toLowerCase().replace(' ', '-') === currentValue?.replace('flex-', ''))?.icon || controls[0].icon;
                    
                    alignmentControls.push(
                        wp.element.createElement(ToolbarDropdownMenu, {
                            controls: controls,
                            icon: currentIcon,
                            label: 'Horizontal Alignment',
                            className: 'orbitools-alignment-dropdown'
                        })
                    );
                }
                
                // Align Items (vertical alignment for row)
                if (isControlSupported(flexSupports, 'alignItems')) {
                    const currentValue = getValue('alignItems');
                    const controls = [
                        {
                            icon: wp.element.createElement('div', {
                                className: 'orbitools-alignment-icon',
                                dangerouslySetInnerHTML: { __html: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M2 4L22 4M6 11H18C18.5523 11 19 10.5523 19 10V8C19 7.44772 18.5523 7 18 7H6C5.44772 7 5 7.44772 5 8V10C5 10.5523 5.44772 11 6 11ZM6 18H18C18.5523 18 19 17.5523 19 17V15C19 14.4477 18.5523 14 18 14H6C5.44772 14 5 14.4477 5 15V17C5 17.5523 5.44772 18 6 18Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>' }
                            }),
                            title: 'Start',
                            onClick: () => updateControl('alignItems', 'flex-start'),
                            isActive: currentValue === 'flex-start'
                        },
                        {
                            icon: wp.element.createElement('div', {
                                className: 'orbitools-alignment-icon',
                                dangerouslySetInnerHTML: { __html: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M2 12L22 12M6 16H18C18.5523 16 19 15.5523 19 15V13C19 12.4477 18.5523 12 18 12H6C5.44772 12 5 12.4477 5 13V15C5 15.5523 5.44772 16 6 16ZM6 9H18C18.5523 9 19 8.55228 19 8V6C19 5.44772 18.5523 5 18 5H6C5.44772 5 5 5.44772 5 6V8C5 8.55228 5.44772 9 6 9Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>' }
                            }),
                            title: 'Center',
                            onClick: () => updateControl('alignItems', 'center'),
                            isActive: currentValue === 'center'
                        },
                        {
                            icon: wp.element.createElement('div', {
                                className: 'orbitools-alignment-icon',
                                dangerouslySetInnerHTML: { __html: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M2 20L22 20M6 14H18C18.5523 14 19 13.5523 19 13V11C19 10.4477 18.5523 10 18 10H6C5.44772 10 5 10.4477 5 11V13C5 13.5523 5.44772 14 6 14ZM6 7H18C18.5523 7 19 6.55228 19 6V4C19 3.44772 18.5523 3 18 3H6C5.44772 3 5 3.44772 5 4V6C5 6.55228 5.44772 7 6 7Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>' }
                            }),
                            title: 'End',
                            onClick: () => updateControl('alignItems', 'flex-end'),
                            isActive: currentValue === 'flex-end'
                        },
                        {
                            icon: wp.element.createElement('div', {
                                className: 'orbitools-alignment-icon',
                                dangerouslySetInnerHTML: { __html: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M2 2L22 2M2 22L22 22M6 16H18C18.5523 16 19 15.5523 19 15V9C19 8.44772 18.5523 8 18 8H6C5.44772 8 5 8.44772 5 9V15C5 15.5523 5.44772 16 6 16Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>' }
                            }),
                            title: 'Stretch',
                            onClick: () => updateControl('alignItems', 'stretch'),
                            isActive: currentValue === 'stretch'
                        }
                    ];
                    
                    const currentIcon = controls.find(c => c.title.toLowerCase().replace(' ', '-') === currentValue?.replace('flex-', ''))?.icon || controls[3].icon; // default to stretch
                    
                    alignmentControls.push(
                        wp.element.createElement(ToolbarDropdownMenu, {
                            controls: controls,
                            icon: currentIcon,
                            label: 'Vertical Alignment',
                            className: 'orbitools-alignment-dropdown'
                        })
                    );
                }
            }
            
            // Column orientation controls  
            if (isColumn) {
                // Justify Content (vertical alignment for column)
                if (isControlSupported(flexSupports, 'justifyContent')) {
                    const currentValue = getValue('justifyContent');
                    const controls = [
                        {
                            icon: wp.element.createElement(SVG, { viewBox: "0 0 24 24", xmlns: "http://www.w3.org/2000/svg" }, 
                                wp.element.createElement(Path, { d: "M22 4H2M8 11H16C16.5523 11 17 10.5523 17 10V8C17 7.44772 16.5523 7 16 7H8C7.44772 7 7 7.44772 7 8V10C7 10.5523 7.44772 11 8 11ZM8 18H16C16.5523 18 17 17.5523 17 17V15C17 14.4477 16.5523 14 16 14H8C7.44772 14 7 14.4477 7 15V17C7 17.5523 7.44772 18 8 18Z", stroke: "currentColor", strokeWidth: "1.5", strokeLinecap: "round", strokeLinejoin: "round" })),
                            title: 'Start',
                            onClick: () => updateControl('justifyContent', 'flex-start'),
                            isActive: currentValue === 'flex-start'
                        },
                        {
                            icon: wp.element.createElement(SVG, { viewBox: "0 0 24 24", xmlns: "http://www.w3.org/2000/svg" }, 
                                wp.element.createElement(Path, { d: "M12 2H12V22M8 14H16C16.5523 14 17 13.5523 17 13V11C17 10.4477 16.5523 10 16 10H8C7.44772 10 7 10.4477 7 11V13C7 13.5523 7.44772 14 8 14ZM8 21H16C16.5523 21 17 20.5523 17 20V18C17 17.4477 16.5523 17 16 17H8C7.44772 17 7 17.4477 7 18V20C7 20.5523 7.44772 21 8 21Z", stroke: "currentColor", strokeWidth: "1.5", strokeLinecap: "round", strokeLinejoin: "round" })),
                            title: 'Center',
                            onClick: () => updateControl('justifyContent', 'center'),
                            isActive: currentValue === 'center'
                        },
                        {
                            icon: wp.element.createElement(SVG, { viewBox: "0 0 24 24", xmlns: "http://www.w3.org/2000/svg" }, 
                                wp.element.createElement(Path, { d: "M22 20H2M8 14H16C16.5523 14 17 13.5523 17 13V11C17 10.4477 16.5523 10 16 10H8C7.44772 10 7 10.4477 7 11V13C7 13.5523 7.44772 14 8 14ZM8 7H16C16.5523 7 17 6.55228 17 6V4C17 3.44772 16.5523 3 16 3H8C7.44772 3 7 3.44772 7 4V6C7 6.55228 7.44772 7 8 7Z", stroke: "currentColor", strokeWidth: "1.5", strokeLinecap: "round", strokeLinejoin: "round" })),
                            title: 'End',
                            onClick: () => updateControl('justifyContent', 'flex-end'),
                            isActive: currentValue === 'flex-end'
                        },
                        {
                            icon: wp.element.createElement(SVG, { viewBox: "0 0 24 24", xmlns: "http://www.w3.org/2000/svg" }, 
                                wp.element.createElement(Path, { d: "M22 20H2M22 4H2M8 14H16C16.5523 14 17 13.5523 17 13V11C17 10.4477 16.5523 10 16 10H8C7.44772 10 7 10.4477 7 11V13C7 13.5523 7.44772 14 8 14ZM8 7H16C16.5523 7 17 6.55228 17 6V4C17 3.44772 16.5523 3 16 3H8C7.44772 3 7 3.44772 7 4V6C7 6.55228 7.44772 7 8 7Z", stroke: "currentColor", strokeWidth: "1.5", strokeLinecap: "round", strokeLinejoin: "round" })),
                            title: 'Space Between',
                            onClick: () => updateControl('justifyContent', 'space-between'),
                            isActive: currentValue === 'space-between'
                        },
                        {
                            icon: wp.element.createElement(SVG, { viewBox: "0 0 24 24", xmlns: "http://www.w3.org/2000/svg" }, 
                                wp.element.createElement(Path, { d: "M2 2H22M2 22H22M8 16H16C16.5523 16 17 15.5523 17 15V13C17 12.4477 16.5523 12 16 12H8C7.44772 12 7 12.4477 7 13V15C7 15.5523 7.44772 16 8 16ZM8 9H16C16.5523 9 17 8.55228 17 8V6C17 5.44772 16.5523 5 16 5H8C7.44772 5 7 5.44772 7 6V8C7 8.55228 7.44772 9 8 9Z", stroke: "currentColor", strokeWidth: "1.5", strokeLinecap: "round", strokeLinejoin: "round" })),
                            title: 'Space Around',
                            onClick: () => updateControl('justifyContent', 'space-around'),
                            isActive: currentValue === 'space-around'
                        },
                        {
                            icon: wp.element.createElement(SVG, { viewBox: "0 0 24 24", xmlns: "http://www.w3.org/2000/svg" }, 
                                wp.element.createElement(Path, { d: "M2 2H22M2 22H22M8 15H16C16.5523 15 17 14.5523 17 14V12C17 11.4477 16.5523 11 16 11H8C7.44772 11 7 11.4477 7 12V14C7 14.5523 7.44772 15 8 15ZM8 10H16C16.5523 10 17 9.55228 17 9V7C17 6.44772 16.5523 6 16 6H8C7.44772 6 7 6.44772 7 7V9C7 9.55228 7.44772 10 8 10Z", stroke: "currentColor", strokeWidth: "1.5", strokeLinecap: "round", strokeLinejoin: "round" })),
                            title: 'Space Evenly',
                            onClick: () => updateControl('justifyContent', 'space-evenly'),
                            isActive: currentValue === 'space-evenly'
                        }
                    ];
                    
                    const currentIcon = controls.find(c => c.title.toLowerCase().replace(' ', '-') === currentValue?.replace('flex-', ''))?.icon || controls[0].icon;
                    
                    alignmentControls.push(
                        wp.element.createElement(ToolbarDropdownMenu, {
                            controls: controls,
                            icon: currentIcon,
                            label: 'Vertical Alignment',
                            className: 'orbitools-alignment-dropdown'
                        })
                    );
                }
                
                // Align Items (horizontal alignment for column)
                if (isControlSupported(flexSupports, 'alignItems')) {
                    const currentValue = getValue('alignItems');
                    const controls = [
                        {
                            icon: wp.element.createElement('div', {
                                className: 'orbitools-alignment-icon',
                                dangerouslySetInnerHTML: { __html: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 2L4 22M11 8H13C13.5523 8 14 7.55228 14 7V5C14 4.44772 13.5523 4 13 4H11C10.4477 4 10 4.44772 10 5V7C10 7.55228 10.4477 8 11 8ZM11 15H13C13.5523 15 14 14.5523 14 14V12C14 11.4477 13.5523 11 13 11H11C10.4477 11 10 11.4477 10 12V14C10 14.5523 10.4477 15 11 15Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>' }
                            }),
                            title: 'Start',
                            onClick: () => updateControl('alignItems', 'flex-start'),
                            isActive: currentValue === 'flex-start'
                        },
                        {
                            icon: wp.element.createElement('div', {
                                className: 'orbitools-alignment-icon',
                                dangerouslySetInnerHTML: { __html: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 2L12 22M10 8H14C14.5523 8 15 7.55228 15 7V5C15 4.44772 14.5523 4 14 4H10C9.44772 4 9 4.44772 9 5V7C9 7.55228 9.44772 8 10 8ZM10 15H14C14.5523 15 15 14.5523 15 14V12C15 11.4477 14.5523 11 14 11H10C9.44772 11 9 11.4477 9 12V14C10 14.5523 10.5523 15 10 15Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>' }
                            }),
                            title: 'Center',
                            onClick: () => updateControl('alignItems', 'center'),
                            isActive: currentValue === 'center'
                        },
                        {
                            icon: wp.element.createElement('div', {
                                className: 'orbitools-alignment-icon',
                                dangerouslySetInnerHTML: { __html: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M20 2L20 22M13 8H17C17.5523 8 18 7.55228 18 7V5C18 4.44772 17.5523 4 17 4H13C12.4477 4 12 4.44772 12 5V7C12 7.55228 12.4477 8 13 8ZM13 15H17C17.5523 15 18 14.5523 18 14V12C18 11.4477 17.5523 11 17 11H13C12.4477 11 12 11.4477 12 12V14C12 14.5523 12.4477 15 13 15Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>' }
                            }),
                            title: 'End',
                            onClick: () => updateControl('alignItems', 'flex-end'),
                            isActive: currentValue === 'flex-end'
                        },
                        {
                            icon: wp.element.createElement('div', {
                                className: 'orbitools-alignment-icon',
                                dangerouslySetInnerHTML: { __html: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M2 2L2 22M22 2L22 22M8 8H16C16.5523 8 17 7.55228 17 7V5C17 4.44772 16.5523 4 16 4H8C7.44772 4 7 4.44772 7 5V7C7 7.55228 7.44772 8 8 8ZM8 15H16C16.5523 15 17 14.5523 17 14V12C17 11.4477 16.5523 11 16 11H8C7.44772 11 7 11.4477 7 12V14C7 14.5523 7.44772 15 8 15Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>' }
                            }),
                            title: 'Stretch',
                            onClick: () => updateControl('alignItems', 'stretch'),
                            isActive: currentValue === 'stretch'
                        }
                    ];
                    
                    const currentIcon = controls.find(c => c.title.toLowerCase().replace(' ', '-') === currentValue?.replace('flex-', ''))?.icon || controls[3].icon; // default to stretch
                    
                    alignmentControls.push(
                        wp.element.createElement(ToolbarDropdownMenu, {
                            controls: controls,
                            icon: currentIcon,
                            label: 'Horizontal Alignment',
                            className: 'orbitools-alignment-dropdown'
                        })
                    );
                }
            }

            // Don't show panel if no controls
            if (controls.length === 0 && alignmentControls.length === 0) {
                return wp.element.createElement(BlockEdit, props);
            }

            return wp.element.createElement(
                Fragment,
                {},
                wp.element.createElement(BlockEdit, props),
                // Block toolbar controls
                alignmentControls.length > 0 && wp.element.createElement(
                    BlockControls,
                    { group: 'block' },
                    wp.element.createElement(
                        ToolbarGroup,
                        {},
                        ...alignmentControls
                    )
                ),
                // Inspector controls
                controls.length > 0 && wp.element.createElement(
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