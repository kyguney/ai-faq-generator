/**
 * Computes CSS classes for the FAQ Accordion block wrapper based on attributes.
 *
 * @param {Object} attributes        Block attributes.
 * @param {string} attributes.iconPosition   Icon position: 'left', 'right', or 'none'.
 * @param {boolean} attributes.enableAnimation Whether animation is enabled.
 * @return {string} Space-joined CSS class string.
 */
export function getBlockClasses( attributes ) {
	const { iconPosition, enableAnimation } = attributes;
	const classes = [];

	// Icon position class (always exactly one)
	if ( iconPosition === 'right' ) {
		classes.push( 'has-icon-right' );
	} else if ( iconPosition === 'none' ) {
		classes.push( 'has-no-icon' );
	} else {
		classes.push( 'has-icon-left' ); // default fallback
	}

	// Animation class
	if ( enableAnimation ) {
		classes.push( 'has-animation' );
	}

	return classes.join( ' ' );
}
