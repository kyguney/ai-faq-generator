/**
 * Unit tests for AddItemButton component.
 *
 * Validates: Requirements 3.3, 3.7, 3.8, 4.4
 */
import { render, screen, fireEvent } from '@testing-library/react';
import '@testing-library/jest-dom';
import AddItemButton from '../components/AddItemButton';

describe( 'AddItemButton', () => {
	it( 'renders the "Add FAQ Item" button and calls onClick when clicked', () => {
		const handleClick = jest.fn();
		render( <AddItemButton onClick={ handleClick } disabled={ false } itemCount={ 3 } /> );

		const button = screen.getByRole( 'button', { name: /Add FAQ Item/i } );
		expect( button ).toBeInTheDocument();
		expect( button ).not.toBeDisabled();

		fireEvent.click( button );
		expect( handleClick ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'displays placeholder message when itemCount is 0', () => {
		const handleClick = jest.fn();
		render( <AddItemButton onClick={ handleClick } disabled={ false } itemCount={ 0 } /> );

		expect(
			screen.getByText( 'No FAQ items added yet. Click the button below to add your first item.' )
		).toBeInTheDocument();
	} );

	it( 'does not display placeholder message when itemCount is greater than 0', () => {
		const handleClick = jest.fn();
		render( <AddItemButton onClick={ handleClick } disabled={ false } itemCount={ 2 } /> );

		expect(
			screen.queryByText( 'No FAQ items added yet. Click the button below to add your first item.' )
		).not.toBeInTheDocument();
	} );

	it( 'disables the button and shows limit message when disabled is true', () => {
		const handleClick = jest.fn();
		render( <AddItemButton onClick={ handleClick } disabled={ true } itemCount={ 50 } /> );

		const button = screen.getByRole( 'button', { name: /Add FAQ Item/i } );
		expect( button ).toBeDisabled();

		expect(
			screen.getByText( 'Maximum of 50 FAQ items reached.' )
		).toBeInTheDocument();

		fireEvent.click( button );
		expect( handleClick ).not.toHaveBeenCalled();
	} );

	it( 'does not show limit message when disabled is false', () => {
		const handleClick = jest.fn();
		render( <AddItemButton onClick={ handleClick } disabled={ false } itemCount={ 10 } /> );

		expect(
			screen.queryByText( 'Maximum of 50 FAQ items reached.' )
		).not.toBeInTheDocument();
	} );
} );
