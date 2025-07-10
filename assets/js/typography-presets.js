/**
 * Typography Presets Block Editor Integration
 * 
 * Replaces core typography controls with a preset dropdown system
 */

wp.domReady(function() {
    console.log('Typography Presets: Script loading...');
    
    if (!wp.hooks || !wp.compose || !wp.element || !wp.blockEditor || !wp.components) {
        console.error('Typography Presets: Missing WordPress dependencies');
        return;
    }
    
    const { addFilter } = wp.hooks;
    const { createHigherOrderComponent } = wp.compose;
    const { Fragment, useState } = wp.element;
    const { InspectorControls } = wp.blockEditor;
    const { __experimentalToolsPanel: ToolsPanel, __experimentalToolsPanelItem: ToolsPanelItem, SelectControl } = wp.components;
    
    console.log('Typography Presets: All dependencies loaded');

    // Get localized data
    const { presets, categories, settings, strings } = window.orbitalTypographyPresets || {};

    // Debug logging
    console.log('Typography Presets Data:', {
        hasData: !!window.orbitalTypographyPresets,
        presetsCount: presets ? Object.keys(presets).length : 0,
        settings,
        enabled: settings?.enabled
    });

    if (!presets || !settings || !settings.enabled) {
        console.log('Typography Presets: Not enabled or missing data');
        return;
    }

    // Define which blocks should have the preset control
    const allowedBlocks = settings.allowed_blocks || [
        'core/paragraph',
        'core/heading',
        'core/list',
        'core/quote',
        'core/button'
    ];

    console.log('Typography Presets: Allowed blocks:', allowedBlocks);

    /**
     * Convert presets object to array suitable for SelectControl
     */
    function getPresetsForSelect() {
        const options = [
            { label: strings?.noPreset || 'No Preset', value: '' }
        ];

        // Group by category if enabled
        if (settings.show_categories) {
            const grouped = {};
            
            Object.keys(presets).forEach(id => {
                const preset = presets[id];
                const category = preset.category || 'other';
                
                if (!grouped[category]) {
                    grouped[category] = [];
                }
                
                grouped[category].push({
                    label: preset.label,
                    value: id
                });
            });

            // Add grouped options
            Object.keys(grouped).forEach(category => {
                options.push({
                    label: `--- ${category.charAt(0).toUpperCase() + category.slice(1)} ---`,
                    value: '',
                    disabled: true
                });
                
                options.push(...grouped[category]);
            });
        } else {
            // Simple flat list
            Object.keys(presets).forEach(id => {
                const preset = presets[id];
                options.push({
                    label: preset.label,
                    value: id
                });
            });
        }

        return options;
    }

    /**
     * Apply preset styles to block
     */
    function applyPresetToBlock(preset, attributes, setAttributes) {
        if (!preset || !preset.properties) {
            // Clear preset styles
            setAttributes({
                orbitalTypographyPreset: '',
                style: {
                    ...attributes.style,
                    typography: undefined
                }
            });
            return;
        }

        const typography = {};
        const spacing = {};

        // Map preset properties to block attributes
        Object.keys(preset.properties).forEach(property => {
            const value = preset.properties[property];
            
            switch (property) {
                case 'font-size':
                    typography.fontSize = value;
                    break;
                case 'line-height':
                    typography.lineHeight = value;
                    break;
                case 'font-weight':
                    typography.fontWeight = value;
                    break;
                case 'letter-spacing':
                    typography.letterSpacing = value;
                    break;
                case 'text-transform':
                    typography.textTransform = value;
                    break;
                case 'text-decoration':
                    typography.textDecoration = value;
                    break;
                case 'margin-bottom':
                    spacing.margin = { ...spacing.margin, bottom: value };
                    break;
                case 'margin-top':
                    spacing.margin = { ...spacing.margin, top: value };
                    break;
                case 'padding':
                    spacing.padding = value;
                    break;
            }
        });

        // Update block attributes
        const newStyle = {
            ...attributes.style,
            typography: Object.keys(typography).length > 0 ? typography : undefined,
            spacing: Object.keys(spacing).length > 0 ? spacing : undefined
        };

        setAttributes({
            orbitalTypographyPreset: preset.id || '',
            style: newStyle,
            className: `${attributes.className || ''} orbital-preset-${preset.id || ''}`.trim()
        });
    }

    // Add custom attribute
    function addTypographyPresetAttribute(settings, name) {
        if (allowedBlocks.includes(name)) {
            console.log('Typography Presets: Adding attribute to', name);
            settings.attributes = {
                ...settings.attributes,
                orbitalTypographyPreset: {
                    type: 'string',
                    default: ''
                }
            };
        }
        return settings;
    }

    // Add inspector control
    const withTypographyPresetControl = createHigherOrderComponent(function(BlockEdit) {
        return function(props) {
            if (!allowedBlocks.includes(props.name)) {
                return wp.element.createElement(BlockEdit, props);
            }
            
            console.log('Typography Presets: Adding controls for', props.name);
            
            const { attributes, setAttributes } = props;
            const { orbitalTypographyPreset } = attributes;
            
            const currentPreset = orbitalTypographyPreset && presets[orbitalTypographyPreset] ? 
                presets[orbitalTypographyPreset] : null;
            
            return wp.element.createElement(
                Fragment,
                {},
                wp.element.createElement(BlockEdit, props),
                wp.element.createElement(
                    InspectorControls,
                    { group: 'styles' },
                    wp.element.createElement(
                        ToolsPanel,
                        {
                            label: 'Typography Presets',
                            resetAll: function() {
                                setAttributes({ orbitalTypographyPreset: '' });
                            }
                        },
                        wp.element.createElement(
                            ToolsPanelItem,
                            {
                                hasValue: function() { return !!orbitalTypographyPreset; },
                                label: 'Typography Preset',
                                onDeselect: function() { 
                                    applyPresetToBlock(null, attributes, setAttributes);
                                },
                                isShownByDefault: true
                            },
                            wp.element.createElement(SelectControl, {
                                label: 'Typography Preset',
                                value: orbitalTypographyPreset || '',
                                options: getPresetsForSelect(),
                                onChange: function(presetId) {
                                    console.log('Typography Presets: Setting preset to:', presetId);
                                    if (presetId && presets[presetId]) {
                                        applyPresetToBlock(presets[presetId], attributes, setAttributes);
                                    } else {
                                        applyPresetToBlock(null, attributes, setAttributes);
                                    }
                                },
                                help: currentPreset ? 
                                    currentPreset.description : 
                                    'Choose a typography preset to apply consistent styling.',
                                __nextHasNoMarginBottom: true
                            }),
                            
                            // Show current preset preview
                            currentPreset && wp.element.createElement(
                                'div',
                                {
                                    style: {
                                        background: '#f6f7f7',
                                        padding: '8px 12px',
                                        borderRadius: '4px',
                                        marginTop: '8px',
                                        fontSize: '13px',
                                        border: '1px solid #ddd'
                                    }
                                },
                                wp.element.createElement(
                                    'div',
                                    {
                                        style: {
                                            ...currentPreset.properties,
                                            margin: '0 0 4px 0',
                                            color: '#1e1e1e'
                                        }
                                    },
                                    currentPreset.label
                                ),
                                wp.element.createElement(
                                    'div',
                                    {
                                        style: {
                                            fontSize: '11px',
                                            color: '#757575',
                                            fontFamily: 'monospace'
                                        }
                                    },
                                    Object.keys(currentPreset.properties).slice(0, 2).map(prop => 
                                        `${prop}: ${currentPreset.properties[prop]}`
                                    ).join(' • ')
                                )
                            )
                        )
                    )
                )
            );
        };
    }, 'withTypographyPresetControl');

    /**
     * Add preset class to block wrapper
     */
    function addPresetClassToSave(props, blockType, attributes) {
        if (!allowedBlocks.includes(blockType.name)) {
            return props;
        }
        
        const { orbitalTypographyPreset } = attributes;
        
        if (orbitalTypographyPreset) {
            const presetClass = `orbital-preset-${orbitalTypographyPreset}`;
            const existingClasses = props.className || '';
            props.className = (existingClasses + ' ' + presetClass).trim();
            console.log('Typography Presets: Adding class to frontend:', props.className);
        }
        
        return props;
    }

    /**
     * Remove core typography controls if setting is enabled
     */
    if (settings.replace_core_controls) {
        addFilter(
            'blocks.registerBlockType',
            'orbital-editor-suite/remove-core-typography',
            function(blockType) {
                // Remove typography support from allowed blocks
                if (allowedBlocks.includes(blockType.name)) {
                    if (blockType.supports) {
                        // Remove typography supports
                        if (blockType.supports.typography) {
                            delete blockType.supports.typography;
                        }
                        if (blockType.supports.__experimentalFontFamily) {
                            delete blockType.supports.__experimentalFontFamily;
                        }
                        if (blockType.supports.__experimentalFontSize) {
                            delete blockType.supports.__experimentalFontSize;
                        }
                        if (blockType.supports.__experimentalFontWeight) {
                            delete blockType.supports.__experimentalFontWeight;
                        }
                        if (blockType.supports.__experimentalLineHeight) {
                            delete blockType.supports.__experimentalLineHeight;
                        }
                        if (blockType.supports.__experimentalLetterSpacing) {
                            delete blockType.supports.__experimentalLetterSpacing;
                        }
                    }
                }
                
                return blockType;
            }
        );
    }

    // Register all filters
    console.log('Typography Presets: Registering filters');
    
    addFilter(
        'blocks.registerBlockType',
        'orbital-editor-suite/add-preset-attribute',
        addTypographyPresetAttribute
    );
    
    addFilter(
        'editor.BlockEdit',
        'orbital-editor-suite/add-preset-control',
        withTypographyPresetControl
    );
    
    addFilter(
        'blocks.getSaveContent.extraProps',
        'orbital-editor-suite/add-preset-class',
        addPresetClassToSave
    );
    
    console.log('Typography Presets: Setup complete');
});