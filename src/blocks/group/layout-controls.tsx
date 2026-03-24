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
	SVG,
	Path,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

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
										<ToggleGroupControlOptionIcon value="flex-start" icon={ <JustifyStartH /> } label={ __( 'Start' ) } />
										<ToggleGroupControlOptionIcon value="center" icon={ <JustifyCenterH /> } label={ __( 'Center' ) } />
										<ToggleGroupControlOptionIcon value="flex-end" icon={ <JustifyEndH /> } label={ __( 'End' ) } />
										<ToggleGroupControlOptionIcon value="space-between" icon={ <JustifyBetweenH /> } label={ __( 'Space Between' ) } />
										<ToggleGroupControlOptionIcon value="space-around" icon={ <JustifyAroundH /> } label={ __( 'Space Around' ) } />
										<ToggleGroupControlOptionIcon value="space-evenly" icon={ <JustifyEvenlyH /> } label={ __( 'Space Evenly' ) } />
									</>
								) : (
									<>
										<ToggleGroupControlOptionIcon value="flex-start" icon={ <AlignTopV /> } label={ __( 'Start' ) } />
										<ToggleGroupControlOptionIcon value="center" icon={ <AlignMiddleV /> } label={ __( 'Center' ) } />
										<ToggleGroupControlOptionIcon value="flex-end" icon={ <AlignBottomV /> } label={ __( 'End' ) } />
										<ToggleGroupControlOptionIcon value="space-between" icon={ <JustifyBetweenV /> } label={ __( 'Space Between' ) } />
										<ToggleGroupControlOptionIcon value="space-around" icon={ <JustifyAroundH /> } label={ __( 'Space Around' ) } />
										<ToggleGroupControlOptionIcon value="space-evenly" icon={ <JustifyEvenlyH /> } label={ __( 'Space Evenly' ) } />
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
										<ToggleGroupControlOptionIcon value="stretch" icon={ <AlignStretchV /> } label={ __( 'Stretch' ) } />
										<ToggleGroupControlOptionIcon value="flex-start" icon={ <AlignTopV /> } label={ __( 'Start' ) } />
										<ToggleGroupControlOptionIcon value="center" icon={ <AlignMiddleV /> } label={ __( 'Center' ) } />
										<ToggleGroupControlOptionIcon value="flex-end" icon={ <AlignBottomV /> } label={ __( 'End' ) } />
									</>
								) : (
									<>
										<ToggleGroupControlOptionIcon value="stretch" icon={ <AlignStretchH /> } label={ __( 'Stretch' ) } />
										<ToggleGroupControlOptionIcon value="flex-start" icon={ <AlignStartH /> } label={ __( 'Start' ) } />
										<ToggleGroupControlOptionIcon value="center" icon={ <AlignCenterH /> } label={ __( 'Center' ) } />
										<ToggleGroupControlOptionIcon value="flex-end" icon={ <AlignEndH /> } label={ __( 'End' ) } />
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
									<ToggleGroupControlOptionIcon value="nowrap" icon={ <WrapNone /> } label={ __( 'No Wrap' ) } />
									<ToggleGroupControlOptionIcon value="wrap" icon={ <WrapOn /> } label={ __( 'Wrap' ) } />
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
			{ icon: <JustifyStartH />, title: 'Start', onClick: () => setAttributes( { justifyContent: 'flex-start' } ), isActive: justifyContent === 'flex-start' },
			{ icon: <JustifyCenterH />, title: 'Center', onClick: () => setAttributes( { justifyContent: 'center' } ), isActive: justifyContent === 'center' },
			{ icon: <JustifyEndH />, title: 'End', onClick: () => setAttributes( { justifyContent: 'flex-end' } ), isActive: justifyContent === 'flex-end' },
			{ icon: <JustifyBetweenH />, title: 'Space Between', onClick: () => setAttributes( { justifyContent: 'space-between' } ), isActive: justifyContent === 'space-between' },
			{ icon: <JustifyAroundH />, title: 'Space Around', onClick: () => setAttributes( { justifyContent: 'space-around' } ), isActive: justifyContent === 'space-around' },
			{ icon: <JustifyEvenlyH />, title: 'Space Evenly', onClick: () => setAttributes( { justifyContent: 'space-evenly' } ), isActive: justifyContent === 'space-evenly' },
		];
		controls.push(
			<ToolbarDropdownMenu key="justify" controls={ justifyControls } icon={ justifyControls.find( c => c.isActive )?.icon || justifyControls[ 0 ].icon } label={ isRow ? "Horizontal" : "Vertical" } />
		);
	} else {
		const alignControls = [
			{ icon: <AlignStartH />, title: 'Start', onClick: () => setAttributes( { alignItems: 'flex-start' } ), isActive: alignItems === 'flex-start' },
			{ icon: <AlignCenterH />, title: 'Center', onClick: () => setAttributes( { alignItems: 'center' } ), isActive: alignItems === 'center' },
			{ icon: <AlignEndH />, title: 'End', onClick: () => setAttributes( { alignItems: 'flex-end' } ), isActive: alignItems === 'flex-end' },
			{ icon: <AlignStretchH />, title: 'Stretch', onClick: () => setAttributes( { alignItems: 'stretch' } ), isActive: alignItems === 'stretch' },
		];
		controls.push(
			<ToolbarDropdownMenu key="align" controls={ alignControls } icon={ alignControls.find( c => c.isActive )?.icon || alignControls[ 3 ].icon } label={ isRow ? "Horizontal" : "Vertical" } />
		);
	}

	// --- Vertical alignment ---
	if ( isRow ) {
		const alignControls = [
			{ icon: <AlignTopV />, title: 'Top', onClick: () => setAttributes( { alignItems: 'flex-start' } ), isActive: alignItems === 'flex-start' },
			{ icon: <AlignMiddleV />, title: 'Middle', onClick: () => setAttributes( { alignItems: 'center' } ), isActive: alignItems === 'center' },
			{ icon: <AlignBottomV />, title: 'Bottom', onClick: () => setAttributes( { alignItems: 'flex-end' } ), isActive: alignItems === 'flex-end' },
			{ icon: <AlignStretchV />, title: 'Stretch', onClick: () => setAttributes( { alignItems: 'stretch' } ), isActive: alignItems === 'stretch' },
		];
		controls.push(
			<ToolbarDropdownMenu key="align-v" controls={ alignControls } icon={ alignControls.find( c => c.isActive )?.icon || alignControls[ 3 ].icon } label={ isRow ? "Vertical" : "Horizontal" } />
		);
	} else {
		const justifyControls = [
			{ icon: <AlignTopV />, title: 'Start', onClick: () => setAttributes( { justifyContent: 'flex-start' } ), isActive: justifyContent === 'flex-start' },
			{ icon: <AlignMiddleV />, title: 'Center', onClick: () => setAttributes( { justifyContent: 'center' } ), isActive: justifyContent === 'center' },
			{ icon: <AlignBottomV />, title: 'End', onClick: () => setAttributes( { justifyContent: 'flex-end' } ), isActive: justifyContent === 'flex-end' },
			{ icon: <JustifyBetweenV />, title: 'Space Between', onClick: () => setAttributes( { justifyContent: 'space-between' } ), isActive: justifyContent === 'space-between' },
			{ icon: <JustifyAroundH />, title: 'Space Around', onClick: () => setAttributes( { justifyContent: 'space-around' } ), isActive: justifyContent === 'space-around' },
			{ icon: <JustifyEvenlyH />, title: 'Space Evenly', onClick: () => setAttributes( { justifyContent: 'space-evenly' } ), isActive: justifyContent === 'space-evenly' },
		];
		controls.push(
			<ToolbarDropdownMenu key="justify-v" controls={ justifyControls } icon={ justifyControls.find( c => c.isActive )?.icon || justifyControls[ 0 ].icon } label={ isRow ? "Vertical" : "Horizontal" } />
		);
	}

	// --- Wrap (row only) ---
	if ( isRow ) {
		const wrapControls = [
			{ icon: <WrapNone />, title: 'No Wrap', onClick: () => setAttributes( { flexWrap: 'nowrap' } ), isActive: flexWrap === 'nowrap' },
			{ icon: <WrapOn />, title: 'Wrap', onClick: () => setAttributes( { flexWrap: 'wrap' } ), isActive: flexWrap === 'wrap' },
		];
		controls.push(
			<ToolbarDropdownMenu key="wrap" controls={ wrapControls } icon={ wrapControls.find( c => c.isActive )?.icon || wrapControls[ 0 ].icon } label="Wrap" />
		);
	}

	return (
		<BlockControls group="block">
			<ToolbarGroup>{ controls }</ToolbarGroup>
		</BlockControls>
	);
}

// ─── Icon components ────────────────────────────────────────────────────

// Horizontal justify icons (for row)
const JustifyStartH = () => (
	<SVG width="24" height="24" viewBox="0 0 24 24" fill="none">
		<Path d="M4 2L4 22M11 16L11 8C11 7.44772 10.5523 7 10 7H8C7.44772 7 7 7.44772 7 8L7 16C7 16.5523 7.44772 17 8 17H10C10.5523 17 11 16.5523 11 16Z" stroke="currentColor" strokeWidth="1.5" />
	</SVG>
);
const JustifyCenterH = () => (
	<SVG width="24" height="24" viewBox="0 0 24 24" fill="none">
		<Path d="M12 2L12 22M9 16L9 8C9 7.44772 8.55228 7 8 7H6C5.44772 7 5 7.44772 5 8L5 16C5 16.5523 5.44772 17 6 17H8C8.55229 17 9 16.5523 9 16Z" stroke="currentColor" strokeWidth="1.5" />
	</SVG>
);
const JustifyEndH = () => (
	<SVG width="24" height="24" viewBox="0 0 24 24" fill="none">
		<Path d="M20 2L20 22M13 16L13 8C13 7.44772 12.5523 7 12 7H10C9.44772 7 9 7.44772 9 8L9 16C9 16.5523 9.44772 17 10 17H12C12.5523 17 13 16.5523 13 16Z" stroke="currentColor" strokeWidth="1.5" />
	</SVG>
);
const JustifyBetweenH = () => (
	<SVG width="24" height="24" viewBox="0 0 24 24" fill="none">
		<Path d="M4 2L4 22M20 2L20 22M11 16L11 8C11 7.44772 10.5523 7 10 7H8C7.44772 7 7 7.44772 7 8L7 16C7 16.5523 7.44772 17 8 17H10C10.5523 17 11 16.5523 11 16Z" stroke="currentColor" strokeWidth="1.5" />
	</SVG>
);
const JustifyAroundH = () => (
	<SVG width="24" height="24" viewBox="0 0 24 24" fill="none">
		<Path d="M21 2V22M4 2L4 22M10 16L10 8C10 7.44772 9.55229 7 9 7H7C6.44772 7 6 7.44772 6 8L6 16C6 16.5523 6.44772 17 7 17H9C9.55229 17 10 16.5523 10 16Z" stroke="currentColor" strokeWidth="1.5" />
	</SVG>
);
const JustifyEvenlyH = () => (
	<SVG width="24" height="24" viewBox="0 0 24 24" fill="none">
		<Path d="M2 2L2 22M22 2L22 22M11 16L11 8C11 7.44772 10.5523 7 10 7H8C7.44772 7 7 7.44772 7 8L7 16C7 16.5523 7.44772 17 8 17H10C10.5523 17 11 16.5523 11 16Z" stroke="currentColor" strokeWidth="1.5" />
	</SVG>
);

// Horizontal align icons (for stack)
const AlignStartH = () => (
	<SVG width="24" height="24" viewBox="0 0 24 24" fill="none">
		<Path d="M4 2L4 22M11 8H13C13.5523 8 14 7.55228 14 7V5C14 4.44772 13.5523 4 13 4H11C10.4477 4 10 4.44772 10 5V7C10 7.55228 10.4477 8 11 8Z" stroke="currentColor" strokeWidth="1.5" />
	</SVG>
);
const AlignCenterH = () => (
	<SVG width="24" height="24" viewBox="0 0 24 24" fill="none">
		<Path d="M12 2L12 22M10 8H14C14.5523 8 15 7.55228 15 7V5C15 4.44772 14.5523 4 14 4H10C9.44772 4 9 4.44772 9 5V7C9 7.55228 9.44772 8 10 8Z" stroke="currentColor" strokeWidth="1.5" />
	</SVG>
);
const AlignEndH = () => (
	<SVG width="24" height="24" viewBox="0 0 24 24" fill="none">
		<Path d="M20 2L20 22M13 8H17C17.5523 8 18 7.55228 18 7V5C18 4.44772 17.5523 4 17 4H13C12.4477 4 12 4.44772 12 5V7C12 7.55228 12.4477 8 13 8Z" stroke="currentColor" strokeWidth="1.5" />
	</SVG>
);
const AlignStretchH = () => (
	<SVG width="24" height="24" viewBox="0 0 24 24" fill="none">
		<Path d="M2 2L2 22M22 2L22 22M8 8H16C16.5523 8 17 7.55228 17 7V5C17 4.44772 16.5523 4 16 4H8C7.44772 4 7 4.44772 7 5V7C7 7.55228 7.44772 8 8 8Z" stroke="currentColor" strokeWidth="1.5" />
	</SVG>
);

// Vertical align icons (shared)
const AlignTopV = () => (
	<SVG width="24" height="24" viewBox="0 0 24 24" fill="none">
		<Path d="M2 4L22 4M11 16L11 8C11 7.44772 10.5523 7 10 7H8C7.44772 7 7 7.44772 7 8L7 16C7 16.5523 7.44772 17 8 17H10C10.5523 17 11 16.5523 11 16Z" stroke="currentColor" strokeWidth="1.5" />
	</SVG>
);
const AlignMiddleV = () => (
	<SVG width="24" height="24" viewBox="0 0 24 24" fill="none">
		<Path d="M2 12L22 12M11 16L11 8C11 7.44772 10.5523 7 10 7H8C7.44772 7 7 7.44772 7 8L7 16C7 16.5523 7.44772 17 8 17H10C10.5523 17 11 16.5523 11 16Z" stroke="currentColor" strokeWidth="1.5" />
	</SVG>
);
const AlignBottomV = () => (
	<SVG width="24" height="24" viewBox="0 0 24 24" fill="none">
		<Path d="M2 20L22 20M11 16L11 8C11 7.44772 10.5523 7 10 7H8C7.44772 7 7 7.44772 7 8L7 16C7 16.5523 7.44772 17 8 17H10C10.5523 17 11 16.5523 11 16Z" stroke="currentColor" strokeWidth="1.5" />
	</SVG>
);
const AlignStretchV = () => (
	<SVG width="24" height="24" viewBox="0 0 24 24" fill="none">
		<Path d="M2 2L2 22M22 2L22 22M11 16L11 8C11 7.44772 10.5523 7 10 7H8C7.44772 7 7 7.44772 7 8L7 16C7 16.5523 7.44772 17 8 17H10C10.5523 17 11 16.5523 11 16Z" stroke="currentColor" strokeWidth="1.5" />
	</SVG>
);
const JustifyBetweenV = () => (
	<SVG width="24" height="24" viewBox="0 0 24 24" fill="none">
		<Path d="M2 4L22 4M2 20L22 20M11 16L11 8C11 7.44772 10.5523 7 10 7H8C7.44772 7 7 7.44772 7 8L7 16C7 16.5523 7.44772 17 8 17H10C10.5523 17 11 16.5523 11 16Z" stroke="currentColor" strokeWidth="1.5" />
	</SVG>
);

// Wrap icons
const WrapNone = () => (
	<SVG width="24" height="24" viewBox="0 0 24 24" fill="none">
		<Path d="M3 5h18M3 12h18M3 19h18" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" />
	</SVG>
);
const WrapOn = () => (
	<SVG width="24" height="24" viewBox="0 0 24 24" fill="none">
		<Path d="M3 5h18M3 12h12M3 19h18" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" />
		<Path d="M18 10l-2 2 2 2" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" />
	</SVG>
);
