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

const Modal = ( { title, onRequestClose, children, ...props } ) => {
	return React.createElement(
		'div',
		{ 'data-testid': 'modal', role: 'dialog', 'aria-label': title, ...props },
		React.createElement( 'h1', null, title ),
		children,
		React.createElement(
			'button',
			{ 'aria-label': 'Close', onClick: onRequestClose },
			'Close'
		)
	);
};

const TextControl = ( { label, value, onChange, disabled, ...props } ) => {
	return React.createElement(
		'div',
		{ 'data-testid': 'text-control', ...props },
		React.createElement( 'label', null, label ),
		React.createElement( 'input', {
			type: 'text',
			value,
			onChange: ( e ) => onChange( e.target.value ),
			disabled,
			'aria-label': label,
		} )
	);
};

const TextareaControl = ( { label, value, onChange, disabled, ...props } ) => {
	return React.createElement(
		'div',
		{ 'data-testid': 'textarea-control', ...props },
		React.createElement( 'label', null, label ),
		React.createElement( 'textarea', {
			value,
			onChange: ( e ) => onChange( e.target.value ),
			disabled,
			'aria-label': label,
		} )
	);
};

module.exports = {
	Button,
	Spinner,
	Modal,
	TextControl,
	TextareaControl,
};
