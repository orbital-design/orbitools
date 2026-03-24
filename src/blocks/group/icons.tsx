/**
 * Group Block Icons
 *
 * Custom SVG icons for Group block variations and controls.
 *
 * @file blocks/group/icons.tsx
 * @since 1.0.0
 */

import { SVG, Path } from '@wordpress/components';

export const groupIcon = (
	<SVG xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" fillRule="evenodd" clipRule="evenodd">
		<Path fill="currentColor" fillOpacity=".3" fillRule="nonzero" d="M96 192c0-47.3 48.7-94.99 96-94.99h256c50.3 0 97 47.69 97 94.99v256c0 48.3-48.7 96-97 96H192c-47.3 0-96-48.7-96-96z" />
		<Path fill="currentColor" d="M381.99 287.02 384 287c53 0 96 43 96 96s-43 96-96 96-96-43-96-96v-.02c52.07-1.07 93.98-43.64 93.99-95.96" />
		<Path fill="currentColor" fillRule="nonzero" d="M353 258.01c0 53-43 96-96 96s-96-43-96-96 43-96 96-96 96 43 96 96" />
	</SVG>
);

export const rowIcon = (
	<SVG xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" fillRule="evenodd" clipRule="evenodd">
		<Path fill="currentColor" fillOpacity=".29" fillRule="nonzero" d="M544 320c0 53-43 96-96 96s-96-43-96-96 43-96 96-96 96 43 96 96" />
		<Path fill="currentColor" fillRule="nonzero" d="M288 320c0 53-43 96-96 96s-96-43-96-96 43-96 96-96 96 43 96 96" />
	</SVG>
);

export const stackIcon = (
	<SVG xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" fillRule="evenodd" clipRule="evenodd">
		<Path fill="currentColor" fillOpacity=".3" fillRule="nonzero" d="M416 448c0 53-43 96-96 96s-96-43-96-96 43-96 96-96 96 43 96 96" />
		<Path fill="currentColor" fillRule="nonzero" d="M416 192c0 53-43 96-96 96s-96-43-96-96 43-96 96-96 96 43 96 96" />
	</SVG>
);

export const wrapIcon = (
	<SVG xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" fillRule="evenodd" clipRule="evenodd">
		<Path fill="currentColor" fillOpacity=".3" fillRule="nonzero" d="M288 448c0 53-43 96-96 96s-96-43-96-96 43-96 96-96 96 43 96 96" />
		<Path fill="currentColor" fillRule="nonzero" d="M288 192c0 53-43 96-96 96s-96-43-96-96 43-96 96-96 96 43 96 96m256 0c0 53-43 96-96 96s-96-43-96-96 43-96 96-96 96 43 96 96" />
	</SVG>
);
