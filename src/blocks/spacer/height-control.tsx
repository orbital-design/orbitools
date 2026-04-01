/**
 * Spacer Height Control
 *
 * PanelBody with breakpoint tabs + height slider per tab.
 * Matches the spacings/aspect ratio responsive control pattern.
 *
 * @file blocks/spacer/height-control.tsx
 * @since 1.0.0
 */

import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
    PanelBody,
    RangeControl,
    Tooltip,
    __experimentalToolsPanel as ToolsPanel,
    __experimentalToolsPanelItem as ToolsPanelItem,
    __experimentalVStack as VStack,
} from '@wordpress/components';
import { useSettings } from '@wordpress/block-editor';
import { getSpacingDisplayName } from '../utils/control-helpers';
import { getBreakpointOptions } from '../../core/utils/breakpoints';

export interface ResponsiveValue<T = string> {
    base?: T;
    sm?: T;
    md?: T;
    lg?: T;
    xl?: T;
    [key: string]: T | undefined;
}

export interface SpacerHeightControlProps {
    height: ResponsiveValue<string>;
    onHeightChange: (height: ResponsiveValue<string>) => void;
    blockName: string;
}

interface BreakpointTab {
    slug: string;
    label: string;
    tooltip: string;
    icon: string | null;
}

/**
 * Generate CSS classes for height values
 */
export function getHeightClasses(height: ResponsiveValue<string>): string {
    const classes: string[] = [];

    Object.entries(height).forEach(([breakpoint, value]) => {
        if (value === undefined || value === null || value === '') return;

        const className = breakpoint === 'base'
            ? `orb-spacer--${value}`
            : `${breakpoint}:orb-spacer--${value}`;

        classes.push(className);
    });

    return classes.join(' ');
}

/**
 * Spacer Height Control Component
 */
export default function SpacerHeightControl({
    height,
    onHeightChange,
    blockName
}: SpacerHeightControlProps) {
    const [spacingSizes] = useSettings('spacing.spacingSizes');
    const breakpoints = getBreakpointOptions(blockName);

    // Read raw breakpoints from theme config for base entry and icons
    const themeConfig = (window as any).orbitoolsThemeConfig || {};
    const rawBreakpoints = themeConfig?.settings?.breakpoints || [];
    const baseEntry = rawBreakpoints.find((bp: any) => bp.slug === 'base');

    // Build all tabs
    const allBreakpoints: BreakpointTab[] = [{
        slug: 'base',
        label: baseEntry?.name || 'All',
        tooltip: baseEntry?.name || 'All Devices',
        icon: baseEntry?.icon || null,
    }];
    breakpoints.forEach((bp: any) => {
        allBreakpoints.push({
            slug: bp.slug,
            label: bp.slug.toUpperCase(),
            tooltip: bp.name || bp.slug,
            icon: bp.icon || null,
        });
    });

    // Active tab — default to base
    const [activeTab, setActiveTab] = useState('base');

    if (!spacingSizes || !Array.isArray(spacingSizes)) {
        return null;
    }

    // Add fill option
    const allOptions = [...spacingSizes, { slug: 'fill', name: 'Fill' }];

    const getValue = (slug: string): string | undefined => {
        return height?.[slug] || undefined;
    };

    const setValue = (slug: string, value: string | undefined) => {
        const updated = { ...height };
        if (value !== undefined) {
            updated[slug] = value;
        } else {
            delete updated[slug];
        }
        onHeightChange(updated);
    };

    // Active tab data for nested panel label
    const activeTabData = allBreakpoints.find(bp => bp.slug === activeTab);
    const nestedPanelLabel = activeTabData
        ? (activeTabData.slug === 'base'
            ? activeTabData.tooltip
            : activeTabData.tooltip + '+')
        : 'Height';

    // Height slider for active tab
    const currentValue = getValue(activeTab);
    const currentIndex = currentValue ? allOptions.findIndex(o => o.slug === currentValue) : -1;
    const sliderValue = currentIndex >= 0 ? currentIndex : 0;

    const updateHeight = (index: number | undefined) => {
        if (index === undefined || index < 0) {
            setValue(activeTab, undefined);
        } else if (index < allOptions.length) {
            setValue(activeTab, allOptions[index].slug);
        }
    };

    const activeControls = (
        <div className="orbitools-nested-panel">
            <ToolsPanel
                label={ nestedPanelLabel }
                panelId={ `${activeTab}-height-panel` }
            >
                <VStack
                    spacing={ 1 }
                    style={{ gridColumn: '1 / -1' }}
                >
                    <ToolsPanelItem
                        hasValue={ () => getValue(activeTab) !== undefined }
                        onDeselect={ () => setValue(activeTab, undefined) }
                        label="Height"
                        isShownByDefault={ true }
                        panelId={ `${activeTab}-height-panel` }
                    >
                        <div>
                            <div style={{
                                display: 'flex',
                                justifyContent: 'space-between',
                                alignItems: 'center',
                            }}>
                                <label style={{
                                    fontSize: '11px',
                                    fontWeight: 500,
                                    color: '#757575',
                                    margin: 0
                                }}>
                                    Height
                                </label>
                                <span style={{
                                    fontSize: '11px',
                                    fontWeight: 400,
                                    color: '#949494'
                                }}>
                                    { getSpacingDisplayName(spacingSizes, currentValue || '') }
                                </span>
                            </div>
                            <RangeControl
                                value={ sliderValue }
                                onChange={ updateHeight }
                                min={ 0 }
                                max={ allOptions.length - 1 }
                                step={ 1 }
                                marks={ true }
                                withInputField={ false }
                                renderTooltipContent={ (index) => {
                                    if (index === undefined || index === null || typeof index !== 'number') return '';
                                    if (index < 0 || index >= allOptions.length) return 'Default';
                                    return allOptions[index].name;
                                } }
                                __next40pxDefaultSize={ true }
                                __nextHasNoMarginBottom={ true }
                            />
                        </div>
                    </ToolsPanelItem>
                </VStack>
            </ToolsPanel>
        </div>
    );

    return (
        <PanelBody title={ __('Height', 'orbitools') } initialOpen={ true }>
            { /* Tab bar */ }
            <div style={{
                display: 'flex',
                gap: '4px',
                marginBottom: '12px',
                padding: '4px',
                background: '#F3F5F7',
                borderRadius: '6px'
            }}>
                { allBreakpoints.map((bp) => {
                    const isActive = activeTab === bp.slug;
                    return (
                        <Tooltip key={ bp.slug } text={ bp.tooltip }>
                            <button
                                onClick={ () => setActiveTab(bp.slug) }
                                style={{
                                    flex: 1,
                                    display: 'flex',
                                    alignItems: 'center',
                                    justifyContent: 'center',
                                    padding: '6px 4px',
                                    border: 'none',
                                    borderRadius: '4px',
                                    background: isActive ? '#fff' : 'transparent',
                                    boxShadow: isActive ? '0 1px 3px rgba(0,0,0,0.1)' : 'none',
                                    color: isActive ? '#1e1e1e' : '#757575',
                                    cursor: 'pointer',
                                    fontSize: '11px',
                                    fontWeight: isActive ? 600 : 400,
                                    lineHeight: '1',
                                }}
                            >
                                { bp.icon
                                    ? <span
                                        dangerouslySetInnerHTML={{ __html: bp.icon }}
                                        style={{ display: 'flex', alignItems: 'center', width: '20px', height: '20px' }}
                                    />
                                    : bp.label
                                }
                            </button>
                        </Tooltip>
                    );
                }) }
            </div>

            { /* Active tab's controls */ }
            { activeControls }
        </PanelBody>
    );
}
