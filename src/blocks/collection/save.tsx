import { InnerBlocks } from '@wordpress/block-editor';

/**
 * Collection block save function
 *
 * For dynamic blocks with inner blocks, we still need to save the inner blocks content
 * while using server-side rendering for the wrapper element.
 */
export default function save() {
    return <InnerBlocks.Content />;
}
