/**
 * Typography Presets - Editor Controls
 *
 * Adds the typography preset dropdown to the block editor's Typography panel.
 * The preset is responsive: the editor's screen-size preview toggle (Desktop /
 * Tablet / Mobile) decides which viewport you're picking a preset for, via the
 * shared ResponsiveControl framework. Storage is an object keyed by breakpoint
 * slug ({ base, tablet, mobile }); a legacy bare-string value is read as the
 * base preset.
 */

import { ResponsiveControl, ResponsiveDots } from '../../../../core/utils/responsive-control.js';

(function() {
    const { addFilter } = wp.hooks;
    const { createHigherOrderComponent } = wp.compose;
    const { Fragment, createElement } = wp.element;
    const { InspectorControls } = wp.blockEditor;
    const { __experimentalToolsPanelItem: ToolsPanelItem, ComboboxControl } = wp.components;

    /** Normalise the stored value to an object keyed by breakpoint slug. */
    function toResponsive(raw) {
        if (raw && typeof raw === 'object') {
            return raw;
        }
        return raw ? { base: raw } : {};
    }

    /** True when any viewport has a preset. */
    function hasAnyPreset(value) {
        const v = toResponsive(value);
        return Object.keys(v).some(function(slug) { return !!v[slug]; });
    }

    // Add inspector control - needs to be registered early
    const withTypographyPresetControl = createHigherOrderComponent(function(BlockEdit) {
        return function(props) {
            // Get data from localized script
            const { presets, settings, strings } = window.orbitoolsTypographyPresets || {};

            if (!presets || !settings) {
                return createElement(BlockEdit, props);
            }

            const hasPresets = presets && Object.keys(presets).length > 0;

            const allowedBlocks = settings.typography_allowed_blocks;
            if (!Array.isArray(allowedBlocks) || allowedBlocks.length === 0) {
                return createElement(BlockEdit, props);
            }
            if (!allowedBlocks.includes(props.name)) {
                return createElement(BlockEdit, props);
            }

            // Show control with "No Presets" option if none are available
            if (!hasPresets) {
                return createElement(
                    Fragment,
                    {},
                    createElement(BlockEdit, props),
                    createElement(
                        InspectorControls,
                        { group: 'typography' },
                        createElement(
                            ToolsPanelItem,
                            {
                                hasValue: function() { return false; },
                                label: 'Preset',
                                onDeselect: function() {},
                                resetAllFilter: function() { return {}; },
                                panelId: props.clientId,
                                isShownByDefault: true
                            },
                            createElement(
                                'div',
                                { style: { pointerEvents: 'none', opacity: '0.6' } },
                                createElement(ComboboxControl, {
                                    label: 'Preset',
                                    value: 'no-presets',
                                    options: [{ label: 'No Presets Available', value: 'no-presets' }],
                                    onChange: function() { return; },
                                    __nextHasNoMarginBottom: true,
                                    __next40pxDefaultSize: true
                                })
                            ),
                            createElement(
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
                );
            }

            const { attributes, setAttributes } = props;
            const presetValue = toResponsive(attributes.orbitoolsTypographyPreset);

            function getValue(slug) {
                return presetValue[slug] || '';
            }

            function setValue(slug, presetId) {
                const updated = Object.assign({}, presetValue);
                if (presetId && presets[presetId]) {
                    updated[slug] = presetId;
                } else {
                    delete updated[slug];
                }
                setAttributes({
                    orbitoolsTypographyPreset: Object.keys(updated).length ? updated : ''
                });
            }

            /** Convert presets object to options for the combobox. */
            function getPresetsForSelect() {
                const options = [];

                if (settings.typography_show_groups_in_dropdown) {
                    const grouped = {};
                    Object.keys(presets).forEach(function(id) {
                        const preset = presets[id];
                        const group = preset.group || 'other';
                        if (!grouped[group]) { grouped[group] = []; }
                        grouped[group].push({ label: preset.label, value: id });
                    });
                    Object.keys(grouped).forEach(function(group) {
                        options.push({
                            label: '--- ' + group.charAt(0).toUpperCase() + group.slice(1) + ' ---',
                            value: '',
                            disabled: true
                        });
                        options.push.apply(options, grouped[group]);
                    });
                } else {
                    Object.keys(presets).forEach(function(id) {
                        const preset = presets[id];
                        options.push({ label: preset.label, value: id });
                    });
                }

                return options;
            }

            /** Preview box for the active viewport's preset. */
            function renderPreview(presetId) {
                const preset = presetId && presets[presetId] ? presets[presetId] : null;
                if (!preset) {
                    return null;
                }
                return createElement(
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
                    createElement(
                        'div',
                        {
                            className: 'has-type-preset has-type-preset-' + presetId,
                            style: { margin: '0 0 4px 0', color: '#1e1e1e', whiteSpace: 'nowrap', overflow: 'hidden' }
                        },
                        preset.label
                    ),
                    createElement(
                        'div',
                        { style: { fontSize: '11px', color: '#757575', fontFamily: 'monospace' } },
                        Object.keys(preset.properties).map(function(prop) {
                            let value = preset.properties[prop];
                            if (prop === 'font-family' && String(value).startsWith('var(')) {
                                value = preset.label.split(' • ')[0];
                            }
                            return createElement(
                                'div',
                                { key: prop, style: { whiteSpace: 'nowrap', overflow: 'hidden' } },
                                prop + ': ' + value
                            );
                        })
                    )
                );
            }

            const labelWithDots = createElement(
                'span',
                {
                    className: 'orbitools-responsive-label',
                    style: { display: 'inline-flex', alignItems: 'center', gap: '6px' }
                },
                'Preset',
                createElement(ResponsiveDots, { value: presetValue })
            );

            return createElement(
                Fragment,
                {},
                createElement(BlockEdit, props),
                createElement(
                    InspectorControls,
                    { group: 'typography' },
                    createElement(
                        ToolsPanelItem,
                        {
                            hasValue: function() { return hasAnyPreset(attributes.orbitoolsTypographyPreset); },
                            label: 'Preset',
                            onDeselect: function() { setAttributes({ orbitoolsTypographyPreset: '' }); },
                            resetAllFilter: function() { return { orbitoolsTypographyPreset: undefined }; },
                            panelId: props.clientId,
                            isShownByDefault: true
                        },
                        createElement(ResponsiveControl, {
                            blockName: props.name,
                            wrap: false,
                            render: function(ctx) {
                                const slug = ctx.slug;
                                const currentId = getValue(slug);
                                const preset = currentId && presets[currentId] ? presets[currentId] : null;
                                return createElement(
                                    Fragment,
                                    {},
                                    createElement(ComboboxControl, {
                                        label: labelWithDots,
                                        value: currentId,
                                        options: getPresetsForSelect(),
                                        onChange: function(presetId) {
                                            setValue(slug, presetId && presets[presetId] ? presetId : '');
                                        },
                                        help: preset ? preset.description : 'Choose a typography preset to apply consistent styling.',
                                        __nextHasNoMarginBottom: true,
                                        __next40pxDefaultSize: true
                                    }),
                                    renderPreview(currentId)
                                );
                            }
                        })
                    )
                )
            );
        };
    }, 'withTypographyPresetControl');

    addFilter(
        'editor.BlockEdit',
        'orbitools/add-preset-control',
        withTypographyPresetControl,
        5  // Very early priority to appear first in Typography panel
    );
})();
