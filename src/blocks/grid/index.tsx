import { registerBlockType } from '@wordpress/blocks';
import type { BlockConfiguration } from '@wordpress/blocks';
import { SVG, Path } from '@wordpress/components';

import Edit from './edit';
import Save from './save';
import metadata from './block.json';
import type { GridAttributes } from './edit';

import './index.scss';

const GridIcon = () => (
    <SVG xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width={24} height={24}>
        <Path fill="#1d303a" d="M4 4h7v7H4V4Zm9 0h7v7h-7V4ZM4 13h7v7H4v-7Zm9 0h7v7h-7v-7Z" />
    </SVG>
);

const blockConfig: BlockConfiguration<GridAttributes> = {
    ...metadata,
    icon: GridIcon,
    edit: Edit,
    save: Save,
};

registerBlockType('orb/grid', blockConfig);
