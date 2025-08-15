import { InnerBlocks } from '@wordpress/block-editor';

/**
 * Marquee block save function
 *
 * For PHP-rendered blocks, we only need to save the inner blocks content.
 * The PHP render callback handles all wrapper structure and styling.
 */
export default function save() {
    return <InnerBlocks.Content />;
}
