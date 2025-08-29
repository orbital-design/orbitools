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
    <SVG xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 576">
        <Path fill="#1d303a" d="M576 216c0-18-7-37-21-51l-90-90a72 72 0 0 0-123 51v18h-72c-26 0-49 14-62 35-5-5-11-9-18-11-27-12-58-5-79 15l-90 90a72 72 0 0 0-21 51v36c0 18 7 37 21 51l90 90a72 72 0 0 0 123-51v-18h72c26 0 49-14 62-35a72 72 0 0 0 97-4l90-90c14-14 21-33 21-51v-36zM440 101l90 90c14 14 14 36 0 50l-90 90a36 36 0 0 1-62-25v-36c0-10-8-18-18-18h-90a36 36 0 0 1 0-72h90c10 0 18-8 18-18v-36a36 36 0 0 1 61-25zM306 288a36 36 0 0 1 0 72h-90c-10 0-18 8-18 18v36a36 36 0 0 1-61 26l-90-90a36 36 0 0 1 0-51l90-90a36 36 0 0 1 61 25v36c0 10 8 18 18 18z"/>
    </SVG>
);
registerBlockType(metadata.name as any, {
    ...metadata,
    icon: MarqueeIcon,
    edit: Edit,
    save: Save,
} as BlockConfiguration);
