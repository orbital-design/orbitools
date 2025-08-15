/**
 * WordPress dependencies
 */
import { useDispatch, useSelect } from '@wordpress/data';
import {
	InnerBlocks,
	useBlockProps,
	useInnerBlocksProps,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import { useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { View } from '@wordpress/primitives';

/**
 * Internal dependencies
 */
import GroupPlaceHolder, { useShouldShowPlaceHolder } from './placeholder';


function GroupEdit( { attributes, name, setAttributes, clientId } ) {
	const { hasInnerBlocks, themeSupportsLayout } = useSelect(
		( select ) => {
			const { getBlock, getSettings } = select( blockEditorStore );
			const block = getBlock( clientId );
			return {
				hasInnerBlocks: !! ( block && block.innerBlocks.length ),
				themeSupportsLayout: getSettings()?.supportsLayout,
			};
		},
		[ clientId ]
	);

	const {
		templateLock,
		allowedBlocks,
		layout = {},
	} = attributes;

	// Layout settings.
	const { type = 'default' } = layout;
	const layoutSupportEnabled =
		themeSupportsLayout || type === 'flex' || type === 'grid';

	// Hooks.
	const ref = useRef();
	const blockProps = useBlockProps( { ref } );

	const [ showPlaceholder, setShowPlaceholder ] = useShouldShowPlaceHolder( {
		attributes,
		usedLayoutType: type,
		hasInnerBlocks,
	} );

	// Default to the regular appender being rendered.
	let renderAppender;
	if ( showPlaceholder ) {
		// In the placeholder state, ensure the appender is not rendered.
		// This is needed because `...innerBlocksProps` is used in the placeholder
		// state so that blocks can dragged onto the placeholder area
		// from both the list view and in the editor canvas.
		renderAppender = false;
	} else if ( ! hasInnerBlocks ) {
		// When there is no placeholder, but the block is also empty,
		// use the larger button appender.
		renderAppender = InnerBlocks.ButtonBlockAppender;
	}

	const innerBlocksProps = useInnerBlocksProps(
		layoutSupportEnabled
			? blockProps
			: { className: 'wp-block-group__inner-container' },
		{
			dropZoneElement: ref.current,
			templateLock,
			allowedBlocks,
			renderAppender,
		}
	);

	const { selectBlock } = useDispatch( blockEditorStore );

	const selectVariation = ( nextVariation ) => {
		setAttributes( nextVariation.attributes );
		selectBlock( clientId, -1 );
		setShowPlaceholder( false );
	};

	return (
		<>
			{ showPlaceholder && (
				<View>
					{ innerBlocksProps.children }
					<GroupPlaceHolder
						name={ name }
						onSelect={ selectVariation }
					/>
				</View>
			) }
			{ layoutSupportEnabled && ! showPlaceholder && (
				<div { ...innerBlocksProps } />
			) }
			{ /* Ideally this is not needed but it's there for backward compatibility reason
				to keep this div for themes that might rely on its presence */ }
			{ ! layoutSupportEnabled && ! showPlaceholder && (
				<div { ...blockProps }>
					<div { ...innerBlocksProps } />
				</div>
			) }
		</>
	);
}

export default GroupEdit;
