/**
 * Reading Time Block Registration
 *
 * Server-rendered (PHP render_callback in
 * inc/Blocks/Reading_Time/Reading_Time.php) — save() returns null.
 * The block has no user-tunable attributes; the WPM / suffix /
 * count-images settings live on the module page so the same values
 * apply to every instance + the static `get_reading_time()` accessor.
 *
 * @file blocks/reading-time/index.tsx
 * @since 1.0.0
 */
import { registerBlockType } from '@wordpress/blocks';
import { SVG, Path } from '@wordpress/components';

import Edit from './edit';
import Save from './save';
import metadata from './block.json';

import './index.scss';

const ReadingTimeIcon = (): JSX.Element => (
    <SVG xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
        <Path
            fill="currentColor"
            d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2zm0 18a8 8 0 1 1 8-8 8 8 0 0 1-8 8zm.5-13H11v6l5.2 3.1.8-1.3-4.5-2.7z"
        />
    </SVG>
);

registerBlockType(metadata.name as any, {
    ...metadata,
    icon: ReadingTimeIcon,
    edit: Edit,
    save: Save,
} as any);
