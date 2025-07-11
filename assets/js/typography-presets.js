/**
 * Typography Presets Block Editor Integration
 *
 * Replaces core typography controls with a preset dropdown system
 */

// Register typography removal filter immediately when script loads (after dependencies)
(function() {
    const { addFilter } = wp.hooks;
    
    addFilter(
        'blocks.registerBlockType',
        'orbital-editor-suite/remove-core-typography-controls',
        function(settings, name) {
            // Get settings from localized data
            const { settings: moduleSettings } = window.orbitalTypographyPresets || {};
            
            // Debug function for early filter
            function debugLog(...args) {
                const globalSettings = window.orbitalEditorSuiteGlobal?.settings || {};
                if (globalSettings.enable_debug) {
                    console.log('[Typography Presets Debug]', ...args);
                }
            }
            
            debugLog('Early filter - checking block:', name);
            
            if (!moduleSettings || !moduleSettings.replace_core_controls) {
                return settings;
            }
            
            // Define allowed blocks (with fallback)
            const allowedBlocks = moduleSettings.allowed_blocks || [
                'core/paragraph', 'core/heading', 'core/list', 'core/quote', 'core/button'
            ];
            
            if (!allowedBlocks.includes(name)) {
                return settings;
            }
            
            debugLog('Removing typography controls from', name);
            
            if (!settings.supports) {
                settings.supports = {};
            }
            
            // Remove all typography supports
            settings.supports.typography = false;
            settings.supports.fontSize = false;
            settings.supports.lineHeight = false;
            settings.supports.__experimentalFontFamily = false;
            settings.supports.__experimentalFontSize = false;
            settings.supports.__experimentalFontWeight = false;
            settings.supports.__experimentalLineHeight = false;
            settings.supports.__experimentalLetterSpacing = false;
            settings.supports.__experimentalTextDecoration = false;
            settings.supports.__experimentalTextTransform = false;
            settings.supports.__experimentalWritingMode = false;
            
            debugLog('Typography supports removed for', name);
            
            return settings;
        },
        5  // Early priority
    );
})();

// Register editor controls filter immediately when script loads (after dependencies)
(function() {
    const { addFilter } = wp.hooks;
    const { createHigherOrderComponent } = wp.compose;
    const { Fragment } = wp.element;
    const { InspectorControls } = wp.blockEditor;
    const { __experimentalToolsPanel: ToolsPanel, __experimentalToolsPanelItem: ToolsPanelItem, SelectControl } = wp.components;

    // Debug function for early editor filter
    function debugLog(...args) {
        const globalSettings = window.orbitalEditorSuiteGlobal?.settings || {};
        if (globalSettings.enable_debug) {
            console.log('[Typography Presets Debug]', ...args);
        }
    }

    // Add inspector control - needs to be registered early
    const withTypographyPresetControl = createHigherOrderComponent(function(BlockEdit) {
        return function(props) {
            // Get data from localized script
            const { presets, settings, strings } = window.orbitalTypographyPresets || {};
            
            if (!presets || !settings) {
                return wp.element.createElement(BlockEdit, props);
            }

            // Define allowed blocks (with fallback)
            const allowedBlocks = settings.allowed_blocks || [
                'core/paragraph', 'core/heading', 'core/list', 'core/quote', 'core/button'
            ];

            if (!allowedBlocks.includes(props.name)) {
                return wp.element.createElement(BlockEdit, props);
            }

            const { attributes, setAttributes } = props;
            const { orbitalTypographyPreset } = attributes;

            debugLog('Adding controls for', props.name);
            debugLog('All attributes:', attributes);
            debugLog('Current orbitalTypographyPreset attribute:', orbitalTypographyPreset);
            debugLog('Type of orbitalTypographyPreset:', typeof orbitalTypographyPreset);
            debugLog('Available presets keys:', Object.keys(presets));

            const currentPreset = orbitalTypographyPreset && presets[orbitalTypographyPreset] ?
                presets[orbitalTypographyPreset] : null;
                
            debugLog('Current preset object:', currentPreset);
            debugLog('SelectControl value (orbitalTypographyPreset || ""):', orbitalTypographyPreset || '');

            /**
             * Convert presets object to array suitable for SelectControl
             */
            function getPresetsForSelect() {
                const options = [
                    { label: strings?.noPreset || 'No Preset', value: '' }
                ];

                // Group by group if enabled
                if (settings.show_groups) {
                    const grouped = {};

                    Object.keys(presets).forEach(id => {
                        const preset = presets[id];
                        const group = preset.group || 'other';

                        if (!grouped[group]) {
                            grouped[group] = [];
                        }

                        grouped[group].push({
                            label: preset.label,
                            value: id
                        });
                    });

                    // Add grouped options
                    Object.keys(grouped).forEach(group => {
                        options.push({
                            label: `--- ${group.charAt(0).toUpperCase() + group.slice(1)} ---`,
                            value: '',
                            disabled: true
                        });

                        options.push(...grouped[group]);
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
            function applyPresetToBlock(preset, presetId, attributes, setAttributes) {
                // Simply set the preset ID - styling will be handled by CSS classes
                setAttributes({
                    orbitalTypographyPreset: presetId || ''
                });
            }

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
                            label: 'Typography',
                            resetAll: function() {
                                applyPresetToBlock(null, '', attributes, setAttributes);
                            }
                        },
                        wp.element.createElement(
                            ToolsPanelItem,
                            {
                                hasValue: function() { return !!orbitalTypographyPreset; },
                                label: 'Preset',
                                onDeselect: function() {
                                    applyPresetToBlock(null, '', attributes, setAttributes);
                                },
                                isShownByDefault: true
                            },
                            wp.element.createElement(SelectControl, {
                                label: 'Preset',
                                value: orbitalTypographyPreset || '',
                                options: getPresetsForSelect(),
                                onChange: function(presetId) {
                                    debugLog('Setting preset to:', presetId);
                                    if (presetId && presets[presetId]) {
                                        applyPresetToBlock(presets[presetId], presetId, attributes, setAttributes);
                                    } else {
                                        applyPresetToBlock(null, '', attributes, setAttributes);
                                    }
                                },
                                help: currentPreset ?
                                    currentPreset.description :
                                    'Choose a typography preset to apply consistent styling.',
                                __nextHasNoMarginBottom: true,
                                __next40pxDefaultSize: true
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
                                        className: `has-type-preset has-type-preset-${orbitalTypographyPreset}`,
                                        style: {
                                            margin: '0 0 4px 0',
                                            color: '#1e1e1e',
                                            whiteSpace: 'nowrap',
                                            overflow: 'hidden'
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
                                    Object.keys(currentPreset.properties).map(prop => {
                                        let value = currentPreset.properties[prop];
                                        
                                        // Replace font-family CSS vars with font name from label
                                        if (prop === 'font-family' && value.startsWith('var(')) {
                                            const fontName = currentPreset.label.split(' • ')[0];
                                            value = fontName;
                                        }
                                        
                                        return wp.element.createElement(
                                            'div', 
                                            { 
                                                key: prop,
                                                style: {
                                                    whiteSpace: 'nowrap',
                                                    overflow: 'hidden'
                                                }
                                            }, 
                                            `${prop}: ${value}`
                                        );
                                    })
                                )
                            )
                        )
                    )
                )
            );
        };
    }, 'withTypographyPresetControl');

    // Add custom attribute registration
    function addTypographyPresetAttribute(settings, name) {
        // Get data from localized script
        const { settings: moduleSettings } = window.orbitalTypographyPresets || {};
        
        if (!moduleSettings) {
            return settings;
        }

        // Define allowed blocks (with fallback)
        const allowedBlocks = moduleSettings.allowed_blocks || [
            'core/paragraph', 'core/heading', 'core/list', 'core/quote', 'core/button'
        ];

        if (allowedBlocks.includes(name)) {
            debugLog('Adding attribute to', name);
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

    // Register all filters immediately
    // Add preset classes to editor blocks
    const addPresetClassToEditor = createHigherOrderComponent(function(BlockListBlock) {
        return function(props) {
            // Get data from localized script
            const { settings: moduleSettings } = window.orbitalTypographyPresets || {};
            
            if (!moduleSettings) {
                return wp.element.createElement(BlockListBlock, props);
            }

            // Define allowed blocks (with fallback)
            const allowedBlocks = moduleSettings.allowed_blocks || [
                'core/paragraph', 'core/heading', 'core/list', 'core/quote', 'core/button'
            ];

            if (!allowedBlocks.includes(props.name)) {
                return wp.element.createElement(BlockListBlock, props);
            }

            const { orbitalTypographyPreset } = props.attributes;

            if (orbitalTypographyPreset) {
                const existingClasses = props.className || '';
                const presetClasses = `has-type-preset has-type-preset-${orbitalTypographyPreset}`;
                const newClassName = (existingClasses + ' ' + presetClasses).trim();
                
                debugLog('Adding preset classes to editor:', newClassName);
                
                const newProps = {
                    ...props,
                    className: newClassName
                };
                
                return wp.element.createElement(BlockListBlock, newProps);
            }

            return wp.element.createElement(BlockListBlock, props);
        };
    }, 'addPresetClassToEditor');

    // Add preset classes to block wrapper
    function addPresetClassToSave(props, blockType, attributes) {
        // Get data from localized script
        const { settings: moduleSettings } = window.orbitalTypographyPresets || {};
        
        if (!moduleSettings) {
            return props;
        }

        // Define allowed blocks (with fallback)
        const allowedBlocks = moduleSettings.allowed_blocks || [
            'core/paragraph', 'core/heading', 'core/list', 'core/quote', 'core/button'
        ];

        if (!allowedBlocks.includes(blockType.name)) {
            return props;
        }

        const { orbitalTypographyPreset } = attributes;

        if (orbitalTypographyPreset) {
            const existingClasses = props.className || '';
            const presetClasses = `has-type-preset has-type-preset-${orbitalTypographyPreset}`;
            props.className = (existingClasses + ' ' + presetClasses).trim();
            debugLog('Adding preset classes to frontend:', props.className);
        }

        return props;
    }

    addFilter(
        'blocks.registerBlockType',
        'orbital-editor-suite/add-preset-attribute',
        addTypographyPresetAttribute,
        20
    );

    addFilter(
        'editor.BlockEdit',
        'orbital-editor-suite/add-preset-control',
        withTypographyPresetControl,
        20  // Higher priority to ensure our controls show
    );

    addFilter(
        'editor.BlockListBlock',
        'orbital-editor-suite/add-preset-editor-class',
        addPresetClassToEditor,
        20
    );

    addFilter(
        'blocks.getSaveContent.extraProps',
        'orbital-editor-suite/add-preset-class',
        addPresetClassToSave,
        20
    );
})();

wp.domReady(function() {
    // Debug logging function
    function debugLog(...args) {
        const globalSettings = window.orbitalEditorSuiteGlobal?.settings || {};
        if (globalSettings.enable_debug) {
            console.log('[Typography Presets Debug]', ...args);
        }
    }

    debugLog('Setup complete - all filters registered by early IIFEs');
});
