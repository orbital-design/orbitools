/**
 * Resolve whatever WP gave us as a block icon to a renderable
 * React node:
 *   - inline SVG markup ("<svg…") → dangerouslySetInnerHTML
 *   - dashicon slug ("format-image") → <Dashicon />
 *   - null/undefined/empty → block-default placeholder
 *
 * Lifted to its own file so the Dashboard's ItemCard and the Block
 * Manager's grid both render block icons exactly the same way.
 */
import { Dashicon } from '@wordpress/components';

interface BlockIconProps {
    icon: string | null | undefined;
}

export function BlockIcon({ icon }: BlockIconProps): JSX.Element {
    if (icon === null || icon === undefined || icon === '') {
        return <Dashicon icon="block-default" />;
    }
    if (icon.trim().startsWith('<svg')) {
        return (
            <span
                className="orbitools-block-icon orbitools-block-icon--svg"
                // eslint-disable-next-line react/no-danger
                dangerouslySetInnerHTML={{ __html: icon }}
            />
        );
    }
    return <Dashicon icon={icon as Parameters<typeof Dashicon>[0]['icon']} />;
}
