/**
 * Content Width Controls - Attribute Registration
 *
 * Registers the orbContentWidth attribute on blocks that declare
 * orbitools.contentWidth support. Stored as a string: 'full' (no
 * constraint), 'wide', or 'standard'.
 *
 * The default is empty (not 'full') on purpose: an explicit 'full' default
 * would shadow the legacy restrictContentWidth boolean in the resolver, so
 * old blocks would look unconstrained in the editor while the (block.json-
 * less) server render still honoured the legacy flag. Empty lets the
 * resolver fall through to the back-compat path in both places.
 *
 * @since 1.0.0
 */

wp.hooks.addFilter(
    'blocks.registerBlockType',
    'orbitools/add-content-width-attributes',
    function (settings) {
        if (
            !settings.supports ||
            !settings.supports.orbitools ||
            !settings.supports.orbitools.contentWidth
        ) {
            return settings;
        }

        settings.attributes = Object.assign({}, settings.attributes, {
            orbContentWidth: {
                type: 'string',
                default: ''
            }
        });

        return settings;
    },
    1 // Early priority
);
