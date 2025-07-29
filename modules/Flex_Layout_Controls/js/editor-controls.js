/**
 * Flex Layout Controls - Clean Rewrite
 * 
 * Simple, direct approach to flex layout controls using ToolsPanel
 */

(function() {
    const { addFilter } = wp.hooks;
    const { createHigherOrderComponent } = wp.compose;
    const { Fragment } = wp.element;
    const { InspectorControls } = wp.blockEditor;
    const { 
        __experimentalToolsPanel: ToolsPanel,
        __experimentalToolsPanelItem: ToolsPanelItem,
        __experimentalToggleGroupControl: ToggleGroupControl,
        __experimentalToggleGroupControlOption: ToggleGroupControlOption,
        __experimentalToggleGroupControlOptionIcon: ToggleGroupControlOptionIcon,
        ToggleControl,
        RangeControl
    } = wp.components;

    // Simple defaults - matches PHP Block_Helper
    const DEFAULTS = {
        columnCount: 3,
        flexDirection: 'row',
        alignItems: 'stretch',
        justifyContent: 'flex-start',
        alignContent: 'stretch',
        enableGap: true,
        restrictContentWidth: false,
        stackOnMobile: true,
        columnLayout: 'fit',
        gridSystem: '5'
    };

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
    function createToolsPanelItem(controlName, hasValue, onDeselect, label, children, isShownByDefault = false) {
        return wp.element.createElement(ToolsPanelItem, {
            hasValue,
            onDeselect,
            label,
            isShownByDefault,
            panelId: 'flex-layout-panel'
        }, children);
    }

    // Helper to create ToggleGroupControl
    function createToggleGroup(value, onChange, options) {
        return wp.element.createElement(ToggleGroupControl, {
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
            const getValue = (controlName) => flexControls[controlName] ?? DEFAULTS[controlName];
            
            // Helper to check if value is set (not default)
            const hasValue = (controlName) => {
                const stored = flexControls[controlName];
                return stored !== undefined && stored !== DEFAULTS[controlName];
            };

            const controls = [];

            // Column Count Control
            if (isControlSupported(flexSupports, 'columnCount')) {
                controls.push(createToolsPanelItem(
                    'columnCount',
                    () => hasValue('columnCount'),
                    () => updateControl('columnCount', undefined),
                    'Columns',
                    wp.element.createElement(RangeControl, {
                        label: 'Columns',
                        value: getValue('columnCount'),
                        onChange: (value) => updateControl('columnCount', value),
                        min: 1,
                        max: 10,
                        step: 1,
                        __next40pxDefaultSize: true,
                        __nextHasNoMarginBottom: true
                    }),
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
                        DIRECTION_OPTIONS
                    ),
                    true
                ));
            }

            // Combined Alignment Control
            const currentDirection = getValue('flexDirection');
            const isColumn = currentDirection.startsWith('column');
            
            if (isControlSupported(flexSupports, 'justifyContent') || isControlSupported(flexSupports, 'alignItems')) {
                const hasAlignmentValue = hasValue('justifyContent') || hasValue('alignItems');
                
                const alignmentContent = wp.element.createElement('div', { 
                    style: { display: 'flex', flexDirection: 'column', gap: '16px' } 
                },
                    // Main axis (justify-content)
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
                        }, isColumn ? 'Vertical Alignment' : 'Horizontal Alignment'),
                        createToggleGroup(
                            getValue('justifyContent'),
                            (value) => updateControl('justifyContent', value),
                            JUSTIFY_OPTIONS
                        )
                    ),
                    
                    // Cross axis (align-items)
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
                        }, isColumn ? 'Horizontal Alignment' : 'Vertical Alignment'),
                        createToggleGroup(
                            getValue('alignItems'),
                            (value) => updateControl('alignItems', value),
                            ALIGN_OPTIONS
                        )
                    )
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
                        COLUMN_LAYOUT_OPTIONS
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
                        GRID_SYSTEM_OPTIONS
                    )
                ));
            }

            // Gap Control
            if (isControlSupported(flexSupports, 'enableGap')) {
                controls.push(createToolsPanelItem(
                    'enableGap',
                    () => hasValue('enableGap'),
                    () => updateControl('enableGap', undefined),
                    'Item Spacing',
                    wp.element.createElement(ToggleControl, {
                        label: 'Item Spacing',
                        help: 'Add space between items in the layout',
                        checked: getValue('enableGap'),
                        onChange: (value) => updateControl('enableGap', value),
                        __nextHasNoMarginBottom: true
                    })
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