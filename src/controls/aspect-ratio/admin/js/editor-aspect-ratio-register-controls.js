/**
 * Aspect Ratio Controls - Control Registration
 *
 * Adds responsive aspect ratio controls to blocks with orbitools.aspectRatio support.
 * PanelBody with breakpoint tabs, select control per active tab.
 *
 * @since 1.4.0
 */

import { getBreakpointOptions } from '../../../../core/utils/breakpoints.js';

(function() {
    function getBlockAspectRatioConfig(blockName) {
        var configData = window.orbitoolsAspectRatioConfig || {};
        if (configData[blockName]) {
            return configData[blockName];
        }
        return {
            ratios: [],
            breakpoints: [],
            supports: { enabled: false, breakpoints: false }
        };
    }

    var addFilter = wp.hooks.addFilter;
    var createHigherOrderComponent = wp.compose.createHigherOrderComponent;
    var Fragment = wp.element.Fragment;
    var createElement = wp.element.createElement;
    var useState = wp.element.useState;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var PanelBody = wp.components.PanelBody;
    var SelectControl = wp.components.SelectControl;
    var Tooltip = wp.components.Tooltip;

    function blockHasAspectRatioSupport(blockName) {
        var blockType = wp.blocks.getBlockType(blockName);
        if (!blockType || !blockType.supports || !blockType.supports.orbitools) {
            return null;
        }
        var arSupports = blockType.supports.orbitools.aspectRatio;
        if (!arSupports || arSupports === false) {
            return null;
        }
        return arSupports;
    }

    /**
     * Aspect Ratio Control Component
     */
    function AspectRatioControl(props) {
        var aspectRatio = props.aspectRatio;
        var onAspectRatioChange = props.onAspectRatioChange;
        var blockName = props.blockName;

        var breakpoints = getBreakpointOptions(blockName);
        var config = getBlockAspectRatioConfig(blockName);
        var ratios = config.ratios || [];

        // Read raw breakpoints from theme config for base entry and icons
        var themeConfig = window.orbitoolsThemeConfig || {};
        var rawBreakpoints = (themeConfig.settings && themeConfig.settings.breakpoints) || [];
        var baseEntry = null;
        rawBreakpoints.forEach(function(bp) {
            if (bp.slug === 'base') baseEntry = bp;
        });

        // Build all tabs
        var allBreakpoints = [{
            slug: 'base',
            label: (baseEntry && baseEntry.name) || 'All',
            tooltip: (baseEntry && baseEntry.name) || 'All Screens',
            icon: (baseEntry && baseEntry.icon) || null
        }];
        breakpoints.forEach(function(bp) {
            allBreakpoints.push({
                slug: bp.slug,
                label: bp.slug.toUpperCase(),
                tooltip: bp.name || bp.slug,
                icon: bp.icon || null
            });
        });

        // Active tab — default to base
        var tabState = useState('base');
        var activeTab = tabState[0];
        var setActiveTab = tabState[1];

        // Select options
        var selectOptions = [{ label: '— None —', value: '' }];
        ratios.forEach(function(ratio) {
            selectOptions.push({ label: ratio.name, value: ratio.slug });
        });

        function getValue(slug) {
            return (aspectRatio && aspectRatio[slug]) || '';
        }

        function setValue(slug, value) {
            var updated = Object.assign({}, aspectRatio || {});
            if (value) {
                updated[slug] = value;
            } else {
                delete updated[slug];
            }
            onAspectRatioChange(updated);
        }

        // Active tab data for label
        var activeTabData = null;
        allBreakpoints.forEach(function(bp) {
            if (bp.slug === activeTab) activeTabData = bp;
        });
        var activeLabel = activeTabData
            ? (activeTabData.slug === 'base'
                ? activeTabData.tooltip
                : activeTabData.tooltip + '+')
            : '';

        return createElement(PanelBody, {
            title: 'Aspect Ratio',
            initialOpen: false
        },
            // Tab bar
            createElement('div', {
                style: {
                    display: 'flex',
                    gap: '4px',
                    marginBottom: '12px',
                    padding: '4px',
                    background: '#F3F5F7',
                    borderRadius: '6px'
                }
            },
                allBreakpoints.map(function(bp) {
                    var isActive = activeTab === bp.slug;

                    return createElement(Tooltip, {
                        key: bp.slug,
                        text: bp.tooltip
                    },
                        createElement('button', {
                            onClick: function() { setActiveTab(bp.slug); },
                            style: {
                                flex: 1,
                                display: 'flex',
                                alignItems: 'center',
                                justifyContent: 'center',
                                padding: '6px 4px',
                                border: 'none',
                                borderRadius: '3px',
                                background: isActive ? '#fff' : 'transparent',
                                boxShadow: isActive ? '0 1px 3px rgba(0,0,0,0.1)' : 'none',
                                color: isActive ? '#1e1e1e' : '#757575',
                                cursor: 'pointer',
                                fontSize: '11px',
                                fontWeight: isActive ? 600 : 400,
                                lineHeight: '1'
                            }
                        },
                            bp.icon
                                ? createElement('span', {
                                    dangerouslySetInnerHTML: { __html: bp.icon },
                                    style: { display: 'flex', alignItems: 'center', width: '20px', height: '20px' }
                                })
                                : bp.label
                        )
                    );
                })
            ),
            // Label + select for active tab
            createElement('div', { style: { paddingTop: '8px' } },
                createElement('label', {
                    style: {
                        display: 'block',
                        fontSize: '11px',
                        fontWeight: 500,
                        textTransform: 'uppercase',
                        color: '#757575',
                        marginBottom: '4px'
                    }
                }, activeLabel),
                createElement(SelectControl, {
                    value: getValue(activeTab),
                    options: selectOptions,
                    onChange: function(value) { setValue(activeTab, value); },
                    __next40pxDefaultSize: true,
                    __nextHasNoMarginBottom: true
                })
            )
        );
    }

    /**
     * HOC to add aspect ratio controls to blocks
     */
    var withAspectRatioControls = createHigherOrderComponent(function(BlockEdit) {
        return function(props) {
            var supports = blockHasAspectRatioSupport(props.name);
            if (!supports) {
                return createElement(BlockEdit, props);
            }

            var orbAspectRatio = props.attributes.orbAspectRatio || {};

            function onAspectRatioChange(newValue) {
                props.setAttributes({ orbAspectRatio: newValue });
            }

            return createElement(Fragment, null,
                createElement(BlockEdit, props),
                createElement(InspectorControls, null,
                    createElement(AspectRatioControl, {
                        aspectRatio: orbAspectRatio,
                        onAspectRatioChange: onAspectRatioChange,
                        blockName: props.name
                    })
                )
            );
        };
    }, 'withAspectRatioControls');

    addFilter(
        'editor.BlockEdit',
        'orbitools/with-aspect-ratio-controls',
        withAspectRatioControls,
        20
    );
})();
