/**
 * Marquee Block Edit Component
 * 
 * This component provides the editor interface for the Marquee block.
 * The block creates animated scrolling content with customizable direction,
 * speed, and behavior settings.
 * 
 * @file blocks/marquee/edit.tsx
 * @since 1.0.0
 */

import React from 'react';
import {
	InspectorControls,
	useBlockProps,
	useInnerBlocksProps,
	__experimentalColorGradientSettingsDropdown as ColorGradientSettingsDropdown,
	__experimentalUseMultipleOriginColorsAndGradients as useMultipleOriginColorsAndGradients,
} from '@wordpress/block-editor';
import {
	__experimentalToolsPanel as ToolsPanel,
	__experimentalToolsPanelItem as ToolsPanelItem,
	__experimentalToggleGroupControl as ToggleGroupControl,
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
	SelectControl,
	ToggleControl,
	RangeControl,
} from '@wordpress/components';
import {
	Icon,
	arrowLeft,
	arrowRight,
	arrowUp,
	arrowDown,
} from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
import type { BlockEditProps } from '@wordpress/blocks';
import type { MarqueeAttributes } from './types';
import { 
	MARQUEE_DEFAULTS, 
	ANIMATION_SPEED_OPTIONS,
	GAP_SIZE_OPTIONS,
	getMarqueeStyles 
} from './types';

/**
 * Allowed blocks that can be used inside the marquee
 */
const ALLOWED_BLOCKS = [
    'core/paragraph',
    'core/list',
    'core/heading',
    'core/separator',
    'core/shortcode',
    'core/image',
    'orb/spacer',
    'orb/collection',
    'orb/button'
];

/**
 * Default template for new marquee blocks
 */
const TEMPLATE: [string, any][] = [
	[
		'core/paragraph',
		{
			align: 'center',
			content:
				'Marquee block adds a touch of movement and interactivity to your site and helps to capture attention and engage your site visitors in a unique way.',
		},
	],
];

/**
 * Marquee Block Edit Component
 * 
 * Renders the editor interface for the Marquee block.
 * Provides controls for animation settings and content management.
 * 
 * @param props Block edit props containing attributes and setAttributes
 * @returns JSX element with controls and editor preview
 */
const Edit: React.FC<BlockEditProps<MarqueeAttributes>> = ({
    attributes, setAttributes, clientId
}) => {
    // Extract attributes with fallbacks to defaults
    const { 
        orientation = MARQUEE_DEFAULTS.orientation,
		animationDirection = MARQUEE_DEFAULTS.animationDirection,
		hoverAnimationState = MARQUEE_DEFAULTS.hoverAnimationState,
		animationSpeed = MARQUEE_DEFAULTS.animationSpeed,
		gap = MARQUEE_DEFAULTS.gap,
		overlayColor,
		whiteSpace = MARQUEE_DEFAULTS.whiteSpace,
		autoFill = MARQUEE_DEFAULTS.autoFill,
		minDuplicates = MARQUEE_DEFAULTS.minDuplicates
    } = attributes;

    // Generate CSS custom properties for styling
    const marqueeStyles = getMarqueeStyles(attributes);
    
    const blockProps = useBlockProps({
        className: [
            'orb-marquee',
            `orb-marquee--${orientation}`,
            `orb-marquee--${animationDirection}`,
            `orb-marquee--hover-${hoverAnimationState}`,
            overlayColor && 'has-overlay-color'
        ].filter(Boolean).join(' '),
        style: marqueeStyles
    });

    const colorGradientSettings = useMultipleOriginColorsAndGradients();

	const innerBlockProps = useInnerBlocksProps(
		{
			className: 'orb-marquee__content',
		},
		{
			allowedBlocks: ALLOWED_BLOCKS,
			template: TEMPLATE,
			templateInsertUpdatesSelection: false
		}
	);

    // Attribute update functions with type safety
    const setOverlayColor = (newValue: string | undefined) => {
		setAttributes({ overlayColor: newValue });
	};

	const setOrientation = (newValue: 'x' | 'y') => {
		setAttributes({ orientation: newValue });
	};

	const setAnimationDirection = (newValue: 'normal' | 'reverse') => {
		setAttributes({ animationDirection: newValue });
	};

	const setHoverAnimationState = (newValue: 'paused' | 'running') => {
		setAttributes({ hoverAnimationState: newValue });
	};

	const setAnimationSpeed = (newValue: string) => {
		setAttributes({ animationSpeed: newValue });
	};

	const setWhiteSpace = (newValue: 'wrap' | 'nowrap') => {
		setAttributes({ whiteSpace: newValue });
	};

	const setGap = (newValue: string) => {
		setAttributes({ gap: newValue });
	};

	const setAutoFill = (newValue: boolean) => {
		setAttributes({ autoFill: newValue });
	};

	const setMinDuplicates = (newValue: number) => {
		setAttributes({ minDuplicates: newValue });
	};

	// Helper to check if attribute has non-default value
	const hasNonDefaultValue = (key: keyof MarqueeAttributes, defaultValue: any) => {
		return attributes[key] !== undefined && attributes[key] !== defaultValue;
	};

    return (
        <>
			<InspectorControls>
				<ToolsPanel
					label={__('Marquee Settings', 'orbitools')}
					resetAll={() => {
						setAttributes({
							orientation: MARQUEE_DEFAULTS.orientation,
							animationDirection: MARQUEE_DEFAULTS.animationDirection,
							hoverAnimationState: MARQUEE_DEFAULTS.hoverAnimationState,
							animationSpeed: MARQUEE_DEFAULTS.animationSpeed,
							gap: MARQUEE_DEFAULTS.gap,
							whiteSpace: MARQUEE_DEFAULTS.whiteSpace,
							autoFill: MARQUEE_DEFAULTS.autoFill,
							minDuplicates: MARQUEE_DEFAULTS.minDuplicates
						});
					}}
					panelId="marquee-settings-panel"
				>
					{/* Orientation Control */}
					<ToolsPanelItem
						hasValue={() => hasNonDefaultValue('orientation', MARQUEE_DEFAULTS.orientation)}
						label={__('Orientation', 'orbitools')}
						onDeselect={() => setOrientation(MARQUEE_DEFAULTS.orientation)}
						isShownByDefault={true}
						panelId="marquee-settings-panel"
					>
						<ToggleGroupControl
							label={__('Scroll Direction', 'orbitools')}
							value={orientation}
							onChange={setOrientation}
							isBlock
							help={__('Choose whether content scrolls horizontally or vertically', 'orbitools')}
							__nextHasNoMarginBottom={true}
						>
						<ToggleGroupControlOption
							value="x"
							label={
								<Icon
									icon={
										<svg>
											<path
												transform="rotate(45 12 12)"
												d="M7 18h4.5v1.5h-7v-7H6V17L17 6h-4.5V4.5h7v7H18V7L7 18Z"
											></path>
										</svg>
									}
								/>
							}
						/>
						<ToggleGroupControlOption
							value="y"
							label={
								<Icon
									icon={
										<svg>
											<path
												transform="rotate(135 12 12)"
												d="M7 18h4.5v1.5h-7v-7H6V17L17 6h-4.5V4.5h7v7H18V7L7 18Z"
											></path>
										</svg>
									}
								/>
							}
						/>
					</ToggleGroupControl>
					</ToolsPanelItem>

					{/* Animation Direction Control */}
					<ToolsPanelItem
						hasValue={() => hasNonDefaultValue('animationDirection', MARQUEE_DEFAULTS.animationDirection)}
						label={__('Animation Direction', 'orbitools')}
						onDeselect={() => setAnimationDirection(MARQUEE_DEFAULTS.animationDirection)}
						isShownByDefault={true}
						panelId="marquee-settings-panel"
					>
						<ToggleGroupControl
							label={__('Animation Direction', 'orbitools')}
							value={animationDirection}
							onChange={setAnimationDirection}
							isBlock
							help={__('Control the direction of the scrolling animation', 'orbitools')}
							__nextHasNoMarginBottom={true}
						>
						<ToggleGroupControlOption
							value="normal"
							label={
								<Icon
									icon={
										orientation === 'x'
											? arrowLeft
											: arrowUp
									}
									size="30"
								/>
							}
						/>
						<ToggleGroupControlOption
							value="reverse"
							label={
								<Icon
									icon={
										orientation === 'x'
											? arrowRight
											: arrowDown
									}
									size="30"
								/>
							}
						/>
					</ToggleGroupControl>
					</ToolsPanelItem>

					{/* Hover Animation State Control */}
					<ToolsPanelItem
						hasValue={() => hasNonDefaultValue('hoverAnimationState', MARQUEE_DEFAULTS.hoverAnimationState)}
						label={__('Hover Behavior', 'orbitools')}
						onDeselect={() => setHoverAnimationState(MARQUEE_DEFAULTS.hoverAnimationState)}
						isShownByDefault={false}
						panelId="marquee-settings-panel"
					>
						<ToggleGroupControl
							label={__('Hover Behavior', 'orbitools')}
							value={hoverAnimationState}
							onChange={setHoverAnimationState}
							isBlock
							help={__('Choose what happens to the animation when users hover', 'orbitools')}
							__nextHasNoMarginBottom={true}
						>
						<ToggleGroupControlOption
							value="paused"
							label={__('Pause', 'orbitools')}
						/>
						<ToggleGroupControlOption
							value="running"
							label={__('Continue', 'orbitools')}
						/>
					</ToggleGroupControl>
					</ToolsPanelItem>

					{/* Animation Speed Control */}
					<ToolsPanelItem
						hasValue={() => hasNonDefaultValue('animationSpeed', MARQUEE_DEFAULTS.animationSpeed)}
						label={__('Animation Speed', 'orbitools')}
						onDeselect={() => setAnimationSpeed(MARQUEE_DEFAULTS.animationSpeed)}
						isShownByDefault={false}
						panelId="marquee-settings-panel"
					>
						<SelectControl
							label={__('Animation Speed', 'orbitools')}
							value={animationSpeed}
							onChange={setAnimationSpeed}
							options={ANIMATION_SPEED_OPTIONS}
							help={__('Control how fast the content scrolls', 'orbitools')}
							__nextHasNoMarginBottom={true}
						/>
					</ToolsPanelItem>

					{/* Auto-Fill Control */}
					<ToolsPanelItem
						hasValue={() => hasNonDefaultValue('autoFill', MARQUEE_DEFAULTS.autoFill)}
						label={__('Auto-Duplicate Content', 'orbitools')}
						onDeselect={() => setAutoFill(MARQUEE_DEFAULTS.autoFill)}
						isShownByDefault={true}
						panelId="marquee-settings-panel"
					>
						<ToggleControl
							label={__('Auto-Duplicate to Fill Width', 'orbitools')}
							checked={autoFill}
							onChange={setAutoFill}
							help={__('Automatically duplicate content to create a seamless loop effect', 'orbitools')}
						/>
					</ToolsPanelItem>

					{/* Minimum Duplicates Control - only show when autoFill is enabled */}
					{autoFill && (
						<ToolsPanelItem
							hasValue={() => hasNonDefaultValue('minDuplicates', MARQUEE_DEFAULTS.minDuplicates)}
							label={__('Minimum Duplicates', 'orbitools')}
							onDeselect={() => setMinDuplicates(MARQUEE_DEFAULTS.minDuplicates)}
							isShownByDefault={false}
							panelId="marquee-settings-panel"
						>
							<RangeControl
								label={__('Minimum Duplicates', 'orbitools')}
								value={minDuplicates}
								onChange={setMinDuplicates}
								min={1}
								max={10}
								step={1}
								help={__('Minimum number of content duplicates to ensure smooth scrolling', 'orbitools')}
								__nextHasNoMarginBottom={true}
							/>
						</ToolsPanelItem>
					)}
				</ToolsPanel>

				<ToolsPanel
					label={__('Style Settings', 'orbitools')}
					resetAll={() => {
						setAttributes({
							whiteSpace: MARQUEE_DEFAULTS.whiteSpace,
							gap: MARQUEE_DEFAULTS.gap
						});
					}}
					panelId="marquee-style-panel"
				>
					{/* White Space Control */}
					<ToolsPanelItem
						hasValue={() => hasNonDefaultValue('whiteSpace', MARQUEE_DEFAULTS.whiteSpace)}
						label={__('Text Wrapping', 'orbitools')}
						onDeselect={() => setWhiteSpace(MARQUEE_DEFAULTS.whiteSpace)}
						isShownByDefault={false}
						panelId="marquee-style-panel"
					>
						<ToggleGroupControl
							label={__('Text Wrapping', 'orbitools')}
							value={whiteSpace}
							onChange={setWhiteSpace}
							isBlock
							help={__('Control how text content wraps within the marquee', 'orbitools')}
							__nextHasNoMarginBottom={true}
						>
						<ToggleGroupControlOption
							value="wrap"
							label={__('Wrap', 'orbitools')}
						/>
						<ToggleGroupControlOption
							value="nowrap"
							label={__('No Wrap', 'orbitools')}
						/>
					</ToggleGroupControl>
					</ToolsPanelItem>

					{/* Gap Control */}
					<ToolsPanelItem
						hasValue={() => hasNonDefaultValue('gap', MARQUEE_DEFAULTS.gap)}
						label={__('Content Gap', 'orbitools')}
						onDeselect={() => setGap(MARQUEE_DEFAULTS.gap)}
						isShownByDefault={false}
						panelId="marquee-style-panel"
					>
						<SelectControl
							label={__('Content Gap', 'orbitools')}
							value={gap}
							onChange={setGap}
							options={GAP_SIZE_OPTIONS}
							help={__('Space between repeated content items', 'orbitools')}
							__nextHasNoMarginBottom={true}
						/>
					</ToolsPanelItem>
				</ToolsPanel>
			</InspectorControls>

			<InspectorControls group="color">
				<ColorGradientSettingsDropdown
					panelId={clientId}
					settings={[
						{
							label: __('Overlay Color', 'orbitools'),
							colorValue: overlayColor,
							onColorChange: setOverlayColor,
							enableAlpha: true,
						},
					]}
					{...colorGradientSettings}
				/>
			</InspectorControls>

			<div {...blockProps}>
				<div className="orb-marquee__wrapper">
					<div {...innerBlockProps} />
				</div>
				{overlayColor && (
					<div className="orb-marquee__overlay" aria-hidden="true" />
				)}
			</div>
		</>
    );
};

export default Edit;