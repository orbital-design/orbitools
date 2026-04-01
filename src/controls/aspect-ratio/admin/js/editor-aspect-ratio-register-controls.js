/**
 * Aspect Ratio Controls - Control Registration
 *
 * Adds responsive aspect ratio controls to blocks with orbitools.aspectRatio support.
 * ToolsPanel menu adds/removes breakpoints as tabs. Tabs switch the active select.
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
    var useEffect = wp.element.useEffect;
    var useCallback = wp.element.useCallback;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var ToolsPanel = wp.components.__experimentalToolsPanel;
    var ToolsPanelItem = wp.components.__experimentalToolsPanelItem;
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
     * Tiny component rendered inside each ToolsPanelItem.
     * On mount: registers its breakpoint slug as visible.
     * On unmount: unregisters it (user toggled it off via kebab menu).
     */
    function BreakpointRegistrar(props) {
        useEffect(function() {
            props.onRegister(props.slug);
            return function() {
                props.onUnregister(props.slug);
            };
        }, [props.slug]);
        return null;
    }

    /**
     * Aspect Ratio Control Component
     */
    function AspectRatioControl(props) {
        var aspectRatio = props.aspectRatio;
        var onAspectRatioChange = props.onAspectRatioChange;
        var blockName = props.blockName;

        var breakpoints = getBreakpointOptions(blockName); // already filtered, no base
        var config = getBlockAspectRatioConfig(blockName);
        var ratios = config.ratios || [];

        // Read raw breakpoints from theme config to find 'base' entry with name/icon
        var themeConfig = window.orbitoolsThemeConfig || {};
        var rawBreakpoints = (themeConfig.settings && themeConfig.settings.breakpoints) || [];
        var baseEntry = null;
        rawBreakpoints.forEach(function(bp) {
            if (bp.slug === 'base') baseEntry = bp;
        });
        var responsiveBreakpoints = breakpoints;

        // Build all tabs: base + responsive
        // label = short text for the tab, menuLabel = friendly name for the ToolsPanel menu
        var allBreakpoints = [{
            slug: 'base',
            label: (baseEntry && baseEntry.name) || 'All',
            menuLabel: (baseEntry && baseEntry.name) || 'All Screens',
            tooltip: (baseEntry && baseEntry.name) || 'All Screens',
            icon: (baseEntry && baseEntry.icon) || null
        }];
        responsiveBreakpoints.forEach(function(bp) {
            allBreakpoints.push({
                slug: bp.slug,
                label: bp.slug.toUpperCase(),
                menuLabel: bp.name || bp.slug,
                tooltip: bp.name || bp.slug,
                icon: bp.icon || null
            });
        });

        // Track which breakpoints are visible (toggled on via kebab menu)
        var visibleState = useState({});
        var visibleBreakpoints = visibleState[0];
        var setVisibleBreakpoints = visibleState[1];

        // Active tab
        var tabState = useState(null);
        var activeTab = tabState[0];
        var setActiveTab = tabState[1];

        // Register/unregister callbacks
        var registerBreakpoint = useCallback(function(slug) {
            setVisibleBreakpoints(function(prev) {
                var next = Object.assign({}, prev);
                next[slug] = true;
                return next;
            });
            // Auto-select first tab added
            setActiveTab(function(current) {
                return current || slug;
            });
        }, []);

        var unregisterBreakpoint = useCallback(function(slug) {
            setVisibleBreakpoints(function(prev) {
                var next = Object.assign({}, prev);
                delete next[slug];
                return next;
            });
            // If removing active tab, switch to first remaining
            setActiveTab(function(current) {
                if (current === slug) {
                    return null; // will be resolved below
                }
                return current;
            });
        }, []);

        // Get ordered list of visible tabs
        var visibleTabs = allBreakpoints.filter(function(bp) {
            return visibleBreakpoints[bp.slug];
        });

        // Ensure activeTab is valid
        if (visibleTabs.length > 0 && (!activeTab || !visibleBreakpoints[activeTab])) {
            // Can't call setState during render, so we'll handle this in the UI
        }
        var effectiveActiveTab = (activeTab && visibleBreakpoints[activeTab]) ? activeTab : (visibleTabs.length > 0 ? visibleTabs[0].slug : null);

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

        function hasValue(slug) {
            return aspectRatio && aspectRatio[slug] && aspectRatio[slug] !== '';
        }

        // Tab bar (only shows when we have visible tabs)
        var tabBar = visibleTabs.length > 0 ? createElement('div', {
            style: {
                display: 'flex',
                borderRadius: '2px',
                border: '1px solid #e0e0e0',
                overflow: 'hidden',
                marginBottom: '12px'
            }
        },
            visibleTabs.map(function(tab, i) {
                var isActive = effectiveActiveTab === tab.slug;

                return createElement(Tooltip, {
                    key: tab.slug,
                    text: tab.tooltip
                },
                    createElement('button', {
                        onClick: function() { setActiveTab(tab.slug); },
                        style: {
                            flex: 1,
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'center',
                            padding: '6px 0',
                            border: 'none',
                            borderRight: i < visibleTabs.length - 1 ? '1px solid #e0e0e0' : 'none',
                            background: isActive ? '#1e1e1e' : 'transparent',
                            color: isActive ? '#fff' : '#1e1e1e',
                            cursor: 'pointer',
                            fontSize: '11px',
                            fontWeight: isActive ? 600 : 400,
                            lineHeight: '1',
                            minHeight: '28px'
                        }
                    },
                        tab.icon
                            ? createElement('span', {
                                dangerouslySetInnerHTML: { __html: tab.icon },
                                style: { display: 'flex', alignItems: 'center', width: '20px', height: '20px' }
                            })
                            : tab.label
                    )
                );
            })
        ) : null;

        // Select for active tab
        var selectControl = effectiveActiveTab ? createElement(SelectControl, {
            value: getValue(effectiveActiveTab),
            options: selectOptions,
            onChange: function(value) { setValue(effectiveActiveTab, value); },
            __next40pxDefaultSize: true,
            __nextHasNoMarginBottom: true
        }) : null;

        return createElement(ToolsPanel, {
            label: 'Aspect Ratio',
            resetAll: function() { onAspectRatioChange({}); },
            panelId: 'aspect-ratio-panel'
        },
            // ToolsPanelItems — populate the kebab menu, hidden visually
            createElement('div', { style: { display: 'contents' } },
                allBreakpoints.map(function(bp) {
                    return createElement(ToolsPanelItem, {
                        key: bp.slug,
                        hasValue: function() { return hasValue(bp.slug); },
                        onDeselect: function() { setValue(bp.slug, ''); },
                        label: bp.menuLabel,
                        isShownByDefault: false,
                        panelId: 'aspect-ratio-panel',
                        style: { display: 'none' }
                    },
                        createElement(BreakpointRegistrar, {
                            slug: bp.slug,
                            onRegister: registerBreakpoint,
                            onUnregister: unregisterBreakpoint
                        })
                    );
                })
            ),
            // Visible UI: tab bar + select (rendered outside the hidden items)
            visibleTabs.length > 0 ? createElement('div', {
                style: { gridColumn: '1 / -1' }
            }, tabBar, selectControl) : null
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
