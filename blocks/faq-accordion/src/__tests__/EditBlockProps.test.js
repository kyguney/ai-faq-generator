/**
 * Unit tests for editor component rendering — verifying that useBlockProps
 * receives custom className from getBlockClasses and that icon-position and
 * animation classes coexist with supports-generated classes on the wrapper.
 *
 * Validates: Requirements 1.2, 2.2, 7.3
 *
 * @package AiFaqGenerator
 */

import { render } from '@testing-library/react';
import '@testing-library/jest-dom';

import Edit from '../edit';

// Access the mocked module to spy on useBlockProps calls.
const blockEditorMock = require( '@wordpress/block-editor' );

describe( 'Edit component — useBlockProps integration', () => {
	const baseAttributes = {
		items: [],
		titleTag: 'h3',
		openFirstItem: false,
		iconPosition: 'left',
		enableAnimation: false,
	};

	describe( 'useBlockProps is called with custom className', () => {
		let originalUseBlockProps;
		let useBlockPropsCalls;

		beforeEach( () => {
			useBlockPropsCalls = [];
			originalUseBlockProps = blockEditorMock.useBlockProps;
			blockEditorMock.useBlockProps = ( props = {} ) => {
				useBlockPropsCalls.push( props );
				return { className: props.className || 'wp-block-mock', ...props };
			};
		} );

		afterEach( () => {
			blockEditorMock.useBlockProps = originalUseBlockProps;
		} );

		it( 'calls useBlockProps with has-icon-left when iconPosition is "left"', () => {
			render(
				<Edit
					attributes={ { ...baseAttributes, iconPosition: 'left' } }
					setAttributes={ jest.fn() }
				/>
			);

			expect( useBlockPropsCalls.length ).toBeGreaterThan( 0 );
			expect( useBlockPropsCalls[ 0 ] ).toEqual(
				expect.objectContaining( { className: 'has-icon-left' } )
			);
		} );

		it( 'calls useBlockProps with has-icon-right when iconPosition is "right"', () => {
			render(
				<Edit
					attributes={ { ...baseAttributes, iconPosition: 'right' } }
					setAttributes={ jest.fn() }
				/>
			);

			expect( useBlockPropsCalls[ 0 ] ).toEqual(
				expect.objectContaining( { className: 'has-icon-right' } )
			);
		} );

		it( 'calls useBlockProps with has-no-icon when iconPosition is "none"', () => {
			render(
				<Edit
					attributes={ { ...baseAttributes, iconPosition: 'none' } }
					setAttributes={ jest.fn() }
				/>
			);

			expect( useBlockPropsCalls[ 0 ] ).toEqual(
				expect.objectContaining( { className: 'has-no-icon' } )
			);
		} );

		it( 'calls useBlockProps with has-animation when enableAnimation is true', () => {
			render(
				<Edit
					attributes={ { ...baseAttributes, enableAnimation: true } }
					setAttributes={ jest.fn() }
				/>
			);

			expect( useBlockPropsCalls[ 0 ] ).toEqual(
				expect.objectContaining( {
					className: 'has-icon-left has-animation',
				} )
			);
		} );

		it( 'does not include has-animation when enableAnimation is false', () => {
			render(
				<Edit
					attributes={ { ...baseAttributes, enableAnimation: false } }
					setAttributes={ jest.fn() }
				/>
			);

			expect( useBlockPropsCalls[ 0 ].className ).not.toContain(
				'has-animation'
			);
		} );
	} );

	describe( 'wrapper div renders icon-position classes', () => {
		it( 'renders wrapper with has-icon-left class', () => {
			const { container } = render(
				<Edit
					attributes={ { ...baseAttributes, iconPosition: 'left' } }
					setAttributes={ jest.fn() }
				/>
			);

			const wrapper = container.querySelector( '.has-icon-left' );
			expect( wrapper ).toBeInTheDocument();
		} );

		it( 'renders wrapper with has-icon-right class', () => {
			const { container } = render(
				<Edit
					attributes={ { ...baseAttributes, iconPosition: 'right' } }
					setAttributes={ jest.fn() }
				/>
			);

			const wrapper = container.querySelector( '.has-icon-right' );
			expect( wrapper ).toBeInTheDocument();
		} );

		it( 'renders wrapper with has-no-icon class', () => {
			const { container } = render(
				<Edit
					attributes={ { ...baseAttributes, iconPosition: 'none' } }
					setAttributes={ jest.fn() }
				/>
			);

			const wrapper = container.querySelector( '.has-no-icon' );
			expect( wrapper ).toBeInTheDocument();
		} );
	} );

	describe( 'wrapper div renders animation class', () => {
		it( 'renders wrapper with has-animation class when enableAnimation is true', () => {
			const { container } = render(
				<Edit
					attributes={ { ...baseAttributes, enableAnimation: true } }
					setAttributes={ jest.fn() }
				/>
			);

			const wrapper = container.querySelector( '.has-animation' );
			expect( wrapper ).toBeInTheDocument();
		} );

		it( 'does not render has-animation class when enableAnimation is false', () => {
			const { container } = render(
				<Edit
					attributes={ { ...baseAttributes, enableAnimation: false } }
					setAttributes={ jest.fn() }
				/>
			);

			const wrapper = container.querySelector( '.has-animation' );
			expect( wrapper ).not.toBeInTheDocument();
		} );
	} );

	describe( 'custom classes coexist with supports classes', () => {
		let originalUseBlockProps;
		let useBlockPropsCalls;

		beforeEach( () => {
			originalUseBlockProps = blockEditorMock.useBlockProps;
			useBlockPropsCalls = [];
		} );

		afterEach( () => {
			blockEditorMock.useBlockProps = originalUseBlockProps;
		} );

		it( 'icon-position and animation classes appear alongside supports-injected className', () => {
			// Simulate WordPress merging supports classes with the passed className.
			blockEditorMock.useBlockProps = ( props = {} ) => {
				useBlockPropsCalls.push( props );
				const supportsClass = 'has-text-color has-background';
				const merged = props.className
					? `${ supportsClass } ${ props.className }`
					: supportsClass;
				return { className: merged };
			};

			const { container } = render(
				<Edit
					attributes={ {
						...baseAttributes,
						iconPosition: 'right',
						enableAnimation: true,
					} }
					setAttributes={ jest.fn() }
				/>
			);

			// Verify useBlockProps received our custom classes
			expect( useBlockPropsCalls[ 0 ] ).toEqual(
				expect.objectContaining( {
					className: 'has-icon-right has-animation',
				} )
			);

			// Verify the rendered wrapper contains both supports and custom classes
			const wrapper = container.querySelector( '.has-icon-right' );
			expect( wrapper ).toBeInTheDocument();
			expect( wrapper ).toHaveClass( 'has-text-color' );
			expect( wrapper ).toHaveClass( 'has-background' );
			expect( wrapper ).toHaveClass( 'has-icon-right' );
			expect( wrapper ).toHaveClass( 'has-animation' );
		} );

		it( 'icon-position class coexists with spacing supports class', () => {
			blockEditorMock.useBlockProps = ( props = {} ) => {
				useBlockPropsCalls.push( props );
				const supportsClass = 'has-custom-padding';
				const merged = props.className
					? `${ supportsClass } ${ props.className }`
					: supportsClass;
				return { className: merged };
			};

			const { container } = render(
				<Edit
					attributes={ {
						...baseAttributes,
						iconPosition: 'left',
						enableAnimation: false,
					} }
					setAttributes={ jest.fn() }
				/>
			);

			// Verify useBlockProps received our custom classes
			expect( useBlockPropsCalls[ 0 ] ).toEqual(
				expect.objectContaining( { className: 'has-icon-left' } )
			);

			// Verify the rendered wrapper contains both supports and custom classes
			const wrapper = container.querySelector( '.has-icon-left' );
			expect( wrapper ).toBeInTheDocument();
			expect( wrapper ).toHaveClass( 'has-custom-padding' );
			expect( wrapper ).toHaveClass( 'has-icon-left' );
		} );
	} );
} );
