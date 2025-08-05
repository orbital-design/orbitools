/**
 * Typography Presets - Editor Controls
 *
 * Adds the typography preset dropdown control to the block editor inspector
 */

(function() {
    const { addFilter } = wp.hooks;
    const { createHigherOrderComponent } = wp.compose;
    const { Fragment } = wp.element;
    const { InspectorControls } = wp.blockEditor;
    const { __experimentalToolsPanel: ToolsPanel, __experimentalToolsPanelItem: ToolsPanelItem, ComboboxControl } = wp.components;

    // Add inspector control - needs to be registered early
    const withTypographyPresetControl = createHigherOrderComponent(function(BlockEdit) {
        return function(props) {
            // Get data from localized script
            const { presets, settings, strings } = window.orbitoolsTypographyPresets || {};

            if (!presets || !settings) {
                return wp.element.createElement(BlockEdit, props);
            }

            // Check if we have any presets to work with
            const hasPresets = presets && Object.keys(presets).length > 0;

            // Define allowed blocks (with fallback)
            const allowedBlocks = settings.typography_allowed_blocks || [
                'core/paragraph', 'core/heading', 'core/post-title', 'core/list', 'core/quote', 'core/button'
            ];

            if (!allowedBlocks.includes(props.name)) {
                return wp.element.createElement(BlockEdit, props);
            }

            // Show control with "No Presets" option if none are available
            if (!hasPresets) {
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
                                    // Nothing to reset when no presets
                                }
                            },
                            wp.element.createElement(
                                ToolsPanelItem,
                                {
                                    hasValue: function() { return false; },
                                    label: 'Preset',
                                    onDeselect: function() {
                                        // Nothing to deselect when no presets
                                    },
                                    isShownByDefault: true
                                },
                                wp.element.createElement(
                                    'div',
                                    {
                                        style: {
                                            pointerEvents: 'none',
                                            opacity: '0.6'
                                        }
                                    },
                                    wp.element.createElement(ComboboxControl, {
                                        label: 'Preset',
                                        value: 'no-presets',
                                        options: [{ label: 'No Presets Available', value: 'no-presets' }],
                                        onChange: function() {
                                            // Prevent any changes when no presets
                                            return;
                                        },
                                        __nextHasNoMarginBottom: true,
                                        __next40pxDefaultSize: true
                                    })
                                ),

                                // Show help message in preview box style
                                wp.element.createElement(
                                    'div',
                                    {
                                        style: {
                                            background: '#f6f7f7',
                                            padding: '8px 12px',
                                            borderRadius: '4px',
                                            marginTop: '8px',
                                            fontSize: '13px',
                                            border: '1px solid #ddd',
                                            color: '#646970'
                                        }
                                    },
                                    strings.noPresetsFound || 'No typography presets found. Add presets to your theme.json file to use this feature.'
                                )
                            )
                        )
                    )
                );
            }

            const { attributes, setAttributes } = props;
            const { orbitoolsTypographyPreset } = attributes;

            const currentPreset = orbitoolsTypographyPreset && presets[orbitoolsTypographyPreset] ?
                presets[orbitoolsTypographyPreset] : null;

            /**
             * Convert presets object to array suitable for SelectControl
             */
            function getPresetsForSelect() {
                const options = [];

                // Group by group if enabled
                if (settings.typography_show_groups_in_dropdown) {
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
                    orbitoolsTypographyPreset: presetId || ''
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
                                hasValue: function() { return !!orbitoolsTypographyPreset; },
                                label: 'Preset',
                                onDeselect: function() {
                                    applyPresetToBlock(null, '', attributes, setAttributes);
                                },
                                isShownByDefault: true
                            },
                            wp.element.createElement(ComboboxControl, {
                                label: 'Preset',
                                value: orbitoolsTypographyPreset || '',
                                options: getPresetsForSelect(),
                                onChange: function(presetId) {
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
                                        className: `has-type-preset has-type-preset-${orbitoolsTypographyPreset}`,
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

    addFilter(
        'editor.BlockEdit',
        'orbitools/add-preset-control',
        withTypographyPresetControl,
        20  // Higher priority to ensure our controls show
    );
})();
