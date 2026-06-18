/**
 * Content Width Controls - Control Registration
 *
 * Adds a "Content Width" control to full-aligned blocks that declare
 * orbitools.contentWidth support. Options: Full (no constraint), Wide
 * (only when the block also supports wide alignment) and Standard
 * (site content width). The block stays full-bleed; only its inner
 * content is constrained — the wrapper itself is composed per-block.
 *
 * @since 1.0.0
 */

import { resolveContentWidth } from '../../../../core/utils/content-width.js';

(function () {
    var addFilter = wp.hooks.addFilter;
    var createHigherOrderComponent = wp.compose.createHigherOrderComponent;
    var Fragment = wp.element.Fragment;
    var createElement = wp.element.createElement;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var ToolsPanel = wp.components.__experimentalToolsPanel;
    var ToolsPanelItem = wp.components.__experimentalToolsPanelItem;
    var ToggleGroupControl = wp.components.__experimentalToggleGroupControl;
    var ToggleGroupControlOption = wp.components.__experimentalToggleGroupControlOption;

    function blockHasContentWidthSupport(blockName) {
        var blockType = wp.blocks.getBlockType(blockName);
        if (!blockType || !blockType.supports || !blockType.supports.orbitools) {
            return false;
        }
        return !!blockType.supports.orbitools.contentWidth;
    }

    // The Wide option only makes sense when the theme actually defines a wide
    // size in theme.json (settings.layout.wideSize). WordPress fills a default
    // server-side, but the raw editor settings reflect what the theme truly
    // specified — so read it from there. Independent of the block's own align
    // support: a full-aligned block can constrain its content to wide width
    // even if the block itself can't be wide-aligned.
    function themeSpecifiesWideSize() {
        try {
            var settings = wp.data.select('core/block-editor').getSettings();
            var layout = settings && settings.__experimentalFeatures && settings.__experimentalFeatures.layout;
            return !!(layout && layout.wideSize);
        } catch (e) {
            return false;
        }
    }

    var withContentWidthControls = createHigherOrderComponent(function (BlockEdit) {
        return function (props) {
            // Only full-aligned, supporting blocks get the control — at any
            // other alignment the block is already at that width.
            if (!blockHasContentWidthSupport(props.name) || props.attributes.align !== 'full') {
                return createElement(BlockEdit, props);
            }

            var current = resolveContentWidth(props.attributes);
            var showWide = themeSpecifiesWideSize();

            function onChange(value) {
                props.setAttributes({ orbContentWidth: value || 'full' });
            }

            var options = [
                createElement(ToggleGroupControlOption, { value: 'full', label: 'Full' })
            ];
            if (showWide) {
                options.push(
                    createElement(ToggleGroupControlOption, { value: 'wide', label: 'Wide' })
                );
            }
            options.push(
                createElement(ToggleGroupControlOption, { value: 'standard', label: 'Standard' })
            );

            var panelId = 'orbitools-content-width-panel';

            return createElement(Fragment, null,
                createElement(BlockEdit, props),
                createElement(InspectorControls, { group: 'settings' },
                    createElement(ToolsPanel, {
                        label: 'Width',
                        resetAll: function () { onChange('full'); },
                        panelId: panelId
                    },
                        createElement(ToolsPanelItem, {
                            hasValue: function () { return current !== 'full'; },
                            label: 'Content Width',
                            onDeselect: function () { onChange('full'); },
                            isShownByDefault: true,
                            panelId: panelId
                        },
                            createElement(ToggleGroupControl, {
                                label: 'Content Width',
                                help: 'Keep the background full-width while constraining the content.',
                                value: current,
                                onChange: onChange,
                                isBlock: true,
                                __next40pxDefaultSize: true,
                                __nextHasNoMarginBottom: true
                            }, options)
                        )
                    )
                )
            );
        };
    }, 'withContentWidthControls');

    addFilter(
        'editor.BlockEdit',
        'orbitools/with-content-width-controls',
        withContentWidthControls,
        20
    );
})();
