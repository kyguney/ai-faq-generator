/**
 * Mock for @wordpress/icons.
 *
 * Returns simple string identifiers for icon constants.
 */
const React = require( 'react' );

const Icon = ( { icon, size, ...props } ) => {
	return React.createElement( 'span', {
		'data-testid': 'icon',
		'data-icon': typeof icon === 'string' ? icon : 'svg-icon',
		'data-size': size,
		...props,
	} );
};

module.exports = {
	chevronUp: 'chevron-up-icon',
	chevronDown: 'chevron-down-icon',
	chevronRight: 'chevron-right-icon',
	close: 'close-icon',
	plus: 'plus-icon',
	trash: 'trash-icon',
	arrowUp: 'arrow-up-icon',
	arrowDown: 'arrow-down-icon',
	arrowRight: 'arrow-right-icon',
	link: 'link-icon',
	linkOff: 'link-off-icon',
	Icon,
};
