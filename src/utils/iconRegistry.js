import { chevronDown, chevronRight, arrowDown, arrowRight } from '@wordpress/icons';

/**
 * Legacy-to-new icon identifier mapping.
 * Handles backward compatibility for saved blocks using old Unicode icon names.
 */
export const LEGACY_ICON_MAP = {
	chevron: 'chevron-down',
	plus: 'plus-minus',
	arrow: 'arrow-down',
};

/**
 * Custom SVG element for the plus-minus toggle icon.
 * Renders a "+" symbol used as the expand/collapse indicator.
 */
const plusMinusSvg = (
	<svg
		xmlns="http://www.w3.org/2000/svg"
		viewBox="0 0 24 24"
		width="24"
		height="24"
		fill="none"
		stroke="currentColor"
		strokeWidth="2"
		strokeLinecap="round"
		strokeLinejoin="round"
	>
		<line x1="12" y1="5" x2="12" y2="19" />
		<line x1="5" y1="12" x2="19" y2="12" />
	</svg>
);

/**
 * Available SVG icon definitions.
 * Each entry contains an SVG component (from @wordpress/icons or custom) and a human-readable label.
 * The "none" entry renders no icon.
 */
export const ICON_REGISTRY = {
	'chevron-down': { icon: chevronDown, label: 'Chevron Down' },
	'chevron-right': { icon: chevronRight, label: 'Chevron Right' },
	'plus-minus': { icon: null, label: 'Plus / Minus', svg: plusMinusSvg },
	'arrow-down': { icon: arrowDown, label: 'Arrow Down' },
	'arrow-right': { icon: arrowRight, label: 'Arrow Right' },
	none: { icon: null, label: 'None' },
};

/**
 * Default icon size in pixels when no custom title font size is set.
 */
export const DEFAULT_ICON_SIZE = 20;

/**
 * Resolves a selectedIcon attribute value to a valid icon identifier.
 * Handles legacy mappings and unrecognized values.
 *
 * @param {string} iconId - The raw selectedIcon attribute value.
 * @returns {string} A valid icon identifier from ICON_REGISTRY.
 */
export function resolveIconId( iconId ) {
	if ( Object.hasOwn( LEGACY_ICON_MAP, iconId ) ) {
		return LEGACY_ICON_MAP[ iconId ];
	}
	if ( Object.hasOwn( ICON_REGISTRY, iconId ) ) {
		return iconId;
	}
	return 'chevron-down'; // fallback for unrecognized values
}

/**
 * Computes the icon pixel size based on the title font size.
 * Scales proportionally; defaults to DEFAULT_ICON_SIZE when no font size is set.
 *
 * @param {number} titleFontSize - The title font size in pixels (0 means default/unset).
 * @returns {number} The icon size in pixels.
 */
export function getIconSize( titleFontSize ) {
	if ( ! titleFontSize || titleFontSize <= 0 ) {
		return DEFAULT_ICON_SIZE;
	}
	return Math.round( titleFontSize * 1.1 );
}
