/**
 * Group Block Registration
 *
 * Registers the Group block with WordPress, providing a flexible container
 * for organizing other blocks with various layout options.
 *
 * @file blocks/group/index.tsx
 * @since 1.0.0
 */

import { registerBlockType } from '@wordpress/blocks';
import { group as icon } from '@wordpress/icons';

// Import block components and metadata
import Edit from './edit';
import save from './save';
import variations from './variations';
import metadata from './block.json';

// Styles
import './index.scss';

/**
 * Register the Group block
 */
registerBlockType(metadata.name as 'orb/group', {
    ...metadata,
    icon,
    edit: Edit,
    save,
    variations,
} as any);
