/**
 * Marquee Block Registration
 *
 * Registers the read more block with WordPress block editor.
 * Provides a customizable read more button/link.
 *
 * @file blocks/marquee/index.tsx
 * @since 1.0.0
 */

import { registerBlockType } from '@wordpress/blocks';

import Edit from './edit';
import Save from './save';
import metadata from './block.json';

import './index.scss';

registerBlockType('orb/marquee', {
    ...metadata,
    edit: Edit,
    save: Save,
} as any);
