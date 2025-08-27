/**
 * Marquee Block Registration
 *
 * Registers the marquee block with WordPress block editor.
 * Provides an animated scrolling content container with customizable speed and direction.
 *
 * @file blocks/marquee/index.tsx
 * @since 1.0.0
 */

import { registerBlockType } from '@wordpress/blocks';
import type { BlockConfiguration } from '@wordpress/blocks';
import { SVG, Path } from '@wordpress/components';

import Edit from './edit';
import Save from './save';
import metadata from './block.json';

import './index.scss';

const MarqueeIcon = () => (
    <SVG width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <Path d="M2 6a1 1 0 0 1 1-1h3.5a1 1 0 1 1 0 2H4v1.5a1 1 0 0 1-2 0V6ZM8 7a1 1 0 0 1 1-1h6a1 1 0 1 1 0 2H9a1 1 0 0 1-1-1ZM18 5a1 1 0 0 0-1 1v1a1 1 0 1 0 2 0V6a1 1 0 0 0-1-1ZM2 12a1 1 0 0 1 1-1h14a1 1 0 1 1 0 2H3a1 1 0 0 1-1-1ZM19 11a1 1 0 1 0 0 2h3a1 1 0 1 0 0-2h-3ZM6 16a1 1 0 0 0-1 1v1a1 1 0 1 0 2 0v-1a1 1 0 0 0-1-1ZM2 18.5a1 1 0 0 1 1-1H6a1 1 0 1 1 0 2H4V21a1 1 0 1 1-2 0v-2.5ZM10 17a1 1 0 0 0 0 2h6a1 1 0 1 0 0-2h-6ZM17 16a1 1 0 0 0-1 1v4a1 1 0 1 0 2 0v-1.5h1.5a1 1 0 1 0 0-2H18V17a1 1 0 0 0-1-1Z" fill="currentColor"/>
    </SVG>
);

registerBlockType(metadata.name as any, {
    ...metadata,
    icon: MarqueeIcon,
    edit: Edit,
    save: Save,
} as BlockConfiguration);
