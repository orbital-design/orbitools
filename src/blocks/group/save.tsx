/**
 * WordPress dependencies
 */
import { useInnerBlocksProps, useBlockProps } from '@wordpress/block-editor';

export default function save( { attributes } ) {
	const { layout } = attributes;
	const layoutType = layout?.type || 'group';
	
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
	
	return <div { ...useInnerBlocksProps.save( useBlockProps.save( {
		className
	} ) ) } />;
}
