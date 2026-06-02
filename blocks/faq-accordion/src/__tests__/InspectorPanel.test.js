/**
 * Unit tests for InspectorPanel component.
 *
 * Validates: Requirements 1.3, 1.4, 1.5, 1.6, 2.1, 4.1
 *
 * @package AiFaqGenerator
 */

import { render, screen, fireEvent } from '@testing-library/react';
import '@testing-library/jest-dom';

import InspectorPanel from '../components/InspectorPanel';

// --- Helpers ---

const defaultAttributes = {
	titleTag: 'h3',
	openFirstItem: false,
	iconPosition: 'left',
	enableAnimation: false,
};

function renderComponent( overrides = {}, setAttributes = jest.fn() ) {
	const attributes = { ...defaultAttributes, ...overrides };
	return {
		...render(
			<InspectorPanel
				attributes={ attributes }
				setAttributes={ setAttributes }
			/>
		),
		setAttributes,
	};
}

// --- Test Suite ---

describe( 'InspectorPanel', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	describe( 'rendering controls with correct labels', () => {
		it( 'renders a SelectControl labeled "Title Tag"', () => {
			renderComponent();

			expect(
				screen.getByLabelText( 'Title Tag' )
			).toBeInTheDocument();
		} );

		it( 'renders a ToggleControl labeled "Open first item"', () => {
			renderComponent();

			expect(
				screen.getByLabelText( 'Open first item' )
			).toBeInTheDocument();
		} );

		it( 'renders a SelectControl labeled "Icon Position"', () => {
			renderComponent();

			expect(
				screen.getByLabelText( 'Icon Position' )
			).toBeInTheDocument();
		} );

		it( 'renders a ToggleControl labeled "Enable animation"', () => {
			renderComponent();

			expect(
				screen.getByLabelText( 'Enable animation' )
			).toBeInTheDocument();
		} );
	} );

	describe( 'Title Tag SelectControl options', () => {
		it( 'has options H2, H3, H4', () => {
			renderComponent();

			const select = screen.getByLabelText( 'Title Tag' );
			const options = select.querySelectorAll( 'option' );

			expect( options ).toHaveLength( 3 );
			expect( options[ 0 ] ).toHaveTextContent( 'H2' );
			expect( options[ 0 ] ).toHaveValue( 'h2' );
			expect( options[ 1 ] ).toHaveTextContent( 'H3' );
			expect( options[ 1 ] ).toHaveValue( 'h3' );
			expect( options[ 2 ] ).toHaveTextContent( 'H4' );
			expect( options[ 2 ] ).toHaveValue( 'h4' );
		} );
	} );

	describe( 'Icon Position SelectControl options', () => {
		it( 'has options Left, Right, None', () => {
			renderComponent();

			const select = screen.getByLabelText( 'Icon Position' );
			const options = select.querySelectorAll( 'option' );

			expect( options ).toHaveLength( 3 );
			expect( options[ 0 ] ).toHaveTextContent( 'Left' );
			expect( options[ 0 ] ).toHaveValue( 'left' );
			expect( options[ 1 ] ).toHaveTextContent( 'Right' );
			expect( options[ 1 ] ).toHaveValue( 'right' );
			expect( options[ 2 ] ).toHaveTextContent( 'None' );
			expect( options[ 2 ] ).toHaveValue( 'none' );
		} );
	} );

	describe( 'setAttributes calls on change', () => {
		it( 'calls setAttributes with { titleTag } when Title Tag is changed', () => {
			const setAttributes = jest.fn();
			renderComponent( {}, setAttributes );

			const select = screen.getByLabelText( 'Title Tag' );
			fireEvent.change( select, { target: { value: 'h2' } } );

			expect( setAttributes ).toHaveBeenCalledWith( { titleTag: 'h2' } );
		} );

		it( 'calls setAttributes with { openFirstItem } when Open first item is toggled', () => {
			const setAttributes = jest.fn();
			renderComponent( { openFirstItem: false }, setAttributes );

			const toggle = screen.getByLabelText( 'Open first item' );
			fireEvent.click( toggle );

			expect( setAttributes ).toHaveBeenCalledWith( { openFirstItem: true } );
		} );

		it( 'calls setAttributes with { iconPosition } when Icon Position is changed', () => {
			const setAttributes = jest.fn();
			renderComponent( {}, setAttributes );

			const select = screen.getByLabelText( 'Icon Position' );
			fireEvent.change( select, { target: { value: 'right' } } );

			expect( setAttributes ).toHaveBeenCalledWith( { iconPosition: 'right' } );
		} );

		it( 'calls setAttributes with { enableAnimation } when Enable animation is toggled', () => {
			const setAttributes = jest.fn();
			renderComponent( { enableAnimation: false }, setAttributes );

			const toggle = screen.getByLabelText( 'Enable animation' );
			fireEvent.click( toggle );

			expect( setAttributes ).toHaveBeenCalledWith( { enableAnimation: true } );
		} );
	} );
} );
