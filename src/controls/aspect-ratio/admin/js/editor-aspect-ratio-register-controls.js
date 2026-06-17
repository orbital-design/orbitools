/**
 * Aspect Ratio Controls - Control Registration
 *
 * Adds responsive aspect ratio controls to blocks with
 * orbitools.aspectRatio support. The responsive behaviour is provided
 * by the shared ResponsiveControl framework: the editor's native
 * screen-size preview toggle (Desktop / Tablet / Mobile) decides which
 * viewport's value you're editing, so there's no bespoke tab bar here —
 * just the SelectControl for the active device.
 *
 * @since 1.4.0
 */

import { ResponsiveControl, ResponsiveDots } from '../../../../core/utils/responsive-control.js';

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
    var InspectorControls = wp.blockEditor.InspectorControls;
    var SelectControl = wp.components.SelectControl;

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
     *
     * Thin wrapper over the ResponsiveControl framework. getValue /
     * setValue preserve the existing attribute shape — an object keyed
     * by breakpoint slug ({ base, tablet, mobile }).
     */
    function AspectRatioControl(props) {
        var aspectRatio = props.aspectRatio;
        var onAspectRatioChange = props.onAspectRatioChange;
        var blockName = props.blockName;

        var config = getBlockAspectRatioConfig(blockName);
        var ratios = config.ratios || [];

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

        return createElement(ResponsiveControl, {
            title: 'Aspect Ratio',
            blockName: blockName,
            initialOpen: false,
            indicator: createElement(ResponsiveDots, { value: aspectRatio }),
            render: function(ctx) {
                return createElement(SelectControl, {
                    value: getValue(ctx.slug),
                    options: selectOptions,
                    onChange: function(value) { setValue(ctx.slug, value); },
                    __next40pxDefaultSize: true,
                    __nextHasNoMarginBottom: true
                });
            }
        });
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
                createElement(InspectorControls, { group: 'styles' },
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
