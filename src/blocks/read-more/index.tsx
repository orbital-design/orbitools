/**
 * Read More Block Registration
 *
 * Registers the read more block with WordPress block editor.
 * Provides a customizable read more button/link.
 *
 * @file blocks/read-more/index.tsx
 * @since 1.0.0
 */

import { registerBlockType } from '@wordpress/blocks';

import Edit from './edit';
import Save from './save';
import metadata from './block.json';

import './index.scss';

registerBlockType('orb/read-more', {
    ...metadata,
    edit: Edit,
    save: Save,
} as any);
