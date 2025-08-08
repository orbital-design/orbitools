import { registerBlockType } from '@wordpress/blocks';
import type { BlockConfiguration } from '@wordpress/blocks';

import Edit from './edit';
import Save from './save';
import metadata from './block.json';
import type { LayoutAttributes } from '../types';

import './index.scss';

const blockConfig: BlockConfiguration<LayoutAttributes> = {
    ...metadata,
    edit: Edit,
    save: Save,
};

registerBlockType('orb/collection', blockConfig);
