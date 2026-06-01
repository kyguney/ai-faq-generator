/**
 * Mock for @wordpress/components.
 */
const React = require( 'react' );

const Button = ( { children, onClick, isBusy, disabled, ...props } ) => {
	return React.createElement(
		'button',
		{
			onClick,
			disabled,
			'data-is-busy': isBusy ? 'true' : 'false',
			...props,
		},
		children
	);
};

const Spinner = () => {
	return React.createElement( 'span', { 'data-testid': 'spinner' }, 'Loading...' );
};

module.exports = {
	Button,
	Spinner,
};
