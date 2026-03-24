import { InnerBlocks } from '@wordpress/block-editor';

/**
 * Group block save function
 *
 * For dynamic blocks with inner blocks, we still need to save the inner blocks content
 * so the server-side render_callback can access them.
 */
export default function save() {
	return <InnerBlocks.Content />;
}
