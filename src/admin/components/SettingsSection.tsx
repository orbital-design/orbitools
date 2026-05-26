/**
 * A card-wrapped group of fields. Optional title + description above
 * the field stack. SettingsPage uses one of these per section
 * declared in the manifest (or a single un-titled one if no sections).
 */
// VStack is only exported as `__experimentalVStack`; the plain
// `VStack` alias is undefined and would render as <undefined />
// (React #130).
import {
    Card,
    CardHeader,
    CardBody,
    __experimentalVStack as VStack,
} from '@wordpress/components';
import type { ReactNode } from 'react';

interface SettingsSectionProps {
    title?: string;
    description?: string;
    children: ReactNode;
}

export function SettingsSection({ title, description, children }: SettingsSectionProps): JSX.Element {
    return (
        <Card className="orbitools-settings-section">
            {title !== undefined && (
                <CardHeader>
                    <div>
                        <h2 className="orbitools-settings-section__title">{title}</h2>
                        {description !== undefined && (
                            <p className="orbitools-settings-section__description">{description}</p>
                        )}
                    </div>
                </CardHeader>
            )}
            <CardBody>
                <VStack spacing={4}>{children}</VStack>
            </CardBody>
        </Card>
    );
}
