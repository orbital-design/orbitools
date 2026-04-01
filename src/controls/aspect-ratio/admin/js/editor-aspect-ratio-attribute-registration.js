/**
 * Aspect Ratio Controls - Attribute Registration
 *
 * Registers orbAspectRatio attribute on blocks with orbitools.aspectRatio support.
 * Also disables core dimensions.aspectRatio to prevent conflicting controls.
 *
 * @since 1.4.0
 */

wp.hooks.addFilter(
    'blocks.registerBlockType',
    'orbitools/add-aspect-ratio-attributes',
    function(settings, name) {
        // Check if block has orbitools aspectRatio support
        if (!settings.supports || !settings.supports.orbitools || !settings.supports.orbitools.aspectRatio) {
            return settings;
        }

        var arSupports = settings.supports.orbitools.aspectRatio;

        if (arSupports === false || (typeof arSupports === 'object' && Object.keys(arSupports).length === 0)) {
            return settings;
        }

        // Add aspect ratio attribute
        settings.attributes = Object.assign({}, settings.attributes, {
            orbAspectRatio: {
                type: 'object',
                default: {}
            }
        });

        // Disable core aspect ratio control to prevent conflict
        if (settings.supports && settings.supports.dimensions) {
            settings.supports = Object.assign({}, settings.supports, {
                dimensions: Object.assign({}, settings.supports.dimensions, {
                    aspectRatio: false
                })
            });
        }

        return settings;
    },
    1 // Early priority
);
