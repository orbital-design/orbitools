/**
 * WordPress dependencies
 */
import { __, _x } from '@wordpress/i18n';
import { group, row, stack } from '@wordpress/icons';

const example = {
	innerBlocks: [
		{
			name: 'core/paragraph',
			attributes: {
				customTextColor: '#cf2e2e',
				fontSize: 'large',
				content: __( 'One.' ),
			},
		},
		{
			name: 'core/paragraph',
			attributes: {
				customTextColor: '#ff6900',
				fontSize: 'large',
				content: __( 'Two.' ),
			},
		},
		{
			name: 'core/paragraph',
			attributes: {
				customTextColor: '#fcb900',
				fontSize: 'large',
				content: __( 'Three.' ),
			},
		},
		{
			name: 'core/paragraph',
			attributes: {
				customTextColor: '#00d084',
				fontSize: 'large',
				content: __( 'Four.' ),
			},
		},
		{
			name: 'core/paragraph',
			attributes: {
				customTextColor: '#0693e3',
				fontSize: 'large',
				content: __( 'Five.' ),
			},
		},
		{
			name: 'core/paragraph',
			attributes: {
				customTextColor: '#9b51e0',
				fontSize: 'large',
				content: __( 'Six.' ),
			},
		},
	],
};

const variations = [
	{
		name: 'group',
		title: __( 'Group' ),
		description: __( 'Gather blocks in a container.' ),
		attributes: { layout: { type: 'group' } },
		isDefault: true,
		scope: [ 'block', 'inserter', 'transform' ],
		isActive: ( blockAttributes: any ) =>
			! blockAttributes.layout ||
			! blockAttributes.layout?.type ||
			blockAttributes.layout?.type === 'group',
		icon: group,
	},
	{
		name: 'group-row',
		title: _x( 'Row', 'single horizontal line' ),
		description: __( 'Arrange blocks horizontally.' ),
		attributes: { layout: { type: 'group-row' } },
		scope: [ 'block', 'inserter', 'transform' ],
		isActive: ( blockAttributes: any ) =>
			blockAttributes.layout?.type === 'group-row',
		icon: row,
		example,
	},
	{
		name: 'group-stack',
		title: __( 'Stack' ),
		description: __( 'Arrange blocks vertically.' ),
		attributes: { layout: { type: 'group-stack' } },
		scope: [ 'block', 'inserter', 'transform' ],
		isActive: ( blockAttributes: any ) =>
			blockAttributes.layout?.type === 'group-stack',
		icon: stack,
		example,
	},
];

export default variations;
