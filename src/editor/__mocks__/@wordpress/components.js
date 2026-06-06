/**
 * Mock for @wordpress/components.
 */
const React = require( 'react' );

const Button = ( { children, onClick, isBusy, disabled, icon, label, size, isDestructive, variant, className, isPressed, ...props } ) => {
	return React.createElement(
		'button',
		{
			onClick,
			disabled,
			'aria-label': label,
			'aria-pressed': isPressed ? 'true' : undefined,
			'data-is-busy': isBusy ? 'true' : 'false',
			className,
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

const PanelBody = ( { title, children, initialOpen, ...props } ) => {
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

const PanelRow = ( { children, ...props } ) => {
	return React.createElement( 'div', { 'data-testid': 'panel-row', ...props }, children );
};

const RangeControl = ( { label, value, onChange, min, max, step, withInputField, renderTooltipContent, initialPosition, ...props } ) => {
	return React.createElement(
		'div',
		{ 'data-testid': 'range-control' },
		React.createElement( 'label', { htmlFor: `range-${ label }` }, label ),
		React.createElement( 'input', {
			id: `range-${ label }`,
			type: 'range',
			value,
			onChange: ( e ) => onChange( Number( e.target.value ) ),
			min,
			max,
			step,
			'aria-label': label,
		} )
	);
};

const ColorPalette = ( { value, onChange, ...props } ) => {
	return React.createElement(
		'div',
		{ 'data-testid': 'color-palette', ...props },
		React.createElement( 'input', {
			type: 'color',
			value: value || '',
			onChange: ( e ) => onChange( e.target.value ),
			'aria-label': 'Color Palette',
		} )
	);
};

const ButtonGroup = ( { children, className, ...props } ) => {
	return React.createElement(
		'div',
		{ 'data-testid': 'button-group', role: 'group', className, ...props },
		children
	);
};

const BaseControl = ( { label, id, children, ...props } ) => {
	return React.createElement(
		'div',
		{ 'data-testid': 'base-control', ...props },
		label && React.createElement( 'label', { htmlFor: id }, label ),
		children
	);
};

const ColorIndicator = ( { colorValue, ...props } ) => {
	return React.createElement( 'span', {
		'data-testid': 'color-indicator',
		style: { backgroundColor: colorValue },
		...props,
	} );
};

const BoxControl = ( { label, values, onChange, ...props } ) => {
	return React.createElement(
		'div',
		{ 'data-testid': 'box-control' },
		React.createElement( 'label', null, label ),
		React.createElement( 'input', {
			type: 'text',
			value: JSON.stringify( values ),
			onChange: ( e ) => {
				try { onChange( JSON.parse( e.target.value ) ); } catch ( err ) { /* noop */ }
			},
			'aria-label': label,
		} )
	);
};

const Dropdown = ( { renderToggle, renderContent, className, contentClassName, popoverProps, ...props } ) => {
	const [ isOpen, setIsOpen ] = React.useState( false );
	return React.createElement(
		'div',
		{ 'data-testid': 'dropdown', className },
		renderToggle( { isOpen, onToggle: () => setIsOpen( ! isOpen ) } ),
		isOpen && renderContent()
	);
};

module.exports = {
	Button,
	Spinner,
	Modal,
	TextControl,
	TextareaControl,
	PanelBody,
	PanelRow,
	RangeControl,
	SelectControl,
	ToggleControl,
	ColorPalette,
	ButtonGroup,
	BaseControl,
	ColorIndicator,
	BoxControl,
	Dropdown,
	__experimentalBoxControl: BoxControl,
};
