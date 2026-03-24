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
import LayoutControls from './layout-controls';


function GroupEdit( { attributes, name, setAttributes, clientId } ) {
	const { hasInnerBlocks } = useSelect(
		( select ) => {
			const { getBlock } = select( blockEditorStore );
			const block = getBlock( clientId );
			return {
				hasInnerBlocks: !! ( block && block.innerBlocks.length )
			};
		},
		[ clientId ]
	);

	const {
		templateLock,
		allowedBlocks,
		layout,
		alignItems = 'stretch',
		justifyContent = 'flex-start',
		flexWrap = 'nowrap',
		align,
		restrictContentWidth = false,
	} = attributes;

	const layoutType = layout?.type || 'group';
	const isRow = layoutType === 'group-row';
	const isStack = layoutType === 'group-stack';
	const isFlex = isRow || isStack;
	const showLayoutPanel = isFlex || align === 'full';

	// Get variation-specific class name
	const getVariationClass = (type: string) => {
		switch (type) {
			case 'group-row':
				return 'orb-row';
			case 'group-stack':
				return 'orb-stack';
			default:
				return 'orb-group';
		}
	};

	const className = getVariationClass(layoutType);

	// Hooks.
	const ref = useRef();
	const blockProps = useBlockProps( {
		ref,
		className
	} );

	const [ showPlaceholder, setShowPlaceholder ] = useShouldShowPlaceHolder( {
		attributes,
		hasInnerBlocks,
	} );

	// Default to the regular appender being rendered.
	let renderAppender;
	if ( showPlaceholder ) {
		renderAppender = false;
	} else if ( ! hasInnerBlocks ) {
		renderAppender = InnerBlocks.ButtonBlockAppender;
	}

	const innerBlocksProps = useInnerBlocksProps(
		blockProps,
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
			{ showLayoutPanel && (
				<LayoutControls
					variant={ isRow ? 'row' : ( isStack ? 'stack' : null ) }
					alignItems={ alignItems }
					justifyContent={ justifyContent }
					flexWrap={ flexWrap }
					align={ align }
					restrictContentWidth={ restrictContentWidth }
					setAttributes={ setAttributes }
				/>
			) }
			{ showPlaceholder && (
				<View>
					{ innerBlocksProps.children }
					<GroupPlaceHolder
						name={ name }
						onSelect={ selectVariation }
					/>
				</View>
			) }
			{ ! showPlaceholder && (
				<div { ...innerBlocksProps } />
			) }
		</>
	);
}

export default GroupEdit;
