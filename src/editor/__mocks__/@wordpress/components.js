/**
 * Mock for @wordpress/components.
 */
const React = require( 'react' );

const Button = ( { children, onClick, isBusy, disabled, icon, label, size, isDestructive, ...props } ) => {
	return React.createElement(
		'button',
		{
			onClick,
			disabled,
			'aria-label': label,
			'data-is-busy': isBusy ? 'true' : 'false',
			...props,
		},
		children || label
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

const PanelBody = ( { title, children, ...props } ) => {
	return React.createElement(
		'div',
		{ 'data-testid': 'panel-body', ...props },
		React.createElement( 'h2', null, title ),
		children
	);
};

const SelectControl = ( { label, value, options, onChange, ...props } ) => {
	return React.createElement(
		'div',
		{ 'data-testid': 'select-control', ...props },
		React.createElement( 'label', { htmlFor: `select-${ label }` }, label ),
		React.createElement(
			'select',
			{
				id: `select-${ label }`,
				value,
				onChange: ( e ) => onChange( e.target.value ),
				'aria-label': label,
			},
			options.map( ( opt ) =>
				React.createElement( 'option', { key: opt.value, value: opt.value }, opt.label )
			)
		)
	);
};

const ToggleControl = ( { label, checked, onChange, ...props } ) => {
	return React.createElement(
		'div',
		{ 'data-testid': 'toggle-control', ...props },
		React.createElement( 'label', { htmlFor: `toggle-${ label }` }, label ),
		React.createElement( 'input', {
			id: `toggle-${ label }`,
			type: 'checkbox',
			checked,
			onChange: ( e ) => onChange( e.target.checked ),
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
	PanelBody,
	SelectControl,
	ToggleControl,
};
