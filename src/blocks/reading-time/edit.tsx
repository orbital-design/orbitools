/**
 * Reading Time — Edit component.
 *
 * Just frames the server-side render. The block has no editable
 * attributes; what shows depends on the current post's cached
 * reading-time meta + the module's settings page. We pass the
 * current post ID + post type through as context so the
 * render_callback can resolve the right post inside the REST
 * preview request.
 */
import { useBlockProps } from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';
import ServerSideRender from '@wordpress/server-side-render';

interface CoreEditorSelect {
    getCurrentPostId(): number | undefined;
    getCurrentPostType(): string | undefined;
}

export default function Edit(): JSX.Element {
    const blockProps = useBlockProps();

    const { postId, postType } = useSelect((select) => {
        const editor = select('core/editor') as unknown as CoreEditorSelect;
        return {
            postId: editor.getCurrentPostId(),
            postType: editor.getCurrentPostType(),
        };
    }, []);

    return (
        <div {...blockProps}>
            <ServerSideRender
                block="orb/reading-time"
                attributes={{}}
                urlQueryArgs={
                    postId !== undefined && postType !== undefined
                        ? { post_id: postId, post_type: postType }
                        : undefined
                }
            />
        </div>
    );
}
