import { registerBlockType } from '@wordpress/blocks';
import type { BlockConfiguration } from '@wordpress/blocks';
import { SVG, Path } from '@wordpress/components';

import Edit from './edit';
import Save from './save';
import metadata from './block.json';
import type { GridCellAttributes } from './edit';

import './index.scss';

const GridCellIcon = () => (
    <SVG xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width={24} height={24}>
        <Path fill="#32a3e2" fillOpacity=".4" d="M4 4h16v16H4V4Z" />
        <Path fill="#1d303a" d="M4 4h7v16H4V4Z" />
    </SVG>
);

const blockConfig: BlockConfiguration<GridCellAttributes> = {
    ...metadata,
    icon: GridCellIcon,
    edit: Edit,
    save: Save,
};

registerBlockType('orb/grid-cell', blockConfig);
