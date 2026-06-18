/**
 * Content Width Controls - Attribute Registration
 *
 * Registers the orbContentWidth attribute on blocks that declare
 * orbitools.contentWidth support. Stored as a string: 'full' (default,
 * no constraint), 'wide', or 'standard'.
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
                default: 'full'
            }
        });

        return settings;
    },
    1 // Early priority
);
