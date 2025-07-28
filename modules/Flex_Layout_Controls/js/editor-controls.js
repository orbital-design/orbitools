/**
 * Flex Layout Controls - Editor Controls
 * 
 * Adds flex layout controls to the block editor inspector using ToggleGroupControl
 */

(function() {
    const { addFilter } = wp.hooks;
    const { createHigherOrderComponent } = wp.compose;
    const { Fragment } = wp.element;
    const { InspectorControls } = wp.blockEditor;
    const { 
        __experimentalToggleGroupControl: ToggleGroupControl,
        __experimentalToggleGroupControlOption: ToggleGroupControlOption,
        __experimentalToggleGroupControlOptionIcon: ToggleGroupControlOptionIcon,
        PanelBody,
        ToggleControl
    } = wp.components;

    // Helper function to get block supports
    function getBlockSupports(blockName) {
        const blockType = wp.blocks.getBlockType(blockName);
        return blockType?.supports?.flexControls;
    }

    // Use external configuration and icons
    const flexControlsConfig = window.FlexControlsConfig || {
        flexDirection: {
            name: "Flex Direction",
            niceName: "Orientation",
            prop: "flex-direction",
            control: "ToggleGroupControl",
            desc: "Direction of flow for content.",
            default: "row",
            options: [
                {
                    slug: "row",
                    name: "Row", 
                    niceName: "Horizontal",
                    icon: flexDirectionIcons.row
                },
                {
                    slug: "column",
                    name: "Column",
                    niceName: "Vertical", 
                    icon: flexDirectionIcons.column
                },
                {
                    slug: "row-reverse",
                    name: "Row Reverse",
                    niceName: "Horizontal Reverse",
                    icon: null
                },
                {
                    slug: "column-reverse", 
                    name: "Column Reverse",
                    niceName: "Vertical Reverse",
                    icon: null
                }
            ]
        },
        flexWrap: {
            name: "Flex Wrap",
            niceName: "Wrapping",
            prop: "flex-wrap",
            control: "ToggleGroupControl",
            desc: "Controls whether items wrap to new lines.",
            default: "nowrap",
            options: [
                {
                    slug: "nowrap",
                    name: "No Wrap",
                    niceName: "No Wrap",
                    icon: null
                },
                {
                    slug: "wrap",
                    name: "Wrap", 
                    niceName: "Wrap",
                    icon: null
                },
                {
                    slug: "wrap-reverse",
                    name: "Wrap Reverse",
                    niceName: "Wrap Reverse",
                    icon: null
                }
            ]
        },
        alignItems: {
            name: "Align Items",
            niceName: "Cross-Axis Alignment", 
            prop: "align-items",
            control: "ToggleGroupControl",
            desc: "How items align on the cross axis (perpendicular to flex direction).",
            default: "stretch",
            options: [
                {
                    slug: "stretch",
                    name: "Stretch",
                    niceName: "Stretch",
                    icon: null
                },
                {
                    slug: "center",
                    name: "Center",
                    niceName: "Center", 
                    icon: null
                },
                {
                    slug: "flex-start",
                    name: "Flex Start",
                    niceName: "Start",
                    icon: null
                },
                {
                    slug: "flex-end",
                    name: "Flex End", 
                    niceName: "End",
                    icon: null
                },
                {
                    slug: "baseline",
                    name: "Baseline",
                    niceName: "Baseline",
                    icon: null
                }
            ]
        },
        justifyContent: {
            name: "Justify Content",
            niceName: "Main-Axis Alignment",
            prop: "justify-content", 
            control: "ToggleGroupControl",
            desc: "How items align on the main axis (along flex direction).",
            default: "flex-start",
            options: [
                {
                    slug: "flex-start",
                    name: "Flex Start",
                    niceName: "Start",
                    icon: null
                },
                {
                    slug: "center", 
                    name: "Center",
                    niceName: "Center",
                    icon: null
                },
                {
                    slug: "flex-end",
                    name: "Flex End",
                    niceName: "End",
                    icon: null
                },
                {
                    slug: "space-between",
                    name: "Space Between",
                    niceName: "Space Between", 
                    icon: null
                },
                {
                    slug: "space-around",
                    name: "Space Around",
                    niceName: "Space Around",
                    icon: null
                },
                {
                    slug: "space-evenly",
                    name: "Space Evenly",
                    niceName: "Space Evenly",
                    icon: null
                }
            ]
        },
        alignContent: {
            name: "Align Content",
            niceName: "Multi-line Alignment",
            prop: "align-content",
            control: "ToggleGroupControl", 
            desc: "Controls spacing between wrapped flex lines.",
            default: "stretch",
            options: [
                {
                    slug: "stretch",
                    name: "Stretch",
                    niceName: "Stretch",
                    icon: null
                },
                {
                    slug: "center",
                    name: "Center", 
                    niceName: "Center",
                    icon: null
                },
                {
                    slug: "flex-start",
                    name: "Flex Start",
                    niceName: "Start",
                    icon: null
                },
                {
                    slug: "flex-end",
                    name: "Flex End",
                    niceName: "End",
                    icon: null
                },
                {
                    slug: "space-between",
                    name: "Space Between",
                    niceName: "Space Between",
                    icon: null
                },
                {
                    slug: "space-around",
                    name: "Space Around", 
                    niceName: "Space Around",
                    icon: null
                },
                {
                    slug: "space-evenly",
                    name: "Space Evenly",
                    niceName: "Space Evenly",
                    icon: null
                }
            ]
        }
    };
    
    const flexControlsIcons = window.FlexControlsIcons || {};

    // Helper function to get available options for a control based on supports config and flex direction
    function getControlOptions(supports, controlName, flexDirection = 'row') {
        const controlConfig = flexControlsConfig[controlName];
        if (!controlConfig) return [];

        let availableOptions = [];
        
        if (supports === true) {
            // Return all options when flexControls: true
            availableOptions = controlConfig.options.map(opt => opt.slug);
        } else if (typeof supports === 'object' && supports[controlName]) {
            if (Array.isArray(supports[controlName])) {
                // Return custom array of options
                availableOptions = supports[controlName];
            } else if (supports[controlName] === true) {
                // Return all options for this control
                availableOptions = controlConfig.options.map(opt => opt.slug);
            }
        }
        
        // Filter options based on flex direction availability
        return availableOptions.filter(optionSlug => {
            const option = controlConfig.options.find(opt => opt.slug === optionSlug);
            if (!option || !option.availableFor) {
                return true; // Include if no availability restriction
            }
            return option.availableFor.includes(flexDirection);
        });
    }

    // Helper function to check if nice names should be used
    function useNiceNames(supports) {
        if (typeof supports === 'object' && supports.niceNames === false) {
            return false;
        }
        return true; // default to true
    }

    // Helper function to get display name for an option
    function getOptionDisplayName(controlName, optionSlug, useNice) {
        const controlConfig = flexControlsConfig[controlName];
        if (!controlConfig) return optionSlug;
        
        const option = controlConfig.options.find(opt => opt.slug === optionSlug);
        if (!option) return optionSlug;
        
        return useNice ? option.niceName : option.name;
    }

    // Helper function to get control title based on flex direction
    function getControlTitle(controlName, useNice, flexDirection = 'row') {
        const controlConfig = flexControlsConfig[controlName];
        if (!controlConfig) return controlName;
        
        if (!useNice) {
            return controlConfig.name;
        }
        
        // Handle dynamic nice names based on flex direction
        if (typeof controlConfig.niceName === 'object') {
            const baseDirection = flexDirection.replace('-reverse', '');
            return controlConfig.niceName[baseDirection] || controlConfig.niceName.row || controlConfig.name;
        }
        
        return controlConfig.niceName || controlConfig.name;
    }

    // Helper function to check if a control should be shown based on config conditions
    function shouldShowControl(controlName, currentValues) {
        const controlConfig = flexControlsConfig[controlName];
        if (!controlConfig || !controlConfig.showWhen) {
            return true; // Show by default if no conditions
        }
        
        // Check all conditions in showWhen
        for (const [dependentControl, allowedValues] of Object.entries(controlConfig.showWhen)) {
            const currentValue = currentValues[dependentControl];
            if (!Array.isArray(allowedValues)) {
                continue;
            }
            
            // If current value is not in allowed values, don't show control
            if (!allowedValues.includes(currentValue)) {
                return false;
            }
        }
        
        return true;
    }


    // Create control elements using centralized config
    function createFlexControl(controlName, value, onChange, options, useNice, flexDirection = 'row') {
        if (options.length === 0) return null;
        
        const controlConfig = flexControlsConfig[controlName];
        if (!controlConfig) return null;
        
        const controlTitle = getControlTitle(controlName, useNice, flexDirection);
        
        return wp.element.createElement(
            'div',
            { style: { marginBottom: '16px' } },
            wp.element.createElement('label', {
                style: { 
                    display: 'block', 
                    marginBottom: '8px',
                    fontSize: '11px',
                    fontWeight: '500',
                    textTransform: 'uppercase',
                    color: '#1e1e1e'
                }
            }, controlTitle),
            wp.element.createElement(ToggleGroupControl, {
                value: value,
                onChange: onChange,
                isBlock: true,
                __next40pxDefaultSize: true,
                __nextHasNoMarginBottom: true
            }, 
                options.map(option => {
                    const label = getOptionDisplayName(controlName, option, useNice);
                    const optionConfig = controlConfig.options.find(opt => opt.slug === option);
                    const iconKey = optionConfig?.icon;
                    const icon = iconKey ? flexControlsIcons[iconKey] : null;
                    
                    if (icon) {
                        return wp.element.createElement(ToggleGroupControlOptionIcon, {
                            key: option,
                            value: option,
                            icon: icon,
                            label: label
                        });
                    } else {
                        return wp.element.createElement(ToggleGroupControlOption, {
                            key: option,
                            value: option,
                            label: label
                        });
                    }
                })
            )
        );
    }

    // Add inspector control
    const withFlexLayoutControl = createHigherOrderComponent(function(BlockEdit) {
        return function(props) {
            // Get data from localized script
            const flexData = window.orbitoolsFlexLayout || {};
            
            if (!flexData.isEnabled) {
                return wp.element.createElement(BlockEdit, props);
            }
            
            // Check if this block supports flex controls
            const flexSupports = getBlockSupports(props.name);
            
            if (!flexSupports) {
                return wp.element.createElement(BlockEdit, props);
            }

            const { attributes, setAttributes } = props;
            
            // Get flex controls from the object attribute, with fallbacks
            const flexControls = attributes.orbitoolsFlexControls || {};
            const orbitoolsFlexDirection = flexControls.flexDirection || flexControlsConfig.flexDirection.default;
            const orbitoolsFlexWrap = flexControls.flexWrap || flexControlsConfig.flexWrap.default;
            const orbitoolsAlignItems = flexControls.alignItems || flexControlsConfig.alignItems.default;
            const orbitoolsJustifyContent = flexControls.justifyContent || flexControlsConfig.justifyContent.default;
            const orbitoolsAlignContent = flexControls.alignContent || flexControlsConfig.alignContent.default;
            const orbitoolsStackOnMobile = flexControls.stackOnMobile || flexControlsConfig.stackOnMobile.default;
            
            // Helper function to update flex controls
            const updateFlexControl = (property, value) => {
                const newFlexControls = {
                    ...flexControls,
                    [property]: value
                };
                setAttributes({ orbitoolsFlexControls: newFlexControls });
            };

            const useNice = useNiceNames(flexSupports);
            
            // Function to regenerate controls when direction changes
            const generateControls = (currentFlexDirection) => {
                const controls = [];
                
                // Get available options for each control based on current flex direction
                const flexDirectionOptions = getControlOptions(flexSupports, 'flexDirection', currentFlexDirection);
                const flexWrapOptions = getControlOptions(flexSupports, 'flexWrap', currentFlexDirection);
                const alignItemsOptions = getControlOptions(flexSupports, 'alignItems', currentFlexDirection);
                const justifyContentOptions = getControlOptions(flexSupports, 'justifyContent', currentFlexDirection);
                
                // Get align-content options based on config conditions
                const currentFlexWrap = orbitoolsFlexWrap || flexControlsConfig.flexWrap.default;
                const alignContentOptions = shouldShowControl('alignContent', { flexWrap: currentFlexWrap }) ? 
                    getControlOptions(flexSupports, 'alignContent', currentFlexDirection) : [];
                
                // Add controls based on what's enabled
                if (flexDirectionOptions.length > 0) {
                    controls.push(createFlexControl(
                        'flexDirection',
                        currentFlexDirection,
                        (value) => updateFlexControl('flexDirection', value),
                        flexDirectionOptions,
                        useNice,
                        currentFlexDirection
                    ));
                }
                
                if (flexWrapOptions.length > 0) {
                    controls.push(createFlexControl(
                        'flexWrap',
                        orbitoolsFlexWrap,
                        (value) => updateFlexControl('flexWrap', value),
                        flexWrapOptions,
                        useNice,
                        currentFlexDirection
                    ));
                }
                
                // Swap order of justify-content and align-items based on direction
                // In row: justify (main/horizontal) then align (cross/vertical)  
                // In column: justify (main/vertical) then align (cross/horizontal)
                // By swapping them, they stay in the same visual position relative to the layout
                const isColumn = currentFlexDirection.startsWith('column');
                
                if (isColumn) {
                    // Column direction: show align-items first (cross/horizontal), then justify-content (main/vertical)
                    if (alignItemsOptions.length > 0) {
                        controls.push(createFlexControl(
                            'alignItems',
                            orbitoolsAlignItems,
                            (value) => updateFlexControl('alignItems', value),
                            alignItemsOptions,
                            useNice,
                            currentFlexDirection
                        ));
                    }
                    
                    if (justifyContentOptions.length > 0) {
                        controls.push(createFlexControl(
                            'justifyContent',
                            orbitoolsJustifyContent,
                            (value) => updateFlexControl('justifyContent', value),
                            justifyContentOptions,
                            useNice,
                            currentFlexDirection
                        ));
                    }
                } else {
                    // Row direction: show justify-content first (main/horizontal), then align-items (cross/vertical)
                    if (justifyContentOptions.length > 0) {
                        controls.push(createFlexControl(
                            'justifyContent',
                            orbitoolsJustifyContent,
                            (value) => updateFlexControl('justifyContent', value),
                            justifyContentOptions,
                            useNice,
                            currentFlexDirection
                        ));
                    }
                    
                    if (alignItemsOptions.length > 0) {
                        controls.push(createFlexControl(
                            'alignItems',
                            orbitoolsAlignItems,
                            (value) => updateFlexControl('alignItems', value),
                            alignItemsOptions,
                            useNice,
                            currentFlexDirection
                        ));
                    }
                }
                
                if (alignContentOptions.length > 0) {
                    controls.push(createFlexControl(
                        'alignContent',
                        orbitoolsAlignContent,
                        (value) => updateFlexControl('alignContent', value),
                        alignContentOptions,
                        useNice,
                        currentFlexDirection
                    ));
                }
                
                // Add stack on mobile toggle if supported
                if (flexSupports.stackOnMobile !== false) {
                    controls.push(
                        wp.element.createElement(ToggleControl, {
                            key: 'stackOnMobile',
                            label: 'Stack on Mobile',
                            help: 'Stack columns vertically on mobile devices',
                            checked: orbitoolsStackOnMobile,
                            onChange: (value) => updateFlexControl('stackOnMobile', value),
                            __nextHasNoMarginBottom: true
                        })
                    );
                }
                
                return controls;
            };
            
            const controls = generateControls(orbitoolsFlexDirection);
            

            // Only show panel if there are controls to display
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
                        PanelBody,
                        {
                            title: 'Layout',
                            initialOpen: true
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