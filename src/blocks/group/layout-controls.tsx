/**
 * Group Block Layout Controls
 *
 * Flex alignment controls for Row and Stack variants.
 * Provides both toolbar dropdowns and sidebar toggle groups.
 *
 * @file blocks/group/layout-controls.tsx
 * @since 1.0.0
 */

import { InspectorControls, BlockControls } from '@wordpress/block-editor';
import {
	PanelBody,
	ToggleControl,
	__experimentalToggleGroupControl as ToggleGroupControl,
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
	__experimentalToggleGroupControlOptionIcon as ToggleGroupControlOptionIcon,
	ToolbarGroup,
	ToolbarDropdownMenu,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { justifyContentIcons, alignItemsIcons, flexWrapIcons } from '../utils/flex-icons';

interface LayoutControlsProps {
	variant: 'row' | 'stack' | null;
	alignItems: string;
	justifyContent: string;
	flexWrap: string;
	align: string;
	restrictContentWidth: boolean;
	setAttributes: ( attrs: Record< string, any > ) => void;
}

const DEFAULTS = {
	alignItems: 'stretch',
	justifyContent: 'flex-start',
	flexWrap: 'nowrap',
};

export default function LayoutControls( {
	variant,
	alignItems,
	justifyContent,
	flexWrap,
	align,
	restrictContentWidth,
	setAttributes,
}: LayoutControlsProps ) {
	const isRow = variant === 'row';
	const isFlex = variant !== null;

	return (
		<>
			{ isFlex && renderToolbarControls( isRow, alignItems, justifyContent, flexWrap, setAttributes ) }
			{ renderInspectorControls( isFlex, isRow, alignItems, justifyContent, flexWrap, align, restrictContentWidth, setAttributes ) }
		</>
	);
}

// ─── Inspector Controls (sidebar toggle groups) ─────────────────────────

function renderInspectorControls(
	isFlex: boolean,
	isRow: boolean,
	alignItems: string,
	justifyContent: string,
	flexWrap: string,
	align: string,
	restrictContentWidth: boolean,
	setAttributes: ( attrs: Record< string, any > ) => void,
) {
	return (
		<InspectorControls>
			<PanelBody title={ __( 'Layout' ) } initialOpen={ true }>
				{ /* Flex Controls (row/stack only) */ }
				{ isFlex && (
					<>
						<div>
							<label style={ labelStyle }>{ isRow ? __( 'Horizontal' ) : __( 'Vertical' ) }</label>
							<ToggleGroupControl
								value={ justifyContent }
								onChange={ ( value ) => setAttributes( { justifyContent: value } ) }
								isBlock={ true }
								__next40pxDefaultSize={ true }
								__nextHasNoMarginBottom={ true }
							>
								{ isRow ? (
									<>
										<ToggleGroupControlOptionIcon value="flex-start" icon={ justifyContentIcons.row['flex-start'] } label={ __( 'Start' ) } />
										<ToggleGroupControlOptionIcon value="center" icon={ justifyContentIcons.row['center'] } label={ __( 'Center' ) } />
										<ToggleGroupControlOptionIcon value="flex-end" icon={ justifyContentIcons.row['flex-end'] } label={ __( 'End' ) } />
										<ToggleGroupControlOptionIcon value="space-between" icon={ justifyContentIcons.row['space-between'] } label={ __( 'Space Between' ) } />
										<ToggleGroupControlOptionIcon value="space-around" icon={ justifyContentIcons.row['space-around'] } label={ __( 'Space Around' ) } />
										<ToggleGroupControlOptionIcon value="space-evenly" icon={ justifyContentIcons.row['space-evenly'] } label={ __( 'Space Evenly' ) } />
									</>
								) : (
									<>
										<ToggleGroupControlOptionIcon value="flex-start" icon={ justifyContentIcons.column['flex-start'] } label={ __( 'Start' ) } />
										<ToggleGroupControlOptionIcon value="center" icon={ justifyContentIcons.column['center'] } label={ __( 'Center' ) } />
										<ToggleGroupControlOptionIcon value="flex-end" icon={ justifyContentIcons.column['flex-end'] } label={ __( 'End' ) } />
										<ToggleGroupControlOptionIcon value="space-between" icon={ justifyContentIcons.column['space-between'] } label={ __( 'Space Between' ) } />
										<ToggleGroupControlOptionIcon value="space-around" icon={ justifyContentIcons.column['space-around'] } label={ __( 'Space Around' ) } />
										<ToggleGroupControlOptionIcon value="space-evenly" icon={ justifyContentIcons.column['space-evenly'] } label={ __( 'Space Evenly' ) } />
									</>
								) }
							</ToggleGroupControl>
						</div>

						<div style={ { marginTop: '16px' } }>
							<label style={ labelStyle }>{ isRow ? __( 'Vertical' ) : __( 'Horizontal' ) }</label>
							<ToggleGroupControl
								value={ alignItems }
								onChange={ ( value ) => setAttributes( { alignItems: value } ) }
								isBlock={ true }
								__next40pxDefaultSize={ true }
								__nextHasNoMarginBottom={ true }
							>
								{ isRow ? (
									<>
										<ToggleGroupControlOptionIcon value="stretch" icon={ alignItemsIcons.row['stretch'] } label={ __( 'Stretch' ) } />
										<ToggleGroupControlOptionIcon value="flex-start" icon={ alignItemsIcons.row['flex-start'] } label={ __( 'Start' ) } />
										<ToggleGroupControlOptionIcon value="center" icon={ alignItemsIcons.row['center'] } label={ __( 'Center' ) } />
										<ToggleGroupControlOptionIcon value="flex-end" icon={ alignItemsIcons.row['flex-end'] } label={ __( 'End' ) } />
									</>
								) : (
									<>
										<ToggleGroupControlOptionIcon value="stretch" icon={ alignItemsIcons.column['stretch'] } label={ __( 'Stretch' ) } />
										<ToggleGroupControlOptionIcon value="flex-start" icon={ alignItemsIcons.column['flex-start'] } label={ __( 'Start' ) } />
										<ToggleGroupControlOptionIcon value="center" icon={ alignItemsIcons.column['center'] } label={ __( 'Center' ) } />
										<ToggleGroupControlOptionIcon value="flex-end" icon={ alignItemsIcons.column['flex-end'] } label={ __( 'End' ) } />
									</>
								) }
							</ToggleGroupControl>
						</div>

						{ isRow && (
							<div style={ { marginTop: '16px' } }>
								<label style={ labelStyle }>{ __( 'Wrap' ) }</label>
								<ToggleGroupControl
									value={ flexWrap }
									onChange={ ( value ) => setAttributes( { flexWrap: value } ) }
									isBlock={ true }
									__next40pxDefaultSize={ true }
									__nextHasNoMarginBottom={ true }
								>
									<ToggleGroupControlOptionIcon value="nowrap" icon={ flexWrapIcons.row['nowrap'] } label={ __( 'No Wrap' ) } />
									<ToggleGroupControlOptionIcon value="wrap" icon={ flexWrapIcons.row['wrap'] } label={ __( 'Wrap' ) } />
								</ToggleGroupControl>
							</div>
						) }
					</>
				) }

				{ /* Constrain Content (full-width only) */ }
				{ align === 'full' && (
					<ToggleControl
						label={ __( 'Nested blocks use content width' ) }
						help={ __( 'Constrain child blocks to the standard content width.' ) }
						checked={ restrictContentWidth }
						onChange={ ( value ) => setAttributes( { restrictContentWidth: value } ) }
						__nextHasNoMarginBottom={ true }
					/>
				) }
			</PanelBody>
		</InspectorControls>
	);
}

const labelStyle: React.CSSProperties = {
	display: 'block',
	marginBottom: '8px',
	fontSize: '11px',
	fontWeight: 500,
	textTransform: 'uppercase',
	color: '#1e1e1e',
};

// ─── Toolbar Controls (quick-access dropdowns) ──────────────────────────

function renderToolbarControls(
	isRow: boolean,
	alignItems: string,
	justifyContent: string,
	flexWrap: string,
	setAttributes: ( attrs: Record< string, any > ) => void,
) {
	const controls = [];

	// --- Horizontal alignment ---
	if ( isRow ) {
		const justifyControls = [
			{ icon: justifyContentIcons.row['flex-start'], title: 'Start', onClick: () => setAttributes( { justifyContent: 'flex-start' } ), isActive: justifyContent === 'flex-start' },
			{ icon: justifyContentIcons.row['center'], title: 'Center', onClick: () => setAttributes( { justifyContent: 'center' } ), isActive: justifyContent === 'center' },
			{ icon: justifyContentIcons.row['flex-end'], title: 'End', onClick: () => setAttributes( { justifyContent: 'flex-end' } ), isActive: justifyContent === 'flex-end' },
			{ icon: justifyContentIcons.row['space-between'], title: 'Space Between', onClick: () => setAttributes( { justifyContent: 'space-between' } ), isActive: justifyContent === 'space-between' },
			{ icon: justifyContentIcons.row['space-around'], title: 'Space Around', onClick: () => setAttributes( { justifyContent: 'space-around' } ), isActive: justifyContent === 'space-around' },
			{ icon: justifyContentIcons.row['space-evenly'], title: 'Space Evenly', onClick: () => setAttributes( { justifyContent: 'space-evenly' } ), isActive: justifyContent === 'space-evenly' },
		];
		controls.push(
			<ToolbarDropdownMenu key="justify" controls={ justifyControls } icon={ justifyControls.find( c => c.isActive )?.icon || justifyControls[ 0 ].icon } label={ isRow ? "Horizontal" : "Vertical" } />
		);
	} else {
		const alignControls = [
			{ icon: alignItemsIcons.column['flex-start'], title: 'Start', onClick: () => setAttributes( { alignItems: 'flex-start' } ), isActive: alignItems === 'flex-start' },
			{ icon: alignItemsIcons.column['center'], title: 'Center', onClick: () => setAttributes( { alignItems: 'center' } ), isActive: alignItems === 'center' },
			{ icon: alignItemsIcons.column['flex-end'], title: 'End', onClick: () => setAttributes( { alignItems: 'flex-end' } ), isActive: alignItems === 'flex-end' },
			{ icon: alignItemsIcons.column['stretch'], title: 'Stretch', onClick: () => setAttributes( { alignItems: 'stretch' } ), isActive: alignItems === 'stretch' },
		];
		controls.push(
			<ToolbarDropdownMenu key="align" controls={ alignControls } icon={ alignControls.find( c => c.isActive )?.icon || alignControls[ 3 ].icon } label={ isRow ? "Horizontal" : "Vertical" } />
		);
	}

	// --- Vertical alignment ---
	if ( isRow ) {
		const alignControls = [
			{ icon: alignItemsIcons.row['flex-start'], title: 'Top', onClick: () => setAttributes( { alignItems: 'flex-start' } ), isActive: alignItems === 'flex-start' },
			{ icon: alignItemsIcons.row['center'], title: 'Middle', onClick: () => setAttributes( { alignItems: 'center' } ), isActive: alignItems === 'center' },
			{ icon: alignItemsIcons.row['flex-end'], title: 'Bottom', onClick: () => setAttributes( { alignItems: 'flex-end' } ), isActive: alignItems === 'flex-end' },
			{ icon: alignItemsIcons.row['stretch'], title: 'Stretch', onClick: () => setAttributes( { alignItems: 'stretch' } ), isActive: alignItems === 'stretch' },
		];
		controls.push(
			<ToolbarDropdownMenu key="align-v" controls={ alignControls } icon={ alignControls.find( c => c.isActive )?.icon || alignControls[ 3 ].icon } label={ isRow ? "Vertical" : "Horizontal" } />
		);
	} else {
		const justifyControls = [
			{ icon: justifyContentIcons.column['flex-start'], title: 'Start', onClick: () => setAttributes( { justifyContent: 'flex-start' } ), isActive: justifyContent === 'flex-start' },
			{ icon: justifyContentIcons.column['center'], title: 'Center', onClick: () => setAttributes( { justifyContent: 'center' } ), isActive: justifyContent === 'center' },
			{ icon: justifyContentIcons.column['flex-end'], title: 'End', onClick: () => setAttributes( { justifyContent: 'flex-end' } ), isActive: justifyContent === 'flex-end' },
			{ icon: justifyContentIcons.column['space-between'], title: 'Space Between', onClick: () => setAttributes( { justifyContent: 'space-between' } ), isActive: justifyContent === 'space-between' },
			{ icon: justifyContentIcons.column['space-around'], title: 'Space Around', onClick: () => setAttributes( { justifyContent: 'space-around' } ), isActive: justifyContent === 'space-around' },
			{ icon: justifyContentIcons.column['space-evenly'], title: 'Space Evenly', onClick: () => setAttributes( { justifyContent: 'space-evenly' } ), isActive: justifyContent === 'space-evenly' },
		];
		controls.push(
			<ToolbarDropdownMenu key="justify-v" controls={ justifyControls } icon={ justifyControls.find( c => c.isActive )?.icon || justifyControls[ 0 ].icon } label={ isRow ? "Vertical" : "Horizontal" } />
		);
	}

	// --- Wrap (row only) ---
	if ( isRow ) {
		const wrapControls = [
			{ icon: flexWrapIcons.row['nowrap'], title: 'No Wrap', onClick: () => setAttributes( { flexWrap: 'nowrap' } ), isActive: flexWrap === 'nowrap' },
			{ icon: flexWrapIcons.row['wrap'], title: 'Wrap', onClick: () => setAttributes( { flexWrap: 'wrap' } ), isActive: flexWrap === 'wrap' },
		];
		controls.push(
			<ToolbarDropdownMenu key="wrap" controls={ wrapControls } icon={ wrapControls.find( c => c.isActive )?.icon || wrapControls[ 0 ].icon } label="Wrap" />
		);
	}

	return (
		<BlockControls>
			<ToolbarGroup>{ controls }</ToolbarGroup>
		</BlockControls>
	);
}
