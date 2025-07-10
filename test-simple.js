console.log('Typography Utility Controls: Script starting...');

// Use wp.domReady to ensure WordPress is fully loaded
wp.domReady(function() {
    console.log('Typography Utility Controls: WordPress ready');
    
    // Check if required objects exist
    if (!wp.hooks || !wp.compose || !wp.element || !wp.blockEditor || !wp.components) {
        console.error('Typography Utility Controls: Missing WordPress dependencies');
        return;
    }
    
    const { addFilter } = wp.hooks;
    const { createHigherOrderComponent } = wp.compose;
    const { Fragment } = wp.element;
    const { InspectorControls } = wp.blockEditor;
    const { PanelBody, SelectControl } = wp.components;
    
    console.log('Typography Utility Controls: All dependencies loaded');
    
    // Simple utility options
    const utilityOptions = [
        { label: 'None', value: '' },
        { label: 'Bold Text', value: 'font-bold' },
        { label: 'Italic Text', value: 'italic' },
        { label: 'Underlined', value: 'underline' },
        { label: 'Center Aligned', value: 'text-center' },
        { label: 'Large Text', value: 'text-xl' }
    ];
    
    // Add custom attribute to paragraph blocks
    function addCustomAttribute(settings, name) {
        if (name === 'core/paragraph') {
            console.log('Typography Utility Controls: Adding attribute to paragraph block');
            settings.attributes = {
                ...settings.attributes,
                customTypographyClass: {
                    type: 'string',
                    default: ''
                }
            };
        }
        return settings;
    }
    
    // Add inspector control
    const withCustomControl = createHigherOrderComponent(function(BlockEdit) {
        return function(props) {
            if (props.name !== 'core/paragraph') {
                return wp.element.createElement(BlockEdit, props);
            }
            
            console.log('Typography Utility Controls: Rendering control');
            
            const { attributes, setAttributes } = props;
            const { customTypographyClass } = attributes;
            
            return wp.element.createElement(
                Fragment,
                {},
                wp.element.createElement(BlockEdit, props),
                wp.element.createElement(
                    InspectorControls,
                    {},
                    wp.element.createElement(
                        PanelBody,
                        {
                            title: 'Typography Utilities',
                            initialOpen: true
                        },
                        wp.element.createElement(SelectControl, {
                            label: 'Typography Class',
                            value: customTypographyClass || '',
                            options: utilityOptions,
                            onChange: function(newValue) {
                                console.log('Typography Utility Controls: Setting class to:', newValue);
                                setAttributes({ customTypographyClass: newValue });
                            }
                        })
                    )
                )
            );
        };
    }, 'withCustomControl');
    
    // Register the filters
    console.log('Typography Utility Controls: Registering filters');
    
    addFilter(
        'blocks.registerBlockType',
        'typography-utility-controls/add-attribute',
        addCustomAttribute
    );
    
    addFilter(
        'editor.BlockEdit',
        'typography-utility-controls/add-control',
        withCustomControl
    );
    
    console.log('Typography Utility Controls: Setup complete');
});