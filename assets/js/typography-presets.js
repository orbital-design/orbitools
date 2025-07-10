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
            
            if (!presets || !settings || !settings.enabled) {
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
            function applyPresetToBlock(preset, presetId, attributes, setAttributes) {
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
                    orbitalTypographyPreset: presetId || '',
                    style: newStyle,
                    className: `${attributes.className || ''} orbital-preset-${presetId || ''}`.trim()
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
                            label: 'Typography Presets',
                            resetAll: function() {
                                applyPresetToBlock(null, '', attributes, setAttributes);
                            }
                        },
                        wp.element.createElement(
                            ToolsPanelItem,
                            {
                                hasValue: function() { return !!orbitalTypographyPreset; },
                                label: 'Typography Preset',
                                onDeselect: function() {
                                    applyPresetToBlock(null, '', attributes, setAttributes);
                                },
                                isShownByDefault: true
                            },
                            wp.element.createElement(SelectControl, {
                                label: 'Typography Preset',
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

    // Add custom attribute registration
    function addTypographyPresetAttribute(settings, name) {
        // Get data from localized script
        const { settings: moduleSettings } = window.orbitalTypographyPresets || {};
        
        if (!moduleSettings || !moduleSettings.enabled) {
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
