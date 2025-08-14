import React from 'react';
import {InnerBlocks, useBlockProps, RichText, BlockControls} from '@wordpress/block-editor';
import { ToolbarGroup, ToolbarDropdownMenu } from '@wordpress/components';
import { chevronDown, arrowDown, plus, close } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
import type { BlockEditProps } from '@wordpress/blocks';

interface ReadMoreAttributes {
    openText: string;
    closeText: string;
    iconType: string;
    orbPadding?: any;
    orbGap?: any;
    orbMargin?: any;
}

const ALLOWED_BLOCKS = [
    'core/paragraph',
    'core/list',
    'core/heading',
    'core/separator',
    'core/shortcode',
    'orb/spacer',
    'orb/collection',
    'orb/button'
];

const TEMPLATE: [string, any][] = [
    ['core/paragraph', { placeholder: 'Add your content here...' }]
];

/**
 * Extract spacing classes from a className string
 */
function extractSpacingClasses(className: string = ''): { spacing: string; remaining: string } {
    const classes = className.split(' ').filter(Boolean);
    const spacingClasses: string[] = [];
    const remainingClasses: string[] = [];
    
    classes.forEach(cls => {
        // Check if class is a spacing class (has-spacing, has-gap-, p-, px-, py-, pt-, pr-, pb-, pl-, m-, mx-, my-, mt-, mr-, mb-, ml-, or responsive variants)
        if (cls === 'has-spacing' || 
            cls.match(/^(has-gap|p|px|py|pt|pr|pb|pl|m|mx|my|mt|mr|mb|ml)(-|$)/) ||
            cls.match(/^(sm|md|lg|xl):(has-gap|p|px|py|pt|pr|pb|pl|m|mx|my|mt|mr|mb|ml)(-|$)/)) {
            spacingClasses.push(cls);
        } else {
            remainingClasses.push(cls);
        }
    });
    
    return {
        spacing: spacingClasses.join(' '),
        remaining: remainingClasses.join(' ')
    };
}

const Edit: React.FC<BlockEditProps<ReadMoreAttributes>> = ({
    attributes,
    setAttributes
}) => {
    const { openText, closeText, iconType } = attributes;
    
    // Get block props with automatic spacing classes
    const blockProps = useBlockProps({
        className: 'orb-read-more-block'
    });

    // Extract spacing classes from blockProps and remove them
    const { spacing: spacingClasses, remaining: nonSpacingClasses } = extractSpacingClasses(blockProps.className);
    
    // Create clean block props without spacing classes
    const cleanBlockProps = {
        ...blockProps,
        className: nonSpacingClasses
    };

    // Apply spacing classes to inner wrapper (matches PHP structure for animations)
    const innerContentClasses = `orb-read-more__inner ${spacingClasses}`.trim();

    // Icon mapping for display
    const iconMap = {
        none: null,
        chevron: <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="m9 18 6-6-6-6"/></svg>,
        arrow: <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>,
        plus: <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
    };

    const getIcon = (type: string, isOpen: boolean = false) => {
        if (type === 'none') return null;
        
        const icon = iconMap[type as keyof typeof iconMap] || iconMap.chevron;
        return <span className={`orb-read-more__icon ${isOpen ? 'orb-read-more__icon--is-rotated' : ''}`}>{icon}</span>;
    };

    return (
        <>
            <BlockControls>
                <ToolbarGroup>
                    <ToolbarDropdownMenu
                        icon={chevronDown}
                        label={__('Icon Type', 'orb')}
                        controls={[
                            {
                                title: __('No Icon', 'orb'),
                                icon: close,
                                isActive: iconType === 'none',
                                onClick: () => setAttributes({ iconType: 'none' })
                            },
                            {
                                title: __('Chevron', 'orb'),
                                icon: chevronDown,
                                isActive: iconType === 'chevron',
                                onClick: () => setAttributes({ iconType: 'chevron' })
                            },
                            {
                                title: __('Arrow', 'orb'),
                                icon: arrowDown,
                                isActive: iconType === 'arrow',
                                onClick: () => setAttributes({ iconType: 'arrow' })
                            },
                            {
                                title: __('Plus', 'orb'),
                                icon: plus,
                                isActive: iconType === 'plus',
                                onClick: () => setAttributes({ iconType: 'plus' })
                            }
                        ]}
                    />
                </ToolbarGroup>
            </BlockControls>
        <div {...cleanBlockProps}>
            <div className="orb-read-more-button-settings">
                <div className="orb-read-more-button-field">
                    <label>{__('Open Button Text:', 'orb')}</label>
                    <div className="orb-read-more-button-preview">
                        <RichText
                            tagName="span"
                            value={openText}
                            onChange={(value) => setAttributes({ openText: value })}
                            placeholder={__('Read More', 'orb')}
                            allowedFormats={['core/bold', 'core/italic']}
                        />
                        {getIcon(iconType, false)}
                    </div>
                </div>
                <div className="orb-read-more-button-field">
                    <label>{__('Close Button Text:', 'orb')}</label>
                    <div className="orb-read-more-button-preview">
                        <RichText
                            tagName="span"
                            value={closeText}
                            onChange={(value) => setAttributes({ closeText: value })}
                            placeholder={__('Read Less', 'orb')}
                            allowedFormats={['core/bold', 'core/italic']}
                        />
                        {getIcon(iconType, true)}
                    </div>
                </div>
            </div>
            <div className="orb-read-more__content">
                <div className={innerContentClasses}>
                    <InnerBlocks
                        allowedBlocks={ALLOWED_BLOCKS}
                        template={TEMPLATE}
                    />
                </div>
            </div>
        </div>
        </>
    );
};

export default Edit;
