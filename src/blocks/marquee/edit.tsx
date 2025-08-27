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

import React, { useMemo } from 'react';
import {
	InspectorControls,
	useBlockProps,
	useInnerBlocksProps,
	// @ts-ignore - WordPress experimental API
	__experimentalColorGradientSettingsDropdown as ColorGradientSettingsDropdown,
	// @ts-ignore - WordPress experimental API
	__experimentalUseMultipleOriginColorsAndGradients as useMultipleOriginColorsAndGradients,
} from '@wordpress/block-editor';
import {
	__experimentalToolsPanel as ToolsPanel,
	__experimentalToolsPanelItem as ToolsPanelItem,
	__experimentalToggleGroupControl as ToggleGroupControl,
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
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
	getMarqueeStyles
} from './types';

/**
 * Constants for marquee configuration
 */
const SPEED_MIN = 1;
const SPEED_MAX = 30;
const SPEED_STEP = 0.25;
const DEFAULT_SPEED_VALUE = 10;

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
    'orb/button',
    'orb/group'
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
		direction = MARQUEE_DEFAULTS.direction,
		hoverState = MARQUEE_DEFAULTS.hoverState,
		speed = MARQUEE_DEFAULTS.speed,
		overlayColor,
    } = attributes;

    // Generate CSS custom properties for styling
    const marqueeStyles = getMarqueeStyles(attributes);

    const blockProps = useBlockProps({
        className: [
            overlayColor && 'has-overlay-color'
        ].filter(Boolean).join(' '),
        'data-orientation': orientation,
        'data-direction': direction,
        'data-hover': hoverState,
        'data-speed': speed,
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

	const setDirection = (newValue: 'normal' | 'reverse') => {
		setAttributes({ direction: newValue });
	};

	const setHoverState = (newValue: 'paused' | 'running') => {
		setAttributes({ hoverState: newValue });
	};

	const setSpeed = (newValue: number | undefined) => {
		if (newValue === undefined) {
			setAttributes({ speed: MARQUEE_DEFAULTS.speed });
		} else {
			setAttributes({ speed: `${newValue}s` });
		}
	};

	// Memoize current speed value for RangeControl
	const currentSpeedValue = useMemo(() => {
		if (!speed) return DEFAULT_SPEED_VALUE;
		const match = speed.match(/^(\d+(?:\.\d+)?)s$/);
		return match ? parseFloat(match[1]) : DEFAULT_SPEED_VALUE;
	}, [speed]);

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
							direction: MARQUEE_DEFAULTS.direction,
							hoverState: MARQUEE_DEFAULTS.hoverState,
							speed: MARQUEE_DEFAULTS.speed,
						});
					}}
					panelId="marquee-settings-panel"
				>
					{/* Orientation Control */}
					<ToolsPanelItem
						hasValue={() => hasNonDefaultValue('orientation', MARQUEE_DEFAULTS.orientation)}
						label={__('Orientation', 'orbitools')}
						onDeselect={() => setOrientation(MARQUEE_DEFAULTS.orientation)}
						isShownByDefault={false}
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
						hasValue={() => hasNonDefaultValue('direction', MARQUEE_DEFAULTS.direction)}
						label={__('Animation Direction', 'orbitools')}
						onDeselect={() => setDirection(MARQUEE_DEFAULTS.direction)}
						isShownByDefault={false}
						panelId="marquee-settings-panel"
					>
						<ToggleGroupControl
							label={__('Animation Direction', 'orbitools')}
							value={direction}
							onChange={setDirection}
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
						hasValue={() => hasNonDefaultValue('hoverState', MARQUEE_DEFAULTS.hoverState)}
						label={__('Hover Behavior', 'orbitools')}
						onDeselect={() => setHoverState(MARQUEE_DEFAULTS.hoverState)}
						isShownByDefault={false}
						panelId="marquee-settings-panel"
					>
						<ToggleGroupControl
							label={__('Hover Behavior', 'orbitools')}
							value={hoverState}
							onChange={setHoverState}
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
						hasValue={() => hasNonDefaultValue('speed', MARQUEE_DEFAULTS.speed)}
						label={__('Animation Speed', 'orbitools')}
						onDeselect={() => setSpeed(undefined)}
						isShownByDefault={false}
						panelId="marquee-settings-panel"
					>
						<div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: '8px' }}>
							<span style={{ fontSize: '11px', fontWeight: 500, textTransform: 'uppercase', color: '#1e1e1e' }}>
								{__('Animation Speed', 'orbitools')}
							</span>
							<span style={{ fontSize: '11px', color: '#757575' }}>
								{currentSpeedValue}s
							</span>
						</div>
						<RangeControl
							value={currentSpeedValue}
							onChange={setSpeed}
							min={SPEED_MIN}
							max={SPEED_MAX}
							step={SPEED_STEP}
							help={__('Control how fast the content scrolls (in seconds)', 'orbitools')}
							hideLabelFromVision={true}
							withInputField={false}
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
