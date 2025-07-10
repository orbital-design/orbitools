// console.log('Typography Utility Controls: Full version loading...');

wp.domReady(function() {
    // console.log('Typography Utility Controls: WordPress ready');
    
    if (!wp.hooks || !wp.compose || !wp.element || !wp.blockEditor || !wp.components) {
        console.error('Typography Utility Controls: Missing WordPress dependencies');
        return;
    }
    
    const { addFilter } = wp.hooks;
    const { createHigherOrderComponent } = wp.compose;
    const { Fragment, useState } = wp.element;
    const { InspectorControls } = wp.blockEditor;
    const { PanelBody, SelectControl, TextControl } = wp.components;
    
    // console.log('Typography Utility Controls: All dependencies loaded');
    
    // Define which blocks should have the custom control
    const allowedBlocks = [
        'core/paragraph',
        'core/heading',
        'core/list',
        'core/list-item',
        'core/quote',
        'core/pullquote',
        'core/button',
        'core/group',
        'core/column',
        'core/columns',
        'core/cover',
        'core/image'
    ];
    
    // All typography utility classes
    const typographyUtilities = [
        { label: 'None', value: '' },
        
        // Font Family
        { label: 'Font Sans', value: 'font-sans' },
        { label: 'Font Serif', value: 'font-serif' },
        { label: 'Font Mono', value: 'font-mono' },
        
        // Font Size
        { label: 'Text XS (12px)', value: 'text-xs' },
        { label: 'Text SM (14px)', value: 'text-sm' },
        { label: 'Text Base (16px)', value: 'text-base' },
        { label: 'Text LG (18px)', value: 'text-lg' },
        { label: 'Text XL (20px)', value: 'text-xl' },
        { label: 'Text 2XL (24px)', value: 'text-2xl' },
        { label: 'Text 3XL (30px)', value: 'text-3xl' },
        { label: 'Text 4XL (36px)', value: 'text-4xl' },
        { label: 'Text 5XL (48px)', value: 'text-5xl' },
        { label: 'Text 6XL (60px)', value: 'text-6xl' },
        { label: 'Text 7XL (72px)', value: 'text-7xl' },
        { label: 'Text 8XL (96px)', value: 'text-8xl' },
        { label: 'Text 9XL (128px)', value: 'text-9xl' },
        
        // Font Style
        { label: 'Italic', value: 'italic' },
        { label: 'Not Italic', value: 'not-italic' },
        
        // Font Weight
        { label: 'Font Thin (100)', value: 'font-thin' },
        { label: 'Font Extralight (200)', value: 'font-extralight' },
        { label: 'Font Light (300)', value: 'font-light' },
        { label: 'Font Normal (400)', value: 'font-normal' },
        { label: 'Font Medium (500)', value: 'font-medium' },
        { label: 'Font Semibold (600)', value: 'font-semibold' },
        { label: 'Font Bold (700)', value: 'font-bold' },
        { label: 'Font Extrabold (800)', value: 'font-extrabold' },
        { label: 'Font Black (900)', value: 'font-black' },
        
        // Letter Spacing
        { label: 'Tracking Tighter (-0.05em)', value: 'tracking-tighter' },
        { label: 'Tracking Tight (-0.025em)', value: 'tracking-tight' },
        { label: 'Tracking Normal (0em)', value: 'tracking-normal' },
        { label: 'Tracking Wide (0.025em)', value: 'tracking-wide' },
        { label: 'Tracking Wider (0.05em)', value: 'tracking-wider' },
        { label: 'Tracking Widest (0.1em)', value: 'tracking-widest' },
        
        // Line Height
        { label: 'Leading None (1)', value: 'leading-none' },
        { label: 'Leading Tight (1.25)', value: 'leading-tight' },
        { label: 'Leading Snug (1.375)', value: 'leading-snug' },
        { label: 'Leading Normal (1.5)', value: 'leading-normal' },
        { label: 'Leading Relaxed (1.625)', value: 'leading-relaxed' },
        { label: 'Leading Loose (2)', value: 'leading-loose' },
        
        // Text Align
        { label: 'Text Left', value: 'text-left' },
        { label: 'Text Center', value: 'text-center' },
        { label: 'Text Right', value: 'text-right' },
        { label: 'Text Justify', value: 'text-justify' },
        
        // Text Decoration
        { label: 'Underline', value: 'underline' },
        { label: 'Overline', value: 'overline' },
        { label: 'Line Through', value: 'line-through' },
        { label: 'No Underline', value: 'no-underline' },
        
        // Text Transform
        { label: 'Uppercase', value: 'uppercase' },
        { label: 'Lowercase', value: 'lowercase' },
        { label: 'Capitalize', value: 'capitalize' },
        { label: 'Normal Case', value: 'normal-case' },
        
        // Text Overflow
        { label: 'Truncate', value: 'truncate' },
        { label: 'Text Wrap', value: 'text-wrap' },
        { label: 'Text Nowrap', value: 'text-nowrap' },
        
        // Whitespace
        { label: 'Whitespace Normal', value: 'whitespace-normal' },
        { label: 'Whitespace Nowrap', value: 'whitespace-nowrap' },
        { label: 'Whitespace Pre', value: 'whitespace-pre' },
        { label: 'Whitespace Pre Line', value: 'whitespace-pre-line' },
        { label: 'Whitespace Pre Wrap', value: 'whitespace-pre-wrap' }
    ];
    
    // Add custom attribute
    function addTypographyAttribute(settings, name) {
        if (allowedBlocks.includes(name)) {
            // console.log('Typography Utility Controls: Adding attribute to', name);
            settings.attributes = {
                ...settings.attributes,
                typographyUtilityClass: {
                    type: 'string',
                    default: ''
                }
            };
        }
        return settings;
    }
    
    // Add inspector control with search
    const withTypographyControl = createHigherOrderComponent(function(BlockEdit) {
        return function(props) {
            if (!allowedBlocks.includes(props.name)) {
                return wp.element.createElement(BlockEdit, props);
            }
            
            const { attributes, setAttributes } = props;
            const { typographyUtilityClass } = attributes;
            
            // Use React hooks for search functionality
            const [searchTerm, setSearchTerm] = useState('');
            
            // Filter options based on search
            const filteredOptions = typographyUtilities.filter(option => 
                option.label.toLowerCase().includes(searchTerm.toLowerCase()) ||
                option.value.toLowerCase().includes(searchTerm.toLowerCase())
            );
            
            return wp.element.createElement(
                Fragment,
                {},
                wp.element.createElement(BlockEdit, props),
                wp.element.createElement(
                    InspectorControls,
                    { group: 'styles' },
                    wp.element.createElement(
                        PanelBody,
                        {
                            title: 'Typography Utilities',
                            initialOpen: false
                        },
                        wp.element.createElement(TextControl, {
                            label: 'Search utilities',
                            value: searchTerm,
                            onChange: setSearchTerm,
                            placeholder: 'Type to filter...',
                            __nextHasNoMarginBottom: true,
                            __next40pxDefaultSize: true
                        }),
                        wp.element.createElement(SelectControl, {
                            label: 'Select Typography Utility',
                            value: typographyUtilityClass || '',
                            options: filteredOptions,
                            onChange: function(newValue) {
                                // console.log('Typography Utility Controls: Setting class to:', newValue);
                                // console.log('Typography Utility Controls: Current attributes:', attributes);
                                setAttributes({ typographyUtilityClass: newValue });
                                setSearchTerm(''); // Clear search after selection
                            },
                            help: filteredOptions.length + ' utilities available',
                            __nextHasNoMarginBottom: true,
                            __next40pxDefaultSize: true
                        })
                    )
                )
            );
        };
    }, 'withTypographyControl');
    
    // Apply the utility class in the editor
    const addUtilityClassToEditor = createHigherOrderComponent(function(BlockListBlock) {
        return function(props) {
            if (!allowedBlocks.includes(props.name)) {
                return wp.element.createElement(BlockListBlock, props);
            }
            
            const { typographyUtilityClass } = props.attributes;
            
            // console.log('Typography Utility Controls: Editor rendering - Block:', props.name, 'Class:', typographyUtilityClass);
            
            if (typographyUtilityClass) {
                const existingClasses = props.className || '';
                const newClassName = (existingClasses + ' ' + typographyUtilityClass).trim();
                
                // console.log('Typography Utility Controls: Editor - Adding class:', newClassName);
                
                const newProps = {
                    ...props,
                    className: newClassName
                };
                
                return wp.element.createElement(BlockListBlock, newProps);
            }
            
            return wp.element.createElement(BlockListBlock, props);
        };
    }, 'addUtilityClassToEditor');
    
    // Save the utility class for the frontend
    function addUtilityClassToSave(props, blockType, attributes) {
        if (!allowedBlocks.includes(blockType.name)) {
            return props;
        }
        
        const { typographyUtilityClass } = attributes;
        
        if (typographyUtilityClass) {
            const existingClasses = props.className || '';
            props.className = (existingClasses + ' ' + typographyUtilityClass).trim();
            // console.log('Typography Utility Controls: Adding class to frontend:', props.className);
        }
        
        return props;
    }
    
    // Alternative method to ensure class is added to wrapper element
    function addClassToWrapperProps(props, blockType, attributes) {
        if (!allowedBlocks.includes(blockType.name)) {
            return props;
        }
        
        const { typographyUtilityClass } = attributes;
        
        if (typographyUtilityClass) {
            // Ensure the class is added to the main wrapper
            if (props.className) {
                props.className = props.className + ' ' + typographyUtilityClass;
            } else {
                props.className = typographyUtilityClass;
            }
            // console.log('Typography Utility Controls: Wrapper class set to:', props.className);
        }
        
        return props;
    }
    
    // Register all filters
    // console.log('Typography Utility Controls: Registering filters');
    
    addFilter(
        'blocks.registerBlockType',
        'typography-utility-controls/add-attribute',
        addTypographyAttribute
    );
    
    addFilter(
        'editor.BlockEdit',
        'typography-utility-controls/add-control',
        withTypographyControl
    );
    
    addFilter(
        'editor.BlockListBlock',
        'typography-utility-controls/add-editor-class',
        addUtilityClassToEditor
    );
    
    addFilter(
        'blocks.getSaveContent.extraProps',
        'typography-utility-controls/add-save-class',
        addUtilityClassToSave
    );
    
    // Additional filter to ensure classes are applied to block wrapper
    addFilter(
        'blocks.getBlockDefaultClassName',
        'typography-utility-controls/add-wrapper-class',
        addClassToWrapperProps
    );
    
    // console.log('Typography Utility Controls: Setup complete with search functionality');
});