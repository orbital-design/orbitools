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

wp.domReady(function() {
    // Get localized data first
    const { presets, categories, settings, strings } = window.orbitalTypographyPresets || {};
    
    // Debug logging function - check global debug setting
    function debugLog(...args) {
        const globalSettings = window.orbitalEditorSuiteGlobal?.settings || {};
        if (globalSettings.enable_debug) {
            console.log('[Typography Presets Debug]', ...args);
        }
    }
    
    function debugWarn(...args) {
        const globalSettings = window.orbitalEditorSuiteGlobal?.settings || {};
        if (globalSettings.enable_debug) {
            console.warn('[Typography Presets Debug]', ...args);
        }
    }
    
    function debugError(...args) {
        const globalSettings = window.orbitalEditorSuiteGlobal?.settings || {};
        if (globalSettings.enable_debug) {
            console.error('[Typography Presets Debug]', ...args);
        }
    }
    
    debugLog('Script loading...');

    if (!wp.hooks || !wp.compose || !wp.element || !wp.blockEditor || !wp.components) {
        debugError('Missing WordPress dependencies');
        return;
    }

    const { addFilter, removeFilter } = wp.hooks;
    const { createHigherOrderComponent } = wp.compose;
    const { Fragment, useState } = wp.element;
    const { InspectorControls } = wp.blockEditor;
    const { __experimentalToolsPanel: ToolsPanel, __experimentalToolsPanelItem: ToolsPanelItem, SelectControl } = wp.components;

    debugLog('All dependencies loaded');

    // Debug: List all registered filters
    debugLog('Checking existing filters...');
    if (wp.hooks.filters && wp.hooks.filters['blocks.registerBlockType']) {
        debugLog('blocks.registerBlockType filters:', wp.hooks.filters['blocks.registerBlockType']);
    }
    if (wp.hooks.filters && wp.hooks.filters['editor.BlockEdit']) {
        debugLog('editor.BlockEdit filters:', wp.hooks.filters['editor.BlockEdit']);
    }

    // Debug logging
    debugLog('Data loaded:', {
        hasData: !!window.orbitalTypographyPresets,
        presetsCount: presets ? Object.keys(presets).length : 0,
        settings,
        enabled: settings?.enabled
    });

    if (!presets || !settings || !settings.enabled) {
        debugLog('Not enabled or missing data');
        return;
    }

    // Define which blocks should have the preset control
    let allowedBlocks = settings.allowed_blocks || [
        'core/paragraph',
        'core/heading',
        'core/list',
        'core/quote',
        'core/button'
    ];

    // Ensure allowedBlocks is always an array
    if (!Array.isArray(allowedBlocks)) {
        debugWarn('allowed_blocks is not an array, using defaults');
        allowedBlocks = [
            'core/paragraph',
            'core/heading',
            'core/list',
            'core/quote',
            'core/button'
        ];
    }

    debugLog('Allowed blocks:', allowedBlocks);

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

    // Add custom attribute
    function addTypographyPresetAttribute(settings, name) {
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

    // Add inspector control
    const withTypographyPresetControl = createHigherOrderComponent(function(BlockEdit) {
        return function(props) {
            if (!allowedBlocks.includes(props.name)) {
                return wp.element.createElement(BlockEdit, props);
            }

            debugLog('Adding controls for', props.name);

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
            debugLog('Adding class to frontend:', props.className);
        }

        return props;
    }

    /**
     * Remove core typography controls if setting is enabled
     */
    debugLog('replace_core_controls setting:', settings.replace_core_controls);
    
    if (settings.replace_core_controls) {
        debugLog('Removing core typography controls');

        // Define typography controls to remove for allowed blocks
        const typographyControlsToRemove = [
            'fontSize', 'fontFamily', 'fontWeight', 'lineHeight',
            'letterSpacing', 'textDecoration', 'textTransform',
            '__experimentalFontFamily', '__experimentalFontSize',
            '__experimentalFontWeight', '__experimentalLineHeight',
            '__experimentalLetterSpacing', '__experimentalTextDecoration',
            '__experimentalTextTransform', '__experimentalWritingMode'
        ];

        /**
         * Applies typography control restrictions to block settings
         */
        function removeTypographyControls(settings, blockName) {
            debugLog('fn: removeTypographyControls')
            if (!settings || !blockName || !allowedBlocks.includes(blockName)) {
                return settings;
            }

            debugLog('Removing typography controls from', blockName);

            if (!settings.supports) {
                settings.supports = {};
            }
debugLog('typography supports:', settings.supports.typography)
            // Remove individual typography controls
            if (settings.supports.typography) {
                typographyControlsToRemove.forEach(control => {
                    if (settings.supports.typography[control] !== undefined) {
                        settings.supports.typography[control] = false;
                    }
                });
            }

            // Also remove top-level typography supports
            typographyControlsToRemove.forEach(control => {
                if (settings.supports[control] !== undefined) {
                    settings.supports[control] = false;
                }
            });

            return settings;
        }

        debugLog('Core typography removal handled by early filter');
    }

    // Register all filters
    debugLog('Registering filters');

    addFilter(
        'blocks.registerBlockType',
        'orbital-editor-suite/add-preset-attribute',
        addTypographyPresetAttribute,
        20  // Higher priority than core typography removal
    );

    addFilter(
        'editor.BlockEdit',
        'orbital-editor-suite/add-preset-control',
        withTypographyPresetControl,
        20  // Higher priority to ensure our controls show
    );

    addFilter(
        'blocks.getSaveContent.extraProps',
        'orbital-editor-suite/add-preset-class',
        addPresetClassToSave,
        20
    );

    debugLog('Setup complete');
});
