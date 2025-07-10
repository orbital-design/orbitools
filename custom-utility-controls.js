(function() {
    console.log('Typography Utility Controls: Script loaded');
    
    // Check if wp object exists
    if (typeof wp === 'undefined') {
        console.error('Typography Utility Controls: wp object not found');
        return;
    }
    
    // Check if required dependencies exist
    if (!wp.hooks || !wp.compose || !wp.element || !wp.blockEditor || !wp.components) {
        console.error('Typography Utility Controls: Required dependencies not found', {
            hooks: !!wp.hooks,
            compose: !!wp.compose,
            element: !!wp.element,
            blockEditor: !!wp.blockEditor,
            components: !!wp.components
        });
        return;
    }
    
    const { addFilter } = wp.hooks;
    const { createHigherOrderComponent } = wp.compose;
    const { Fragment, useState } = wp.element;
    const { InspectorControls } = wp.blockEditor;
    const { PanelBody, SelectControl, TextControl } = wp.components;

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

    // All typography utility classes from your list
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
        
        // Font Smoothing
        { label: 'Antialiased', value: 'antialiased' },
        { label: 'Subpixel Antialiased', value: 'subpixel-antialiased' },
        
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
        
        // Font Variant Numeric
        { label: 'Normal Nums', value: 'normal-nums' },
        { label: 'Ordinal', value: 'ordinal' },
        { label: 'Slashed Zero', value: 'slashed-zero' },
        { label: 'Lining Nums', value: 'lining-nums' },
        { label: 'Oldstyle Nums', value: 'oldstyle-nums' },
        { label: 'Proportional Nums', value: 'proportional-nums' },
        { label: 'Tabular Nums', value: 'tabular-nums' },
        { label: 'Diagonal Fractions', value: 'diagonal-fractions' },
        { label: 'Stacked Fractions', value: 'stacked-fractions' },
        
        // Letter Spacing
        { label: 'Tracking Tighter (-0.05em)', value: 'tracking-tighter' },
        { label: 'Tracking Tight (-0.025em)', value: 'tracking-tight' },
        { label: 'Tracking Normal (0em)', value: 'tracking-normal' },
        { label: 'Tracking Wide (0.025em)', value: 'tracking-wide' },
        { label: 'Tracking Wider (0.05em)', value: 'tracking-wider' },
        { label: 'Tracking Widest (0.1em)', value: 'tracking-widest' },
        
        // Line Clamp
        { label: 'Line Clamp 1', value: 'line-clamp-1' },
        { label: 'Line Clamp 2', value: 'line-clamp-2' },
        { label: 'Line Clamp 3', value: 'line-clamp-3' },
        { label: 'Line Clamp 4', value: 'line-clamp-4' },
        { label: 'Line Clamp 5', value: 'line-clamp-5' },
        { label: 'Line Clamp 6', value: 'line-clamp-6' },
        { label: 'Line Clamp None', value: 'line-clamp-none' },
        
        // Line Height
        { label: 'Leading 3 (12px)', value: 'leading-3' },
        { label: 'Leading 4 (16px)', value: 'leading-4' },
        { label: 'Leading 5 (20px)', value: 'leading-5' },
        { label: 'Leading 6 (24px)', value: 'leading-6' },
        { label: 'Leading 7 (28px)', value: 'leading-7' },
        { label: 'Leading 8 (32px)', value: 'leading-8' },
        { label: 'Leading 9 (36px)', value: 'leading-9' },
        { label: 'Leading 10 (40px)', value: 'leading-10' },
        { label: 'Leading None (1)', value: 'leading-none' },
        { label: 'Leading Tight (1.25)', value: 'leading-tight' },
        { label: 'Leading Snug (1.375)', value: 'leading-snug' },
        { label: 'Leading Normal (1.5)', value: 'leading-normal' },
        { label: 'Leading Relaxed (1.625)', value: 'leading-relaxed' },
        { label: 'Leading Loose (2)', value: 'leading-loose' },
        
        // List Style Image
        { label: 'List Image None', value: 'list-image-none' },
        
        // List Style Position
        { label: 'List Inside', value: 'list-inside' },
        { label: 'List Outside', value: 'list-outside' },
        
        // List Style Type
        { label: 'List None', value: 'list-none' },
        { label: 'List Disc', value: 'list-disc' },
        { label: 'List Decimal', value: 'list-decimal' },
        
        // Text Align
        { label: 'Text Left', value: 'text-left' },
        { label: 'Text Center', value: 'text-center' },
        { label: 'Text Right', value: 'text-right' },
        { label: 'Text Justify', value: 'text-justify' },
        { label: 'Text Start', value: 'text-start' },
        { label: 'Text End', value: 'text-end' },
        
        // Text Color (Basic - you can expand this)
        { label: 'Text Inherit', value: 'text-inherit' },
        { label: 'Text Current', value: 'text-current' },
        { label: 'Text Transparent', value: 'text-transparent' },
        { label: 'Text Black', value: 'text-black' },
        { label: 'Text White', value: 'text-white' },
        
        // Text Decoration
        { label: 'Underline', value: 'underline' },
        { label: 'Overline', value: 'overline' },
        { label: 'Line Through', value: 'line-through' },
        { label: 'No Underline', value: 'no-underline' },
        
        // Text Decoration Style
        { label: 'Decoration Solid', value: 'decoration-solid' },
        { label: 'Decoration Double', value: 'decoration-double' },
        { label: 'Decoration Dotted', value: 'decoration-dotted' },
        { label: 'Decoration Dashed', value: 'decoration-dashed' },
        { label: 'Decoration Wavy', value: 'decoration-wavy' },
        
        // Text Decoration Thickness
        { label: 'Decoration Auto', value: 'decoration-auto' },
        { label: 'Decoration From Font', value: 'decoration-from-font' },
        { label: 'Decoration 0', value: 'decoration-0' },
        { label: 'Decoration 1', value: 'decoration-1' },
        { label: 'Decoration 2', value: 'decoration-2' },
        { label: 'Decoration 4', value: 'decoration-4' },
        { label: 'Decoration 8', value: 'decoration-8' },
        
        // Text Underline Offset
        { label: 'Underline Offset Auto', value: 'underline-offset-auto' },
        { label: 'Underline Offset 0', value: 'underline-offset-0' },
        { label: 'Underline Offset 1', value: 'underline-offset-1' },
        { label: 'Underline Offset 2', value: 'underline-offset-2' },
        { label: 'Underline Offset 4', value: 'underline-offset-4' },
        { label: 'Underline Offset 8', value: 'underline-offset-8' },
        
        // Text Transform
        { label: 'Uppercase', value: 'uppercase' },
        { label: 'Lowercase', value: 'lowercase' },
        { label: 'Capitalize', value: 'capitalize' },
        { label: 'Normal Case', value: 'normal-case' },
        
        // Text Overflow
        { label: 'Truncate', value: 'truncate' },
        { label: 'Text Ellipsis', value: 'text-ellipsis' },
        { label: 'Text Clip', value: 'text-clip' },
        
        // Text Wrap
        { label: 'Text Wrap', value: 'text-wrap' },
        { label: 'Text Nowrap', value: 'text-nowrap' },
        { label: 'Text Balance', value: 'text-balance' },
        { label: 'Text Pretty', value: 'text-pretty' },
        
        // Text Indent
        { label: 'Indent 0', value: 'indent-0' },
        { label: 'Indent px', value: 'indent-px' },
        { label: 'Indent 0.5', value: 'indent-0.5' },
        { label: 'Indent 1', value: 'indent-1' },
        { label: 'Indent 1.5', value: 'indent-1.5' },
        { label: 'Indent 2', value: 'indent-2' },
        { label: 'Indent 2.5', value: 'indent-2.5' },
        { label: 'Indent 3', value: 'indent-3' },
        { label: 'Indent 3.5', value: 'indent-3.5' },
        { label: 'Indent 4', value: 'indent-4' },
        { label: 'Indent 5', value: 'indent-5' },
        { label: 'Indent 6', value: 'indent-6' },
        { label: 'Indent 7', value: 'indent-7' },
        { label: 'Indent 8', value: 'indent-8' },
        { label: 'Indent 9', value: 'indent-9' },
        { label: 'Indent 10', value: 'indent-10' },
        { label: 'Indent 11', value: 'indent-11' },
        { label: 'Indent 12', value: 'indent-12' },
        { label: 'Indent 14', value: 'indent-14' },
        { label: 'Indent 16', value: 'indent-16' },
        { label: 'Indent 20', value: 'indent-20' },
        { label: 'Indent 24', value: 'indent-24' },
        { label: 'Indent 28', value: 'indent-28' },
        { label: 'Indent 32', value: 'indent-32' },
        { label: 'Indent 36', value: 'indent-36' },
        { label: 'Indent 40', value: 'indent-40' },
        { label: 'Indent 44', value: 'indent-44' },
        { label: 'Indent 48', value: 'indent-48' },
        { label: 'Indent 52', value: 'indent-52' },
        { label: 'Indent 56', value: 'indent-56' },
        { label: 'Indent 60', value: 'indent-60' },
        { label: 'Indent 64', value: 'indent-64' },
        { label: 'Indent 72', value: 'indent-72' },
        { label: 'Indent 80', value: 'indent-80' },
        { label: 'Indent 96', value: 'indent-96' },
        
        // Vertical Align
        { label: 'Align Baseline', value: 'align-baseline' },
        { label: 'Align Top', value: 'align-top' },
        { label: 'Align Middle', value: 'align-middle' },
        { label: 'Align Bottom', value: 'align-bottom' },
        { label: 'Align Text Top', value: 'align-text-top' },
        { label: 'Align Text Bottom', value: 'align-text-bottom' },
        { label: 'Align Sub', value: 'align-sub' },
        { label: 'Align Super', value: 'align-super' },
        
        // Whitespace
        { label: 'Whitespace Normal', value: 'whitespace-normal' },
        { label: 'Whitespace Nowrap', value: 'whitespace-nowrap' },
        { label: 'Whitespace Pre', value: 'whitespace-pre' },
        { label: 'Whitespace Pre Line', value: 'whitespace-pre-line' },
        { label: 'Whitespace Pre Wrap', value: 'whitespace-pre-wrap' },
        { label: 'Whitespace Break Spaces', value: 'whitespace-break-spaces' },
        
        // Word Break
        { label: 'Break Normal', value: 'break-normal' },
        { label: 'Break Words', value: 'break-words' },
        { label: 'Break All', value: 'break-all' },
        { label: 'Break Keep', value: 'break-keep' },
        
        // Hyphens
        { label: 'Hyphens None', value: 'hyphens-none' },
        { label: 'Hyphens Manual', value: 'hyphens-manual' },
        { label: 'Hyphens Auto', value: 'hyphens-auto' },
        
        // Content
        { label: 'Content None', value: 'content-none' }
    ];

    // Add custom attribute to store the utility class
    function addUtilityClassAttribute(settings, name) {
        if (allowedBlocks.includes(name)) {
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

    // Add the control to the inspector with search functionality
    const withUtilityClassControl = createHigherOrderComponent((BlockEdit) => {
        return (props) => {
            if (!allowedBlocks.includes(props.name)) {
                return <BlockEdit {...props} />;
            }

            const { attributes, setAttributes } = props;
            const { typographyUtilityClass } = attributes;
            const [searchTerm, setSearchTerm] = useState('');

            // Filter options based on search
            const filteredOptions = typographyUtilities.filter(option => 
                option.label.toLowerCase().includes(searchTerm.toLowerCase()) ||
                option.value.toLowerCase().includes(searchTerm.toLowerCase())
            );

            return (
                <Fragment>
                    <BlockEdit {...props} />
                    <InspectorControls>
                        <PanelBody 
                            title="Typography Utilities" 
                            initialOpen={false}
                        >
                            <TextControl
                                label="Search utilities"
                                value={searchTerm}
                                onChange={setSearchTerm}
                                placeholder="Type to filter..."
                            />
                            <SelectControl
                                label="Select Typography Utility"
                                value={typographyUtilityClass || ''}
                                options={filteredOptions}
                                onChange={(newClass) => {
                                    setAttributes({ typographyUtilityClass: newClass });
                                    setSearchTerm(''); // Clear search after selection
                                }}
                                help={`${filteredOptions.length} utilities available`}
                            />
                        </PanelBody>
                    </InspectorControls>
                </Fragment>
            );
        };
    }, 'withUtilityClassControl');

    // Apply the utility class in the editor
    const addUtilityClassToEditor = createHigherOrderComponent((BlockListBlock) => {
        return (props) => {
            if (!allowedBlocks.includes(props.name)) {
                return <BlockListBlock {...props} />;
            }

            const { typographyUtilityClass } = props.attributes;
            
            if (typographyUtilityClass) {
                const existingClasses = props.className || '';
                const newClassName = `${existingClasses} ${typographyUtilityClass}`.trim();
                
                return <BlockListBlock {...props} className={newClassName} />;
            }

            return <BlockListBlock {...props} />;
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
            props.className = `${existingClasses} ${typographyUtilityClass}`.trim();
        }

        return props;
    }

    // Register all filters
    console.log('Typography Utility Controls: Registering filters');
    
    addFilter(
        'blocks.registerBlockType',
        'typography-utility-controls/utility-class-attribute',
        addUtilityClassAttribute
    );

    addFilter(
        'editor.BlockEdit',
        'typography-utility-controls/utility-class-control',
        withUtilityClassControl
    );

    addFilter(
        'editor.BlockListBlock',
        'typography-utility-controls/utility-class-editor',
        addUtilityClassToEditor
    );

    addFilter(
        'blocks.getSaveContent.extraProps',
        'typography-utility-controls/utility-class-save',
        addUtilityClassToSave
    );
    
    console.log('Typography Utility Controls: All filters registered successfully');
})();