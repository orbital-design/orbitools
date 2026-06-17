/**
 * Spacings Controls - Control Registration
 *
 * Automatically adds spacing controls to blocks with orbitools.spacings support
 */

import { ResponsiveControl, ResponsiveDots } from '../../../../core/utils/responsive-control.js';

(function() {
    // Configuration functions from spacings-config.js (available globally)
    function getBlockSpacingsConfig(blockName) {
        const configData = window.orbitoolsSpacingsConfig || {};

        if (configData[blockName]) {
            return configData[blockName];
        }

        return {
            spacings: [],
            breakpoints: [],
            supports: {
                enabled: false,
                breakpoints: false,
                gap: false,
                margin: false,
                padding: false
            }
        };
    }


    const { addFilter } = wp.hooks;
    const { createHigherOrderComponent } = wp.compose;
    const { Fragment } = wp.element;
    const { InspectorControls, useSettings } = wp.blockEditor;
    const {
        __experimentalToolsPanel: ToolsPanel,
        __experimentalToolsPanelItem: ToolsPanelItem,
        RangeControl,
        Button,
        __experimentalVStack: VStack
    } = wp.components;


    /**
     * Check if block has spacings support
     */
    function blockHasSpacingsSupport(blockName) {
        const blockType = wp.blocks.getBlockType(blockName);
        if (!blockType || !blockType.supports || !blockType.supports.orbitools) {
            return null;
        }

        const spacingsSupports = blockType.supports.orbitools.spacings;
        if (!spacingsSupports || spacingsSupports === false) {
            return null;
        }

        return spacingsSupports;
    }

    /**
     * Get spacing presets from WordPress settings
     */
    function useSpacingPresets() {
        const [settings] = useSettings('spacing.spacingSizes');
        return settings || [];
    }

    /**
     * Simple Spacings Control Component
     */
    function SpacingsControl({ gap, padding, margin, onGapChange, onPaddingChange, onMarginChange, blockName, supports }) {
        // Spacing presets from the configuration system (falls back to
        // theme.json via useSpacingPresets). The responsive behaviour —
        // which viewport's value is being edited — is owned by the shared
        // ResponsiveControl framework and driven by the editor's native
        // screen-size preview toggle, so there's no breakpoint tab bar here.
        const config = getBlockSpacingsConfig(blockName);
        const spacingPresets = config.spacings || useSpacingPresets();

        /**
         * Helper to get spacing index by slug
         */
        function getSpacingIndexByValue(spacingSizes, value) {
            if (!spacingSizes || !Array.isArray(spacingSizes) || !value) return -1;
            return spacingSizes.findIndex((size) => size.slug === value);
        }

        /**
         * Get display name for spacing value
         */
        function getSpacingDisplayName(spacingSizes, value) {
            if (!value) return 'Default';
            if (value === '0') return 'None';

            const index = getSpacingIndexByValue(spacingSizes, value);
            if (index >= 0 && spacingSizes[index]) {
                return spacingSizes[index].name;
            }

            return value;
        }

        /**
         * Create a custom box control for padding/margin using our spacing presets (EXACT COPY from working version)
         */
        function createBoxControl(spacingSizes, spacingType, value, onChange) {
            // Handle legacy string format (convert to new format)
            if (typeof value === 'string') {
                value = {
                    type: 'all',
                    value: value
                };
            }

            // Get current mode from stored data
            const currentMode = value?.type || 'all';

            const toggleMode = () => {
                const currentValue = value?.value || value?.x || value?.y || value?.top || undefined;

                if (currentMode === 'all') {
                    // Switch to split (x/y) mode
                    onChange({
                        type: 'split',
                        x: currentValue, // horizontal (left/right)
                        y: currentValue  // vertical (top/bottom)
                    });
                } else if (currentMode === 'split') {
                    // Switch to sides mode
                    onChange({
                        type: 'sides',
                        top: value?.y || currentValue,
                        right: value?.x || currentValue,
                        bottom: value?.y || currentValue,
                        left: value?.x || currentValue
                    });
                } else {
                    // Switch back to all mode
                    const sides = [value?.top, value?.right, value?.bottom, value?.left];
                    const definedSides = sides.filter(s => s !== undefined);
                    const uniqueSides = Array.from(new Set(definedSides));

                    onChange({
                        type: 'all',
                        value: uniqueSides[0] || undefined
                    });
                }
            };

            // Get current values - handle all 3 modes
            const getCurrentValues = () => {
                if (!value) return { all: undefined, x: undefined, y: undefined, top: undefined, right: undefined, bottom: undefined, left: undefined };

                if (value.type === 'all') {
                    return {
                        all: value.value,
                        x: undefined,
                        y: undefined,
                        top: undefined,
                        right: undefined,
                        bottom: undefined,
                        left: undefined
                    };
                } else if (value.type === 'split') {
                    return {
                        all: undefined,
                        x: value.x,
                        y: value.y,
                        top: undefined,
                        right: undefined,
                        bottom: undefined,
                        left: undefined
                    };
                } else if (value.type === 'sides') {
                    return {
                        all: undefined,
                        x: undefined,
                        y: undefined,
                        top: value.top,
                        right: value.right,
                        bottom: value.bottom,
                        left: value.left
                    };
                }

                // Legacy format support (backwards compatibility)
                if (typeof value === 'string') {
                    return {
                        all: value,
                        x: undefined,
                        y: undefined,
                        top: undefined,
                        right: undefined,
                        bottom: undefined,
                        left: undefined
                    };
                }

                return { all: undefined, x: undefined, y: undefined, top: undefined, right: undefined, bottom: undefined, left: undefined };
            };

            const currentValues = getCurrentValues();

            // Handle all sides value change
            const handleAllChange = (newValue) => {
                onChange({
                    type: 'all',
                    value: newValue
                });
            };

            // Handle x/y split value change
            const handleSplitChange = (axis, newValue) => {
                onChange({
                    type: 'split',
                    x: axis === 'x' ? newValue : value?.x,
                    y: axis === 'y' ? newValue : value?.y
                });
            };

            // Handle individual side value change
            const handleSideChange = (side, newValue) => {
                const currentSides = {
                    top: value?.top,
                    right: value?.right,
                    bottom: value?.bottom,
                    left: value?.left
                };

                onChange({
                    type: 'sides',
                    ...currentSides,
                    [side]: newValue
                });
            };

            // Icons for different modes (EXACT COPY from working version)
            const icons = {
                // All sides icon
                all: wp.element.createElement('svg', { width: "16", height: "16", viewBox: "0 0 640 640", fill: "none" },
                    wp.element.createElement('path', { fill: "#32A3E2", d: "M344 320c0 13.3-10.7 24-24 24s-24-10.7-24-24 10.7-24 24-24 24 10.7 24 24Z" }),
                    wp.element.createElement('path', { fill: "#1D303A", d: "M480 160v320H160V160h320ZM160 96c-35.3 0-64 28.7-64 64v320c0 35.3 28.7 64 64 64h320c35.3 0 64-28.7 64-64V160c0-35.3-28.7-64-64-64H160Z" })
                ),
                // Side icons for individual sides
                sides: {
                    top: wp.element.createElement('svg', { width: "16", height: "16", viewBox: "0 0 640 640", fill: "none" },
                        wp.element.createElement('path', { fill: "#32A3E2", d: "M96 256c0 17.7 14.3 32 32 32s32-14.3 32-32-14.3-32-32-32-32 14.3-32 32Zm0 128c0 17.7 14.3 32 32 32s32-14.3 32-32-14.3-32-32-32-32 14.3-32 32Zm0 128c0 17.7 14.3 32 32 32s32-14.3 32-32-14.3-32-32-32-32 14.3-32 32Zm128 0c0 17.7 14.3 32 32 32s32-14.3 32-32-14.3-32-32-32-32 14.3-32 32Zm128 0c0 17.7 14.3 32 32 32s32-14.3 32-32-14.3-32-32-32-32 14.3-32 32Zm128-256c0 17.7 14.3 32 32 32s32-14.3 32-32-14.3-32-32-32-32 14.3-32 32Zm0 128c0 17.7 14.3 32 32 32s32-14.3 32-32-14.3-32-32-32-32 14.3-32 32Zm0 128c0 17.7 14.3 32 32 32s32-14.3 32-32-14.3-32-32-32-32 14.3-32 32Z" }),
                        wp.element.createElement('path', { fill: "#1D303A", d: "M96 128c0-17.7 14.3-32 32-32h384c17.7 0 32 14.3 32 32s-14.3 32-32 32H128c-17.7 0-32-14.3-32-32Z" })
                    ),
                    right: wp.element.createElement('svg', { width: "16", height: "16", viewBox: "0 0 640 640", fill: "none" },
                        wp.element.createElement('path', { fill: "#32A3E2", d: "M96 128c0 17.7 14.3 32 32 32s32-14.3 32-32-14.3-32-32-32-32 14.3-32 32Zm0 128c0 17.7 14.3 32 32 32s32-14.3 32-32-14.3-32-32-32-32 14.3-32 32Zm0 128c0 17.7 14.3 32 32 32s32-14.3 32-32-14.3-32-32-32-32 14.3-32 32Zm0 128c0 17.7 14.3 32 32 32s32-14.3 32-32-14.3-32-32-32-32 14.3-32 32Zm128-384c0 17.7 14.3 32 32 32s32-14.3 32-32-14.3-32-32-32-32 14.3-32 32Zm0 384c0 17.7 14.3 32 32 32s32-14.3 32-32-14.3-32-32-32-32 14.3-32 32Zm128-384c0 17.7 14.3 32 32 32s32-14.3 32-32-14.3-32-32-32-32 14.3-32 32Zm0 384c0 17.7 14.3 32 32 32s32-14.3 32-32-14.3-32-32-32-32 14.3-32 32Z" }),
                        wp.element.createElement('path', { fill: "#1D303A", d: "M512 96c17.7 0 32 14.3 32 32v384c0 17.7-14.3 32-32 32s-32-14.3-32-32V128c0-17.7 14.3-32 32-32Z" })
                    ),
                    bottom: wp.element.createElement('svg', { width: "16", height: "16", viewBox: "0 0 640 640", fill: "none" },
                        wp.element.createElement('path', { fill: "#32A3E2", d: "M160 128c0 17.7-14.3 32-32 32s-32-14.3-32-32 14.3-32 32-32 32 14.3 32 32Zm0 128c0 17.7-14.3 32-32 32s-32-14.3-32-32 14.3-32 32-32 32 14.3 32 32Zm0 128c0 17.7-14.3 32-32 32s-32-14.3-32-32 14.3-32 32-32 32 14.3 32 32Zm128-256c0 17.7-14.3 32-32 32s-32-14.3-32-32 14.3-32 32-32 32 14.3 32 32Zm128 0c0 17.7-14.3 32-32 32s-32-14.3-32-32 14.3-32 32-32 32 14.3 32 32Zm128 0c0 17.7-14.3 32-32 32s-32-14.3-32-32 14.3-32 32-32 32 14.3 32 32Zm0 128c0 17.7-14.3 32-32 32s-32-14.3-32-32 14.3-32 32-32 32 14.3 32 32Zm0 128c0 17.7-14.3 32-32 32s-32-14.3-32-32 14.3-32 32-32 32 14.3 32 32Z" }),
                        wp.element.createElement('path', { fill: "#1D303A", d: "M544 512c0 17.7-14.3 32-32 32H128c-17.7 0-32-14.3-32-32s14.3-32 32-32h384c17.7 0 32 14.3 32 32Z" })
                    ),
                    left: wp.element.createElement('svg', { width: "16", height: "16", viewBox: "0 0 640 640", fill: "none" },
                        wp.element.createElement('path', { fill: "#32A3E2", d: "M224 128c0 17.7 14.3 32 32 32s32-14.3 32-32-14.3-32-32-32-32 14.3-32 32Zm0 384c0 17.7 14.3 32 32 32s32-14.3 32-32-14.3-32-32-32-32 14.3-32 32Zm128-384c0 17.7 14.3 32 32 32s32-14.3 32-32-14.3-32-32-32-32 14.3-32 32Zm0 384c0 17.7 14.3 32 32 32s32-14.3 32-32-14.3-32-32-32-32 14.3-32 32Zm128-384c0 17.7 14.3 32 32 32s32-14.3 32-32-14.3-32-32-32-32 14.3-32 32Zm0 128c0 17.7 14.3 32 32 32s32-14.3 32-32-14.3-32-32-32-32 14.3-32 32Zm0 128c0 17.7 14.3 32 32 32s32-14.3 32-32-14.3-32-32-32-32 14.3-32 32Zm0 128c0 17.7 14.3 32 32 32s32-14.3 32-32-14.3-32-32-32-32 14.3-32 32Z" }),
                        wp.element.createElement('path', { fill: "#1D303A", d: "M128 544c-17.7 0-32-14.3-32-32V128c0-17.7 14.3-32 32-32s32 14.3 32 32v384c0 17.7-14.3 32-32 32Z" })
                    )
                },
                // Split icons for X/Y axes
                split: {
                    x: wp.element.createElement('svg', { width: "16", height: "16", viewBox: "0 0 640 640", fill: "none" },
                        wp.element.createElement('path', { fill: "#32A3E2", d: "M224 128c0 17.7 14.3 32 32 32s32-14.3 32-32-14.3-32-32-32-32 14.3-32 32Zm0 384c0 17.7 14.3 32 32 32s32-14.3 32-32-14.3-32-32-32-32 14.3-32 32Zm128-384c0 17.7 14.3 32 32 32s32-14.3 32-32-14.3-32-32-32-32 14.3-32 32Zm0 384c0 17.7 14.3 32 32 32s32-14.3 32-32-14.3-32-32-32-32 14.3-32 32Z" }),
                        wp.element.createElement('path', { fill: "#1D303A", d: "M128 544c-17.7 0-32-14.3-32-32V128c0-17.7 14.3-32 32-32s32 14.3 32 32v384c0 17.7-14.3 32-32 32ZM512 96c17.7 0 32 14.3 32 32v384c0 17.7-14.3 32-32 32s-32-14.3-32-32V128c0-17.7 14.3-32 32-32Z" })
                    ),
                    y: wp.element.createElement('svg', { width: "16", height: "16", viewBox: "0 0 640 640", fill: "none" },
                        wp.element.createElement('path', { fill: "#32A3E2", d: "M160 256c0 17.7-14.3 32-32 32s-32-14.3-32-32 14.3-32 32-32 32 14.3 32 32Zm0 128c0 17.7-14.3 32-32 32s-32-14.3-32-32 14.3-32 32-32 32 14.3 32 32Zm384-128c0 17.7-14.3 32-32 32s-32-14.3-32-32 14.3-32 32-32 32 14.3 32 32Zm0 128c0 17.7-14.3 32-32 32s-32-14.3-32-32 14.3-32 32-32 32 14.3 32 32Z" }),
                        wp.element.createElement('path', { fill: "#1D303A", d: "M544 512c0 17.7-14.3 32-32 32H128c-17.7 0-32-14.3-32-32s14.3-32 32-32h384c17.7 0 32 14.3 32 32ZM96 128c0-17.7 14.3-32 32-32h384c17.7 0 32 14.3 32 32s-14.3 32-32 32H128c-17.7 0-32-14.3-32-32Z" })
                    )
                }
            };

            // Toggle icon for button
            const toggleIcon = wp.element.createElement('svg', { xmlns: "http://www.w3.org/2000/svg", viewBox: "0 0 640 640", width: "16", height: "16" },
                wp.element.createElement('path', { fill: "#1d303a", d: "M64 224c0 17.7 14.3 32 32 32h293.5c-3.5-10-5.5-20.8-5.5-32s1.9-22 5.5-32H96c-17.7 0-32 14.3-32 32zm186.5 160c3.5 10 5.5 20.8 5.5 32s-1.9 22-5.5 32H544c17.7 0 32-14.3 32-32s-14.3-32-32-32H250.5z" }),
                wp.element.createElement('path', { fill: "#32a3e2", d: "M480 256c-17.7 0-32-14.3-32-32s14.3-32 32-32 32 14.3 32 32-14.3 32-32 32zm0-128c-53 0-96 43-96 96s43 96 96 96 96-43 96-96-43-96-96-96zM160 448c-17.7 0-32-14.3-32-32s14.3-32 32-32 32 14.3 32 32-14.3 32-32 32zm0-128c-53 0-96 43-96 96s43 96 96 96 96-43 96-96-43-96-96-96z" })
            );

            return wp.element.createElement('div', null,
                // Header with label and toggle button
                wp.element.createElement('div', {
                    style: {
                        display: 'flex',
                        justifyContent: 'space-between',
                        alignItems: 'center',
                    }
                },
                    wp.element.createElement('label', {
                        style: {
                            fontSize: '11px',
                            fontWeight: '500',
                            color: '#757575',
                            margin: 0
                        }
                    }, spacingType === 'padding' ? 'Padding' : 'Margin'),
                    wp.element.createElement(Button, {
                        size: 'small',
                        variant: 'tertiary',
                        onClick: toggleMode,
                        style: { minWidth: 'auto', padding: '6px', background: 'transparent' }
                    }, toggleIcon)
                ),

                currentMode === 'all' ? (
                    // All sides control
                    wp.element.createElement('div', {
                        style: {
                            display: 'flex',
                            alignItems: 'center',
                            gap: '8px'
                        }
                    },
                        wp.element.createElement('div', {
                            style: {
                                width: '20px',
                                height: '20px',
                                display: 'flex',
                                alignItems: 'center',
                                justifyContent: 'center',
                                color: '#757575',
                                flexShrink: 0
                            }
                        }, icons.all),
                        wp.element.createElement('div', { style: { flex: 1 } },
                            createSpacingControl(spacingSizes, spacingType, currentValues.all, handleAllChange, true)
                        )
                    )
                ) : currentMode === 'split' ? (
                    // X/Y split controls
                    wp.element.createElement('div', { style: { display: 'grid', gap: '12px' } },
                        ['x', 'y'].map((axis) =>
                            wp.element.createElement('div', {
                                key: axis,
                                style: {
                                    display: 'flex',
                                    alignItems: 'center',
                                    gap: '8px'
                                }
                            },
                                wp.element.createElement('div', {
                                    style: {
                                        width: '20px',
                                        height: '20px',
                                        display: 'flex',
                                        alignItems: 'center',
                                        justifyContent: 'center',
                                        color: '#757575',
                                        flexShrink: 0
                                    }
                                }, icons.split[axis]),
                                wp.element.createElement('div', { style: { flex: 1 } },
                                    createSpacingControl(spacingSizes, spacingType, currentValues[axis], (newValue) => handleSplitChange(axis, newValue), true)
                                )
                            )
                        )
                    )
                ) : (
                    // Individual sides controls
                    wp.element.createElement('div', { style: { display: 'grid', gap: '12px' } },
                        ['top', 'right', 'bottom', 'left'].map((side) =>
                            wp.element.createElement('div', {
                                key: side,
                                style: {
                                    display: 'flex',
                                    alignItems: 'center',
                                    gap: '8px'
                                }
                            },
                                wp.element.createElement('div', {
                                    style: {
                                        width: '20px',
                                        height: '20px',
                                        display: 'flex',
                                        alignItems: 'center',
                                        justifyContent: 'center',
                                        color: '#757575',
                                        flexShrink: 0
                                    }
                                }, icons.sides[side]),
                                wp.element.createElement('div', { style: { flex: 1 } },
                                    createSpacingControl(spacingSizes, spacingType, currentValues[side], (newValue) => handleSideChange(side, newValue), true)
                                )
                            )
                        )
                    )
                )
            );
        }

        /**
         * Create a spacing control for gap (simple slider) (EXACT COPY from working version)
         */
        function createSpacingControl(spacingSizes, spacingType, value, onChange, hideLabel) {
            const currentIndex = getSpacingIndexByValue(spacingSizes, value || '');
            const maxIndex = spacingSizes.length - 1;

            // Convert to slider index (0 = default, 1 = none, 2+ = spacing sizes)
            let sliderValue = 0;
            if (value === undefined) {
                sliderValue = 0; // Default
            } else if (value === '0') {
                sliderValue = 1; // None
            } else if (currentIndex >= 0) {
                sliderValue = currentIndex + 2; // Spacing size
            } else {
                sliderValue = 0; // Fallback to default
            }

            const updateValue = (index) => {
                if (index === undefined || index === 0) {
                    onChange(undefined); // Default
                } else if (index === 1) {
                    onChange('0'); // None
                } else if (index !== null && index !== undefined) {
                    const spacingIndex = index - 2;
                    const spacing = spacingSizes[spacingIndex];
                    if (spacing) {
                        onChange(spacing.slug);
                    } else {
                        onChange(undefined);
                    }
                }
            };

            const spacingLabels = {
                gap: 'Gap',
                padding: 'Padding',
                margin: 'Margin'
            };

            // Gap icon for consistency with padding controls
            const gapIcon = spacingType === 'gap' ? wp.element.createElement('svg', { xmlns: "http://www.w3.org/2000/svg", fill: "none", viewBox: "0 0 640 640", width: "16", height: "16" },
                wp.element.createElement('path', { fill: "#32A3E2", d: "M32 192v256c0 17.7 14.3 32 32 32s32-14.3 32-32V192c0-17.7-14.3-32-32-32s-32 14.3-32 32Zm512 0v256c0 17.7 14.3 32 32 32s32-14.3 32-32V192c0-17.7-14.3-32-32-32s-32 14.3-32 32Z" }),
                wp.element.createElement('path', { fill: "#1D303A", d: "m422.6 406.6 64-64c12.5-12.5 12.5-32.8 0-45.3l-64-64c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3l9.4 9.4H253.2l9.4-9.4c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0l-64 64c-6 6-9.4 14.1-9.4 22.6 0 8.5 3.4 16.6 9.4 22.6l64 64c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3l-9.4-9.4h133.5l-9.4 9.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0v.1Z" })
            ) : null;

            return wp.element.createElement('div', null,
                !hideLabel && wp.element.createElement('div', {
                    style: {
                        display: 'flex',
                        justifyContent: 'space-between',
                        alignItems: 'center',
                    }
                },
                    wp.element.createElement('label', {
                        style: {
                            fontSize: '11px',
                            fontWeight: '500',
                            color: '#757575',
                            margin: 0
                        }
                    }, spacingLabels[spacingType]),
                    wp.element.createElement('span', {
                        style: {
                            fontSize: '11px',
                            fontWeight: '400',
                            color: '#949494'
                        }
                    }, getSpacingDisplayName(spacingSizes, value || ''))
                ),

                // Gap control with icon layout matching padding controls
                spacingType === 'gap' ? wp.element.createElement('div', {
                    style: {
                        display: 'flex',
                        alignItems: 'center',
                        gap: '8px'
                    }
                },
                    wp.element.createElement('div', {
                        style: {
                            width: '20px',
                            height: '20px',
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'center',
                            color: '#757575',
                            flexShrink: 0
                        }
                    }, gapIcon),
                    wp.element.createElement('div', { style: { flex: 1 } },
                        wp.element.createElement(RangeControl, {
                            value: sliderValue,
                            onChange: updateValue,
                            min: 0,
                            max: maxIndex + 2,
                            step: 1,
                            marks: true,
                            withInputField: false,
                            renderTooltipContent: (index) => {
                                if (!index || index === 0) return 'Default';
                                if (index === 1) return 'None';
                                const spacingIndex = index - 2;
                                if (spacingIndex >= 0 && spacingIndex < spacingSizes.length) {
                                    const spacing = spacingSizes[spacingIndex];
                                    return spacing ? spacing.name : 'None';
                                }
                                return 'None';
                            },
                            __next40pxDefaultSize: true,
                            __nextHasNoMarginBottom: true
                        })
                    )
                ) : wp.element.createElement(RangeControl, {
                    value: sliderValue,
                    onChange: updateValue,
                    min: 0,
                    max: maxIndex + 2,
                    step: 1,
                    marks: true,
                    withInputField: false,
                    renderTooltipContent: (index) => {
                        if (!index || index === 0) return 'Default';
                        if (index === 1) return 'None';
                        const spacingIndex = index - 2;
                        if (spacingIndex >= 0 && spacingIndex < spacingSizes.length) {
                            const spacing = spacingSizes[spacingIndex];
                            return spacing ? spacing.name : 'None';
                        }
                        return 'None';
                    },
                    __next40pxDefaultSize: true,
                    __nextHasNoMarginBottom: true
                })
            );
        }

        // Combined responsive indicator: a viewport counts as "set" when
        // any of gap / padding / margin has a value for that slug.
        function spacingsSetForSlug(slug) {
            function has(obj) {
                var v = obj && obj[slug];
                if (v === undefined || v === null || v === '') return false;
                if (typeof v === 'object') return Object.keys(v).length > 0;
                return true;
            }
            return has(gap) || has(padding) || has(margin);
        }

        // The active device (slug) comes from the ResponsiveControl
        // framework, driven by the editor's screen-size preview toggle.
        // Each device gets its own nested ToolsPanel of gap/padding/margin
        // bound to that slug's slice of the attribute object.
        return wp.element.createElement(ResponsiveControl, {
            blockName: blockName,
            wrap: false,
            render: function(ctx) {
                const slug = ctx.slug;
                const panelId = slug + '-spacings-panel';

                // No PanelBody wrapper — the ToolsPanel is the panel. The
                // dots sit inline right after the "Spacings" label.
                return wp.element.createElement(ToolsPanel, {
                        label: wp.element.createElement('span', { className: 'orbitools-spacings-label' },
                            'Spacings',
                            wp.element.createElement(ResponsiveDots, { isSet: spacingsSetForSlug })
                        ),
                        panelId: panelId
                    },
                        wp.element.createElement(VStack, {
                            spacing: 1,
                            style: { gridColumn: '1 / -1' }
                        },
                            supports.gap && wp.element.createElement(ToolsPanelItem, {
                                hasValue: () => gap?.[slug] !== undefined,
                                label: 'Gap',
                                onDeselect: () => {
                                    const newGap = { ...gap }; delete newGap[slug]; onGapChange(newGap);
                                },
                                isShownByDefault: false,
                                panelId: panelId
                            },
                                createSpacingControl(
                                    spacingPresets, 'gap', gap?.[slug],
                                    (newValue) => { onGapChange({ ...gap, [slug]: newValue }); }
                                )
                            ),
                            supports.padding && wp.element.createElement(ToolsPanelItem, {
                                hasValue: () => padding?.[slug] !== undefined,
                                label: 'Padding',
                                onDeselect: () => {
                                    const newPadding = { ...padding }; delete newPadding[slug]; onPaddingChange(newPadding);
                                },
                                isShownByDefault: false,
                                panelId: panelId
                            },
                                createBoxControl(
                                    spacingPresets, 'padding', padding?.[slug] || {},
                                    (newValue) => { onPaddingChange({ ...padding, [slug]: newValue }); }
                                )
                            ),
                            supports.margin && wp.element.createElement(ToolsPanelItem, {
                                hasValue: () => margin?.[slug] !== undefined,
                                label: 'Margin',
                                onDeselect: () => {
                                    const newMargin = { ...margin }; delete newMargin[slug]; onMarginChange(newMargin);
                                },
                                isShownByDefault: false,
                                panelId: panelId
                            },
                                createBoxControl(
                                    spacingPresets, 'margin', margin?.[slug] || {},
                                    (newValue) => { onMarginChange({ ...margin, [slug]: newValue }); }
                                )
                            )
                        )
                    );
            }
        });
    }

    // Add automatic spacing controls
    const withSpacingControls = createHigherOrderComponent(function(BlockEdit) {
        return function(props) {
            const spacingsSupports = blockHasSpacingsSupport(props.name);

            if (!spacingsSupports) {
                return wp.element.createElement(BlockEdit, props);
            }

            const { attributes, setAttributes } = props;
            const { orbGap, orbPadding, orbMargin } = attributes;

            // Handle spacing changes
            const handleGapChange = (newGap) => {
                setAttributes({ orbGap: newGap });
            };

            const handlePaddingChange = (newPadding) => {
                setAttributes({ orbPadding: newPadding });
            };

            const handleMarginChange = (newMargin) => {
                setAttributes({ orbMargin: newMargin });
            };

            return wp.element.createElement(Fragment, {},
                wp.element.createElement(BlockEdit, props),
                wp.element.createElement(InspectorControls, { group: 'styles' },
                    wp.element.createElement(SpacingsControl, {
                        gap: orbGap,
                        padding: orbPadding,
                        margin: orbMargin,
                        onGapChange: handleGapChange,
                        onPaddingChange: handlePaddingChange,
                        onMarginChange: handleMarginChange,
                        blockName: props.name,
                        supports: spacingsSupports
                    })
                )
            );
        };
    }, 'withSpacingControls');

    addFilter(
        'editor.BlockEdit',
        'orbitools/add-spacing-controls',
        withSpacingControls,
        5
    );
})();
