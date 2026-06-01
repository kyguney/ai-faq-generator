/**
 * Mock for @wordpress/block-editor.
 *
 * Provides minimal implementations of block editor hooks and components.
 */
const React = require( 'react' );

const useBlockProps = ( props = {} ) => ( {
	className: 'wp-block-mock',
	...props,
} );

const InspectorControls = ( { children } ) => {
	return React.createElement( 'div', { 'data-testid': 'inspector-controls' }, children );
};

const BlockControls = ( { children } ) => {
	return React.createElement( 'div', { 'data-testid': 'block-controls' }, children );
};

const RichText = ( { value, onChange, tagName, placeholder, ...props } ) => {
	const Tag = tagName || 'div';
	return React.createElement( Tag, {
		'data-testid': 'rich-text',
		contentEditable: true,
		dangerouslySetInnerHTML: { __html: value || '' },
		...props,
	} );
};

module.exports = {
	useBlockProps,
	InspectorControls,
	BlockControls,
	RichText,
};
