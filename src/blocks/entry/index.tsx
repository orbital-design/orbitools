import { registerBlockType } from '@wordpress/blocks';
import type { BlockConfiguration } from '@wordpress/blocks';

import Edit from './edit';
import Save from './save';
import metadata from './block.json';
import type { LayoutItemAttributes } from '../types';

import './index.scss';

const blockConfig: BlockConfiguration<LayoutItemAttributes> = {
    ...metadata,
    edit: Edit,
    save: Save,
};

registerBlockType('orb/entry', blockConfig);