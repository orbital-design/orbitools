/**
 * Content Width Controls - Control Registration
 *
 * Adds a "Content width" dropdown to the block toolbar of full-aligned blocks
 * that declare orbitools.contentWidth support — sitting alongside the core
 * alignment control. Options: Full (no constraint), Wide (only when the theme
 * specifies a wide size) and Standard (site content width). The block stays
 * full-bleed; only its inner content is constrained, composed per-block.
 *
 * Icons mirror core's alignment language (positionCenter / stretchWide /
 * stretchFullWidth). They're inlined as SVG rather than pulled from
 * @wordpress/icons because the wp-icons script handle isn't registered —
 * depending on it would silently drop this whole script.
 *
 * @since 1.0.0
 */

import { resolveContentWidth } from '../../../../core/utils/content-width.js';

(function () {
    var addFilter = wp.hooks.addFilter;
    var createHigherOrderComponent = wp.compose.createHigherOrderComponent;
    var Fragment = wp.element.Fragment;
    var createElement = wp.element.createElement;
    var BlockControls = wp.blockEditor.BlockControls;
    var ToolbarDropdownMenu = wp.components.ToolbarDropdownMenu;

    // Core alignment icon paths (24x24), reused so this reads as a sibling of
    // the native align control.
    var ICON_PATHS = {
        // stretchFullWidth
        full: 'M5 4h14v11H5V4Zm11 16H8v-1.5h8V20Z',
        // stretchWide
        wide: 'M16 5.5H8V4h8v1.5ZM16 20H8v-1.5h8V20ZM5 9h14v6H5V9Z',
        // positionCenter — full-width bars with a narrow centred block
        standard: 'M19 5.5H5V4h14v1.5ZM19 20H5v-1.5h14V20ZM7 9h10v6H7V9Z'
    };

    function icon(name) {
        return createElement(
            'svg',
            {
                width: 24,
                height: 24,
                viewBox: '0 0 24 24',
                xmlns: 'http://www.w3.org/2000/svg',
                fill: 'currentColor',
                'aria-hidden': 'true',
                focusable: 'false'
            },
            createElement('path', { d: ICON_PATHS[name] || ICON_PATHS.full })
        );
    }

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
    // specified. Independent of the block's own align support: a full-aligned
    // block can constrain its content to wide width even if it can't be
    // wide-aligned.
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

            function onChange(value) {
                props.setAttributes({ orbContentWidth: value || 'full' });
            }

            var options = [{ value: 'full', label: 'Full width' }];
            if (themeSpecifiesWideSize()) {
                options.push({ value: 'wide', label: 'Wide' });
            }
            options.push({ value: 'standard', label: 'Standard' });

            var controls = options.map(function (o) {
                return {
                    title: o.label,
                    icon: icon(o.value),
                    isActive: current === o.value,
                    role: 'menuitemradio',
                    onClick: function () { onChange(o.value); }
                };
            });

            return createElement(Fragment, null,
                createElement(BlockEdit, props),
                createElement(BlockControls, { group: 'block' },
                    createElement(ToolbarDropdownMenu, {
                        icon: icon(current),
                        label: 'Content width',
                        controls: controls
                    })
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
