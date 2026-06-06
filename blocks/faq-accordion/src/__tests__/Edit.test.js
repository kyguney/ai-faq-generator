/**
 * Unit tests for the Edit component of the FAQ Accordion Block.
 *
 * @package AiFaqGenerator
 */

import { render, screen, fireEvent } from '@testing-library/react';
import '@testing-library/jest-dom';

import Edit from '../edit';

describe( 'Edit component', () => {
	const defaultProps = {
		attributes: { items: [] },
		setAttributes: jest.fn(),
	};

	beforeEach( () => {
		jest.clearAllMocks();
	} );

	describe( 'renders all FAQ items from attributes', () => {
		it( 'renders one FaqItemEditor per item in the items array', () => {
			const items = [
				{ question: 'What is WordPress?', answer: 'A CMS platform.' },
				{ question: 'What is Gutenberg?', answer: 'The block editor.' },
				{ question: 'What is a plugin?', answer: 'An extension.' },
			];

			render( <Edit attributes={ { items } } setAttributes={ jest.fn() } /> );

			// Each item should render a question input with its value
			expect( screen.getByDisplayValue( 'What is WordPress?' ) ).toBeInTheDocument();
			expect( screen.getByDisplayValue( 'What is Gutenberg?' ) ).toBeInTheDocument();
			expect( screen.getByDisplayValue( 'What is a plugin?' ) ).toBeInTheDocument();
		} );

		it( 'renders answer textareas for each item', () => {
			const items = [
				{ question: 'Q1', answer: 'Answer one' },
				{ question: 'Q2', answer: 'Answer two' },
			];

			render( <Edit attributes={ { items } } setAttributes={ jest.fn() } /> );

			expect( screen.getByDisplayValue( 'Answer one' ) ).toBeInTheDocument();
			expect( screen.getByDisplayValue( 'Answer two' ) ).toBeInTheDocument();
		} );
	} );

	describe( 'add button appears and is functional', () => {
		it( 'renders the Add FAQ Item button', () => {
			render( <Edit { ...defaultProps } /> );

			expect( screen.getByRole( 'button', { name: 'Add FAQ Item' } ) ).toBeInTheDocument();
		} );

		it( 'calls setAttributes with a new empty item when add button is clicked', () => {
			const setAttributes = jest.fn();
			const items = [
				{ question: 'Existing Q', answer: 'Existing A' },
			];

			render( <Edit attributes={ { items } } setAttributes={ setAttributes } /> );

			fireEvent.click( screen.getByRole( 'button', { name: 'Add FAQ Item' } ) );

			expect( setAttributes ).toHaveBeenCalledWith( {
				items: [
					{ question: 'Existing Q', answer: 'Existing A' },
					{ question: '', answer: '', _open: false },
				],
			} );
		} );

		it( 'adds first item to an empty list when add button is clicked', () => {
			const setAttributes = jest.fn();

			render( <Edit attributes={ { items: [] } } setAttributes={ setAttributes } /> );

			fireEvent.click( screen.getByRole( 'button', { name: 'Add FAQ Item' } ) );

			expect( setAttributes ).toHaveBeenCalledWith( {
				items: [ { question: '', answer: '', _open: false } ],
			} );
		} );
	} );

	describe( 'placeholder state when items array is empty', () => {
		it( 'displays placeholder message when no items exist', () => {
			render( <Edit { ...defaultProps } /> );

			expect(
				screen.getByText( 'No FAQ items added yet. Click the button below to add your first item.' )
			).toBeInTheDocument();
		} );

		it( 'does not display placeholder message when items exist', () => {
			const items = [ { question: 'Q1', answer: 'A1' } ];

			render( <Edit attributes={ { items } } setAttributes={ jest.fn() } /> );

			expect(
				screen.queryByText( 'No FAQ items added yet. Click the button below to add your first item.' )
			).not.toBeInTheDocument();
		} );
	} );

	describe( 'add button is disabled at 50 items', () => {
		it( 'disables the add button when items array has 50 items', () => {
			const items = Array.from( { length: 50 }, ( _, i ) => ( {
				question: `Question ${ i + 1 }`,
				answer: `Answer ${ i + 1 }`,
			} ) );

			render( <Edit attributes={ { items } } setAttributes={ jest.fn() } /> );

			const addButton = screen.getByRole( 'button', { name: 'Add FAQ Item' } );
			expect( addButton ).toBeDisabled();
		} );

		it( 'shows limit message when 50 items are reached', () => {
			const items = Array.from( { length: 50 }, ( _, i ) => ( {
				question: `Question ${ i + 1 }`,
				answer: `Answer ${ i + 1 }`,
			} ) );

			render( <Edit attributes={ { items } } setAttributes={ jest.fn() } /> );

			expect(
				screen.getByText( 'Maximum of 50 FAQ items reached.' )
			).toBeInTheDocument();
		} );

		it( 'does not call setAttributes when add is clicked at 50 items', () => {
			const setAttributes = jest.fn();
			const items = Array.from( { length: 50 }, ( _, i ) => ( {
				question: `Question ${ i + 1 }`,
				answer: `Answer ${ i + 1 }`,
			} ) );

			render( <Edit attributes={ { items } } setAttributes={ setAttributes } /> );

			fireEvent.click( screen.getByRole( 'button', { name: 'Add FAQ Item' } ) );

			expect( setAttributes ).not.toHaveBeenCalled();
		} );

		it( 'enables the add button when items array has fewer than 50 items', () => {
			const items = Array.from( { length: 49 }, ( _, i ) => ( {
				question: `Question ${ i + 1 }`,
				answer: `Answer ${ i + 1 }`,
			} ) );

			render( <Edit attributes={ { items } } setAttributes={ jest.fn() } /> );

			const addButton = screen.getByRole( 'button', { name: 'Add FAQ Item' } );
			expect( addButton ).not.toBeDisabled();
		} );
	} );
} );
