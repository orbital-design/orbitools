/**
 * Spacer Block Registration
 * 
 * Registers the spacer block with WordPress block editor.
 * Simple responsive spacer with height controls.
 * 
 * @file blocks/spacer/index.tsx
 * @since 1.0.0
 */

import { registerBlockType } from '@wordpress/blocks';

import Edit from './edit';
import Save from './save';
import metadata from './block.json';

import './index.scss';

registerBlockType('orb/spacer', {
    ...metadata,
    edit: Edit,
    save: Save,
} as any);