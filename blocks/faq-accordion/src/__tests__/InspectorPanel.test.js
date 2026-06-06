/**
 * Unit tests for InspectorPanel component.
 *
 * @package AiFaqGenerator
 */

import { render, screen, fireEvent } from '@testing-library/react';
import '@testing-library/jest-dom';

import InspectorPanel from '../components/InspectorPanel';

// --- Helpers ---

const defaultAttributes = {
	openFirstItem: false,
	iconPosition: 'left',
	enableAnimation: false,
	titleBackgroundColor: '',
	contentBackgroundColor: '',
	titleFontWeight: '',
	titleFontStyle: '',
	titleTextDecoration: '',
	titleTextTransform: '',
	selectedIcon: 'chevron-down',
	iconColor: '',
	titleColor: '',
	titleFontSize: 0,
	titleFontFamily: '',
	titlePadding: 16,
	contentColor: '',
	contentFontSize: 0,
	contentFontFamily: '',
	contentPadding: 16,
	itemSpacing: 8,
	layoutMode: 'edit',
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
		it( 'renders a ToggleControl labeled "Open first item"', () => {
			renderComponent();

			expect(
				screen.getByLabelText( 'Open first item' )
			).toBeInTheDocument();
		} );

		it( 'renders a ToggleControl labeled "Enable animation"', () => {
			renderComponent();

			expect(
				screen.getByLabelText( 'Enable animation' )
			).toBeInTheDocument();
		} );

		it( 'renders a SelectControl labeled "Position" for icon position', () => {
			renderComponent();

			expect(
				screen.getByLabelText( 'Position' )
			).toBeInTheDocument();
		} );
	} );

	describe( 'Icon Position SelectControl options', () => {
		it( 'has options Left, Right, None', () => {
			renderComponent();

			const select = screen.getByLabelText( 'Position' );
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
		it( 'calls setAttributes with { openFirstItem } when Open first item is toggled', () => {
			const setAttributes = jest.fn();
			renderComponent( { openFirstItem: false }, setAttributes );

			const toggle = screen.getByLabelText( 'Open first item' );
			fireEvent.click( toggle );

			expect( setAttributes ).toHaveBeenCalledWith( { openFirstItem: true } );
		} );

		it( 'calls setAttributes with { iconPosition } when Position is changed', () => {
			const setAttributes = jest.fn();
			renderComponent( {}, setAttributes );

			const select = screen.getByLabelText( 'Position' );
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

	describe( 'ColorPalette rendering', () => {
		it( 'renders color picker controls for icon color', () => {
			renderComponent();

			expect(
				screen.getByLabelText( 'Icon Color' )
			).toBeInTheDocument();
		} );

		it( 'renders Background color picker in Title Styling', () => {
			renderComponent();

			const bgButtons = screen.getAllByLabelText( 'Background' );
			expect( bgButtons.length ).toBeGreaterThanOrEqual( 1 );
		} );
	} );

	describe( 'Font Weight SelectControl', () => {
		it( 'renders a SelectControl labeled "Font Weight"', () => {
			renderComponent();

			expect(
				screen.getByLabelText( 'Font Weight' )
			).toBeInTheDocument();
		} );

		it( 'has options Default, Normal, Medium, Semi-Bold, Bold, Extra-Bold', () => {
			renderComponent();

			const select = screen.getByLabelText( 'Font Weight' );
			const options = select.querySelectorAll( 'option' );

			expect( options ).toHaveLength( 6 );
			expect( options[ 0 ] ).toHaveTextContent( 'Default' );
			expect( options[ 0 ] ).toHaveValue( '' );
			expect( options[ 1 ] ).toHaveTextContent( 'Normal' );
			expect( options[ 1 ] ).toHaveValue( '400' );
			expect( options[ 2 ] ).toHaveTextContent( 'Medium' );
			expect( options[ 2 ] ).toHaveValue( '500' );
			expect( options[ 3 ] ).toHaveTextContent( 'Semi-Bold' );
			expect( options[ 3 ] ).toHaveValue( '600' );
			expect( options[ 4 ] ).toHaveTextContent( 'Bold' );
			expect( options[ 4 ] ).toHaveValue( '700' );
			expect( options[ 5 ] ).toHaveTextContent( 'Extra-Bold' );
			expect( options[ 5 ] ).toHaveValue( '800' );
		} );
	} );

	describe( 'Italic ToggleControl', () => {
		it( 'calls setAttributes with { titleFontStyle: "italic" } when toggled on', () => {
			const setAttributes = jest.fn();
			renderComponent( { titleFontStyle: '' }, setAttributes );

			const toggle = screen.getByLabelText( 'Italic' );
			fireEvent.click( toggle );

			expect( setAttributes ).toHaveBeenCalledWith( { titleFontStyle: 'italic' } );
		} );

		it( 'calls setAttributes with { titleFontStyle: "" } when toggled off', () => {
			const setAttributes = jest.fn();
			renderComponent( { titleFontStyle: 'italic' }, setAttributes );

			const toggle = screen.getByLabelText( 'Italic' );
			fireEvent.click( toggle );

			expect( setAttributes ).toHaveBeenCalledWith( { titleFontStyle: '' } );
		} );
	} );

	describe( 'Underline ToggleControl', () => {
		it( 'calls setAttributes with { titleTextDecoration: "underline" } when toggled on', () => {
			const setAttributes = jest.fn();
			renderComponent( { titleTextDecoration: '' }, setAttributes );

			const toggle = screen.getByLabelText( 'Underline' );
			fireEvent.click( toggle );

			expect( setAttributes ).toHaveBeenCalledWith( { titleTextDecoration: 'underline' } );
		} );

		it( 'calls setAttributes with { titleTextDecoration: "" } when toggled off', () => {
			const setAttributes = jest.fn();
			renderComponent( { titleTextDecoration: 'underline' }, setAttributes );

			const toggle = screen.getByLabelText( 'Underline' );
			fireEvent.click( toggle );

			expect( setAttributes ).toHaveBeenCalledWith( { titleTextDecoration: '' } );
		} );
	} );

	describe( 'Text Transform SelectControl', () => {
		it( 'renders a SelectControl labeled "Text Transform"', () => {
			renderComponent();

			expect(
				screen.getByLabelText( 'Text Transform' )
			).toBeInTheDocument();
		} );

		it( 'has options None, Uppercase, Lowercase, Capitalize', () => {
			renderComponent();

			const select = screen.getByLabelText( 'Text Transform' );
			const options = select.querySelectorAll( 'option' );

			expect( options ).toHaveLength( 4 );
			expect( options[ 0 ] ).toHaveTextContent( 'None' );
			expect( options[ 0 ] ).toHaveValue( '' );
			expect( options[ 1 ] ).toHaveTextContent( 'Uppercase' );
			expect( options[ 1 ] ).toHaveValue( 'uppercase' );
			expect( options[ 2 ] ).toHaveTextContent( 'Lowercase' );
			expect( options[ 2 ] ).toHaveValue( 'lowercase' );
			expect( options[ 3 ] ).toHaveTextContent( 'Capitalize' );
			expect( options[ 3 ] ).toHaveValue( 'capitalize' );
		} );
	} );

	describe( 'Visual icon picker', () => {
		it( 'renders buttons for all 6 icon options', () => {
			renderComponent();

			const iconGrid = screen.getByRole( 'radiogroup', { name: 'Accordion Icon' } );
			expect( iconGrid ).toBeInTheDocument();

			const buttons = iconGrid.querySelectorAll( 'button' );
			expect( buttons ).toHaveLength( 6 );
		} );

		it( 'renders buttons with labels for each icon in ICON_REGISTRY', () => {
			renderComponent();

			const iconGrid = screen.getByRole( 'radiogroup', { name: 'Accordion Icon' } );
			const expectedLabels = [
				'Chevron Down',
				'Chevron Right',
				'Plus / Minus',
				'Arrow Down',
				'Arrow Right',
				'None',
			];

			expectedLabels.forEach( ( label ) => {
				const button = iconGrid.querySelector( `button[aria-label="${ label }"]` );
				expect( button ).toBeInTheDocument();
			} );
		} );

		it( 'marks the currently selected icon button as pressed', () => {
			renderComponent( { selectedIcon: 'chevron-down' } );

			const iconGrid = screen.getByRole( 'radiogroup', { name: 'Accordion Icon' } );
			const buttons = iconGrid.querySelectorAll( 'button' );

			expect( buttons[ 0 ] ).toHaveAttribute( 'aria-pressed', 'true' );
		} );

		it( 'calls setAttributes with the icon id when a button is clicked', () => {
			const setAttributes = jest.fn();
			renderComponent( {}, setAttributes );

			const iconGrid = screen.getByRole( 'radiogroup', { name: 'Accordion Icon' } );
			const buttons = iconGrid.querySelectorAll( 'button' );

			// Click the "arrow-down" button (4th button, index 3)
			fireEvent.click( buttons[ 3 ] );

			expect( setAttributes ).toHaveBeenCalledWith( { selectedIcon: 'arrow-down' } );
		} );
	} );
} );
